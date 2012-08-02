jQuery(function(){
	var errors = jQuery('.settings-error');
	jQuery.each(errors, function(index, val) {
		var error_setting = jQuery(this).attr('id').replace('_error', '');
		error_setting = error_setting.replace('setting-error-', '');
		// look for the label with the "for" attribute=setting title and give it an "error" class (style this in the css file!)  
        jQuery("label[for='" + error_setting + "']").addClass('error');  
          
        // look for the input with id=setting title and add a red border to it.  
        jQuery("#" + error_setting).addClass('error');
	});   
}); 