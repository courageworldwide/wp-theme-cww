<?php
/*******************************************************************************
/* Author: Jesse Rosato
/* Email: jesse.rosato@gmail.com
/* 
/* Processes donation form data and sends the request to authorize.net.
/*
/* TO-DO: Implement more specific error handling for declined cards.
/********************************************************************************/
// Load third-party services
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/cww/library/authorizenet/AuthorizeNet.php'; // Authorize.net SDK
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/cww/library/highrise/CwwHighriseInterface.class.php'; // Highrise interface
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/cww/library/mailchimp/MailchimpCww.php'; // Mailchimp interface

// Use test mode if WP_DEBUG is on
WP_DEBUG ? define('AUTHORIZENET_SANDBOX', true) : define('AUTHORIZENET_SANDBOX', false);

// Set org vars
$df_org_from = get_bloginfo('name');
$df_org_from_email = get_bloginfo('admin_email');
$df_org_email = $df_org_from_email;


// You may use the following tokens in your confirmation email post:
// - %transaction_id 
// - %donation_type 
// - %name
// - %address
// - %company
// - %card_number
// - %amount

// Fields requring user input
$df_required_fields = array(
	'df_firstname',
	'df_lastname', 
	'df_address', 
	'df_city', 
	'df_state', 
	'df_zip', 
	'df_country', 
	'df_phone', 
	'df_email',
	'df_card_num',
	'df_exp_date',
	'df_card_code',
	'df_type',
);
// 'Initialize' errors array.
// Used to display proper error messages
$df_errors = array();

