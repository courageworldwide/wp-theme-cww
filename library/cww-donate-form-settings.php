<?php
require_once('cww-donate-form-options.php');

add_action('admin_enqueue_scripts', 'cww_df_settings_scripts');
/* 
 * Group scripts (js & css) 
 */  
function cww_df_settings_scripts(){
    wp_enqueue_script( 'cww_theme_settings_js', get_stylesheet_directory_uri() . '/js/admin/theme_settings.js', array('jquery'));
    wp_register_style( 'cww_theme_settings_css', get_stylesheet_directory_uri() . '/css/admin/theme-settings.css');
    wp_enqueue_style('cww_theme_settings_css');
}

add_action( 'admin_menu', 'cww_df_options_add_page' );
function cww_df_options_add_page() {
	// May want to require a more advanced 'capability' to make these changes.
	$cww_df_settings_page = add_options_page(__('Donate form options'), __('Donate forms'), 'manage_options', 'cww_df_options', 'cww_df_options_page_callback' );
	
	// css & js  
    add_action( 'load-'. $cww_df_settings_page, 'cww_df_settings_scripts' ); 
} 

function cww_df_options_page_callback() {
	// get the settings sections array  
    $settings_output = cww_df_options_get_settings();  
    ?>  
    <div class="wrap">  
        <div class="icon32" id="icon-options-general"></div>  
        <h2><?php echo $settings_output['cww_df_options_page_title']; ?></h2>  
          
        <form action="options.php" method="post">  
            <?php   
            // http://codex.wordpress.org/Function_Reference/settings_fields  
            settings_fields($settings_output['cww_df_options_option_name']);   
              
            // http://codex.wordpress.org/Function_Reference/do_settings_sections  
            do_settings_sections(__FILE__);   
            ?>  
            <p class="submit">  
                <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes','cww'); ?>" />  
            </p>  
              
        </form>  
    </div><!-- wrap -->
<?php
} // cww_df_options_page_callback

 /** 
 * Helper function for defining variables for the current page 
 * 
 * @return array 
 */  
function cww_df_options_get_settings() { 
    $output = array();  
    // put together the output array   
    $output['cww_df_options_option_name']       = 'cww_df_options'; // the option name as used in the get_option() call.  
    $output['cww_df_options_page_title']        = __( 'Donate form options','cww'); // the settings page title  
    $output['cww_df_options_page_sections']     = cww_df_options_page_sections(); // the setting section  
    $output['cww_df_options_page_fields']       = cww_df_options_page_fields(); // the setting fields  
    $output['cww_df_options_contextual_help']   = ''; // the contextual help  
    
    return $output;  
}

/** 
 * Helper function for registering our form field settings 
 * 
 * src: http://alisothegeek.com/2011/01/wordpress-settings-api-tutorial-1/ 
 * @param (array) $args The array of arguments to be used in creating the field 
 * @return function call 
 */  
function cww_df_options_create_settings_field( $args = array() ) {  
    // default array to overwrite when calling the function  
    $defaults = array(  
        'id'      => 'cww_df_options_field',                    // the ID of the setting in our options array, and the ID of the HTML form element  
        'title'   => 'Field',                    // the label for the HTML form element  
        'desc'    => 'This is a default description.',  // the description displayed under the HTML form element  
        'std'     => '',                                 // the default value for this setting  
        'type'    => 'text',                             // the HTML form element to use  
        'section' => 'main_section',                     // the section this setting belongs to — must match the array key of a section in wptuts_options_page_sections()  
        'choices' => array(),                            // (optional): the values in radio buttons or a drop-down menu  
        'class'   => ''                                  // the HTML form element class. Also used for validation purposes!  
    );  
      
    // "extract" to be able to use the array keys as variables in our function output below  
    extract( wp_parse_args( $args, $defaults ) );  
      
    // additional arguments for use in form field output in the function wptuts_form_field_fn!  
    $field_args = array(  
        'type'      => $type,  
        'id'        => $id,  
        'desc'      => $desc,  
        'std'       => $std,  
        'choices'   => $choices,  
        'label_for' => $id,  
        'class'     => $class  
    );  
  
    add_settings_field( $id, $title, 'cww_df_options_form_field_callback', __FILE__, $section, $field_args );  
  
}  


add_action( 'admin_init', 'cww_df_options_register_settings' );
/* 
 * Register our settings
 */  
function cww_df_options_register_settings(){  
    // get the settings sections array
    $settings_output    = cww_df_options_get_settings();  
    $cww_df_option_name = $settings_output['cww_df_options_option_name']; 
    //setting  
    //register_setting( $option_group, $option_name, $sanitize_callback );  
    register_setting($cww_df_option_name, $cww_df_option_name, 'cww_df_validate_options' );
    //sections  
    // add_settings_section( $id, $title, $callback, $page );  
    if(!empty($settings_output['cww_df_options_page_sections'])){  
        // call the "add_settings_section" for each!  
        foreach ( $settings_output['cww_df_options_page_sections'] as $id => $title ) {  
            add_settings_section( $id, $title, 'cww_df_options_section_callback', __FILE__);  
        }  
    }
    //fields  
    if(!empty($settings_output['cww_df_options_page_fields'])){  
        // call the "add_settings_field" for each!  
        foreach ($settings_output['cww_df_options_page_fields'] as $option) {  
            cww_df_options_create_settings_field($option);  
        }  
    } 
}

/* 
 * Section HTML, displayed before the first option 
 * @return echoes output 
 */  
function  cww_df_options_section_callback($desc) {  
	// __('Settings for this section','cww')
    echo "<p>" . $desc['title'] . " " . __('settings for donate forms', 'cww') . ".</p>";
}

