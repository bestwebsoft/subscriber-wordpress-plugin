(function($) {
	$( document ).ready( function() {
		$( 'input[name="sbscrbr_admin_message"]' ).change( function () {
			if ( $( this ).is( ':checked' ) ) {
				$( '.sbscrbr_for_admin_message:hidden' ).show();
			} else {
				$( '.sbscrbr_for_admin_message' ).hide();
			}
		}).trigger( 'change' );

		$( 'select[name="sbscrbr_email_user"]' ).on( 'change', function() {
			var name = $( this ).attr( 'name' );
			if ( name == "sbscrbr_email_user" ) {
				$( 'input#sbscrbr_to_email_user:not(:checked)' ).trigger( 'click' );
			}
		});

		$( 'input[name="sbscrbr_email_custom"]' ).on( 'change', function() {
			var name = $( this ).attr( 'name' );
			if ( name == "sbscrbr_email_custom" ) {
				$( 'input#sbscrbr_to_email_custom:not(:checked)' ).trigger( 'click' );
			}
		});
	});
})(jQuery);
