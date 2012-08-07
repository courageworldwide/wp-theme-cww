<?php
function cww_event_meta_boxes() {
	$meta_boxes = array();
	// Start date
	$meta_boxes['cww_event_start_date'] = array(
		'handle'	=> 'cww_df_event_start_date',
		'title' 	=> __('Start Date'),
		'callback'	=> false,
		'post_type'	=> 'cww_event',
		'context'	=> 'advanced',
		'priority'	=> 'high',
		'args'		=> array(
			'type' 		=> 'date',
			'class' 	=> 'datepicker',
			'desc' 		=> __("Please choose the event's start date.", 'cww'),
			'req'		=> true,
			'default' 	=> date('m-d-Y'),
		)
	);
	// Start time
	$default_mins = floor(date('i') / 15) * 15;
	$default_mins = $default_mins ? $default_mins : '00';
	$default_start = date('h') . ':' . $default_mins . date('A');
	$meta_boxes['cww_event_start_time'] = array(
		'handle'	=> 'cww_event_start_time',
		'title' 	=> __('Start Time'),
		'callback'	=> false,
		'post_type'	=> 'cww_event',
		'context'	=> 'advanced',
		'priority'	=> 'high',
		'args'		=> array(
			'type' 		=> 'time',
			'class' 	=> 'time',
			'desc' 		=> __("Please enter the event's start time.", 'cww'),
			'req'		=> true,
			'default' 	=> $default_start,
		)
	);
	// End date
	$meta_boxes['cww_event_end_date'] = array(
		'handle'	=> 'cww_event_end_date',
		'title' 	=> __('End Date'),
		'callback'	=> false,
		'post_type'	=> 'cww_event',
		'context'	=> 'advanced',
		'priority'	=> 'high',
		'args'		=> array(
			'type' 		=> 'date',
			'class' 	=> 'datepicker',
			'desc' 		=> __("The ending date of the event (defaults to 'Start Date').", 'cww'),
			'default' 	=> date('m-d-Y'),
		)
	);
	// End time
	$default_end = date('h:i A', strtotime($default_start)+60*60);
	$meta_boxes['cww_event_end_time'] = array(
		'handle'	=> 'cww_event_end_time',
		'title' 	=> __('End Time'),
		'callback'	=> false,
		'post_type'	=> 'cww_event',
		'context'	=> 'advanced',
		'priority'	=> 'high',
		'args'		=> array(
			'type' 		=> 'time',
			'class' 	=> 'time',
			'desc' 		=> __("Please enter the event's end time (defaults to 4 hours after 'End Date').", 'cww'),
			'default' 	=> $default_end,
		)
	);
	
	$meta_boxes['cww_event_location'] = array(
		'handle'	=> 'cww_event_location',
		'title' 	=> __('Location'),
		'callback'	=> false,
		'post_type'	=> 'cww_event',
		'context'	=> 'advanced',
		'priority'	=> 'high',
		'args'		=> array(
			'type' 		=> 'textarea',
			'class' 	=> 'location',
			'desc' 		=> __("Please enter the event's location/address.", 'cww'),
			'default' 	=> 'TBD',
		)
	);
	
	$meta_boxes['cww_event_register_btn_url'] = array(
		'handle'	=> 'cww_event_register_btn_url',
		'title' 	=> __('Registration Link'),
		'callback'	=> false,
		'post_type'	=> 'cww_event',
		'context'	=> 'advanced',
		'priority'	=> 'high',
		'args'		=> array(
			'type' 		=> 'text',
			'class' 	=> 'url',
			'desc' 		=> __("Please enter the url for the event's registration button.  Leave blank to skip adding a registration button to this event.", 'cww'),
			'default' 	=> '',
		)
	);
	
	$meta_boxes['cww_event_register_btn_text'] = array(
		'handle'	=> 'cww_event_register_btn_text',
		'title' 	=> __('Registration Text'),
		'callback'	=> false,
		'post_type'	=> 'cww_event',
		'context'	=> 'advanced',
		'priority'	=> 'high',
		'args'		=> array(
			'type' 		=> 'text',
			'desc' 		=> __("Please enter the text for the event's registration button (i.e. 'Register').", 'cww'),
			'default' 	=> '',
		)
	);
	
	return $meta_boxes;
}