(function($) {
	$( document ).ready( function() {
		/**
		 * add notice about changing on the settings page 
		 */
		$( '#sbscrbr-settings-page input' ).bind( "change select", function() {
			if ( $( this ).attr( 'type' ) != 'submit' ) {
				$( '.updated.fade, .error' ).css( 'display', 'none' );
				$( '#sbscrbr-settings-notice' ).css( 'display', 'block' );
			};
		});

		/**
		 * show/hide neccessary blocks on settings page
		 */
		$( '.sbscrbr-service-messages, .sbscrbr-messages-settings' ).hide();
		$( '#sbscrbr-show-service-messages' ).click( function() {
			$( '.sbscrbr-service-messages, #sbscrbr-hide-service-messages').show();
			$( this ).hide();
		});
		$( '#sbscrbr-hide-service-messages' ).click( function() {
			$( '#sbscrbr-show-service-messages' ).show();
			$( '.sbscrbr-service-messages' ).hide();
			$( this ).hide();
		});
		$( '#sbscrbr-show-messages-settings' ).click( function() {
			$( '.sbscrbr-messages-settings, #sbscrbr-hide-messages-settings').show();
			$( this ).hide();
		});
		$( '#sbscrbr-hide-messages-settings' ).click( function() {
			$( '#sbscrbr-show-messages-settings' ).show();
			$( '.sbscrbr-messages-settings' ).hide(); 
			$( this ).hide();
		});

		/**
		 * event on click on submit button on settings page
		 */
		$( '#sbscrbr-submit-button' ).click( function() {
			if( $( 'input[name="sbscrbr_from_email"]' ).is( ':disabled' ) ) {
				$( 'input[name="sbscrbr_from_email"]' ).attr( 'disabled', false );
			}
			$( this ).trigger( 'click' );
			return false;
		});
	});
})(jQuery);