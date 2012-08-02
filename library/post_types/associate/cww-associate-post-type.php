<?php
require_once(get_stylesheet_directory_uri() . '/library/post-types/shared/settings.php)

add_action( 'init', 'cww_associate_create_post_type' );
function cww_associate_create_post_type() {
	register_post_type(
		'cww_associate',
		array(
			'labels' => array(
				'name' => __( 'Associates' ),
				'singular_name' => __( 'Associate' ),
				'all items' => __( 'All Associates' ),
				'add_new_itm' => __( 'Add New Associate' ),
				'edit_item' => __( 'Edit Associate' ),
				'new_item' => __( 'New Associate' ),
				'view_item' => __( 'View Associate' ),
				'search_item' => __( 'Search Associates' ),
				'not_found' => __( 'No Associates found' ),
				'not_found_in_trash' => __( 'No Associates found in trash' )
			),
			'description' => __( 'Use this post type to create new associates.' ),
			'rewrite' => array('slug' => 'associates','with_front' => false),
			'public' => true,
			'has_archive' => true,
			'show_in_nav_menus' => false,
			'menu_position' => 20,
			'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'page-attributes', 'post-formats'),
		)
	);
	flush_rewrite_rules();
}

add_action('admin_init', 'cww_associate_add_meta_boxes_to_post_type');
function cww_associate_add_meta_boxes_to_post_type() {
	$temp = cww_associate_options_get_settings();
	$options = $temp['cww_associate_options_page_fields'];

	// Confirmation post ID
	add_meta_box(
		'cww_associate_first_name',
		__('First name'),
		'cww_associate_meta_box_callback',
		'cww_associate',
		'advanced',
		'default',
		array(
			'type' => 'text',
			'class' => 'first-name',
			'desc' => __("The Associate's first name (and middle name or names if necessary).", 'cww'),
			'default' => 'e.g. Andrea Lynn',
		)
	);
	// Confirmation mail post ID
	add_meta_box(
		'cww_associate_last_name',
		__('Last name'),
		'cww_associate_meta_box_callback',
		'cww_associate',
		'advanced',
		'default',
		array(
			'type' => 'text',
			'class' => 'last-name',
			'desc' => __("The associate's last name (and hyphenates if necessary).", 'cww'),
			'default' => 'e.g. Bonham Carter',
		)
	);
	// Monthly donation duration (in months)
	add_meta_box(
		'cww_associate_organization',
		__('Organization'),
		'cww_associate_meta_box_callback',
		'cww_associate',
		'advanced',
		'default',
		array(
			'type' => 'text',
			'desc' => __("Associate's company, agency, etc.", 'cww'),
			'default' => 'Courage Worldwide',
		)
	);
	// Annual donation duration (in years)
	add_meta_box(
		'cww_associate_relationship',
		__('Relationship'),
		'cww_associate_meta_box_callback',
		'cww_associate',
		'advanced',
		'default',
		array(
			'type' => 'select',
			'desc' => __("Associate's relationship to Courage Worldwide.", 'cww'),
			'options' => cww_associate_get_relationship_options();
		)
	);
}

/* Prints the box content */
function cww_associate_meta_box_callback( $post, $metabox ) {
		// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'cww_associate_nonce' );
	$mb_key		= $metabox['id'];
	$mb_title	= $metabox['title'];
	$mb_type	= isset($metabox['args']['type']) ? $metabox['args']['type'] : 'text';
	$mb_class 	= isset($metabox['args']['class']) ? $metabox['args']['class'] : '';
	$mb_desc	= isset($metabox['args']['desc']) ? $metabox['args']['desc'] : '';
	$mb_val 	= get_post_meta($post->ID, $mb_key, true);
	
	$mb_val 	= $mb_val ? $mb_val : $metabox['args']['default'];
	
	$label  = '<label for="' . $mb_key . '" >' . $mb_title . '</label>';
	$desc  = $mb_desc ? '<p class="metabox-description">' . $mb_desc . '</p>' : '';

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
		case 'select':
			$input  = '<select ';
			$input .= 'class="' . $mb_class . '" ';
			$input .= 'id="' . $mb_key . '" ';
			$input .= 'name="' . $mb_key . '" ';
			$input .= '>';
			foreach ($metabox['args']['options'] as $value => $text) {
				$input .= '<option value="' . $value; '" ';
				$input .= $mb_val == $value ? 'selected="selected"' : '';
				$input .= '>' . $text . '</option>';
			}
			$input .= '</select>';
			echo $label . '&nbsp;' . $input . $desc;
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

add_action( 'save_post', 'cww_associate_save_post');
function cww_associate_save_post( $post_id ) {
	// verify if this is an auto save routine. 
	// If it is our form has not been submitted, so we dont want to do anything
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	return;

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( !wp_verify_nonce( $_POST['cww_associate_nonce'], plugin_basename( __FILE__ ) ) )
	  return;
	
	// Check permissions
	if ( 'page' == $_POST['cww_associate'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
		    return;
	} else {
		if ( !current_user_can( 'edit_post', $post_id ) )
		    return;
	}
	
	// OK, we're authenticated: we need to find and save the data
	foreach ( $_POST as $key => $value ) {
		if ( preg_match( '/^cww_associate_.*/', $key ) ) {
			$value = trim( $_POST[$key] );
			add_post_meta( $post_id, $key, $value );
		}
	}
}

add_action('admin_notices','cww_associate_admin_notice');
function cww_associate_admin_notice(){
	if (!current_user_can('manage_options'))
		return;

    if ( cww_associate_required_options_are_set() ) {
    ?>
    	<div class="error">
    	  <p>
    	  	<strong><?php _e('Courage Worldwide Notice'); ?></strong><br />
    	  </p>
    	  <p>
    	    <?php _e( "'Associates' post type is not enabled.  You must first supply your sitewide information in 'Settings >> Associates'." ); ?>
    	  </p>
    	</div>
	<?php
	}
 }
 
 add_filter( 'custom_menu_order', 'cww_associate_required_options_are_set' );
 function cww_associate_required_options_are_set() {
 	$options = cww_associate_options_get_settings();
	foreach ( $options['cww_associate_options_page_fields'] as $option ) {
		if ( isset( $option['req'] ) && $option['req'] && !cww_associate_option_is_set($option) )
			return true;
	}
    return false;
 }
 
 function cww_associate_option_is_set( $option ) {
 	static $settings = false;
 	$settings = $settings ? $settings : get_option('cww_associate_options');
 	$setting = isset($settings[$option['id']]) ? $settings[$option['id']] : false;
	return ( $setting && $option['std'] != $setting );
 }
 
 add_filter( 'menu_order', 'cww_associate_hide_post_type' );
 function cww_associate_hide_post_type($menu_order) {		
	global $menu;
	foreach ( $menu as $key => $array ) {
		if ( in_array( 'edit.php?post_type=cww_associate', $array ) ) 
			$unset_key = $key;
	}
	unset($menu[$unset_key]);
	return $menu_order;
 }