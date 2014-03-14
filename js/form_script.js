(function($) {
	$( document ).ready( function() {
		/**
		 * show preloader-icon
		 */
		$( '.sbscrbr-submit-block' ).each( function() {
			var form = $( this ).parent( '.subscrbr-sign-up-form' );
			$( this ).find( 'input.submit' ).click( function() {
				var offsetTop  = ( $( this ).outerHeight() - 16 ) / 2,
					offsetLeft = $( this ).outerWidth() + 4;
				$( this ).parent().append( '<div style="position: absolute;top: ' + offsetTop + 'px;left: ' + offsetLeft +'px;width: 16px;height: 16px;background: url( ' + preloaderIconPath + ' );background-size: 100%;"></div>' );
			});
		});
	});
})(jQuery);