if(isset($_POST["df_submit"]) && $_POST["df_submit"] != "") {
  // Get donate-form related meta information
  $df_post_id							= $_POST['df_post_id'];
  unset($_POST['df_post_id']);
  $df_mc_list_id 						= get_post_meta($df_post_id, 'cww_df_mc_list_id', TRUE);
  $df_update_hr							= get_post_meta($df_post_id, 'cww_df_update_hr', TRUE);
  $df_confirmation_post_id				= get_post_meta($df_post_id, 'cww_df_conf_post_id', TRUE);
  $df_confirmation_mail_post_id			= get_post_meta($df_post_id, 'cww_df_conf_mail_post_id', TRUE);
  
  $df_confirmation_post_id = is_numeric($df_confirmation_post_id) ? $df_confirmation_post_id : $df_post_id;
  $df_confirmation_mail_post_id = is_numeric($df_confirmation_mail_post_id) ? $df_confirmation_mail_post_id : $df_confirmation_post_id;
  // Form has been submitted.
    // Clean input data.
	$df_clean = array();
	foreach($_POST as $key=>$val) {
		$_POST[$key] = trim($val);
		if ($key == 'df_email') {
			$flag = FILTER_SANITIZE_EMAIL;
		} else {
			$flag = FILTER_SANITIZE_STRING;
		}
		$df_clean[$key] = trim(filter_var($_POST[$key], $flag));
	}
	// Sort input data.
	$df_data = array();
	$df_options = get_option('cww_df_options');
	// - Authorize.net data
	//   - We double check to make sure Authorize.net credentials are set.
	if (isset($df_options['cww_df_authorizenet_setting_api_login_id']) && $df_options['cww_df_authorizenet_setting_api_login_id'])
		$df_data['authnet']['login'] = $df_options['cww_df_authorizenet_setting_api_login_id'];
	else
		throw new Exception('Authorize.net login id not set.');
	if (isset($df_options['cww_df_authorizenet_setting_transaction_key']) && $df_options['cww_df_authorizenet_setting_transaction_key'])
		$df_data['authnet']['key'] = $df_options['cww_df_authorizenet_setting_transaction_key'];
	else
		throw new Exception('Authorize.net transaction key not set.');
	// - Customer data
	$df_data['donor'] = array();
	$df_data['donor']['first_name'] 	= $df_clean["df_firstname"];
	$df_data['donor']['last_name'] 		= $df_clean["df_lastname"];
	$df_data['donor']['company'] 		= $df_clean["df_company"];
	$df_data['donor']['address'] 		= $df_clean["df_address"];
	$df_data['donor']['city'] 			= $df_clean["df_city"];
	$df_data['donor']['state'] 			= $df_clean["df_state"];
	$df_data['donor']['zip'] 			= $df_clean["df_zip"];
	$df_data['donor']['country'] 		= $df_clean["df_country"];
	$df_data['donor']['phone'] 			= $df_clean["df_phone"];
	$df_data['donor']['email'] 			= $df_clean["df_email"];
	$df_data['donor']['notes']			= $df_clean["df_notes"];
	$df_data['donor']['subscribe'] 		= isset($df_clean["df_subscribe"]) ? $df_clean["df_subscribe"] : FALSE; // Whether to add to mailchimp
	// - Card data: SECURITY RISK!! DO NOT STORE!!! ONLY FOR AUTHORIZE.NET
	$df_data['card'] = array();
	$df_data['card']['num']  			= $df_clean["df_card_num"]; 
	$df_data['card']['exp']  			= $df_clean["df_exp_date"];
	$df_data['card']['code'] 			= $df_clean["df_card_code"];
	// - Transaction data
	$df_date['donation'] = array();
	$df_data['donation']['type_code']	= isset($df_clean["df_type"]) ? $df_clean["df_type"] : 'onetime';
	$df_data['donation']['start_date']	= $df_clean["df_startdate"];
	// Server-side validation
	// - Empty field checks.
	foreach($df_required_fields as $field) {
		if (!isset($df_clean[$field]) || !$df_clean[$field])
			$df_errors[$field] = 'empty';
	} 
	// Set up recurring payment data
	switch ($df_data['donation']['type_code']) {
		case "monthly":
			$df_data['donation']['amount']		= $df_clean['df_amount_monthly'];
			$df_data['donation']['interval']	= "1";
			$df_data['donation']['type']		= "Monthly Partner";
			$df_data['donation']['recurring']	= TRUE;
			$df_required_fields[]				= 'df_amount_monthly';
			$df_required_fields[] 				= 'df_startdate';
			break;
		case "annual":
			$df_data['donation']['amount']		= $df_clean['df_amount_annual'];
			$df_data['donation']['interval']	= "12";
			$df_data['donation']['type']		= "Annual Donation";
			$df_data['donation']['recurring']	= TRUE;
			$df_required_fields[]				= 'df_amount_annual';
			$df_required_fields[] 				= 'df_startdate';
			break;
		case "business":
			$df_data['donation']['amount']		= $df_clean['df_amount_business'];
			$df_data['donation']['interval']	= "1";
			$df_data['donation']['type']		= "Business Partner";
			$df_data['donation']['recurring']	= TRUE;
			$df_required_fields[]				= 'df_amount_business';
			$df_required_fields[] 				= 'df_startdate';
			break;
		case "onetime":
			$df_data['donation']['type']		= "One Time";
			$df_data['donation']['amount']		= $df_clean['df_amount_onetime'];
			$df_data['donation']['recurring']	= FALSE;
			$df_required_fields[]				= 'df_amount_onetime';
			break;
	}
	// - Empty donation field checks.
	foreach ($df_required_fields as $field) {
		if (!isset($df_clean[$field]) || !$df_clean[$field])
			$df_errors[$field] = 'empty';
	}
	
	// Validate
	
	// - Validate amount
	$df_amount_field = 'df_amount_' . $df_data['donation']['type_code'];
	//   - Validate amount only if a donation type has been chosen
	if ((!isset($df_errors['df_type']))) {
		validate_currency($df_data['donation']['amount'], $df_errors, $df_amount_field);
		if (!isset($df_errors[$df_amount_field])) {
			// Amount passes muster, strip everything but digits and period.
			$df_data['donation']['amount'] = preg_replace('/[^0-9.]/', '', $df_data['donation']['amount']);
		}
	}

	// - Validate start date	
	if ($df_data['donation']['recurring']) {
		validate_start_date($df_data['donation']['start_date'], $df_errors);
		if (!isset($df_errors['df_startdate'])) {
			// Start date passes muster. Replace non-dash delimiters
			$df_data['donation']['start_date'] = preg_replace('[-/]', '-', $df_data['donation']['start_date']);
		}
	}
	
	// - Validate card info
	//   - Validate credit card number
	validate_card_num($df_data['card']['num'], $df_errors, 'df_card_num');
	//   - Validate credit card expiration date
	validate_card_exp($df_data['card']['exp'], $df_errors, 'df_exp_date');
	//   - Validate credit card code
	validate_card_code($df_data['card']['code'], $df_errors, 'df_card_code');

	
	// - Validate email address
	if (((!isset($df_errors['df_email'])) || (!$df_errors['df_email'])) && (!filter_var($df_data['donor']['email'], FILTER_VALIDATE_EMAIL))) {
		$df_errors['df_email'] = 'format';
	}
	
	// - Validate phone number
	validate_phone_number($df_data['donor']['phone'], $df_errors, 'df_phone');
	if (!isset($df_errors['df_phone'])) {
		// Phone number passes muster, replace the non-numerical one in the donor array.
		$df_data['donor']['phone'] = preg_replace('[^0-9]', '', $df_data['donor']['phone']);
	}
	// End server-side validation
	
	// No errors so far attempt payment and third-party connections.
	if (empty($df_errors)) {
		// Auth.net submission
		if ($df_data['donation']['recurring']) {
			// - Recurring
			$response = df_submit_recurring_donation($df_data, $df_post_id);
			// Handle errors thrown during payment processing
			if($response->xml->messages->resultCode == "Error"){
				// Transaction was NOT approved.
				$df_errors['form'] = 'processing';
			}else{
				// Transaction approved.
				$df_data['donation']['subscription_id'] = $response->getSubscriptionId();
				
			}
		} else {  // end if($df_data['donation']['recurring'])
			// - One time
			$response = df_submit_onetime_donation($df_data, $df_post_id); 
			// Handle errors thrown during payment processing
			if($response->approved){
				// Transaction approved.
				$df_data['donation']['transaction_id'] = $response->transaction_id;
			} else{
				// Transaction was NOT approved.
				if ($response->declined) {
					// Card was declined.
					$df_errors['form'] = 'declined';
				} else {
					// Transaction error, or transaction held.
					$df_errors['form'] = 'processing';
				}
			}
			
		} //end if($df_data['donation']['recurring']) else
	} // end if (empty($df_errors))
	// Finished processing form.
	if (empty($df_errors)) {
		// No errors
		// Submit data to third-party services.
		// - Highrise
		if ($df_update_hr) {
			// Process Highrise transaction data.		
			$df_type = $df_data['donation']['type_code'] == 'business' ? 'monthly' : $df_data['donation']['type_code'];
			if ($df_type == 'monthly')
				$df_duration = $df_monthly_duration;
			if ($df_type == 'annual')
				$df_duration = $df_annual_duration;
			if ($df_type != 'onetime')
				$df_start = $df_data['donation']['start_date'];
			$df_hr_transaction	= array(
				'id' => (isset($df_data['donation']['transaction_id']) ? $df_data['donation']['transaction_id'] : $df_data['donation']['subscription_id']),
				'source' => $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
				'pay_method' => card_type($df_data['card']['num']),
				'account' => 'Auth.net',
				'products' => array(
					0 => array(
						'amount' => $df_data['donation']['amount'],
						'type' => $df_type,
						'duration' => isset($df_duration) ? $df_duration : 0
					),
				),
			);
			if (isset($df_start))
				$df_hr_transaction['products'][0]['start_date'] = $df_start;
			if ($df_data['donation']['type_code'] == 'business')
				$df_hr_transaction['products'][0]['category'] = 'business';
				
			$df_hr_config_fields = array(
				'admin_user_id' => 'cww_df_highrise_setting_admin_user_id',
				'admin_group_id' => 'cww_df_highrise_setting_admin_group_id',
				'deals_admin_user_id' => 'cww_df_highrise_setting_deals_admin_user_id',
				'task_delay' => 'cww_df_highrise_setting_task_delay',
				'general_onetime_deal_category_id' => 'cww_df_highrise_setting_onetime_category_id',
				'general_monthly_deal_category_id' => 'cww_df_highrise_setting_monthly_category_id',
				'general_annual_deal_category_id' =>'cww_df_highrise_setting_annual_category_id',
				'business_monthly_deal_category_id' => 'cww_df_highrise_setting_business_category_id'
			);
			$df_hr_config = array();
			$df_hr_error = FALSE;
			foreach ( $df_hr_config_fields as $index => $key ) {
				if (isset($df_options[$key])) {
					$df_hr_config[$index] = $df_options[$key];
				} else {
					$df_hr_error = true;
					if (WP_DEBUG === true) {
						$message = 'Donate form setting with id "' . $key . '" required to use Highrise.  Donation processed without updating Highrise.';
						error_log(__($message));
					}
				}
			}
			if (isset($df_options['cww_df_highrise_setting_account'])) {
				$df_hr_account = $df_options['cww_df_highrise_setting_account'];
			} else {
				$df_hr_error = true;
				if (WP_DEBUG === true) {
					$message = 'Donate form setting "Highrise account" required to use Highrise.  Donation processed without updating Highrise.';
					error_log(__($message));
				}
			}
			if (isset($df_options['cww_df_highrise_setting_api_token'])) {
				$df_hr_token	= $df_options['cww_df_highrise_setting_api_token'];
			} else {
				$df_hr_error = true;
				if (WP_DEBUG === true) {
					$message = 'Donate form setting "Highrise API token" required to use Highrise.  Donation processed without updating Highrise.';
					error_log(__($message));
				}
			}
			if (!$df_hr_error) {
				$df_hr = new CwwHighriseInterface($df_hr_config, $df_hr_account, $df_hr_token);
				$df_person = $df_hr->syncContact($df_data['donor']);
			
				$df_hr->addTransaction($df_hr_transaction, $df_person);
			}
		}
		// - Mailchimp
		if ($df_mc_list_id && $df_data['donor']['subscribe']) {
			$df_mc_token = isset($df_options['cww_df_mailchimp_setting_api_token']) ? $df_options['cww_df_mailchimp_setting_api_token'] : FALSE;
			if ($df_mc_token) {
				syncMailchimpContact($df_data['donor'], $df_mc_token, $df_mc_list_id);
			} else if (WP_DEBUG === true) {
				$message = 'Donate form setting "Mailchimp API token" required to use Mailchimp.  Donation processed without adding contact to Mailchimp.';
				error_log(__($message));
			}
		}
		// Send confirmation email
		$df_mail_post    = get_post($df_confirmation_mail_post_id);
		$df_mail_body	 = $df_mail_post->post_content;
		$df_mail_body    = df_token_replace($df_mail_body, $df_data);
		$df_mail_subject = $df_mail_post->post_title;
		add_filter('wp_mail_content_type',create_function('', 'return "text/html"; '));
		if (!wp_mail($df_data['donor']['email'], $df_mail_subject, $df_mail_body)) {
			if (WP_DEBUG)
				error_log('ERROR: Donate form email confirmation sending failed!');
		}
		// Redirect to confirmation page.
		$df_url = get_permalink($df_confirmation_post_id);
		header('Location: ' . $df_url);
	} else {
		// Errors
		foreach($df_errors as $key => $error) {
			if (!$error)
				unset($df_errors[$key]);
		}
		if (!isset($df_errors['form'])) {
			$df_errors['form'] = count($df_errors) > 1 ? 'multiple' : 'single';
		}
	}
} // end if (isset($_POST['donate']))


