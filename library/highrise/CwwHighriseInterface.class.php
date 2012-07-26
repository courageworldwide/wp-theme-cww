<?php
/****************************************************************************************
 * Simplified Wordpress/Highrise interface designed for Courage Worldwide (CWW).
 * Author: Jesse Rosato
 * Date:   6-15-12
 *
 * Version: 0.1
 *
 * Uses AppSaloon Highrise PHP API wrapper.
 *  - https://github.com/AppSaloon/Highrise-PHP-Api
 *
 * Note: Probably don't mess with this if you're not sure what you're doing :)
 *
 /***************************************************************************************/
class CwwHighriseInterface {
	protected $_hr; // Highrise API object
	protected $_config; // Configuration array.
	
	public function __construct($config, $hr_account = FALSE, $hr_token = FALSE)
	{	
		// Load required files
		require_once('lib/HighriseAPI.class.php');
		// Set up Highrise credentials
		if (!$hr_account)
			$hr_account = HIGHRISE_ACCOUNT;
		if (!$hr_token)
			$hr_token = HIGHRISE_TOKEN;
		if (!$hr_account || !$hr_token)
			throw new Exception('This Highrise interface requires a Highrise account and token.');
			
		// Initialize API
		$this->_hr = new HighriseAPI();
		$this->_hr->debug = false;
		$this->_hr->setAccount($hr_account);
		$this->_hr->setToken($hr_token);
		
		if (is_array($config))
			$this->_config = $config;
		else
			throw new Exception('This Highrise interface requires a configuration array. (This is the Wordpress version.)');
	}
	
	/***************************************************************************************
	 * syncContact($data)
	 * Sync contact to Highrise, create new contact if unable to sync.
	 * params
	 * - $data	Associative array with some or all of the following keys (* = required):
	 * 		- first_name*
	 *		- last_name*
	 *		- email*
	 *		- company
	 *		- phone
	 *		- address
	 *		- city
	 *		- state
	 *		- zip
	 *		- country
	 *		- notes
	 * returns
	 * - The person object synced or created.
	/***************************************************************************************/
	public function syncContact($data) {
		// Move array into variables
		foreach ($data as $key=>$val)
			$$key = $val;
		
		// Create and populate a 'person' object.
		$found  = TRUE;
		$person = $this->loadPerson($first_name, $last_name, $email);
		if (!$person) {
			$found = FALSE;
			$person = new HighrisePerson($this->_hr);
			$person->setFirstName($first_name);
			$person->setLastName($last_name);
			$person->addEmailAddress($email, 'Home');
		}
		
		// Check whether optional user-entered fields exist.
		$company = isset($company) ? trim($company) : FALSE;
		if ($company)
			$person->setCompanyName($company);
			
		// Check whether address and phone number are unique.
		if (!$found || $this->isNewPhoneNumber($phone, $person))
			$person->addPhoneNumber($phone, 'Home');
		if (!$found || $this->isNewAddress($address, $zip, $person))
			$person->addAddress($address, $city, $state, $zip, $country, 'Home');
		
		// Save person object (must be done before adding notes to person).
		$person->save();
		
		// Add user comments as a note.
		$notes = isset($notes) ? trim($notes) : FALSE;
		if ($notes) {
			$this->addNote($notes, $person);
		}
		
		return $person;
	} // end syncContact
	
