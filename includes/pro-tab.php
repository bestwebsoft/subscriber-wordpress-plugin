<?php
/**
 *
 * @package subscriber
 * @since 1.0.0
 */

if ( ! function_exists( 'sbscrbr_display_advertising' ) ) {
	function sbscrbr_display_advertising() {
		global $sbscrbr_plugin_info, $wp_version, $sbscrbr_options;
        if ( isset( $_POST['bws_hide_premium_options'] ) ) {
            check_admin_referer( plugin_basename( __FILE__ ), 'sbscrbr_nonce_name' );
            $result = bws_hide_premium_options( $sbscrbr_options );
            update_option( 'sbscrbr_options', $result['options'] ); ?>
            <div class="updated fade inline"><p><strong><?php echo $result['message']; ?></strong></p></div>
        <?php } elseif ( ! bws_hide_premium_options_check( $sbscrbr_options ) ) { ?>

				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'subscriber' ); ?>"></button>
                        <div class="bws_table_bg"></div>
                        <div>
                            <img class="sbscrbr_attempts" src="<?php echo plugins_url( '../images/subccriber-info.png', __FILE__ ); ?>" alt="" />
                        </div>
					</div>
					<div class="bws_pro_version_tooltip">
						<a class="bws_button" href="https://bestwebsoft.com/products/wordpress/plugins/subscriber/?k=d356381b0c3554404e34cdc4fe936455&pn=122&v=<?php echo $sbscrbr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank" title="subscriber Pro"><?php _e( "Upgrade to Pro", 'subscriber' ); ?></a>
						<div class="clear"></div>
					</div>
				</div>
				<?php wp_nonce_field( plugin_basename( __FILE__ ), 'sbscrbr_nonce_name' ); ?>
		<?php }
}
}