# ----------------------------------------
# START FUNCTIONS
# ----------------------------------------
function validate_currency($cur, $errors, $key = 'price') {
	$filter_options = array('options' => array('regexp' => '/^\$?([0-9]{1,3},([0-9]{3},)*[0-9]{3}|[0-9]+)(.[0-9][0-9])?$/'));
	if ((!isset($errors[$key])) && (!filter_var($cur, FILTER_VALIDATE_REGEXP, $filter_options))) {
		$errors[$df_amount_field] = 'format';
	}
}

function validate_start_date($date, $errors, $key = 'startdate', $after_today = TRUE) {
	$filter_options = array('options' => array('regexp' => '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/'));
	if ((!isset($errors[$key])) && (!filter_var($date, FILTER_VALIDATE_REGEXP, $filter_options))) {
		$errors[$key] = 'format';
	} else if ($after_today) {
		$today = date("Y-m-d");
		$today = strtotime($today);
		$start = strtotime($date);
		if ($start < $today)
			$errors[$key] = 'invalid';
	}
}

function validate_card_num($num, &$errors, $key = 'card_num') {
	$filter_options = array('options' => array('regexp' => '/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})$/'));
	if ((!isset($errors[$key])) && (!filter_var($num, FILTER_VALIDATE_REGEXP, $filter_options))) {
		$errors[$key] = 'format';
	}
}