/* 
 * Form Fields HTML 
 * All form field types share the same function!! 
 * @return echoes output 
 */  
function cww_df_options_form_field_callback($args = array()) {  
      
    extract( $args );  
      
    // get the settings sections array  
    $settings_output    = cww_df_options_get_settings();  
      
    $cww_df_option_name = $settings_output['cww_df_options_option_name'];  
    $options            = get_option($cww_df_option_name);  
      
    // pass the standard value if the option is not yet set in the database  
    if ( !isset( $options[$id] ) && 'type' != 'checkbox' ) {  
        $options[$id] = $std;  
    }  
      
    // additional field class. output only if the class is defined in the create_setting arguments  
    $field_class = ($class != '') ? ' ' . $class : '';  
      
      
    // switch html display based on the setting type.  
    switch ( $type ) {  
        case 'text':  
            $options[$id] = stripslashes($options[$id]);  
            $options[$id] = esc_attr( $options[$id]);  
            echo "<input class='regular-text$field_class' type='text' id='$id' name='" . $cww_df_option_name . "[$id]' value='$options[$id]' />";  
            echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";  
        break;  
          
        case "multi-text":  
            foreach($choices as $item) {  
                $item = explode("|",$item); // cat_name|cat_slug  
                $item[0] = esc_html__($item[0], 'cww');  
                if (!empty($options[$id])) {  
                    foreach ($options[$id] as $option_key => $option_val){  
                        if ($item[1] == $option_key) {  
                            $value = $option_val;  
                        }  
                    }  
                } else {  
                    $value = '';  
                }  
                echo "<span>$item[0]:</span> <input class='$field_class' type='text' id='$id|$item[1]' name='" . $cww_df_option_name . "[$id|$item[1]]' value='$value' /><br/>";  
            }  
            echo ($desc != '') ? "<span class='description'>$desc</span>" : "";  
        break;  
          
        case 'textarea':  
            $options[$id] = stripslashes($options[$id]);  
            $options[$id] = esc_html( $options[$id]);  
            echo "<textarea class='textarea$field_class' type='text' id='$id' name='" . $cww_df_option_name . "[$id]' rows='5' cols='30'>$options[$id]</textarea>";  
            echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";           
        break;  
          
        case 'select':  
            echo "<select id='$id' class='select$field_class' name='" . $cww_df_option_name . "[$id]'>";  
                foreach($choices as $item) {  
                    $value  = esc_attr($item, 'cww');  
                    $item   = esc_html($item, 'cww');  
                      
                    $selected = ($options[$id]==$value) ? 'selected="selected"' : '';  
                    echo "<option value='$value' $selected>$item</option>";  
                }  
            echo "</select>";  
            echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";   
        break;  
          
        case 'select2':  
            echo "<select id='$id' class='select$field_class' name='" . $cww_df_option_name . "[$id]'>";  
            foreach($choices as $item) {  
                  
                $item = explode("|",$item);  
                $item[0] = esc_html($item[0], 'cww');  
                  
                $selected = ($options[$id]==$item[1]) ? 'selected="selected"' : '';  
                echo "<option value='$item[1]' $selected>$item[0]</option>";  
            }  
            echo "</select>";  
            echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";  
        break;  
          
        case 'checkbox':  
            echo "<input class='checkbox$field_class' type='checkbox' id='$id' name='" . $cww_df_option_name . "[$id]' value='1' " . checked( $options[$id], 1, false ) . " />";  
            echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";  
        break;  
          
        case "multi-checkbox":  
            foreach($choices as $item) {  
                  
                $item = explode("|",$item);  
                $item[0] = esc_html($item[0], 'cww');  
                  
                $checked = '';  
                  
                if ( isset($options[$id][$item[1]]) ) {  
                    if ( $options[$id][$item[1]] == 'true') {  
                        $checked = 'checked="checked"';  
                    }  
                }  
                  
                echo "<input class='checkbox$field_class' type='checkbox' id='$id|$item[1]' name='" . $cww_df_option_name . "[$id|$item[1]]' value='1' $checked /> $item[0] <br/>";  
            }  
            echo ($desc != '') ? "<br /><span class='description'>$desc</span>" : "";  
        break;  
    }  
}  

/* 
 * Validate input 
 *  
 * @return array 
 */  
function cww_df_validate_options($input) {
	// for enhanced security, create a new empty array  
    $valid_input = array();  
      
    // collect only the values we expect and fill the new $valid_input array i.e. whitelist our option IDs  
      
    // get the settings sections array  
    $settings_output = cww_df_options_get_settings();  
      
    $options = $settings_output['cww_df_options_page_fields'];  
      
    // run a foreach and switch on option type  
    foreach ($options as $option) {  
        switch ( $option['type'] ) {  
            default:  // text
                //switch validation based on the class!  
                switch ( $option['class'] ) {
                	case 'numeric':
                		//accept the input only when numeric!  
                        $input[$option['id']]       = trim($input[$option['id']]); // trim whitespace  
                        $valid_input[$option['id']] = (is_numeric($input[$option['id']])) ? $input[$option['id']] : 'Expecting a Numeric value!';  
                          
                        // register error  
                        if(is_numeric($input[$option['id']]) == FALSE) {  
                            add_settings_error(  
                                $option['id'], // setting title  
                                $option['id'] . '_error', // error ID  
                                "The field '" . $option['title'] . "' " . __('must be a numeric value', 'cww') . '.', // error message  
                                'error' // type of message  
                            );  
                        }  
                    break;
                    default:
                    	$valid_input[$option['id']] = trim($input[$option['id']]);
                }
        }
   }
   return $valid_input;
}