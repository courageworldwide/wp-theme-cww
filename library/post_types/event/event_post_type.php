<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-content/themes/cww/library/cww/CwwPostTypeEngine.class.php');
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
		'description' => __( 'Create an event, with start date and time, etc.' ),
		'rewrite' => array('slug' => 'events','with_front' => false),
		'public' => true,
		'has_archive' => true,
		'show_in_nav_menus' => false,
		'menu_position' => 20,
		'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'page-attributes', 'post-formats')
	)
);
$cww_event_meta_boxes = cww_event_meta_boxes();
$cww_event_post_type_engine = new CwwPostTypeEngine($cww_event_post_type, $cww_event_meta_boxes);
add_action('init', array(&$cww_event_post_type_engine, 'create_post_type'));
add_action('admin_init', array(&$cww_event_post_type_engine, 'add_meta_boxes'));

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
    
    // Check if the current user has permission to edit the post.
    if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
        return;
	
	foreach ( $_POST as $key => $value ) {
		if ( preg_match( '/^cww_event_.*/', $key ) ) {
			// Times
			if (is_array($value)) {
				$value = $value[1] . ':' . $value[2] . ' ' . $value[3];
			}
			update_post_meta( $post_id, $key, trim( $value ) );
		}
	}
}