function validate_card_exp($exp, &$errors, $key = 'card_exp') {
	$filter_options = array('options' => array('regexp' => '/^(0[1-9]|1[012])(1[2-9]|[2-9][0-9])$/'));
	if ((!isset($errors[$key])) && (!filter_var($exp, FILTER_VALIDATE_REGEXP, $filter_options))) {
		$errors[$key] = 'format';
	} else {
		// Check card expiration date.
		$today = date("m-y");
		$today = strtotime($today);
		$exp = strtotime($exp);
		if ($exp < $today)
			$errors[$key] = 'invalid';
	}
}

function validate_card_code($code, &$errors, $key = 'card_code') {
	$filter_options = array('options' => array('regexp' => '/^[0-9]{3,4}$/'));
	if ((!isset($errors[$key])) && (!filter_var($code, FILTER_VALIDATE_REGEXP, $filter_options)))
		$errors[$key] = 'format';
}

function validate_phone_number($num, &$errors, $key = 'phone') {
	$filter_options = array('options' => array('regexp'=>'/\(?\d{3}\)?[-\s.]?\d{3}[-\s.]?\d{4}/x'));
	if ((!isset($errors[$key])) && (!filter_var($num, FILTER_VALIDATE_REGEXP, $filter_options))) {
		$errors[$key] = 'format';
	}
}

