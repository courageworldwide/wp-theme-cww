<?php

function cww_df_create_post_type() {
	register_post_type(
		'cww_donate_form',
		array(
			'labels' => array(
				'name' => __( 'Donate Forms' ),
				'singular_name' => __( 'Donate Form' ),
				'all items' => __( 'All Donate Forms' ),
				'add_new_itm' => __( 'Add New Donate Form' ),
				'edit_item' => __( 'Edit Donate Form' ),
				'new_item' => __( 'New Donate Form' ),
				'view_item' => __( 'View Donate Form' ),
				'search_item' => __( 'Search Donate Forms' ),
				'not_found' => __( 'No Donate Forms found' ),
				'not_found_in_trash' => __( 'No Donate Forms found in trash' )
			),
			'description' => __( 'Use this post type to create new donate forms.' ),
			'rewrite' => array('slug' => 'cww_forms','with_front' => false),
			'public' => true,
			'has_archive' => false,
			'show_in_nav_menus' => false,
			'menu_position' => 20,
			'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'page-attributes'),
		)
	);
	flush_rewrite_rules();
}
add_action( 'init', 'cww_df_create_post_type' );

function cww_df_add_meta_boxes_to_post_type() {
	// Confirmation post ID
	add_meta_box(
		'cww_df_conf_post_id',
		__('Success Post ID'),
		'cww_df_meta_box_callback',
		'cww_donate_form',
		'advanced',
		'default',
		array(
			'type' => 'text',
			'class' => 'numeric',
			'desc' => __("Please enter the ID of the Wordpress post to which you'd like users to be redirected upon successful completion of this form.", 'cww'),
			'default' => 'e.g. 1293',
		)
	);
	// Confirmation mail post ID
	add_meta_box(
		'cww_df_conf_mail_post_id',
		__('Success Email Post ID'),
		'cww_df_meta_box_callback',
		'cww_donate_form',
		'advanced',
		'default',
		array(
			'type' => 'text',
			'class' => 'numeric',
			'desc' => __("Leave blank to use the success post ID above. Please enter the ID of the Wordpress post you'd like to email to the user upon successful completion of this form.  The following tokens are available: %transaction_id, %donation_type, %name, %address, %company, %card_number, and %amount.", 'cww'),
			'default' => 'e.g. 1293',
		)
	);
	// Monthly donation duration (in months)
	add_meta_box(
		'cww_df_monthly_duration',
		__('Monthly donation duration'),
		'cww_df_meta_box_callback',
		'cww_donate_form',
		'advanced',
		'default',
		array(
			'type' => 'text',
			'class' => 'numeric',
			'desc' => __("Number of months to repeat monthly (and business) donations.", 'cww'),
			'default' => '12',
		)
	);
	// Annual donation duration (in years)
	add_meta_box(
		'cww_df_annual_duration',
		__('Annual donation duration'),
		'cww_df_meta_box_callback',
		'cww_donate_form',
		'advanced',
		'default',
		array(
			'type' => 'text',
			'class' => 'numeric',
			'desc' => __("Number of years to repeat annual donations.", 'cww'),
			'default' => '5',
		)
	);
	// Mailchimp List ID
	add_meta_box(
		'cww_df_mc_list_id',
		__('Mailchimp List ID'),
		'cww_df_meta_box_callback',
		'cww_donate_form',
		'advanced',
		'default',
		array(
			'type' => 'text',
			'desc' => __("Please enter the ID of the Mailchimp list to which you'd like to add users who complete this form.  Leave blank to disable Mailchimp sign-up on this form.", 'cww'),
			'default' => '',
		)
	);
	// Confirmation mail post ID
	add_meta_box(
		'cww_df_update_hr',
		__('Update Highrise'),
		'cww_df_meta_box_callback',
		'cww_donate_form',
		'advanced',
		'default',
		array(
			'type' => 'checkbox',
			'desc' => __("Choose whether or not to update the Highrise database with the user and donation data upon completion of this form.", 'cww'),
			'default' => '1',
		)
	);
}
add_action('admin_init', 'cww_df_add_meta_boxes_to_post_type');

