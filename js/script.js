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
		 * Select/deselect neccessary elements
		 */
		if ( $( '#sbscrbr-choose-admin-name' ).is( ':checked' ) ) {
			$( 'input[name="sbscrbr_from_email"]' ).attr( 'disabled', true );
		}
		$( 'select[name="sbscrbr_from_admin_name"]' ).focus( function() {
			$( '#sbscrbr-choose-admin-name' ).attr( 'checked', 'checked' );
			$( 'input[name="sbscrbr_from_email"]' ).attr( 'disabled', true );
			sbscrbrShowEmail( $( this ).val() );
		}).change( function() {
			sbscrbrShowEmail( $( this ).val() );
		});
		$( 'input[name="sbscrbr_from_custom_name"]' ).focus( function() {
			$( '#sbscrbr-choose-custom-name' ).attr( 'checked', 'checked' );
			$( 'input[name="sbscrbr_from_email"]' ).attr( 'disabled', false ).val( '' );
		});
		$( '#sbscrbr-choose-admin-name' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				$( 'input[name="sbscrbr_from_email"]' ).attr( 'disabled', true );
				sbscrbrShowEmail( $( 'select[name="sbscrbr_from_admin_name"]' ).val() );
			}
		});
		$( '#sbscrbr-choose-custom-name' ).change( function() {
			if ( $( this ).is( ':checked' ) ) {
				$( 'input[name="sbscrbr_from_email"]' ).attr( 'disabled', false ).val( '' );
			}
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

/**
 * Get admin e-mail and insert them in neccessary input[type='text'] on settings page
 * @param   string   adminName login name of selected user on settings page
 * @return  void
 */
function sbscrbrShowEmail ( adminName ) {
	( function( $ ) {
		$.ajax({
			type: "POST",
			url: ajaxurl,
			data: { action: 'sbscrbr_show_email', display_name: adminName },
			beforeSend: function() {
				/* display preloader */
				$( 'input[name="sbscrbr_from_email"]' ).parent().append( '<div class="sbscrbr-preloader"></div>' );
			},
			success: function ( result ) {
				/* hide preloader */
				$( '.sbscrbr-preloader' ).remove();
				/* insert e-mail */
				$( 'input[name="sbscrbr_from_email"]' ).val( result );
			},
			error: function( request, status, error ) {
				alert( error + request.status );
				errors == 0;
			}
		});
	})(jQuery);
}