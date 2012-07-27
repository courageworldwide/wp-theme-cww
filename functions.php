<?php
/****************************************************************
 * Functions for the Courage Worldwide theme.
 ****************************************************************/

require_once('library/cww-donate-form-post-type.php');

// Require donate form settings in admin
if (is_admin()) {
	require_once('library/cww-donate-form-settings.php');
}

function cww_scripts() {
	// Build dependency array for donate-form
	$deps = array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker');
	// Register and enqueue donate-form
	wp_register_script( 'donate-form', get_stylesheet_directory_uri() . '/js/donate-form.js', $deps) ;
	wp_enqueue_script( 'donate-form' );
}
add_action('wp_enqueue_scripts', 'cww_scripts');

function cww_styles() {
	wp_register_style( 'jquery-ui', get_stylesheet_directory_uri() . '/css/smoothness/jquery-ui-1.8.21.custom.css');  
    wp_enqueue_style( 'jquery-ui' );  
}
add_action('wp_enqueue_scripts', 'cww_styles');