	/****************************************************************************************
	 * addTransaction($transaction, HighrisePerson($person)
	 * Add Highrise 'transaction', consisting of:
	 * - Deal: Deal describing the transaction.
	 * - Task: Sets a reminder Task one year from the date of the donation.
	 * - Note: Adds a note describing the transaction.
	 * params
	 * - transaction	Associative array describing an online transaction (* = required):
	 *		- id* (payment gateway transaction id)
	 *		- source* (e.g. http://transactionsite.com)
	 *		- pay_method* (e.g. VISA, MC, AMEX, etc...)
	 *		- account* (account the payment is going to, i.e. 'Auth.net')
	 *		- products
	 *			- amount
	 *			- name
	 *			- type ('onetime', 'monthly', or 'annual')
	 *			- category ('rescue', 'restore', 'business' or unset)
	 *			- duration (in months for monthly, in years for annual, or unset for onetime)
	 *			- quantity
	 *			- start_date
	 * - person			HighrisePerson object.
	 * returns
	 * An array for each product containing deal, note and task objects.
	 * - index
	 * 		- deal
	 * 		- note
	 * 		- task
	/***************************************************************************************/
	public function addTransaction($transaction, HighrisePerson $person) {
		
		// Move transaction data into variables
		foreach($transaction as $key => $val)
			$$key = $val;
		// To store results
		$result = array();
			
		// Create deal, task and note for each product.
		$i = 0;
		foreach ($products as $product) {
			// - Deal
			$product_name = isset($product['name']) ? $product['name'] : 'N/A';
			$product_quantity	= isset($product['quantity']) ? $product['quantity'] : '1';
			$deal_name_parts = array(
				'Online donation',
				$source, 
				$pay_method, 
				$account, 
				$id,
				$product_name,
				'Qty: ' . $product_quantity,
			);
			$result[$i]['deal'] = $this->addDeal(implode(' | ', $deal_name_parts), $product, $person);
			
			// - Note 
			$name 				= $person->getFirstName() . ' ' . $person->getLastName();
			$product_type 		= ucfirst($product['type']) . ' Donation';
			$product_category 	= isset($product['category']) ? $product['category'] : 'General donation';
			$task_delay			= $transaction['task_delay'];
			$datetime			= date('n-d-Y H:i');
			if (!(isset($product['start_date'])) || !$product['start_date']) {
				if ($product['type'] == 'onetime')
					$start_date = 'N/A';
				else
					$start_date = date('n-d-Y');
			} else {
				$start_date = 'N/A';
			}
			$note_body_parts  = array(
				"Online donation",
				"Site: $source",
				"Name: $name",
				"Type: $product_type",
				"Category: $product_category",
				"Date and Time: $datetime",
				"Start date: $start_date",
				"Item: $product_name",
				"Quantity: $product_quantity",
			);
			$result[$i]['note'] = $this->addNote(implode(', ', $note_body_parts), $person);
			
			// - Task
			if ($product['type'] == 'monthly' || $product['type'] == 'annual') {
				$start_date = isset($product['start_date']) && $product['start_date'] ? $product['start_date'] : date('Y-m-d');
				// Set deal expiration using years or months according to 'type'
				if ($product['type'] == 'monthly')
					$exp_date = date('n/d/Y', strtotime($start_date . "+" . $product['duration'] . " months"));
				if ($product['type'] == 'annual')
					$exp_date = date('n/d/Y', strtotime($start_date . "+" . $product['duration'] . " years"));
				$due_date = date('Y-m-d', strtotime($start_date . "+" . $task_delay));
				$task_body = 'Follow up on recurring donation, made ';
				$task_body .= $task_delay;
				$task_body .= " ago.  Donation expires on $exp_date.";
				$result[$i]['task'] = $this->addTask($task_body, $due_date, $result[$i]['deal']);
			}
			
			// Increment counter
			$i++;
			
		} // end foreach($products as $product)
		
		// Return an array containing the created Highrise objects.
		return $result;
		
	} // end addTransaction
	
	/****************************************************************************************
	 * Search by email.  If people with email are found, check first name and last name.
	 * If all three match, return Highrise Person Object, else return false.
	/****************************************************************************************/
	public function loadPerson($first_name, $last_name, $email) {
		$first_name = strtolower($first_name);
		$last_name = strtolower($last_name);
		$people = $this->_hr->findPeopleByEmail($email);
		if (!count($people)) return FALSE;
		foreach ($people as $person) {
			if ((strtolower($person->getFirstName()) == $first_name) && (strtolower($person->getLastName()) == $last_name))
				return $person;
		}
		return FALSE;
	}
	
	/****************************************************************************************
	 * Check street and zip against existing addresses
	 * Returns true if new address, false if existing.
	/****************************************************************************************/
	public function isNewAddress($street, $zip, HighrisePerson $person) {
		$is_new = TRUE;
		$street = strtolower($street);
		$addresses	= $person->getAddresses();
		if (!count($addresses))
			return $is_new;
		foreach($addresses as $address) {
			if ((strtolower($address->getStreet()) == $street) && ($address->getZip() == $zip))
				$is_new = FALSE;
		}
		return $is_new;
	}
	
	/****************************************************************************************
	 * Check phone number against existing phone numbers.
	 * Returns true if new number, false if existing.
	/****************************************************************************************/
	public function isNewPhoneNumber($phone_number, HighrisePerson $person) {
		$is_new = TRUE;
		$phones = $person->getPhoneNumbers();
		if (!count($phones))
			return $is_new;
		foreach ($phones as $phone) {
			if ($phone->getNumber() == $phone_number)
				$is_new = FALSE;
		}
		return $is_new;
	}
	