function df_submit_onetime_donation($data) {
	$transaction = new AuthorizeNetAIM($data['authnet']['login'], $data['authnet']['key']);
	
	// Donor data
	$transaction->setFields(
        array(
        'amount' => $data['donation']['amount'], 
        'card_num' => $data['card']['num'], 
        'exp_date' => $data['card']['exp'],
        'first_name' => $data['donor']['first_name'],
        'last_name' => $data['donor']['last_name'],
        'address' => $data['donor']['address'],
        'city' => $data['donor']['city'],
        'state' => $data['donor']['state'],
        'country' => $data['donor']['country'],
        'zip' => $data['donor']['zip'],
        'email' => $data['donor']['email'],
        'phone' => $data['donor']['phone'],
        'card_code' => $data['card']['code'],
        'invoice_num' => $data['donation']['type'],
        )
    );
	return $transaction->authorizeAndCapture();
}
function df_submit_recurring_donation($data) {
	$request = new AuthorizeNetARB($data['authnet']['login'], $data['authnet']['key']);
	
	if (preg_match('/(month)|(business)/', $data['donation']['type'])) {
		$data['donation']['interval_unit'] 	= "months";
		$data['donation']['occurrences']		= get_meta_data($post_id, 'cww_monthly_duration', TRUE);
	} else {
		$data['donation']['interval_unit'] 	= "years";
		$data['donation']['occurrences']		= get_meta_data($post_id, 'cww_annual_duration', TRUE);
	}
	
	// Set the subscription fields.
	$name = $data['donor']['first_name'] . ' ' . $data['donor']['last_name'];
	$subscription = new AuthorizeNet_Subscription;
	// Donation data
	$subscription->name = $name;
	$subscription->intervalLength			= $data['donation']['interval'];
	$subscription->intervalUnit				= $data['donation']['interval_unit'];
	$subscription->startDate				= $data['donation']['start_date'];
	$subscription->totalOccurrences			= $data['donation']['occurrences'];
	$subscription->amount					= $data['donation']['amount'];
	// Card data
	$subscription->creditCardCardNumber		= $data['card']['num'];
	$subscription->creditCardExpirationDate	= $data['card']['exp'];
	$subscription->creditCardCardCode		= $data['card']['code'];
	// Donor data
	$subscription->customerEmail			= $data['donor']['email'];
	$subscription->customerPhoneNumber		= $data['donor']['phone'];
	$subscription->billToFirstName			= $data['donor']['first_name'];
	$subscription->billToLastName			= $data['donor']['last_name'];
	$subscription->billToCompany			= $data['donor']['company'];
	$subscription->billToAddress			= $data['donor']['address'];
	$subscription->billToCity 				= $data['donor']['city'];
	$subscription->billToState 				= $data['donor']['state'];
	$subscription->billToZip 				= $data['donor']['zip'];
	$subscription->billToCountry 			= $data['donor']['country'];
	// Order data
	$subscription->orderInvoiceNumber		= $data['donation']['type'];
	
	// Submit request to Authorize.net
	return $request->createSubscription($subscription);
}