/* Prints the box content */
function cww_df_meta_box_callback( $post, $metabox ) {
		// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'cww_df_nonce' );
	$mb_key		= $metabox['id'];
	$mb_title	= $metabox['title'];
	$mb_type	= isset($metabox['args']['type']) ? $metabox['args']['type'] : 'text';
	$mb_class 	= isset($metabox['args']['class']) ? $metabox['args']['class'] : '';
	$mb_desc	= isset($metabox['args']['desc']) ? $metabox['args']['desc'] : '';
	$mb_val 	= get_post_meta($post->ID, $mb_key, TRUE);
	
	$mb_val 	= $mb_val ? $mb_val : $metabox['args']['default'];
	
	$label  = '<label for="' . $mb_key . '" >' . $mb_title . '</label>';
	$desc  = $mb_desc ? '<div class="input-description">' . $mb_desc . '</div>' : '';

	switch ($mb_type) {
		case 'checkbox':
			$checked = $mb_val ? 'checked' : '';
			$input  = '<input type="checkbox" ';
			$input .= 'class="' . $mb_class . '" '; 
			$input .= 'id="' . $mb_key . '" ';
			$input .= 'name="' . $mb_key . '" ';
			$input .= 'checked="' . $checked . '" ';
			$input .= 'value="1" ';
			$input .= '/>';
			echo $input . '&nbsp' . $label . $desc;
			
		break;
		default:
			$input  = '<input type="text" ';
			$input .= 'class="' . $mb_class . '" '; 
			$input .= 'id="' . $mb_key . '" ';
			$input .= 'name="' . $mb_key . '" ';
			$input .= 'value="' . $mb_val . '" ';
			$input .= '/>';
			echo $label . '&nbsp;' . $input . $desc;
	}
}

function cww_df_save_post( $post_id ) {
	// verify if this is an auto save routine. 
	// If it is our form has not been submitted, so we dont want to do anything
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	return;

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( $_POST['cww_df_nonce'], plugin_basename( __FILE__ ) ) )
	  return;
	
	// Check permissions
	if ( 'page' == $_POST['cww_donate_form'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
		    return;
	} else {
		if ( !current_user_can( 'edit_post', $post_id ) )
		    return;
	}
	
	// OK, we're authenticated: we need to find and save the data
	$_POST['cww_df_hr_update'] = isset($_POST['cww_df_mc_list_id']) && $_POST['cww_df_mc_list_id'] ? 1 : 0;
	
	foreach ($_POST as $key => $value) {
		if ( preg_match( '/^cww_df_.*/', $key ) ) {
			$value = trim( $_POST[$key] );
			add_post_meta( $post_id, $key, $value );
		}
	}
}
add_action( 'save_post', 'cww_df_save_post');


function cww_df_admin_notice(){
    $screen = get_current_screen();
    //If not on the screen with ID 'edit-post' abort.
    if( $screen->id !='edit-cww_donate_form' )
        return;
     
    $settings = cww_df_options_get_settings();
    $options = get_option('cww_df_options');
     
    $error = FALSE;
    foreach ( $settings['cww_df_options_page_fields'] as $array ) {
     	$key = $array['id'];
	    if ( $options[$key] == $array['std'] || !$options[$key] )
	    	$error = TRUE;
    }
    
    if ($error) {
    ?>
    	<div class="error">
    	  <p>
    	  	<strong>Warning</strong><br />
    	    <?php _e( "You should visit 'Settings > Donate forms' and make sure your settings are correct before you attempt to add any donate forms." ); ?>
    	  </p>
    	</div>
	<?php
	}
 }
 add_action('admin_notices','cww_df_admin_notice');