	/****************************************************************************************
	 * Add a new note
	/****************************************************************************************/
	public function addNote($note_body, HighrisePerson $person) {
		$note = new HighriseNote($this->_hr);
		$note->setBody($note_body);
		$person->addNote($note);
		$person->save();
		return $note;
	}
	
	/****************************************************************************************
	 * addHighriseDeal($transaction, HighrisePerson $person)
	 * Add Highrise deal
	 * params
	 * - deal_name	String containing the name for the Deal
	 * - product	Associative array describing an online transaction (* = required):
	 *		- amount*
	 *		- name (product name)*
	 *		- type ('onetime', 'monthly', or 'annual')
	 *		- category ('Rescue', 'Restore' or unset - for Highrise Deals category)
	 *		- duration (in months for monthly, in years for annual)
	 *		- quantity
	 *		- start_date
	 * - person			HighrisePerson object.
	 * returns
	 * An associative array
	 * - deals
	 * - notes
	 * - tasks
	/***************************************************************************************/
	public function addDeal($deal_name, $product, HighrisePerson $person) {
		$deal = new HighriseDeal($this->_hr);
		$deal->setName($deal_name);
		$category = isset($product['category']) ? $product['category'] : FALSE;
		$deal->setCategoryId($this->_getDealCategoryId($category, $product['type']));
		$deal->setStatus('won');
		$deal->setPartyId($person->id);
		$deal->setPrice($product['amount']);
		$deal->setPriceType($this->_getPriceType($product['type']));
		$deal->setCurrency();
		//$deal->setGroupId(HR_GROUP_ID_ADMIN);
		$deal->setVisibleTo("NamedGroup");
		$deal->setGroupId($this->_config['admin_group_id']);
		$deal->setOwnerId($this->_config['deals_admin_user_id']);
		$deal->setAuthorId($this->_config['deals_admin_user_id']);
		$deal->setResponsiblePartyId($this->_config['deals_admin_user_id']);
		if ($product['type'] == 'monthly' || $product['type'] == 'annual')
			$deal->setDuration($product['duration']);
		
		$deal->save();
		return $deal;
	} // end addDeal
	
	/****************************************************************************************
	 * Add a Highrise task associatied with a deal
	/****************************************************************************************/
	public function addTask($task_body, $due_date, HighriseDeal $deal) {
		$task = new HighriseTask($this->_hr);
		// Set due dates and reminders
		$task->setFrame("specific");
		$task->setDueAt($due_date);
		$task->setBody($task_body);
		$task->setSubjectType('Deal');
		$task->setSubjectId($deal->id);
		$task->setPublic('true');
		$result = array('before' => $task);
		$task->save();
		$result['after'] = $task;
		return $result;
	} // end addTask
	
	/****************************************************************************************
	 * Use product array to return appropriate Highrise Deal category ID.
	 * WARNING!!! DEAL CATEGORY ID'S MUST BE PROPERLY SET IN config.php
	/****************************************************************************************/
	protected function _getDealCategoryId($category, $type) {
		// GENERAL
		if (!$category || preg_match('/general/i', $category)) {
			switch ($type) {
				case 'monthly':
					return $this->_config['general_monthly_deal_category_id'];
					break;
				case 'annual':
					return $this->_config['general_annual_deal_category_id'];
					break;
				default:
					return $this->_config['general_onetime_deal_category_id'];
			}
		}
		
		// BUSINESS PARTNER
		if (preg_match('/business/i', $category))
			return $this->_config['business_monthly_deal_category_id'];
	 
		// RESCUE
		if (preg_match('/rescue/i', $category))
			return $this->_config['rescue_onetime_deal_category_id'];
			
		// RESTORE
		switch ($type) {
		 case 'monthly':
		 	return $this->_config['restore_monthly_deal_category_id'];
		 	break;
		 case 'annual':
		 	return $this->_config['restore_annual_deal_category_id'];
		 	break;
		 default:
		 	return $this->_config['restore_onetime_deal_category_id'];
		}
	}
	
	/****************************************************************************************
	 * Return CWW-specific recurring type as highrise Deal Price Type
	/****************************************************************************************/
	protected function _getPriceType($cww_type) {
		switch($cww_type) {
			case 'monthly':
				return 'month';
				break;
			case 'annual':
				return 'year';
				break;
			default:
				return 'fixed';
		}
	}
}