function df_token_replace($text, $data) {
	// Supported tokens:
	// - %transaction_id 
	// - %donation_type 
	// - %name
	// - %address
	// - %company
	// - %card_number
	// - %amount
	$transaction_id = $data['donation']['transaction_id'] ? $data['donation']['transaction_id'] : $data['donation']['subscription_id'];
	$text = str_replace('%transaction_id', $transaction_id, $text);
	$text = str_replace('%donation_type', $data['donation']['type'], $text);
	$name = $data['donor']['first_name'] . ' ' . $data['donor']['last_name'];
	$text = str_replace('%name', $name, $text);
	$address = $data['donor']['address'] . ', ' . $data['donor']['city'] . ', ' . $data['donor']['state'] . ' ' . $data['donor']['zip'] . ' ' . $data['donor']['country'];
	$text = str_replace('%address', $address, $text);
	$text = str_replace('%company', $data['donor']['company'], $text);
	$card_num = substr($data['card']['num'], -4);
	$text = str_replace('%card_number', $card_num, $text);
	$amount = '$' . $data['donation']['amount'];
	$text = str_replace('%amount', $amount, $text);
	return $text;
}

function card_type($num) {
		if ( preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $num) )
			return 'VISA';
		if ( preg_match('/^5[1-5][0-9]{14}$/', $num) )
			return 'MC';
		if ( preg_match('/^3[47][0-9]{13}$/', $num) )
			return 'AMEX';
		if ( preg_match('/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/', $num) )
			return 'DC';
		if ( preg_match('/^6(?:011|5[0-9]{2})[0-9]{12}$/', $num) )
			return 'DISC';
		if ( preg_match('/^(?:2131|1800|35\d{3})\d{11}$/', $num) )
			return 'JCB';
		// No match.
		return FALSE;
		
	}
?>