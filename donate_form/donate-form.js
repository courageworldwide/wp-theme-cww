jQuery(document).ready(function($) {;
	// Donate form client-side validation
	jQuery('#donateform').submit(function(){
		// Client-side validation goes here.
	}); // donate_submit
	
	// Display the appropriate donation wrapper if a donation type has been selected.
	displayDonationWrapper(jQuery("input[name='df_type']:checked").val());
	
	//donate form events
	jQuery(".datepicker").datepicker({ dateFormat: 'yy-mm-dd' });
	/*jQuery("#x_type").click(function(){
		jQuery(".DateWrap").toggle();
	}); */
	jQuery("input[name='df_type']").change(function(){
		//hide all
		jQuery(".amount-date-wrap").hide();
		jQuery(".amount-wrap").hide();
		displayDonationWrapper(jQuery("input[name='df_type']:checked").val());
	});
	jQuery("#cancel-donate").click(function(){
		window.location = "/";							   
	});
});

function displayDonationWrapper(donation_type) {
	switch(donation_type) {
		case "monthly":
			jQuery("#monthly-wrap").show();
			jQuery(".date-wrap").show();
			break;
		case "annually":
			jQuery("#annual-wrap").show();
			jQuery(".date-wrap").show();
			break;
		case "business":
			jQuery("#business-wrap").show();
			jQuery(".date-wrap").show();
			break;
		case "onetime":
			jQuery("#onetime-wrap").show();
			jQuery(".date-wrap").hide();
			break;
		default:
			jQuery(".date-wrap").hide();
	}
	jQuery(".amount-date-wrap").show();
	return;
}