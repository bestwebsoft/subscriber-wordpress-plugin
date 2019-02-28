<?php
/**
 * Displays the content on the plugin settings page
 */

require_once( dirname( dirname( __FILE__ ) ) . '/bws_menu/class-bws-settings.php' );

if ( ! class_exists( 'Sbscrbr_Settings_Tabs' ) ) {
	class Sbscrbr_Settings_Tabs extends Bws_Settings_Tabs {
		/**
		 * Constructor.
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__construct() for more information on default arguments.
		 *
		 * @param string $plugin_basename
		 */
		public function __construct( $plugin_basename ) {
			global $sbscrbr_options, $sbscrbr_plugin_info;
			$tabs = array(
				'settings'				=> array( 'label' => __( 'Settings', 'subscriber' ) ),
				'email_notifications'	=> array( 'label' => __( 'Email Notifications', 'subscriber' ) ),
				'import-export' 		=> array( 'label' => __( 'Import / Export', 'subscriber' ), 'is_pro' => 1 ),
				'misc'					=> array( 'label' => __( 'Misc', 'subscriber' ) ),
				'custom_code'			=> array( 'label' => __( 'Custom Code', 'subscriber' ) ),
				'license'				=> array( 'label' => __( 'License Key', 'subscriber' ) ),
			);

			parent::__construct( array(
				'plugin_basename'			=> $plugin_basename,
				'plugins_info'				=> $sbscrbr_plugin_info,
				'prefix'					=> 'sbscrbr',
				'default_options'			=> sbscrbr_get_default_options(),
				'options'					=> $sbscrbr_options,
				'is_network_options'		=> is_network_admin(),
				'tabs'						=> $tabs,
				'wp_slug'					=> 'subscriber',
				'pro_page'					=> 'admin.php?page=subscriber-pro.php',
				'bws_license_plugin'		=> 'subscriber-pro/subscriber-pro.php',
				'link_key'					=> 'd356381b0c3554404e34cdc4fe936455',
				'link_pn'					=> '122'
			) );

			add_action( get_parent_class( $this ) . '_display_metabox', array( $this, 'display_metabox' ) );

			$this->all_plugins = get_plugins();
		}

		/**
		* Save plugin options to the database
		 * @access public
		 * @param  void
		 * @return array    The action results
		 */
		public function save_options() {

			/* Captcha PRO compatibility */
			$captcha_pro_options = is_multisite() ? get_site_option( 'cptch_options' ) : get_option( 'cptch_options' );
			$captcha_pro_enabled = ( isset( $captcha_pro_options['forms']['bws_subscriber']['enable'] ) && true == $captcha_pro_options['forms']['bws_subscriber']['enable'] ) ? true : false;

			/* reCAPTCHA PRO compatibility */
			$gglcptch_options = is_multisite() ? get_site_option( 'gglcptch_options' ) : get_option( 'gglcptch_options' );
			$gglcptch_enabled = ( ! empty( $gglcptch_options['sbscrbr'] ) ) ? true : false;

			/* form labels */
			$this->options['form_label'] 						= isset( $_POST['sbscrbr_form_label'] ) ? esc_html( $_POST['sbscrbr_form_label'] ) : $this->options['form_label'];
			$this->options['form_placeholder']					= isset( $_POST['sbscrbr_form_placeholder'] ) ? esc_html( $_POST['sbscrbr_form_placeholder'] ) : $this->options['form_placeholder'];
			$this->options['form_checkbox_label']				= isset( $_POST['sbscrbr_form_checkbox_label'] ) ? esc_html( $_POST['sbscrbr_form_checkbox_label'] ) : $this->options['form_checkbox_label'];
			$this->options['form_button_label']					= isset( $_POST['sbscrbr_form_button_label'] ) ? esc_html( $_POST['sbscrbr_form_button_label'] ) : $this->options['form_button_label'];
			$this->options['unsubscribe_button_name']		= isset( $_POST['sbscrbr_unsubscribe_form_button_label'] ) ? esc_html( $_POST['sbscrbr_unsubscribe_form_button_label'] ) : $this->options['unsubscribe_button_name'];
			$this->options['gdpr_cb_name']						= isset( $_POST['sbscrbr_gdpr_cb_name'] ) ? esc_html( $_POST['sbscrbr_gdpr_cb_name'] ) : $this->options['gdpr_cb_name'];
			$this->options['gdpr_text']							= isset( $_POST['sbscrbr_gdpr_text'] ) ? esc_html( $_POST['sbscrbr_gdpr_text'] ) : $this->options['gdpr_text'];
			$this->options['gdpr_link']							= isset( $_POST['sbscrbr_gdpr_link'] ) ? esc_html( $_POST['sbscrbr_gdpr_link'] ) : $this->options['gdpr_link'];
			$this->options['gdpr']								= isset( $_POST['sbscrbr_gdpr'] ) ? 1 : 0;

			/* service messages  */
			$this->options['bad_request']						= isset( $_POST['sbscrbr_bad_request'] ) ? esc_html( $_POST['sbscrbr_bad_request'] ) : $this->options['bad_request'];
			$this->options['empty_email']						= isset( $_POST['sbscrbr_empty_email'] ) ? esc_html( $_POST['sbscrbr_empty_email'] ) : $this->options['empty_email'];
			$this->options['invalid_email']						= isset( $_POST['sbscrbr_invalid_email'] ) ? esc_html( $_POST['sbscrbr_invalid_email'] ) : $this->options['invalid_email'];
			$this->options['not_exists_email']					= isset( $_POST['sbscrbr_not_exists_email'] ) ? esc_html( $_POST['sbscrbr_not_exists_email'] ) : $this->options['not_exists_email'];
			$this->options['cannot_get_email']					= isset( $_POST['sbscrbr_cannot_get_email'] ) ? esc_html( $_POST['sbscrbr_cannot_get_email'] ) : $this->options['cannot_get_email'];
			$this->options['cannot_send_email']					= isset( $_POST['sbscrbr_cannot_send_email'] ) ? esc_html( $_POST['sbscrbr_cannot_send_email'] ) : $this->options['cannot_send_email'];
			$this->options['error_subscribe']					= isset( $_POST['sbscrbr_error_subscribe'] ) ? esc_html( $_POST['sbscrbr_error_subscribe'] ) : $this->options['error_subscribe'];
			$this->options['done_subscribe']					= isset( $_POST['sbscrbr_done_subscribe'] ) ? esc_html( $_POST['sbscrbr_done_subscribe'] ) : $this->options['done_subscribe'];
			$this->options['already_subscribe']					= isset( $_POST['sbscrbr_already_subscribe'] ) ? esc_html( $_POST['sbscrbr_already_subscribe'] ) : $this->options['already_subscribe'];
			$this->options['denied_subscribe']					= isset( $_POST['sbscrbr_denied_subscribe'] ) ? esc_html( $_POST['sbscrbr_denied_subscribe'] ) : $this->options['denied_subscribe'];
			$this->options['already_unsubscribe']				= isset( $_POST['sbscrbr_already_unsubscribe'] ) ? esc_html( $_POST['sbscrbr_already_unsubscribe'] ) : $this->options['already_unsubscribe'];
			$this->options['check_email_unsubscribe']			= isset( $_POST['sbscrbr_check_email_unsubscribe'] ) ? esc_html( $_POST['sbscrbr_check_email_unsubscribe'] ) : $this->options['check_email_unsubscribe'];
			$this->options['done_unsubscribe']					= isset( $_POST['sbscrbr_done_unsubscribe'] ) ? esc_html( $_POST['sbscrbr_done_unsubscribe'] ) : $this->options['done_unsubscribe'];
			$this->options['not_exists_unsubscribe']			= isset( $_POST['sbscrbr_not_exists_unsubscribe'] ) ? esc_html( $_POST['sbscrbr_not_exists_unsubscribe'] ) : $this->options['not_exists_unsubscribe'];

			/* To email settings */
			$this->options['to_email']	= isset( $_POST['sbscrbr_to_email'] ) ? esc_html( $_POST['sbscrbr_to_email'] ) : $this->options['to_email'];

			if ( isset( $_POST['sbscrbr_email_user'] ) && get_user_by( 'login', $_POST['sbscrbr_email_user'] ) ) {
				$this->options['email_user'] = $_POST['sbscrbr_email_user'];
			} else {
				$this->options['email_user'] = ( ! empty( $this->options['to_email'] ) ) ? $this->options['to_email'] : $this->default_options['email_user'];
				if ( empty( $this->options['email_user'] ) && 'user' == $this->options['email_user'] ) {
					$this->options['to_email'] = $this->default_options['to_email'];
				}
			}

			if ( isset( $_POST['sbscrbr_email_custom'] ) ) {
				$sbscrbr_email_list = array();
				$sbscrbr_email_custom = explode( ',', esc_html( $_POST['sbscrbr_email_custom'] ) );
				foreach ( $sbscrbr_email_custom as $sbscrbr_email_value ) {
					$sbscrbr_email_value = trim( $sbscrbr_email_value, ', ' );
					if ( ! empty( $sbscrbr_email_value ) && is_email( $sbscrbr_email_value ) ) {
						$sbscrbr_email_list[] = $sbscrbr_email_value;
					}
				}

				if ( $sbscrbr_email_list ) {
					$this->options['email_custom'] = $sbscrbr_email_list;
				} else {
					$this->options['email_custom'] = ( $this->options['email_custom'] ) ? $this->options['email_custom'] : $this->default_options['email_custom'];
				}
			}

			/* "From" settings */
			if ( isset( $_POST['sbscrbr_from_email'] ) && is_email( trim( $_POST['sbscrbr_from_email'] ) ) ) {
				if ( $this->options['from_email'] != trim( $_POST['sbscrbr_from_email'] ) )
					$notice = __( "'FROM' field option was changed. This may cause email messages being moved to the spam folder or email delivery failures.", 'subscriber' );
				$this->options['from_email']							= trim( $_POST['sbscrbr_from_email'] );
			} else {
				$this->options['from_email']							= $this->default_options['from_email'];
			}

			$this->options['from_custom_name']							= isset( $_POST['sbscrbr_from_custom_name'] ) ? esc_html( $_POST['sbscrbr_from_custom_name'] ) : $this->options['from_custom_name'];
			if ( '' == $this->options['from_custom_name'] )
				$this->options['from_custom_name']						= $this->default_options['from_custom_name'];

			$this->options['admin_message']								= isset( $_POST['sbscrbr_admin_message'] ) ? 1 : 0;
			$this->options['user_message']								= isset( $_POST['sbscrbr_user_message'] ) ? 1 : 0;
			
			/* subject settings */
			$this->options['admin_message_subject']						= isset( $_POST['sbscrbr_admin_message_subject'] ) ? esc_html( $_POST['sbscrbr_admin_message_subject'] ) : $this->options['admin_message_subject'];
			$this->options['subscribe_message_subject']					= isset( $_POST['sbscrbr_subscribe_message_subject'] ) ? esc_html( $_POST['sbscrbr_subscribe_message_subject'] ) : $this->options['subscribe_message_subject'];
			$this->options['unsubscribe_message_subject']				= isset( $_POST['sbscrbr_unsubscribe_message_subject'] ) ? esc_html( $_POST['sbscrbr_unsubscribe_message_subject'] ) : $this->options['unsubscribe_message_subject'];
			
			/* message body settings */
			$this->options['admin_message_text']						= isset( $_POST['sbscrbr_admin_message_text'] ) ? esc_html( $_POST['sbscrbr_admin_message_text'] ) : $this->options['admin_message_text'];
			$this->options['subscribe_message_text']					= isset( $_POST['sbscrbr_subscribe_message_text'] ) ? esc_html( $_POST['sbscrbr_subscribe_message_text'] ) : $this->options['subscribe_message_text'];
			$this->options['unsubscribe_message_text']					= isset( $_POST['sbscrbr_unsubscribe_message_text'] ) ? esc_html( $_POST['sbscrbr_unsubscribe_message_text'] ) : $this->options['unsubscribe_message_text'];

			$this->options['admin_message_use_sender']					= isset( $_POST['sbscrbr_admin_message_use_sender'] ) && 1 == $_POST['sbscrbr_admin_message_use_sender'] ? 1 : 0;
			$this->options['subscribe_message_use_sender']				= isset( $_POST['sbscrbr_subscribe_message_use_sender'] ) && 1 == $_POST['sbscrbr_subscribe_message_use_sender'] ? 1 : 0;
			$this->options['unsubscribe_message_use_sender']			= isset( $_POST['sbscrbr_unsubscribe_message_use_sender'] ) && 1 == $_POST['sbscrbr_unsubscribe_message_use_sender'] ? 1 : 0;

			$this->options['admin_message_sender_template_id']			= isset( $_POST['sbscrbr_admin_message_sender_template_id'] ) ? intval( $_POST['sbscrbr_admin_message_sender_template_id'] ) : '';
			$this->options['subscribe_message_sender_template_id']		= isset( $_POST['sbscrbr_subscribe_message_sender_template_id'] ) ? intval( $_POST['sbscrbr_subscribe_message_sender_template_id'] ) : '';
			$this->options['unsubscribe_message_sender_template_id']	= isset( $_POST['sbscrbr_unsubscribe_message_sender_template_id'] ) ? intval( $_POST['sbscrbr_unsubscribe_message_sender_template_id'] ) : '';

			$this->options["form_one_line"]	= isset( $_POST['sbscrbr_form_one_line'] ) ? 1 : 0;

			/* settings for {unsubscribe_link} */
			$this->options['shortcode_link_type']						= esc_attr( $_POST['sbscrbr_shortcode_link_type'] );
			$this->options['shortcode_url']								= strtok( esc_url( trim( $_POST['sbscrbr_shortcode_url'] ) ), '?' );

			/* Check: if custom domain in option different from current, display error. Settings are not saved */
			if ( false === strpos( $_POST['sbscrbr_shortcode_url'], home_url() ) ) {
				$error = __( 'Error: The domain name in shortcode settings must be the same as the current one. Settings are not saved.', 'subscriber' );
			}

			if ( 'url' == $this->options['shortcode_link_type'] && empty( $this->options['shortcode_url'] ) )
				$this->options['shortcode_link_type']		= 'text';

			/* another settings */
			$this->options['unsubscribe_link_text']			= isset( $_POST['sbscrbr_unsubscribe_link_text'] ) ? esc_html( $_POST['sbscrbr_unsubscribe_link_text'] ) : $this->options['unsubscribe_link_text'];
			$this->options['delete_users']					= ( isset( $_POST['sbscrbr_delete_users'] ) && '1' == $_POST['sbscrbr_delete_users'] ) ? 1 : 0;
			$this->options['contact_form']					= ( isset( $_POST['sbscrbr_contact_form'] ) && '1' == $_POST['sbscrbr_contact_form'] ) ? 1 : 0;

			$this->options									= array_map( 'stripslashes_deep', $this->options );

			/* reCAPTCHA PRO compability */
			if ( ! empty( $gglcptch_options ) ) {
				$gglcptch_enabled = $gglcptch_options['sbscrbr']	= ( isset( $_POST['sbscrbr_display_recaptcha'] ) ) ? 1 : 0;

				if ( is_multisite() ) {
					update_site_option( 'gglcptch_options', $gglcptch_options );
					/* Get all blog ids */
					$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
					$old_blog = $wpdb->blogid;
					foreach ( $blogids as $blog_id ) {
						switch_to_blog( $blog_id );
						if ( $gglcptch_single_options = get_option( 'gglcptch_options' ) ) {
							$gglcptch_single_options['sbscrbr'] = $gglcptch_options['sbscrbr'];
							update_option( 'gglcptch_options', $gglcptch_single_options );
						}
					}
					switch_to_blog( $old_blog );
				} else {
					update_option( 'gglcptch_options', $gglcptch_options );
				}
			}
			/* Captcha PRO compatibility */
			if ( ! empty( $captcha_pro_options ) ) {

				if ( isset( $captcha_pro_options['forms']['bws_subscriber']['enable'] ) )
					$captcha_pro_options['forms']['bws_subscriber']['enable'] = ( isset( $_POST['sbscrbr_display_captcha'] ) ) ? true : false;

				$captcha_pro_enabled = ( isset( $_POST['sbscrbr_display_captcha'] ) ) ? true : false;

				if ( is_multisite() ) {
					update_site_option( 'cptch_options', $captcha_pro_options );

					if ( isset( $captcha_pro_options['network_apply'] ) && 'all' == $captcha_pro_options['network_apply'] ) {
						/* Get all blog ids */
						$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
						$old_blog = $wpdb->blogid;
						foreach ( $blogids as $blog_id ) {
							switch_to_blog( $blog_id );
							if ( $captcha_pro_single_options = get_option( 'cptch_options' ) ) {
								$captcha_pro_single_options['forms']['bws_subscriber']['enable'] = $captcha_pro_options['forms']['bws_subscriber']['enable'];
								update_option( 'cptch_options', $captcha_pro_single_options );
							}
						}
						switch_to_blog( $old_blog );
					}
				} else {
					update_option( 'cptch_options', $captcha_pro_options );
				}
			}
			
			/* Update options of plugin in the database */
			if ( empty( $error ) ) {
				if ( is_multisite() ) {
					update_site_option( 'sbscrbr_options', $this->options );
				} else {
					update_option( 'sbscrbr_options', $this->options );
				}
				$message = __( 'Settings Saved', 'subscriber' );
			}
			return compact( 'message', 'notice', 'error' );
		}
		public function tab_settings() {
			global $wp_version; 
			
			$gglcptch_enabled		= get_option( 'gglcptch_options' );
			$gglcptch_enabled		= $gglcptch_enabled['sbscrbr'];
			$captcha_pro_enabled	= get_option( 'cptch_options' );
			$captcha_pro_enabled	= $captcha_pro_enabled['forms']['bws_subscriber']['enable']; ?>
			
			<h3 class="bws_tab_label"><?php _e( 'Subscriber Settings', 'subscriber' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table id="sbscrbr-settings-table" class="form-table">
				<tr valign="top">
					<th><?php _e( 'Subscribe form labels', 'subscriber' ); ?></th>
					<td colspan="2">
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-form-label" name="sbscrbr_form_label" maxlength="250" max-width="1px" value="<?php echo esc_attr( $this->options['form_label'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Text above the subscribe form', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-form-placeholder" name="sbscrbr_form_placeholder" maxlength="250" value="<?php echo esc_attr( $this->options['form_placeholder'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Placeholder for "E-mail" field', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-form-checkbox-label" name="sbscrbr_form_checkbox_label" maxlength="250" value="<?php echo esc_attr( $this->options['form_checkbox_label'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Label for "Unsubscribe" checkbox', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-form-button-label" name="sbscrbr_form_button_label" maxlength="250" value="<?php echo esc_attr( $this->options['form_button_label'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Label for "Subscribe" button', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-form-unsubscribe-button-label" name="sbscrbr_unsubscribe_form_button_label" maxlength="250" value="<?php echo esc_attr( $this->options['unsubscribe_button_name'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Label for "Unsubscribe" button', 'subscriber' ); ?></span>
					</td>
				</tr>
				<tr valign="top">
					<th><?php _e( 'Add to the subscribe form', 'subscriber' ); ?></th>
					<td colspan="2">
						<?php if ( array_key_exists( 'captcha-pro/captcha_pro.php', $this->all_plugins ) ) {
							if ( is_plugin_active( 'captcha-pro/captcha_pro.php' ) ) { ?>
								<label><input type="checkbox" name="sbscrbr_display_captcha" value="1" <?php checked( $captcha_pro_enabled ); ?> /> Captcha PRO by BestWebSoft</label></br>
							<?php } else { ?>
								<label>
									<input disabled="disabled" type="checkbox" <?php checked( $captcha_pro_enabled ); ?> /> Captcha PRO by BestWebSoft
								</label>
								<span class="bws_info"><a href="<?php echo self_admin_url( 'plugins.php' ); ?>"><?php _e( 'Activate', 'subscriber' ); ?> Captcha PRO</a></span></br>
							<?php }
						} else { ?>
							<label>
								<input disabled="disabled" type="checkbox" /> Captcha PRO by BestWebSoft
							</label>
							<span class="bws_info"><a href="https://bestwebsoft.com/products/wordpress/plugins/captcha/?k=d045de4664b2e847f2612a815d838e60&pn=122&v=<?php echo $this->plugins_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank"><?php _e( 'Download', 'subscriber' ); ?> Captcha PRO</a></span></br>
						<?php } ?>
						<?php if ( array_key_exists( 'google-captcha-pro/google-captcha-pro.php', $this->all_plugins ) ) {
							if ( is_plugin_active( 'google-captcha-pro/google-captcha-pro.php' ) ) { ?>
								<label><input type="checkbox" name="sbscrbr_display_recaptcha" value="1" <?php checked( $gglcptch_enabled ); ?> /> reCAPTCHA PRO by BestWebSoft</label>
							<?php } else { ?>
								<label><input disabled="disabled" type="checkbox" <?php checked( $gglcptch_enabled ); ?> /> reCAPTCHA PRO by BestWebSoft <span class="bws_info"><a href="<?php echo self_admin_url( 'plugins.php' ); ?>"><?php _e( 'Activate', 'subscriber' ); ?> reCAPTCHA PRO</a></span></label>
							<?php }
						} else { ?>
							<label><input disabled="disabled" type="checkbox" /> reCAPTCHA PRO by BestWebSoft <span class="bws_info"><a href="https://bestwebsoft.com/products/wordpress/plugins/google-captcha/?k=30ca0db50bce6fe0feed624a1ce979b2&pn=122&v=<?php echo $this->plugins_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank"><?php _e( 'Download', 'subscriber' ); ?> reCAPTCHA PRO</a></span></label>
						<?php } ?>
						<div>
							<label>
								<input type="checkbox" id="sbscrbr_gdpr" name="sbscrbr_gdpr" value="1" <?php checked( '1', $this->options['gdpr'] ); ?> />
								<?php _e( "GDPR Compliance", 'subscriber' ); ?>
							</label>
						</div>
						<div id="sbscrbr_gdpr_link_options" >
							<label class="gdpr_privacy_policy_text" >
								<?php _e( 'Checkbox label', 'subscriber' ); ?>
								<input type="text" id="sbscrbr_gdpr_cb_name" size="29" name="sbscrbr_gdpr_cb_name" value="<?php echo $this->options['gdpr_cb_name']; ?>"/>
							</label>
							<label class="sbscrbr_privacy_policy_text" >
								<?php _e( "Link to Privacy Policy Page", 'subscriber' ); ?>
								<input type="url" id="sbscrbr_gdpr_link" placeholder="http://" name="sbscrbr_gdpr_link" value="<?php echo $this->options['gdpr_link']; ?>" />
							</label>
							<label class="sbscrbr_privacy_policy_text" >
								<?php _e( "Text for Privacy Policy Link", 'subscriber' ); ?>
								<input type="text" id="sbscrbr_gdpr_text" name="sbscrbr_gdpr_text" value="<?php echo $this->options['gdpr_text']; ?>"/>
							</label>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th><?php _e( 'Add "Subscribe" checkbox to', 'subscriber' ); ?></th>
					<td colspan="2">
						<?php $sbscrbr_cntcfrm_name = $sbscrbr_cntcfrm_notice = $sbscrbr_cntcfrm_attr = '';
						$sbscrbr_cntcfrm_installed = $sbscrbr_cntcfrm_activated = false;

						if ( array_key_exists( 'contact-form-plugin/contact_form.php', $this->all_plugins ) ) {
							$sbscrbr_cntcfrm_name = 'Contact Form';
							$sbscrbr_cntcfrm_installed = true;
							if ( $this->all_plugins['contact-form-plugin/contact_form.php']['Version'] <= '3.97' ) {
								$sbscrbr_cntcfrm_notice = sprintf( '<a href="%s">%s 3.98</a>', self_admin_url( 'plugins.php' ), sprintf( __( 'Update %s at least to version', 'subscriber' ), $sbscrbr_cntcfrm_name ) );
								$sbscrbr_cntcfrm_attr = 'disabled="disabled"';
							} else {
								if ( ! is_plugin_active( 'contact-form-plugin/contact_form.php' ) ) {
									$sbscrbr_cntcfrm_for = ( is_multisite() ) ? __( 'Activate for network', 'subscriber' ) : __( 'Activate', 'subscriber' );
									$sbscrbr_cntcfrm_notice = sprintf( '<a href="%s">%s %s</a>', self_admin_url( 'plugins.php' ), $sbscrbr_cntcfrm_for, $sbscrbr_cntcfrm_name );
									$sbscrbr_cntcfrm_attr = 'disabled="disabled"';
								} else {
									$sbscrbr_cntcfrm_activated = true;
								}
							}
						}

						if ( false == $sbscrbr_cntcfrm_activated && array_key_exists( 'contact-form-pro/contact_form_pro.php', $this->all_plugins ) ) {
							$sbscrbr_cntcfrm_name = 'Contact Form Pro';
							$sbscrbr_cntcfrm_installed = true;
							if ( $this->all_plugins['contact-form-pro/contact_form_pro.php']['Version'] <= '2.1.0' ) {
								$sbscrbr_cntcfrm_notice = sprintf( '<a href="%s">%s 2.1.1</a>', self_admin_url( 'plugins.php' ), sprintf( __( 'Update %s at least to version', 'subscriber' ), $sbscrbr_cntcfrm_name ) );
								$sbscrbr_cntcfrm_attr = 'disabled="disabled"';
							} else {
								if ( ! is_plugin_active( 'contact-form-pro/contact_form_pro.php' ) ) {
									$sbscrbr_cntcfrm_for = ( is_multisite() ) ? __( 'Activate for network', 'subscriber' ) : __( 'Activate', 'subscriber' );
									$sbscrbr_cntcfrm_notice = sprintf( '<a href="%s">%s %s</a>', self_admin_url( 'plugins.php' ), $sbscrbr_cntcfrm_for, $sbscrbr_cntcfrm_name );
									$sbscrbr_cntcfrm_attr = 'disabled="disabled"';
								} else {
									$sbscrbr_cntcfrm_activated = true;
								}
							}
						}

						if ( true == $sbscrbr_cntcfrm_activated ) {
							$sbscrbr_cntcfrm_notice = $sbscrbr_cntcfrm_attr = '';
						}

						if ( false == $sbscrbr_cntcfrm_installed ) {
							$sbscrbr_cntcfrm_name = 'Contact Form';
							$sbscrbr_cntcfrm_notice = '<a href="https://bestwebsoft.com/products/wordpress/plugins/contact-form/?k=507a200ccc60acfd5731b09ba88fb355&pn=122&v=' . $this->plugins_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">' . __( 'Download', 'subscriber' ) . ' ' . $sbscrbr_cntcfrm_name . '</a>';
							$sbscrbr_cntcfrm_attr = 'disabled="disabled"';
						} ?>
						<label>
							<input <?php echo $sbscrbr_cntcfrm_attr; ?> type="checkbox" name="sbscrbr_contact_form" value="1" <?php checked( empty( $sbscrbr_cntcfrm_notice ) && ! empty( $this->options["contact_form"] ) ); ?> /> Contact Form by BestWebSoft</label>
							<span class="bws_info">
								<?php echo $sbscrbr_cntcfrm_notice;
								if ( true == $sbscrbr_cntcfrm_activated && ( is_plugin_active( 'contact-form-multi-pro/contact-form-multi-pro.php' ) || is_plugin_active( 'contact-form-multi/contact-form-multi.php' ) ) )
									echo ' (' . __( 'Check off for adding captcha to forms on their settings pages.', 'subscriber' ) . ')'; ?>
							</span>
						<br />
						<span class="bws_info"><?php _e( 'If you would like to add "Subscribe" checkbox to a custom form, please see', 'subscriber' ); ?>&nbsp;<a href="https://support.bestwebsoft.com/hc/en-us/sections/200538739" target="_blank">FAQ</a></span>
					</td>
				</tr>
				<tr valign="top">
					<th><?php _e( 'One line form', 'subscriber-pro' ); ?></th>
					<td colspan="2">
						<input type="checkbox" name="sbscrbr_form_one_line" value="1" <?php checked( $this->options['form_one_line'] ); ?> />
						<span class="bws_info"><?php _e( 'Enable to display the subscription form on one line. (Works only without the "Name" field).', 'subscriber' ); ?></span>
					</td>
				</tr>
				<tr valign="top">
					<th>
						<?php _e( 'Service messages', 'subscriber' ); ?>
						<span class="bws_help_box dashicons dashicons-editor-help">
							<span class="bws_hidden_help_text" style="width: 240px;">
								<?php _e( 'These messages will be displayed in the frontend of your site.', 'subscriber' ); ?>
							</span>
						</span>
					</th>
					<td colspan="2">
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-bad-request" name="sbscrbr_bad_request" maxlength="250" value="<?php echo $this->options['bad_request'] ; ?>"/>
						<span class="bws_info"><?php _e( 'Unknown error', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-empty-email" name="sbscrbr_empty_email" maxlength="250" value="<?php echo esc_attr( $this->options['empty_email'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If user has not entered e-mail', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-invalid-email" name="sbscrbr_invalid_email" maxlength="250" value="<?php echo esc_attr( $this->options['invalid_email'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If user has entered invalid e-mail', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-not-exists-email" name="sbscrbr_not_exists_email" maxlength="250" value="<?php echo esc_attr( $this->options['not_exists_email'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If the user has entered a non-existent e-mail', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-not-exists-email" name="sbscrbr_cannot_get_email" maxlength="250" value="<?php echo esc_attr( $this->options['cannot_get_email'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If it is impossible to get the data about the entered e-mail', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-cannot-send-email" name="sbscrbr_cannot_send_email" maxlength="250" value="<?php echo esc_attr( $this->options['cannot_send_email'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If it is impossible to send a letter', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-error-subscribe" name="sbscrbr_error_subscribe" maxlength="250" value="<?php echo esc_attr( $this->options['error_subscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If some errors occurred while user registration', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-done-subscribe" name="sbscrbr_done_subscribe" maxlength="250" value="<?php echo esc_attr( $this->options['done_subscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If user registration was succesfully finished', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-already-subscribe" name="sbscrbr_already_subscribe" maxlength="250" value="<?php echo esc_attr( $this->options['already_subscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If the user has already subscribed', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-denied-subscribe" name="sbscrbr_denied_subscribe" maxlength="250" value="<?php echo esc_attr( $this->options['denied_subscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If subscription has been denied', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-already-unsubscribe" name="sbscrbr_already_unsubscribe" maxlength="250" value="<?php echo esc_attr( $this->options['already_unsubscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If the user has been already unsubscribed', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-check-email-unsubscribe" name="sbscrbr_check_email_unsubscribe" maxlength="250" value="<?php echo esc_attr( $this->options['check_email_unsubscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If the user has been sent a letter with a link to unsubscribe', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-done-unsubscribe" name="sbscrbr_done_unsubscribe" maxlength="250" value="<?php echo esc_attr( $this->options['done_unsubscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If user was unsubscribed', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-not-exists-unsubscribe" name="sbscrbr_not_exists_unsubscribe" maxlength="250" value="<?php echo esc_attr( $this->options['not_exists_unsubscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'If the user clicked on a non-existent "unsubscribe"-link', 'subscriber' ); ?></span>
					</td><!-- .sbscrbr-service-messages -->
				</tr>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'subscriber' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr valign="top">
								<th><?php _e( 'Add to the subscribe form', 'subscriber' ); ?></th>
								<td>
									<label><input type="checkbox" name="sbscrbr_form_name_field" disabled value="1" /> <?php _e( '"Name" field', 'subscriber' ); ?> </label><br/>
									<label><input type="checkbox" name="sbscrbr_form_unsubscribe_checkbox" checked disabled value="1" /> <?php _e( '"Unsubscribe" checkbox', 'subscriber' ); ?> </label><br/>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Add checkbox "Subscribe" to', 'subscriber' ); ?></th>
								<td colspan="2">
									<label><input disabled="disabled" type="checkbox" /> <?php _e( 'Registration form', 'subscriber' );?></label>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Subscription confirmation', 'subscriber' ); ?></th>
								<td colspan="2">
									<input disabled="disabled" type="checkbox" />
									<span class="bws_info"><?php _e( 'Enable if you want to send the subscription confirmation via email before user registration.', 'subscriber' ); ?></span>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Delete a user data if the subscription has not been confirmed in', 'subscriber' ); ?></th>
								<td colspan="2">
									<input disabled="disabled" type="number" /> <?php _e( 'hours', 'subscriber' ); ?>
									<?php _e( 'every', 'subscriber' ); ?> <input disabled="disabled" type="number" /> <?php _e( 'hours', 'subscriber' ); ?>.<br/>
									<span class="bws_info"><?php _e( 'Please set 0 if you do not want to delete a user data.', 'subscriber' ); ?></span>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Delete user after unsubscribing', 'subscriber' ); ?></th>
								<td colspan="2">
									<input type="checkbox" disabled="disabled" name="sbscrbr_delete_unsubscribed" />
									<span class="bws_info"><?php _e( 'Enable to delete user account after unsubscribing.', 'subscriber' ); ?></span>
								</td>
							</tr>
						</table>
					</div>
					<div class="bws_pro_version_tooltip">
						<a class="bws_button" href="https://bestwebsoft.com/products/wordpress/plugins/subscriber/?k=d356381b0c3554404e34cdc4fe936455&pn=122&v=<?php echo $this->plugins_info["Version"] . '&wp_v=' . $wp_version; ?>" target="_blank" title="Subscriber Pro"><?php _e( "Learn More", 'subscriber' ); ?></a>
						<div class="clear"></div>
					</div>
				</div>
				<table class="form-table">
					<tr valign="top">
						<th><?php _e( 'Delete users with plugin removing', 'subscriber' ); ?></th>
						<td colspan="2">
							<input type="checkbox" id="sbscrbr-delete-user" name="sbscrbr_delete_users" value="1" <?php checked( $this->options["delete_users"] ); ?> />
							<span class="bws_info"><?php _e( 'If this option enabled, when you remove plugin, all users with role "Mail Subscribed" will be removed from users list.', 'subscriber' ); ?></span>
						</td>
					</tr>
				</table>
			<?php }
		}

		public function tab_email_notifications() {
			global $wp_version; ?>
			<h3 class="bws_tab_label"><?php _e( 'Email Notifications', 'subscriber' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table id="sbscrbr-settings-table" class="form-table">
				<tr>
					<th><?php _e( 'Email Admin', 'subscriber' ); ?></th>
					<td colspan="2">
						<input type="checkbox" name="sbscrbr_admin_message" value="1" <?php checked( $this->options['admin_message'] ); ?> />
						<span class="bws_info"><?php _e( 'Enable to send the notifications about new subscribed users to the administrator.', 'subscriber' ); ?></span>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Email User', 'subscriber' ); ?></th>
					<td colspan="2">
						<input class="bws_option_affect" data-affect-show="#sbscrbr-message-subscribed" type="checkbox" name="sbscrbr_user_message" value="1" <?php checked( $this->options["user_message"] ); ?> />
						<span class="bws_info"><?php _e( 'Enable to notify a user about the subscription he made.', 'subscriber' ); ?></span>
					</td>
				</tr>
				<tr valign="top" class="sbscrbr_for_admin_message">
					<th scope="row"><?php _e( "Recipient email address (To:)", 'subscriber' ); ?></th>
					<td colspan="2">
						<fieldset>
							<div>
								<input type="radio" id="sbscrbr_to_email_user" name="sbscrbr_to_email" value="user" <?php checked( $this->options['to_email'], 'user' ); ?>/>
								<select class="sbscrbr-admin-email-settings sbscrbr-input-text" name="sbscrbr_email_user">
									<option disabled><?php _e( "Select a username", 'subscriber' ); ?></option>
										<?php $sbscrbr_userslogin = get_users( 'blog_id=' . $GLOBALS['blog_id'] . '&role=administrator' );
										foreach ( $sbscrbr_userslogin as $key => $value ) {
											if ( $value->data->user_email != '' ) { ?>
												<option value="<?php echo $value->data->user_login; ?>" <?php selected( $this->options['email_user'], $value->data->user_login ); ?>><?php echo $value->data->user_login; ?></option>
											<?php }
										} ?>
								</select><br>
								<input type="radio" id="sbscrbr_to_email_custom" name="sbscrbr_to_email" value="custom" <?php checked( $this->options['to_email'], 'custom' ); ?>/>
								<input type="text" class="sbscrbr-admin-email-settings sbscrbr-input-text" name="sbscrbr_email_custom" value="<?php echo implode( $this->options['email_custom'], ', ' ); ?>" maxlength="500" />
								<br />
								<span class="bws_info sbscrbr_floating_info"><?php _e( 'If necessary you can specify several email addresses separated by comma (For example: email1@example.com, email2@example.com).', 'subscriber' );?></span>
							</div>						
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( "'FROM' field", 'subscriber' ); ?></th>
					<td style="vertical-align: top;">
						<div style="padding-left: 1px">
							<?php _e( "Name", 'subscriber' ); ?>
						</div>
						<div>
							<input type="text" class="sbscrbr-input-text sbscrbr-input-input" name="sbscrbr_from_custom_name" maxlength="250" value="<?php echo $this->options['from_custom_name']; ?>"/>
						</div>
						<div style="margin-top: 5px; padding-left: 1px">
							<?php _e( "Email", 'subscriber' ); ?></div>
						<div>
							<input type="text" class="sbscrbr-input-text sbscrbr-input-input" name="sbscrbr_from_email" maxlength="250" value="<?php echo $this->options['from_email']; ?>"/>
						</div>
						<span class="bws_info">(<?php _e( "If this option is changed, email messages may be moved to the spam folder or email delivery failures may occur.", 'subscriber' ); ?>)</span>
					</td>
				</tr>
				<tr valign="top">
					<th>
						<?php _e( 'Letters content', 'subscriber' ); ?>
						<span class="bws_help_box dashicons dashicons-editor-help">
							<span class="bws_hidden_help_text" style="width: 240px;">
								<span style="font-size: 14px;"><?php _e( 'You can edit the content of service letters, which will be sent to users. In the text of the message you can use the following shortcodes:', 'subscriber' ); ?></span>
								<ul>
									<li><strong>{user_email}</strong> - <?php _e( 'This shortcode will be replaced with the e-mail of a current user;', 'subscriber' ); ?></li>
									<li><strong>{profile_page}</strong> - <?php _e( 'This shortcode will be replaced with the link to profile page of current user;', 'subscriber' ); ?></li>
									<li><strong>{unsubscribe_link}</strong> - <?php _e( 'This shortcode will be replaced with the link to unsubscribe.', 'subscriber' ); ?></li>
								</ul>
								<p><?php _e( 'There must be a space after the shortcode otherwise the link will be incorrect.', 'subscriber' ); ?></p>
							</span>
						</span>
					</th>
					<td colspan="2">
						<?php /* check sender pro activation */
						$sender_pro_active = false;

						if ( array_key_exists( 'sender-pro/sender-pro.php', $this->all_plugins ) ) {
							if ( ! is_plugin_active( 'sender-pro/sender-pro.php' ) ) {
								$sender_for = ( is_multisite() ) ? __( 'Activate for network', 'subscriber' ) : __( 'Activate', 'subscriber' );
								$sender_pro_notice = sprintf( '<a href="%s">%s %s</a>', self_admin_url( 'plugins.php' ), $sender_for, 'Sender Pro by BestWebSoft' );
							} else {
								$sender_pro_active = true;
							}
						} else {
							$sender_pro_notice = sprintf( __( 'Install %s plugin in order to use HTML letters created with TinyMce visual editor', 'subscriber' ), ' <a href="https://bestwebsoft.com/products/wordpress/plugins/sender/?k=01665f668edd3310e8c5cf13e9cb5181&pn=122&v=' . $this->plugins_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">Sender Pro by BestWebSoft</a>' );
						} ?>
						<div class="sbscrbr-messages-settings message_to_admin" style="margin-bottom: 20px !important">
							<label><strong><?php _e( 'Message to admin about new subscribed users', 'subscriber' ); ?>:</strong></label><br/>
								<input type="radio" name="sbscrbr_admin_message_use_sender" value="1"<?php disabled( $sender_pro_active, false ); checked( ! empty( $this->options['admin_message_use_sender'] ) && $sender_pro_active ); ?> />
								<?php if ( $sender_pro_active ) {
									sbscrbr_sender_letters_list_select( 'sbscrbr_admin_message_sender_template_id', $this->options['admin_message_sender_template_id'] );
								} else { ?>
									<span class="bws_info"><?php echo $sender_pro_notice; ?></span>
								<?php } ?>
							<br />
								<input type="radio" name="sbscrbr_admin_message_use_sender" value="0"<?php disabled( $sender_pro_active, false ); checked( empty( $this->options['admin_message_use_sender'] ) || ! $sender_pro_active ); ?> />
								<span class="description" style="line-height: 30px"><?php _e( "Custom Text", 'subscriber' ); ?></span>
							<br />
								<input type="text" class="sbscrbr-input-text sbscrbr-message-to-admin" id="sbscrbr-admin-message-subject" name="sbscrbr_admin_message_subject" maxlength="250" value="<?php echo esc_attr( $this->options['admin_message_subject'] ); ?>"/>
								<span class="bws_info"><?php _e( "Subject", 'subscriber' ); ?></span>
							<br />
							<div class="sbscrbr-message-text">
								<textarea class="sbscrbr-input-text sbscrbr-message-text sbscrbr-message-to-admin" id="sbscrbr-admin-message-text" name="sbscrbr_admin_message_text"><?php echo $this->options['admin_message_text']; ?></textarea>
								<span class="bws_info sbscrbr_text"><?php _e( "Text", 'subscriber' ); ?></span>
							</div>
						</div>
						<div id="sbscrbr-message-subscribed" class="sbscrbr-messages-settings">
							<label><strong><?php _e( 'Message to subscribed users', 'subscriber' ); ?></strong>:</label><br>
								<input type="radio" name="sbscrbr_subscribe_message_use_sender" value="1"<?php disabled( $sender_pro_active, false ); checked( ! empty( $this->options['subscribe_message_use_sender'] ) && $sender_pro_active ); ?> />
								<?php if ( $sender_pro_active ) {
									sbscrbr_sender_letters_list_select( 'sbscrbr_subscribe_message_sender_template_id', $this->options['subscribe_message_sender_template_id'] );
								} else { ?>
									<span class="bws_info"><?php echo $sender_pro_notice; ?></span>
								<?php } ?>
							<br />
								<input type="radio" name="sbscrbr_subscribe_message_use_sender" value="0"<?php disabled( $sender_pro_active, false ); checked( empty( $this->options['subscribe_message_use_sender'] ) || ! $sender_pro_active ); ?> />
								<span class="description" style="line-height: 30px"><?php _e( "Custom Text", 'subscriber' ); ?></span>
							<br/>
								<input type="text" class="sbscrbr-input-text sbscrbr-message-to-admin" name="sbscrbr_subscribe_message_subject" maxlength="250" value="<?php echo esc_attr( $this->options['subscribe_message_subject'] ); ?>"/>
								<span class="bws_info"><?php _e( "Subject", 'subscriber' ); ?></span>
							<br/>
							<div class="sbscrbr-message-text">
								<textarea class="sbscrbr-input-text sbscrbr-message-text sbscrbr-message-to-admin" name="sbscrbr_subscribe_message_text"><?php echo $this->options['subscribe_message_text']; ?></textarea>
								<span class="bws_info sbscrbr_text"><?php _e( "Text", 'subscriber' ); ?></span>		
							</div>
						</div>
						<div class="sbscrbr-messages-settings">
							<label><strong><?php _e( 'Message with unsubscribe link', 'subscriber' ); ?></strong>:</label><br>
							<input type="radio" name="sbscrbr_unsubscribe_message_use_sender" value="1"<?php disabled( $sender_pro_active, false ); checked( ! empty( $this->options['unsubscribe_message_use_sender'] ) && $sender_pro_active ); ?> />
								<?php if ( $sender_pro_active ) {
									sbscrbr_sender_letters_list_select( 'sbscrbr_unsubscribe_message_sender_template_id', $this->options['unsubscribe_message_sender_template_id'] );
								} else { ?>
									<span class="bws_info"><?php echo $sender_pro_notice; ?></span>
								<?php } ?>
							<br />
								<input type="radio" name="sbscrbr_unsubscribe_message_use_sender" value="0"<?php disabled( $sender_pro_active, false ); checked( empty( $this->options['unsubscribe_message_use_sender'] ) || ! $sender_pro_active ); ?> />
								<span class="description" style="line-height: 30px"><?php _e( "Custom Text", 'subscriber' ); ?></span>
							<br />
								<input type="text" class="sbscrbr-input-text sbscrbr-message-to-admin" id="sbscrbr-unsubscribe-message-subject"  name="sbscrbr_unsubscribe_message_subject" maxlength="250" value="<?php echo esc_attr( $this->options['unsubscribe_message_subject'] ); ?>"/>
								<span class="bws_info"><?php _e( "Subject", 'subscriber' ); ?></span>
							<br />							
							<div class="sbscrbr-message-text">
								<textarea class="sbscrbr-input-text sbscrbr-message-text sbscrbr-message-to-admin" id="sbscrbr-unsubscribe-message-text" name="sbscrbr_unsubscribe_message_text"><?php echo $this->options['unsubscribe_message_text']; ?></textarea>
								<span class="bws_info sbscrbr_text"><?php _e( "Text", 'subscriber' ); ?></span>
							</div>
						</div>
						<div class="sbscrbr-messages-settings">
							<label><strong><?php _e( 'Text to be attached to letters', 'subscriber' ); ?></strong></label>
							<br/>
							<textarea class="sbscrbr-input-text sbscrbr-message-to-admin" id="sbscrbr-unsubscribe-link-text" name="sbscrbr_unsubscribe_link_text"><?php echo $this->options['unsubscribe_link_text']; ?></textarea>
							<br/>
							<span class="bws_info"><?php printf( __( 'This text will be attached to each letter of the mailing, which was created with plugin %s.', 'subscriber' ), '<a href="https://bestwebsoft.com/products/wordpress/plugins/sender/?k=01665f668edd3310e8c5cf13e9cb5181&pn=122&v=' . $this->plugins_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">Sender by BestWebSoft</a>' ); ?></span>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php printf( __( "Shortcode %s refers to", 'subscriber' ), "{unsubscribe_link}" ); ?></th>
					<td colspan="2">
						<div>
							<div>
								<input type="radio" name="sbscrbr_shortcode_link_type" value="url" <?php checked( $this->options["shortcode_link_type"], 'url' ); ?> />
								<input type="url" name="sbscrbr_shortcode_url" class="sbscrbr-input-text sbscrbr-input-shortcode" value="<?php echo $this->options['shortcode_url']; ?>" />
								<span class="bws_help_box dashicons dashicons-editor-help">
									<span class="bws_hidden_help_text" style="width: 240px;">
										<p><?php _e( 'Service message is displayed in a subscriber form if the last one was added on the page.', 'subscriber' ); ?></p>
									</span>
								</span>
							</div>
							<br />
								<input type="radio" name="sbscrbr_shortcode_link_type" value="text" <?php checked( $this->options["shortcode_link_type"], 'text' ); ?> />
								<?php _e( 'Separate page with appropriate service message', 'subscriber' ); ?>
						</div>
					</td>
				</tr>
			</table>
		<?php }
		
		public function tab_import_export() { ?>
			<h3 class="bws_tab_label"><?php _e( 'Import / Export', 'subscriber' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'subscriber' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
				<tr valign="top">
					<th scope="row"><?php _e( 'Export Data', 'subscriber' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="sbscrbr_format_export" value="csv" checked="checked" /><?php _e( 'CSV file format', 'subscriber' ); ?></label><br />
							<label><input type="radio" name="sbscrbr_format_export" value="xml" /><?php _e( 'XML file format', 'subscriber' ); ?></label><br />
						</fieldset>
						<input type="submit" name="sbscrbr_export_submit" class="button" value="<?php _e( 'Export', 'subscriber' ) ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Import Data', 'subscriber' ); ?></th>
					<td>
						<fieldset>
                            <label><input type="radio" name="sbscrbr_method_insert" value="missing_exists" /><?php _e( 'Add missing data', 'subscriber' ); ?></label><br />
                            <label><input type="radio" name="sbscrbr_method_insert" value="clear_data" /><?php _e( 'Clear old and add new subscribers', 'subscriber' ); ?> </label><br />
						</fieldset>
						<label><input name="sbscrbr_import_file_upload" type="file" /></label><br />
						<input type="submit" name="sbscrbr_import_submit" class="button" value="<?php _e( 'Import', 'subscriber' ) ?>" />
					</td>
				</tr>
		  	</table>
		  </div>
		<?php $this->bws_pro_block_links(); ?>
  	</div>
<?php }		








		/**
		 * Display custom metabox
		 * @access public
		 * @param  void
		 * @return array    The action results
		 */
		public function display_metabox() { ?>
			<div class="postbox">
				<h3 class="hndle">
					<?php _e( 'Subscriber Shortcode', 'subscriber' ); ?>
				</h3>
				<div class="inside">
					<?php if( ! $this->is_network_options ) { ?>
						<p><?php _e( 'Add Subscriber button to a widget.', 'subscriber' ); ?> <a href="widgets.php"><?php _e( 'Navigate to Widgets', 'subscriber' ); ?></a></p>
					<?php } ?>
				</div>
				<div class="inside">
					<?php _e( "Add the Subscribe Form to your pages or posts using the following shortcode:", 'subscriber' ); ?>
					<?php bws_shortcode_output( "[sbscrbr_form]" ); ?>
				</div>
			</div>
		<?php }
	}
}