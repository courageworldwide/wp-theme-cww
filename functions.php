<?php
/****************************************************************
 * Functions for the Courage Worldwide theme.
 ****************************************************************/

add_action( 'admin_init', 'cww_df_options_init' );
add_action( 'admin_menu', 'cww_df_options_add_page' );

function cww_df_options_add_page() {
	// May want to require a more advanced 'capability' to make these changes.
	add_theme_page( __( 'Donate form options', 'cww' ), __( 'Donate form', 'cww' ), 'edit_theme_options', 'cww_df_options', 'cww_df_options_callback' );
} 

function cww_df_options_init(){
	// Authorize.net
	add_settings_section('cww_df_authorizenet_setting_section',
						 'Authorize.net',
						 'cww_df_authorizenet_setting_section_callback',
						 'cww_df_options');
	// - API login ID
	add_settings_field('cww_df_authorizenet_setting_api_login_id',
					   'API login ID',
					   'cww_df_authorizenet_setting_api_login_id_callback',
					   'cww_df_options',
					   'cww_df_authorizenet_setting_section');
	register_setting('cww_df_options', 'cww_df_authorizenet_setting_api_login_id');
	// - Transaction Key
	add_settings_field('cww_df_authorizenet_setting_transaction_key',
					   'Transaction key',
					   'cww_df_authorizenet_setting_transaction_key_callback',
					   'cww_df_options',
					   'cww_df_authorizenet_setting_section');
	register_setting('cww_df_options', 'cww_df_authorizenet_setting_transaction_key');
	// Mailchimp
	add_settings_section('cww_df_mailchimp_setting_section',
						 'Mailchimp',
						 'cww_df_mailchimp_setting_section_callback',
						 'cww_df_options');
	// - API Token
	add_settings_field('cww_df_mailchimp_setting_api_token',
					   'API token',
					   'cww_df_mailchimp_setting_api_token_callback',
					   'cww_df_options',
					   'cww_df_mailchimp_setting_section');
	register_setting('cww_df_options', 'cww_df_mailchimp_setting_api_token');
	// Highrise
	add_settings_section('cww_df_highrise_setting_section',
						 'Highrise',
						 'cww_df_highrise_setting_section_callback',
						 'cww_df_options');
	// - Account
	add_settings_field('cww_df_highrise_setting_account',
					   'Account',
					   'cww_df_highrise_setting_account_callback',
					   'cww_df_options',
					   'cww_df_highrise_setting_section');
	register_setting('cww_df_options', 'cww_df_highrise_setting_account');				  
	// - API Token
	add_settings_field('cww_df_highrise_setting_api_token',
					   'API token',
					   'cww_df_highrise_setting_api_token_callback',
					   'cww_df_options',
					   'cww_df_highrise_setting_section');
	register_setting('cww_df_options', 'cww_df_highrise_setting_api_token');
	// - Admin ID
	add_settings_field('cww_df_highrise_setting_admin_user_id',
					   'Administrator ID',
					   'cww_df_highrise_setting_admin_user_id_callback',
					   'cww_df_options',
					   'cww_df_highrise_setting_section');
	register_setting('cww_df_options', 'cww_df_highrise_setting_admin_user_id');
	// - Admin group ID
	add_settings_field('cww_df_highrise_setting_admin_group_id',
					   'Administrator group ID',
					   'cww_df_highrise_setting_admin_group_id_callback',
					   'cww_df_options',
					   'cww_df_highrise_setting_section');
	register_setting('cww_df_options', 'cww_df_highrise_setting_admin_group_id');
	// - Deals admin ID
	add_settings_field('cww_df_highrise_setting_deals_admin_user_id',
					   'Deals administrator ID',
					   'cww_df_highrise_setting_deals_admin_user_id_callback',
					   'cww_df_options',
					   'cww_df_highrise_setting_section');
	register_setting('cww_df_options', 'cww_df_highrise_setting_deals_admin_user_id');
	// - Deal reminder-task delay
	add_settings_field('cww_df_highrise_setting_task_delay',
					   'Deal reminder-task delay',
					   'cww_df_highrise_setting_task_delay_callback',
					   'cww_df_options',
					   'cww_df_highrise_setting_section');
	register_setting('cww_df_options', 'cww_df_highrise_setting_task_delay');
	// - Category for onetime donations
	add_settings_field('cww_df_highrise_setting_onetime_category_id',
					   'Deals category for one time donations.',
					   'cww_df_highrise_setting_onetime_category_id_callback',
					   'cww_df_options',
					   'cww_df_highrise_setting_section');
	register_setting('cww_df_options', 'cww_df_highrise_setting_onetime_category_id');
	// - Category for monthly donations
	add_settings_field('cww_df_highrise_setting_monthly_category_id',
					   'Deals category for monthly donations.',
					   'cww_df_highrise_setting_monthly_category_id_callback',
					   'cww_df_options',
					   'cww_df_highrise_setting_section');
	register_setting('cww_df_options', 'cww_df_highrise_setting_monthly_category_id');
	// - Category for annual donations
	add_settings_field('cww_df_highrise_setting_annual_category_id',
					   'Deals category for annual donations.',
					   'cww_df_highrise_setting_annual_category_id_callback',
					   'cww_df_options',
					   'cww_df_highrise_setting_section');
	register_setting('cww_df_options', 'cww_df_highrise_setting_annual_category_id');
	// - Category for business donations
	add_settings_field('cww_df_highrise_setting_business_category_id',
					   'Deals category for annual donations.',
					   'cww_df_highrise_setting_business_category_id_callback',
					   'cww_df_options',
					   'cww_df_highrise_setting_section');
	register_setting('cww_df_options', 'cww_df_highrise_setting_business_category_id');
	
} 

function cww_scripts() {
	// Build dependency array for donate-form
	$deps = array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker');
	// Register and enqueue donate-form
	wp_register_script('donate-form', get_stylesheet_directory_uri() . '/js/donate-form.js', $deps);
	wp_enqueue_script('donate-form');
	// temp
	wp_register_script('donate-form', get_stylesheet_directory_uri() . '/js/donate-form.js', $deps);
	wp_enqueue_script('donate-form');
}
add_action('wp_enqueue_scripts', 'cww_scripts');

function cww_styles() {
	 wp_register_style( 'jquery-ui', get_stylesheet_directory_uri() . '/css/smoothness/jquery-ui-1.8.21.custom.css');  
    wp_enqueue_style( 'jquery-ui' );  
}
add_action('wp_enqueue_scripts', 'cww_styles');