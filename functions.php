<?php
/****************************************************************
 * Functions for the Courage Worldwide theme.
 ****************************************************************/
// Require donate form settings in admin
if (is_admin()) {
	require_once('library/post_types/donate_form/df_settings.php');
	add_action('admin_enqueue_scripts', 'cww_admin_scripts');
	add_action('admin_enqueue_scripts', 'cww_admin_styles');
}
require_once('library/post_types/donate_form/df_post_type.php');
require_once('library/post_types/event/event_post_type.php');

function cww_admin_scripts() {
	wp_register_script( 'cww-admin', get_stylesheet_directory_uri() . '/js/admin.js', array( 'jquery' ));
	wp_enqueue_script( ' cww-admin' );
}

function cww_admin_styles() {
	wp_register_style( 'cww-admin', get_stylesheet_directory_uri() . '/css/admin.css');
	wp_enqueue_style( 'cww-admin' );
}

function cww_scripts() {
	// Build dependency array for donate-form
	$deps = array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker');
	// Register and enqueue donate-form
	wp_register_script( 'cww-donate-form', get_stylesheet_directory_uri() . '/js/donate-form.js', $deps);
	wp_enqueue_script( 'cww-donate-form' );
}
add_action('wp_enqueue_scripts', 'cww_scripts');
add_action('admin_enqueue_scripts', 'cww_scripts');

function cww_styles() {
	wp_register_style( 'jquery-ui', get_stylesheet_directory_uri() . '/css/smoothness/jquery-ui-1.8.21.custom.css');  
    wp_enqueue_style( 'jquery-ui' );  
}
add_action('wp_enqueue_scripts', 'cww_styles');
add_action('admin_enqueue_scripts', 'cww_styles');