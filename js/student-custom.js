jQuery(document).ready(function(){

	jQuery('#Myform').validate({
		rules: {
			first_name: "required",
			middle_name: "required",
			last_name: "required",
			DOB: {
				required: true,
				date: true
			},
			image: {
				required: function (element){ if(jQuery('#edit_id').val() == 0) { return true; } else { return false; } },
				extension: "jpg|png|jpeg|bmp",
			},
		},
		messages: {
			first_name: "Please Enter Your First Name.",
			middle_name: "Please Enter Your Middle Name.",
			last_name: "Please Enter Your Last Name.",
			DOB: {
				required: "Please Enter Your Birth Date.",
				date: "Please Enter a valid date.",
			},
			image: {
				required: "Please Your Profile Picture.",
				extension: "Please enter a value with a valid extension. Like: jpg|png|jpeg|bmp"
			},
			edit_image: {
				extension: "Please enter a value with a valid extension. Like: jpg|png|jpeg|bmp",
			}
		},

		submitHandler: function( form ){
			form.submit();
		}
	});

});


/* Modal */

function view_student_details( view_id ){
	var v_id = view_id;
	var ajaxUrl = jQuery('#std_ajax_url').val();
	var data = { v_id : v_id, type: 'POST', action: 'std_demo_data'};
	jQuery.post(ajaxUrl,data, function(response){
			jQuery('#per_student_details_ajax_response').html(response);
			/* jQuery("#dialogForm").dialog("open"); */
		}
	);
}
