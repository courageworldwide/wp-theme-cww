<?php
/************************************************************************************ 
/* Definition and functions for Event custom post type.
/*
/* By Jesse Rosato, 2012 - jesse.rosato@gmail.com
/************************************************************************************/
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/cww/library/utilities/CwwPostTypeEngine.class.php');
require_once('event_meta_boxes.php');

$cww_event_post_type = array(
	'handle'	=> 'cww_event',
	'args'		=>array(
		'labels' => array(
			'name' => __( 'Events' ),
			'singular_name' => __( 'Event' ),
			'all items' => __( 'All Events' ),
			'add_new_item' => __( 'Add New Event' ),
			'edit_item' => __( 'Edit Event' ),
			'new_item' => __( 'New Event' ),
			'view_item' => __( 'View Event' ),
			'search_item' => __( 'Search Events' ),
			'not_found' => __( 'No Events found' ),
			'not_found_in_trash' => __( 'No Events found in trash' )
		),
		'singular_label' => __('event', 'cww'),
		'description' => __( 'Create an event, with start date and time, etc.' ),
		'rewrite' => array('slug' => 'events','with_front' => false),
		'public' => true,
		'publicly_queryable' => true,
		'has_archive' => true,
		'show_in_nav_menus' => false,
		'menu_position' => 20,
		'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'page-attributes', 'post-formats')
	),
	'meta_box_groups' => array(
		'cww_event_details' => array(
			'handle' => 'cww_event_details',
			'title' => __('Event Details'),
			'desc' => __('You can use the data entered below via shortcodes. Click the "[+] more info" link on a setting to see an example of its associated shortcode.', 'cww'),
			'priority' => 'high',
			'context' => 'normal'
		),
	)
);
$cww_event_meta_boxes = cww_event_meta_boxes();
$cww_event_post_type_engine = new CwwPostTypeEngine($cww_event_post_type, $cww_event_meta_boxes);
add_action('init', array(&$cww_event_post_type_engine, 'create_post_type'));
add_action('admin_init', array(&$cww_event_post_type_engine, 'add_meta_boxes'));


/************************************************************************************ 
/* Validate and save post meta data.
/*
/* @param int $post_id
/************************************************************************************/
add_action( 'save_post', 'cww_event_save_post');
function cww_event_save_post( $post_id ) {
	$post = get_post($post_id);
	// verify if this is an auto save routine. 
	// If it is our form has not been submitted, so we dont want to do anything
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return;
	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( isset($_POST['cww_event_nonce']) ? $_POST['cww_event_nonce'] : false, 'cww_nonce_field_cww_event' ) )
		return;
	
	// Get the post type object.
    $post_type = get_post_type_object( $post->post_type );
    // Check if the current user has permission to edit this post-type.
    if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
        return;
	foreach ( $_POST as $key => $value ) {
		if ( preg_match( '/^cww_event_.*/', $key ) ) {
			// Times
			if (is_array($value)) {
				$value = $value[1] . ':' . $value[2] . $value[3];
			}
			error_log("$key: $value");
			update_post_meta( $post_id, $key, trim( $value ) );
		}
	}
}

add_shortcode( 'eventstartdate', 'cww_event_startdate_shortcode_callback' );
function cww_event_startdate_shortcode_callback( $atts, $content = null ) {
	$post = $GLOBALS['post'];
	$format = empty($atts['format']) ? 'l, F jS, Y' : $atts['format'];
	$start_date = get_post_meta($post->ID, 'cww_event_start_date', true);
	error_log(print_r(date_parse($start_date),true));
	return date($format, strtotime($start_date));
}

add_shortcode( 'eventstarttime', 'cww_event_starttime_shortcode_callback');
function cww_event_starttime_shortcode_callback( $atts, $content = null ) {
	$post = $GLOBALS['post'];
	return (get_post_meta($post->ID, 'cww_event_start_time', true) . 'm');
}

add_shortcode( 'eventenddate', 'cww_event_enddate_shortcode_callback' );
function cww_event_enddate_shortcode_callback( $atts, $content = null ) {
	$post = $GLOBALS['post'];
	$format = empty($atts['format']) ? 'l, F jS, Y' : $atts['format'];
	$end_date = get_post_meta($post->ID, 'cww_event_end_date', true);
	return date($format, strtotime($end_date));
}

add_shortcode( 'eventendtime', 'cww_event_endtime_shortcode_callback');
function cww_event_endtime_shortcode_callback( $atts, $content = null ) {
	$post = $GLOBALS['post'];
	return (get_post_meta($post->ID, 'cww_event_end_time', true) . 'm');
}

add_shortcode( 'eventlocation', 'cww_event_location_shortcode_callback');
function cww_event_location_shortcode_callback( $atts, $content = null ) {
	$post = $GLOBALS['post'];
	return get_post_meta($post->ID, 'cww_event_location', true);
}

add_shortcode( 'eventregbtn', 'cww_event_regbtn_shortcode_callback');
function cww_event_regbtn_shortcode_callback( $atts, $content = null ) {
	$post = $GLOBALS['post'];
	$url = get_post_meta($post->ID, 'cww_event_reg_btn_url', true);
	$url = $url ? $url : '#';
	$class = empty( $atts['class'] ) ? 'button gray small' : $atts['class'];
	$content = empty( $content ) ? 'Register' : $content;
	return '<a href="' . $url . '" class="' . $class . '"><span>' . $content . '</span></a>';
}