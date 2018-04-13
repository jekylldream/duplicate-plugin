
jQuery.noConflict();

jQuery(document).ready(function($) {
	
	//DASHBOARD OVERVIEW
	
	//Onclick Duplicate AJAX Function
	//These are written to allow the on click binding to apply to dynamically created buttons as well		
	$("body").on('click', '.jd-duplicate',function(evt) {

		
		if ( $(this).attr('name') != 'redirect-to-edit') {
			var check = confirm('Are you sure you want to duplicate ' + $(this).attr('name') + ' (ID: ' + $(this).attr('value') + ')?');
		}
		else {
			var check = confirm('Are you sure you want to duplicate this post?');
		}

		if( check == true ) {

		$.post(
			ajaxurl,
			{
				'action':'jd_duplicate_handler',
				'postname':$(this).attr('name'),
				'postid':$(this).attr('value')
			}
		).done( function( data ) {
			 
			if( data == 0 ){

				location.reload();

			}
			else {

				window.location.href = data;
				
			}
			

		});

	}
	else {
		alert("Canceled duplication.");
	}

	});
		
});