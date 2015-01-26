(function($) {
	$( document ).ready( function() {
		/**
		 * show preloader-icon
		 */
		$( '.sbscrbr-submit-block' ).each( function() {
			var form = $( this ).parent( '.subscrbr-sign-up-form' );
			$( this ).find( 'input.submit' ).click( function() {
				if ( ( $('input[name="cptchpr_number"]').lenght > 0 && $('input[name="cptchpr_number"]').text() != "" ) || $('input[name="cptchpr_number"]').lenght < 0 ) {
					var offsetTop  = ( $( this ).outerHeight() - 16 ) / 2,
						offsetLeft = $( this ).outerWidth() + 4;
					$( this ).parent().append( '<div style="position: absolute;top: ' + offsetTop + 'px;left: ' + offsetLeft +'px;width: 16px;height: 16px;background: url( ' + sbscrbr_js_var.preloaderIconPath + ' );background-size: 100%;"></div>' );
				}
			});
		});
	});
})(jQuery);