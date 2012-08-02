<?php
/************************************************************************************ 
/* A class for generating Wordpress custom Post Types
/* By Jesse Rosato 2012
/************************************************************************************/
class CwwPostTypeEngine {

	protected $_post_type;
	protected $_meta_boxes;
	
	/************************************************************************************ 
	/* Default constructor
	/************************************************************************************/
	public function __construct($post_type = array(), $meta_boxes = array()) {
		if (!$this->set_post_type($post_type))
			throw new Exception('CwwPostTypeEngine constructor requires an array describing the post type.');
		if(!$this->set_meta_boxes($meta_boxes))
			throw new Exceptions('CwwPostTypeEngine constructor failed to process parameter 2, $meta_boxes.');
	}
	
	/************************************************************************************ 
	/* Set post type array
	/************************************************************************************/
	public function set_post_type($post_type = false) {
		if (!$post_type || !is_array($post_type) || empty($post_type))
			return false;
		if (!isset($post_type['handle']) || !$post_type['handle'])
			return false;
		$args = isset($post_type['args']) ? $post_type['args'] : false;
		if (!$args || !is_array($args) || empty($args))
			return false;
		$labels = isset($args['labels']) ? $args['labels'] : false;
		if (!$labels || !is_array($labels) || !isset($labels['name']) || !$labels['name'])
			return false;
		$this->_post_type = $post_type;
		return true;
	}
	
	/************************************************************************************ 
	/* Get post type array
	/************************************************************************************/
	public function get_post_type() {
		return $this->_post_type;
	}
	
	/************************************************************************************ 
	/* Set meta boxes array
	/************************************************************************************/
	public function set_meta_boxes($meta_boxes = false) {
		if (!$meta_boxes || !is_array($meta_boxes) || empty($meta_boxes)) {
			$this->_meta_boxes = array();
			return true;
		}
		foreach ($meta_boxes as $meta_box) {
			if (!isset($meta_box['title']) || !$meta_box['title'])
				return false;
		}
		$this->_meta_boxes = $meta_boxes;
		return true;
	}
	
	/************************************************************************************ 
	/* Get meta boxes array
	/************************************************************************************/
	public function get_meta_boxes() {
		return $this->_meta_boxes;
	}
	
	/************************************************************************************ 
	/* Register post type
	/************************************************************************************/
	public function create_post_type() {
		register_post_type($this->_post_type['handle'], $this->_post_type['args']);
		flush_rewrite_rules();
	}
	
	/************************************************************************************ 
	/* Add meta boxes
	/************************************************************************************/
	public function add_meta_boxes() {
		foreach ($this->_meta_boxes as $meta_box) {
			foreach ($meta_box as $key => $val)
				$$key = $val;
			$callback	= isset($callback) && $callback ? $callback : array(&$this, 'meta_box_callback');
			$post_type	= isset($post_type) && $post_type ? $post_type : $this->_post_type['args']['labels']['name'];
			$context	= isset($context) ? $context : 'advanced';
			$priority	= isset($priority) ? $priority : 'default';
			add_meta_box($handle, $title, $callback, $post_type, $context, $priority, $args);
		}
	}
	
	/************************************************************************************ 
	/* Print meta box
	/************************************************************************************/
	public function meta_box_callback( $post, $meta_box ) {
		// Use nonce for verification
		wp_nonce_field( 'cww_nonce_field_' . $this->_post_type['handle'], $this->_post_type['handle'] . '_nonce' );
		$meta_box_key	= $meta_box['id'];
		$meta_box_title	= $meta_box['title'];
		$meta_box_type	= isset($meta_box['args']['type']) ? $meta_box['args']['type'] : 'text';
		$meta_box_class = isset($meta_box['args']['class']) ? $meta_box['args']['class'] : '';
		$meta_box_desc	= isset($meta_box['args']['desc']) ? $meta_box['args']['desc'] : '';
		$meta_box_def	= isset($meta_box['args']['default']) ? $meta_box['args']['default'] : '';
		$meta_box_val 	= get_post_meta($post->ID, $meta_box_key);
		$meta_box_val 	= empty($meta_box_val) ? $meta_box_def : array_shift(array_values($meta_box_val));
		$label  = '<label for="' . $meta_box_key . '" >' . $meta_box_title . '</label>';
		$desc  = $meta_box_desc ? '<p class="metabox-description">' . $meta_box_desc . '</p>' : '';
		switch ($meta_box_type) {
			case 'checkbox':
				$input  = '<input type="checkbox" ';
				$input .= 'class="' . $meta_box_class . '" '; 
				$input .= 'id="' . $meta_box_key . '" ';
				$input .= 'name="' . $meta_box_key . '" ';
				$input .= $meta_box_val ? 'checked="checked" ' : '';
				$input .= 'value="1" ';
				$input .= '/>';
				echo $input . '&nbsp' . $label . $desc;
				
			break;
			case 'text':
				$input  = '<input type="text" ';
				$input .= 'class="' . $meta_box_class . '" '; 
				$input .= 'id="' . $meta_box_key . '" ';
				$input .= 'name="' . $meta_box_key . '" ';
				$input .= 'value="' . $meta_box_val . '" ';
				$input .= '/>';
				echo $label . '&nbsp;' . $input . $desc;
			break;
			default:
				throw new Exception('CwwPostTypeEngine meta_box_callback() failed handling meta box type "' . $meta_box_type . '".');
		}
	}
}