<?php
/**
 * Displays the content on the plugin settings page
 */

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
				'messages'              => array( 'label' => __( 'Messages', 'subscriber' ) ),
				'notifications'	        => array( 'label' => __( 'Notifications', 'subscriber' ) ),
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
			global $wpdb;
			$message = $notice = $error = '';
			/* Captcha PRO compatibility */
			$captcha_pro_options = is_multisite() ? get_site_option( 'cptch_options' ) : get_option( 'cptch_options' );
			$captcha_pro_enabled = ( isset( $captcha_pro_options['forms']['bws_subscriber']['enable'] ) && true == $captcha_pro_options['forms']['bws_subscriber']['enable'] ) ? true : false;

			/* reCAPTCHA PRO compatibility */
			$gglcptch_options = is_multisite() ? get_site_option( 'gglcptch_options' ) : get_option( 'gglcptch_options' );
			$gglcptch_enabled = ( ! empty( $gglcptch_options['sbscrbr'] ) ) ? true : false;

			/* form labels */
			$this->options['form_title'] 						= isset( $_POST['sbscrbr_form_title'] ) ? sanitize_text_field( $_POST['sbscrbr_form_title'] ) : '';
			$this->options['form_label'] 						= sanitize_text_field( $_POST['sbscrbr_form_label'] );
			$this->options['form_placeholder']					= ! empty( $_POST['sbscrbr_form_placeholder'] ) ? sanitize_text_field( $_POST['sbscrbr_form_placeholder'] ) : $this->options['form_placeholder'];
			$this->options['form_checkbox_label']				= ! empty( $_POST['sbscrbr_form_checkbox_label'] ) ? sanitize_text_field( $_POST['sbscrbr_form_checkbox_label'] ) : $this->options['form_checkbox_label'];
			$this->options['form_button_label']					= ! empty( $_POST['sbscrbr_form_button_label'] ) ? sanitize_text_field( $_POST['sbscrbr_form_button_label'] ) : $this->options['form_button_label'];
			$this->options['unsubscribe_button_name']		    = ! empty( $_POST['sbscrbr_unsubscribe_form_button_label'] ) ? sanitize_text_field( $_POST['sbscrbr_unsubscribe_form_button_label'] ) : $this->options['unsubscribe_button_name'];
			$this->options['gdpr_cb_name']						= ! empty( $_POST['sbscrbr_gdpr_cb_name'] ) ? sanitize_text_field( $_POST['sbscrbr_gdpr_cb_name'] ) : $this->options['gdpr_cb_name'];
			$this->options['gdpr_text']							= sanitize_text_field( $_POST['sbscrbr_gdpr_text'] );
			$this->options['gdpr_link']							= sanitize_text_field( $_POST['sbscrbr_gdpr_link'] );
			$this->options['gdpr']								= isset( $_POST['sbscrbr_gdpr'] ) ? 1 : 0;

			/* service messages  */
			$this->options['bad_request']						= sanitize_text_field( $_POST['sbscrbr_bad_request'] );
			$this->options['empty_email']						= sanitize_text_field( $_POST['sbscrbr_empty_email'] );
			$this->options['invalid_email']						= sanitize_text_field( $_POST['sbscrbr_invalid_email'] );
			$this->options['not_exists_email']					= sanitize_text_field( $_POST['sbscrbr_not_exists_email'] );
			$this->options['cannot_get_email']					= sanitize_text_field( $_POST['sbscrbr_cannot_get_email'] );
			$this->options['cannot_send_email']					= sanitize_text_field( $_POST['sbscrbr_cannot_send_email'] );
			$this->options['error_subscribe']					= sanitize_text_field( $_POST['sbscrbr_error_subscribe'] );
			$this->options['done_subscribe']					= sanitize_text_field( $_POST['sbscrbr_done_subscribe'] );
			$this->options['already_subscribe']					= sanitize_text_field( $_POST['sbscrbr_already_subscribe'] );
			$this->options['denied_subscribe']					= sanitize_text_field( $_POST['sbscrbr_denied_subscribe'] );
			$this->options['already_unsubscribe']				= sanitize_text_field( $_POST['sbscrbr_already_unsubscribe'] );
			$this->options['check_email_unsubscribe']			= sanitize_text_field( $_POST['sbscrbr_check_email_unsubscribe'] );
			$this->options['done_unsubscribe']					= sanitize_text_field( $_POST['sbscrbr_done_unsubscribe'] );
			$this->options['not_exists_unsubscribe']			= sanitize_text_field( $_POST['sbscrbr_not_exists_unsubscribe'] );

			/* To email settings */
			$this->options['notification']      = isset( $_POST['sbscrbr_notification'] ) ? 1 : 0;
			$this->options['admin_message']		= isset( $_POST['sbscrbr_admin_message'] ) ? 1 : 0;
			$this->options['user_message']		= isset( $_POST['sbscrbr_user_message'] ) ? 1 : 0;
			$this->options['to_email']          = isset( $_POST['sbscrbr_to_email'] ) && 'custom' == $_POST['sbscrbr_to_email'] ? $_POST['sbscrbr_to_email'] : "";
			$this->options['email_user']        = get_user_by( 'login', $_POST['sbscrbr_email_user'] ) ? sanitize_text_field( $_POST['sbscrbr_email_user'] ) : "";

			if ( isset( $_POST['sbscrbr_email_custom'] ) ) {
				$sbscrbr_email_list = array();
				$sbscrbr_email_custom = explode( ',', $_POST['sbscrbr_email_custom'] );
				foreach ( $sbscrbr_email_custom as $sbscrbr_email_value ) {
					$sbscrbr_email_value = trim( $sbscrbr_email_value );
					if ( ! empty( $sbscrbr_email_value ) && is_email( $sbscrbr_email_value ) ) {
						$sbscrbr_email_list[] = $sbscrbr_email_value;
					}
				}
				$this->options['email_custom'] = ! empty( $sbscrbr_email_list ) ? $sbscrbr_email_list : $this->options['email_custom'];
			}

			/* "From" settings */
			if ( is_email( trim( $_POST['sbscrbr_from_email'] ) ) ) {
				if ( $this->options['from_email'] != trim( $_POST['sbscrbr_from_email'] ) )
					$notice = __( "'FROM' field option was changed. This may cause email messages being moved to the spam folder or email delivery failures.", 'subscriber' );
				$this->options['from_email']							= trim( $_POST['sbscrbr_from_email'] );
			} else {
				$this->options['from_email']							= $this->default_options['from_email'];
			}

			$this->options['from_custom_name']							= ! empty( $_POST['sbscrbr_from_custom_name'] ) ? sanitize_text_field( $_POST['sbscrbr_from_custom_name'] ) : $this->options['from_custom_name'];

			/* subject settings */
			$this->options['admin_message_subject']						= sanitize_text_field( $_POST['sbscrbr_admin_message_subject'] );
			$this->options['subscribe_message_subject']					= sanitize_text_field( $_POST['sbscrbr_subscribe_message_subject'] );
			$this->options['unsubscribe_message_subject']				= sanitize_text_field( $_POST['sbscrbr_unsubscribe_message_subject'] );

			/* message body settings */
			$this->options['admin_message_text']						= esc_attr( $_POST['sbscrbr_admin_message_text'] );
			$this->options['subscribe_message_text']					= esc_attr( $_POST['sbscrbr_subscribe_message_text'] );
			$this->options['unsubscribe_message_text']					= esc_attr( $_POST['sbscrbr_unsubscribe_message_text'] );

			$this->options['admin_message_use_sender']					= isset( $_POST['sbscrbr_admin_message_use_sender'] ) && 1 == $_POST['sbscrbr_admin_message_use_sender'] ? 1 : 0;
			$this->options['subscribe_message_use_sender']				= isset( $_POST['sbscrbr_subscribe_message_use_sender'] ) && 1 == $_POST['sbscrbr_subscribe_message_use_sender'] ? 1 : 0;
			$this->options['unsubscribe_message_use_sender']			= isset( $_POST['sbscrbr_unsubscribe_message_use_sender'] ) && 1 == $_POST['sbscrbr_unsubscribe_message_use_sender'] ? 1 : 0;

			$this->options['admin_message_sender_template_id']			= isset( $_POST['sbscrbr_admin_message_sender_template_id'] ) ? intval( $_POST['sbscrbr_admin_message_sender_template_id'] ) : '';
			$this->options['subscribe_message_sender_template_id']		= isset( $_POST['sbscrbr_subscribe_message_sender_template_id'] ) ? intval( $_POST['sbscrbr_subscribe_message_sender_template_id'] ) : '';
			$this->options['unsubscribe_message_sender_template_id']	= isset( $_POST['sbscrbr_unsubscribe_message_sender_template_id'] ) ? intval( $_POST['sbscrbr_unsubscribe_message_sender_template_id'] ) : '';

			$this->options["form_one_line"]	= isset( $_POST['sbscrbr_form_one_line'] ) ? 1 : 0;

			/* settings for {unsubscribe_link} */
			$this->options['shortcode_link_type'] = in_array( $_POST['sbscrbr_shortcode_link_type'], array( 'url', 'text' ) ) ? $_POST['sbscrbr_shortcode_link_type'] : $this->options['shortcode_link_type'];
			$this->options['shortcode_url']	= strtok( esc_url( $_POST['sbscrbr_shortcode_url'] ), '?' );

			/* Check: if custom domain in option different from current, display error. Settings are not saved */
			if ( 'url' == $this->options['shortcode_link_type'] && empty( $this->options['shortcode_url'] ) ) {
				$this->options['shortcode_link_type'] = 'text';
			}elseif (false === strpos( $_POST['sbscrbr_shortcode_url'], home_url() )) {
				$error = __( 'Error: The domain name in shortcode settings must be the same as the current one. Settings are not saved.', 'subscriber' );
			}

			/* another settings */
			$this->options["additional_text"]			    = isset( $_POST['sbscrbr_additional_text'] ) ? 1 : 0;
			$this->options['unsubscribe_link_text']			= esc_attr( $_POST['sbscrbr_unsubscribe_link_text'] );
			$this->options['delete_users']					= isset( $_POST['sbscrbr_delete_users'] ) ? 1 : 0;
			$this->options['contact_form']					= isset( $_POST['sbscrbr_contact_form'] ) ? 1 : 0;
			$this->options["form_title_field"]			    = isset( $_POST['sbscrbr_form_title_field'] ) ? 1 : 0;
			$this->options["form_label_field"]			    = isset( $_POST['sbscrbr_form_label_field'] ) ? 1 : 0;

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
			global $wp_version, $sbscrbr_plugin_info;
			$gglcptch_options		= get_option( 'gglcptch_options' );
			$gglcptch_enabled		= $gglcptch_options ? $gglcptch_options['sbscrbr'] : 0;
			$cptch_options          = get_option( 'cptch_options' );
			$captcha_pro_enabled    = $cptch_options ? $cptch_options['forms']['bws_subscriber']['enable'] : 0;?>

			<h3 class="bws_tab_label"><?php _e( 'Subscriber Settings', 'subscriber' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table id="sbscrbr-settings-table" class="form-table">
                <tr valign="top">
                    <th><?php _e( 'Enable Subscription Checkbox for', 'subscriber' ); ?></th>
                    <td>
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
							$sbscrbr_cntcfrm_notice = '<a href="https://bestwebsoft.com/products/wordpress/plugins/contact-form/?k=507a200ccc60acfd5731b09ba88fb355&pn=122&v=' . $this->plugins_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">' . __( 'Install Now', 'subscriber' ) . '</a>';
							$sbscrbr_cntcfrm_attr = 'disabled="disabled"';
						} ?>
                        <label>
                            <input <?php echo $sbscrbr_cntcfrm_attr; ?> type="checkbox" name="sbscrbr_contact_form" value="1" <?php checked( empty( $sbscrbr_cntcfrm_notice ) && ! empty( $this->options["contact_form"] ) ); ?> /> Contact Form by BestWebSoft.</label>
                        <span class="bws_info">
								<?php echo $sbscrbr_cntcfrm_notice;
								if ( true == $sbscrbr_cntcfrm_activated && ( is_plugin_active( 'contact-form-multi-pro/contact-form-multi-pro.php' ) || is_plugin_active( 'contact-form-multi/contact-form-multi.php' ) ) )
									echo ' (' . __( 'Check off for adding captcha to forms on their settings pages.', 'subscriber' ) . ')'; ?>
							</span>
                        <br />
                        <span class="bws_info"><?php _e( 'Want to add a subscription checkbox to a custom form?', 'subscriber' ); ?>&nbsp;<a href="https://support.bestwebsoft.com/hc/en-us/sections/200538739" target="_blank">Learn More</a></span>
                    </td>
                </tr>
				<tr valign="top">
					<th><?php _e( 'Form Fields', 'subscriber' ); ?></th>
					<td>
                        <fieldset>
                            <label><input class="bws_option_affect" data-affect-show="#sbscrbr_title_options" type="checkbox" name="sbscrbr_form_title_field" value="1" <?php checked( $this->options["form_title_field"] ); ?> /> <?php _e( 'Title', 'subscriber' ); ?></label><br />
                            <div id="sbscrbr_title_options" class="sbscrbr_form_bottom">
                                <input type="text" class="sbscrbr-input-text" name="sbscrbr_form_title" maxlength="250" value="<?php echo esc_attr( $this->options['form_title'] ); ?>"/>
                                <p class="bws_info"><?php _e( 'Title for subscribe form.', 'subscriber' ); ?></p>
                            </div>
                            <label><input class="bws_option_affect" data-affect-show="#sbscrbr_label_options" type="checkbox" name="sbscrbr_form_label_field" value="1" <?php checked( $this->options["form_label_field"] ); ?> /> <?php _e( 'Description', 'subscriber' ); ?></label><br />
                            <div id="sbscrbr_label_options" class="sbscrbr_form_bottom">
                                <input type="text" class="sbscrbr-input-text" name="sbscrbr_form_label" maxlength="250" value="<?php echo esc_attr( $this->options['form_label'] ); ?>"/>
                                <p class="bws_info"><?php _e( 'Text above the subscribe form.', 'subscriber' ); ?></p>
                            </div>
                            <label><?php _e( 'E-mail', 'subscriber' ); ?></label><br />
                            <div class="sbscrbr_form_bottom">
                                <input type="text" class="sbscrbr-input-text" name="sbscrbr_form_placeholder" maxlength="250" value="<?php echo esc_attr( $this->options['form_placeholder'] ); ?>"/><br />
                                <p class="bws_info"><?php _e( 'Placeholder for "E-mail" field.', 'subscriber' ); ?></p>
                            </div>
                            <label><?php _e( 'Button', 'subscriber' ); ?></label><br />
                            <div class="sbscrbr_form_bottom">
                                <input type="text" class="sbscrbr-input-text" name="sbscrbr_form_button_label" maxlength="250" value="<?php echo esc_attr( $this->options['form_button_label'] ); ?>"/><br />
                                <p class="bws_info"><?php _e( 'Label for "Subscribe" button.', 'subscriber' ); ?></p>
                            </div>
                            <div class="sbscrbr_form_bottom">
                                <input type="text" class="sbscrbr-input-text" name="sbscrbr_unsubscribe_form_button_label" maxlength="250" value="<?php echo esc_attr( $this->options['unsubscribe_button_name'] ); ?>"/><br />
                                <p class="bws_info"><?php _e( 'Label for "Unsubscribe" button.', 'subscriber' ); ?></p>
                            </div>
                            <?php if ( array_key_exists( 'captcha-pro/captcha_pro.php', $this->all_plugins ) ) {
                                if ( is_plugin_active( 'captcha-pro/captcha_pro.php' ) ) { ?>
                                    <label><input type="checkbox" name="sbscrbr_display_captcha" value="1" <?php checked( $captcha_pro_enabled ); ?> /> Captcha Pro by BestWebSoft</label><br />
                                <?php } else { ?>
                                    <label>
                                        <input disabled="disabled" type="checkbox" <?php checked( $captcha_pro_enabled ); ?> /> Captcha Pro by BestWebSoft
                                    </label>
                                    <span class="bws_info"><a href="<?php echo self_admin_url( 'plugins.php' ); ?>"><?php _e( 'Activate', 'subscriber' ); ?> Captcha Pro</a></span><br />
                                <?php }
                            } else { ?>
                                <label>
                                    <input disabled="disabled" type="checkbox" /> Captcha Pro by BestWebSoft
                                </label>
                                <span class="bws_info"><a href="https://bestwebsoft.com/products/wordpress/plugins/captcha/?k=d045de4664b2e847f2612a815d838e60&pn=122&v=<?php echo $this->plugins_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank"><?php _e( 'Download', 'subscriber' ); ?> Captcha Pro</a></span><br />
                            <?php } ?>
                            <?php if ( array_key_exists( 'google-captcha-pro/google-captcha-pro.php', $this->all_plugins ) ) {
                                if ( is_plugin_active( 'google-captcha-pro/google-captcha-pro.php' ) ) { ?>
                                    <label><input type="checkbox" name="sbscrbr_display_recaptcha" value="1" <?php checked( $gglcptch_enabled ); ?> /> reCaptcha Pro by BestWebSoft</label>
                                <?php } else { ?>
                                    <label><input disabled="disabled" type="checkbox" <?php checked( $gglcptch_enabled ); ?> /> reCaptcha Pro by BestWebSoft <span class="bws_info"><a href="<?php echo self_admin_url( 'plugins.php' ); ?>"><?php _e( 'Activate', 'subscriber' ); ?> reCaptcha Pro</a></span></label>
                                <?php }
                            } else { ?>
                                <label><input disabled="disabled" type="checkbox" /> reCaptcha Pro by BestWebSoft <span class="bws_info"><a href="https://bestwebsoft.com/products/wordpress/plugins/google-captcha/?k=30ca0db50bce6fe0feed624a1ce979b2&pn=122&v=<?php echo $this->plugins_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank"><?php _e( 'Download', 'subscriber' ); ?> reCaptcha Pro</a></span></label>
                            <?php } ?>
                            <br />
                            <label><?php _e( 'Unsubscribe checkbox', 'subscriber' ); ?></label><br />
                            <div class="sbscrbr_form_bottom">
                                <input type="text" class="sbscrbr-input-text" name="sbscrbr_form_checkbox_label" maxlength="250" value="<?php echo esc_attr( $this->options['form_checkbox_label'] ); ?>"/>
                                <p class="bws_info"><?php _e( 'Label for "Unsubscribe" checkbox.', 'subscriber' ); ?></p>
                            </div>
                            <label><input class="bws_option_affect" data-affect-show="#sbscrbr_gdpr_link_options" type="checkbox" name="sbscrbr_gdpr" value="1" <?php checked( '1', $this->options['gdpr'] ); ?> /> <?php _e( "GDPR checkbox", 'subscriber' ); ?></label><br />
                            <div id="sbscrbr_gdpr_link_options">
                                <input type="text" class="sbscrbr-input-text" id="sbscrbr_gdpr_cb_name" size="29" name="sbscrbr_gdpr_cb_name" value="<?php echo $this->options['gdpr_cb_name']; ?>"/><br />
                                <p class="bws_info"><?php _e( 'Label for "GDPR" checkbox.', 'subscriber' ); ?></p>
                                <input type="url" class="sbscrbr-input-text" id="sbscrbr_gdpr_link" placeholder="http://" name="sbscrbr_gdpr_link" value="<?php echo $this->options['gdpr_link']; ?>" /><br />
                                <p class="bws_info"><?php _e( "Link to Privacy Policy Page.", 'subscriber' ); ?></p>
                                <input type="text" class="sbscrbr-input-text" id="sbscrbr_gdpr_text" name="sbscrbr_gdpr_text" value="<?php echo $this->options['gdpr_text']; ?>"/><br />
                                <p class="bws_info"><?php _e( "Text for Privacy Policy Link.", 'subscriber' ); ?></p>
                            </div>
                        </fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th><?php _e( 'Display in One Line', 'subscriber' ); ?></th>
					<td>
						<input type="checkbox" name="sbscrbr_form_one_line" value="1" <?php checked( $this->options['form_one_line'] ); ?> />
						<span class="bws_info"><?php _e( 'Enable to display the form in one line.', 'subscriber' ); ?></span>
					</td>
				</tr>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'subscriber' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr valign="top">
								<th><?php _e( 'Form Fields', 'subscriber' ); ?></th>
								<td>
                                    <fieldset>
                                        <label><input type="checkbox" name="sbscrbr_form_name_field" disabled value="1" /> <?php _e( 'Name', 'subscriber' ); ?> </label><br/>
                                        <label><input type="checkbox" name="sbscrbr_form_unsubscribe_checkbox" checked disabled value="1" /> <?php _e( '"Unsubscribe" checkbox', 'subscriber' ); ?> </label><br/>
                                    </fieldset>
                                </td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Enable Subscription Checkbox for', 'subscriber' ); ?></th>
								<td>
									<label><input disabled="disabled" type="checkbox" /> <?php _e( 'Registration form', 'subscriber' );?></label>
								</td>
							</tr>
							<tr valign="top">
								<th><?php _e( 'Subscription Confirmation', 'subscriber' ); ?></th>
								<td>
									<input disabled="disabled" type="checkbox" />
									<span class="bws_info"><?php _e( 'Enable to send a confirmation link via email.', 'subscriber' ); ?></span>
								</td>
							</tr>
                            <tr valign="top">
                                <th><?php _e( 'Confirmation Timeout', 'subscriber' ); ?></th>
                                <td>
									<?php _e( 'After', 'subscriber' ); ?> <input class="sbscrbr-input-number" type="number" disabled="disabled" name="sbscrbr_hours_for_delete_user" min="1" max="10000" value="6" /> <?php _e( 'hours', 'subscriber' ); ?><br />
									<?php _e( 'Every', 'subscriber' ); ?> <input class="sbscrbr-input-number" type="number" disabled="disabled" name="sbscrbr_clear_data_hours" min="0" max="10000" value="24" /> <?php _e( 'hours', 'subscriber' ); ?><br/>
                                    <span class="bws_info"><?php _e( 'Enable to delete user data automatically if the subscription has not been confirmed. If you do not want to delete user data then set 0.', 'subscriber' ); ?></span>
                                </td>
                            </tr>
							<tr valign="top">
								<th><?php _e( 'Delete User Data Once', 'subscriber' ); ?></th>
								<td>
                                    <fieldset>
                                        <label><input type="checkbox" disabled="disabled" name="sbscrbr_delete_unsubscribed" /><?php _e( 'Unsubscribed', 'subscriber' ); ?></label><br />
									    <span class="bws_info"><?php _e( 'Enable to delete user account after unsubscribing.', 'subscriber' ); ?></span>
                                    </fieldset>
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
						<th><?php _e( 'Delete User Data Once', 'subscriber' ); ?></th>
						<td>
                            <fieldset>
                                <label><input type="checkbox" id="sbscrbr-delete-user" name="sbscrbr_delete_users" value="1" <?php checked( $this->options["delete_users"] ); ?> /><?php _e( 'Plugin deleted', 'subscriber' ); ?></label><br />
                                <span class="bws_info"><?php _e( 'If this option enabled, all users with "Mail Subscribed" role will be removed with the plugin removing.', 'subscriber' ); ?></span>
                            </fieldset>
                        </td>
					</tr>
				</table>
				<?php if ( ! sbscrbr_check_sender_install() ) {
					echo '<p>' . __( 'If you want to send mailout to the users who have subscribed to newsletter use', 'subscriber' ) . ' <a href="https://wordpress.org/plugins/sender/" target="_blank">Sender plugin</a> ' . __( 'that sends mail to registered users. There is also a premium version of the plugin', 'subscriber' ) . ' - <a href="https://bestwebsoft.com/products/wordpress/plugins/sender/?k=0c5039a6150ad529b6e9fc8246aa9cc4&pn=146&v=' . $sbscrbr_plugin_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">Sender Pro</a>, ' . __( 'which allows you to create and save templates for letters, edit the content of messages with a visual editor TinyMce, set priority оf mailing, create and manage mailing lists.', 'subscriber' ) . '</p>';
				}
			}
		}
		public function tab_messages() {
			global $wp_version, $sbscrbr_plugin_info; ?>
			<h3 class="bws_tab_label"><?php _e( 'System Messages Settings', 'subscriber' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table id="sbscrbr-messages-table" class="form-table">
				<tr valign="top">
					<th>
						<?php _e( 'Service messages', 'subscriber' ); ?>
						<span class="bws_help_box dashicons dashicons-editor-help">
							<span class="bws_hidden_help_text" style="width: 240px;">
								<?php _e( 'These messages will be displayed in the frontend of your site.', 'subscriber' ); ?>
							</span>
						</span>
					</th>
					<td>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-bad-request" name="sbscrbr_bad_request" maxlength="250" value="<?php echo $this->options['bad_request'] ; ?>"/>
						<span class="bws_info"><?php _e( 'Unknown Error', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-empty-email" name="sbscrbr_empty_email" maxlength="250" value="<?php echo esc_attr( $this->options['empty_email'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Missing Email', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-invalid-email" name="sbscrbr_invalid_email" maxlength="250" value="<?php echo esc_attr( $this->options['invalid_email'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Invalid Email', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-not-exists-email" name="sbscrbr_not_exists_email" maxlength="250" value="<?php echo esc_attr( $this->options['not_exists_email'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Email Not Found', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr_cannot_get_email" name="sbscrbr_cannot_get_email" maxlength="250" value="<?php echo esc_attr( $this->options['cannot_get_email'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Email Cannot be Located', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-cannot-send-email" name="sbscrbr_cannot_send_email" maxlength="250" value="<?php echo esc_attr( $this->options['cannot_send_email'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Email Not Sent', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-error-subscribe" name="sbscrbr_error_subscribe" maxlength="250" value="<?php echo esc_attr( $this->options['error_subscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Registration Error', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-done-subscribe" name="sbscrbr_done_subscribe" maxlength="250" value="<?php echo esc_attr( $this->options['done_subscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Subscription Completed', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-already-subscribe" name="sbscrbr_already_subscribe" maxlength="250" value="<?php echo esc_attr( $this->options['already_subscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'User Already Subscribed', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-denied-subscribe" name="sbscrbr_denied_subscribe" maxlength="250" value="<?php echo esc_attr( $this->options['denied_subscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Subscription Denied', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-already-unsubscribe" name="sbscrbr_already_unsubscribe" maxlength="250" value="<?php echo esc_attr( $this->options['already_unsubscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'User Already Unsubscribed', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-check-email-unsubscribe" name="sbscrbr_check_email_unsubscribe" maxlength="250" value="<?php echo esc_attr( $this->options['check_email_unsubscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Unsubscribe Link Sent', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-done-unsubscribe" name="sbscrbr_done_unsubscribe" maxlength="250" value="<?php echo esc_attr( $this->options['done_unsubscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Unsubscribe Completed', 'subscriber' ); ?></span>
						<br/>
						<input type="text" class="sbscrbr-input-text" id="sbscrbr-not-exists-unsubscribe" name="sbscrbr_not_exists_unsubscribe" maxlength="250" value="<?php echo esc_attr( $this->options['not_exists_unsubscribe'] ); ?>"/>
						<span class="bws_info"><?php _e( 'Subscribe/Unsubscribe Link Not Exist', 'subscriber' ); ?></span>
					</td><!-- .sbscrbr-service-messages -->
				</tr>
			</table>
			<?php
		}
		public function tab_notifications() {
			global $wp_version; ?>
			<h3 class="bws_tab_label"><?php _e( 'Email Notification Settings', 'subscriber' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table">
				<tr>
					<th><?php _e( 'Email Notification', 'subscriber' ); ?></th>
					<td>
						<input class="bws_option_affect" data-affect-show="#sbscrbr-notifications-table" type="checkbox" name="sbscrbr_notification" value="1" <?php checked( $this->options['notification'] ); ?> />
						<span class="bws_info"><?php _e( 'Enable to receive email notifications.', 'subscriber' ); ?></span>
					</td>
				</tr>
			</table>
			<table id="sbscrbr-notifications-table" class="form-table">
                <tr>
                    <th scope="row"><?php _e( "Send Email Notifications to", 'subscriber' ); ?></th>
                    <td>
                        <fieldset>
                            <label><input class="bws_option_affect" data-affect-show="#sbscrbr_to_email_user_display" type="checkbox" name="sbscrbr_admin_message" value="1" <?php checked( $this->options['admin_message'] ); ?>/> <?php _e( 'Administrator', 'subscriber' ); ?></label><br />
                            <span class="bws_info"><?php _e( 'Enable to send the notifications about new subscribed users to the administrator.', 'subscriber' ); ?></span>
                            <br />
                            <div id="sbscrbr_to_email_user_display">
                                <select class="sbscrbr-input-text sbscrbr-input-input" name="sbscrbr_email_user">
                                    <option disabled><?php _e( "Select a username", 'subscriber' ); ?></option>
                                    <?php $sbscrbr_userslogin = get_users( 'blog_id=' . $GLOBALS['blog_id'] . '&role=administrator' );
                                    foreach ( $sbscrbr_userslogin as $key => $value ) {
                                        if ( $value->data->user_email != '' ) { ?>
                                            <option value="<?php echo $value->data->user_login; ?>" <?php selected( $this->options['email_user'], $value->data->user_login ); ?>><?php echo $value->data->user_login; ?></option>
                                        <?php }
                                    } ?>
                                </select>
                            </div>
                            <label><input type="checkbox" name="sbscrbr_user_message" value="1" <?php checked( $this->options["user_message"] ); ?>/> <?php _e( 'User', 'subscriber' ); ?></label><br />
                            <span class="bws_info"><?php _e( 'Enable to notify a user about the subscription he made.', 'subscriber' ); ?></span>
                            <br />
                            <label>
                                <input class="bws_option_affect" data-affect-show="#sbscrbr_email_custom_display" type="checkbox" name="sbscrbr_to_email" value="custom" <?php checked( 'custom', $this->options['to_email'] ); ?>/> <?php _e( 'Custom', 'subscriber' ); ?><br />
                            </label>
                            <br />
                            <div id="sbscrbr_email_custom_display">
                                <input type="text" class="sbscrbr-input-text sbscrbr-input-input" name="sbscrbr_email_custom" value="<?php echo implode( ', ', $this->options['email_custom'] ); ?>" maxlength="500" /><br />
                                <span class="bws_info sbscrbr_floating_info"><?php _e( 'Add multiple email addresses separated by comma (ex: email1@example.com, email2@example.com).', 'subscriber' );?></span>
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
						<span class="bws_info"><?php _e( "If this option is changed, email messages may be moved to the spam folder or email delivery failures may occur.", 'subscriber' ); ?></span>
					</td>
				</tr>
				<tr valign="top">
					<th>
						<?php _e( 'Email Templates', 'subscriber' ); ?>
					</th>
					<td>
						<?php $available_shortcodes =
							'<div class="bws_info">
								<span>' . __( 'Available shortcodes', 'subscriber' ) . ':</span><br />
								<span><strong>{user_email}</strong> - ' . __( 'The e-mail of a current user.', 'subscriber' ) . '</span><br />
                                <span><strong>{profile_page}</strong> - ' . __( 'The link to profile page of current user.', 'subscriber' ) . '</span><br />
                                <span><strong>{unsubscribe_link}</strong> - ' . __( 'The link to unsubscribe.', 'subscriber' ) . '</span><br />
							</div>';

                        /* check sender pro activation */
						$sender_pro_active = false;
						$sender_pro_notice = '';

						if ( array_key_exists( 'sender-pro/sender-pro.php', $this->all_plugins ) ) {
							if ( ! is_plugin_active( 'sender-pro/sender-pro.php' ) ) {
								$sender_for = ( is_multisite() ) ? __( 'Activate for network', 'subscriber' ) : __( 'Activate', 'subscriber' );
								$sender_pro_notice = '<a href="' . self_admin_url( 'plugins.php' ) . '">' . $sender_for . '</a>';
							} else {
								$sender_pro_active = true;
							}
						} else {
							$sender_pro_notice = '<a href="https://bestwebsoft.com/products/wordpress/plugins/sender/?k=01665f668edd3310e8c5cf13e9cb5181&pn=122&v=' . $this->plugins_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">'. __( 'Install Now', 'subscriber' ) . '</a>';
						} ?>
						<div class="sbscrbr-messages-settings message_to_admin" style="margin-bottom: 20px !important">
							<strong><?php _e( 'New Subscriber', 'subscriber' ); ?>:</strong><br />
							<fieldset>
								<label><input class="bws_option_affect" data-affect-show="#sbscrbr-admin-message-sender" data-affect-hide="#sbscrbr-admin-message-custom" type="radio" name="sbscrbr_admin_message_use_sender" value="1" <?php disabled( $sender_pro_active, false ); checked( ! empty( $this->options['admin_message_use_sender'] ) && $sender_pro_active ); ?> /><?php echo __( "Sender plugin template", 'subscriber' ) . ' ' . $sender_pro_notice ?></label>
								<br />
								<div id="sbscrbr-admin-message-sender">
									<?php if ( $sender_pro_active ) {
										sbscrbr_sender_letters_list_select( 'sbscrbr_admin_message_sender_template_id', $this->options['admin_message_sender_template_id'] );
									} ?>
								</div>
								<label><input class="bws_option_affect" data-affect-hide="#sbscrbr-admin-message-sender" data-affect-show="#sbscrbr-admin-message-custom" type="radio" name="sbscrbr_admin_message_use_sender" value="0"<?php checked( empty( $this->options['admin_message_use_sender'] ) || ! $sender_pro_active ); ?> /><?php _e( "Custom template", 'subscriber' ); ?></label>
							</fieldset>
							<div id="sbscrbr-admin-message-custom">
								<input type="text" class="sbscrbr-input-text sbscrbr-message-to-admin" id="sbscrbr-admin-message-subject" name="sbscrbr_admin_message_subject" maxlength="250" value="<?php echo esc_attr( $this->options['admin_message_subject'] ); ?>"/>
								<span class="bws_info"><?php _e( "Subject", 'subscriber' ); ?></span>
                                <br />
                                <div class="sbscrbr-message-text">
                                    <textarea class="sbscrbr-input-text sbscrbr-message-text sbscrbr-message-to-admin" id="sbscrbr-admin-message-text" name="sbscrbr_admin_message_text"><?php echo $this->options['admin_message_text']; ?></textarea>
                                    <span class="bws_info sbscrbr_text"><?php _e( "Text", 'subscriber' ); ?></span>
                                </div>
                                <?php echo $available_shortcodes; ?>
                            </div>
						</div>
						<div id="sbscrbr-message-subscribed" class="sbscrbr-messages-settings">
							<strong><?php _e( 'Subscription Completed', 'subscriber' ); ?>:</strong><br />
							<fieldset>
								<label><input class="bws_option_affect" data-affect-show="#sbscrbr-subscribe-message-text-sender" data-affect-hide="#sbscrbr-subscribe-message-text-custom" type="radio" name="sbscrbr_subscribe_message_use_sender" value="1" <?php disabled( $sender_pro_active, false ); checked( ! empty( $this->options['subscribe_message_use_sender'] ) && $sender_pro_active ); ?> /><?php echo __( "Sender plugin template", 'subscriber' ) . ' ' . $sender_pro_notice ?></label>
								<br />
								<div id="sbscrbr-subscribe-message-text-sender">
									<?php if ( $sender_pro_active ) {
										sbscrbr_sender_letters_list_select( 'sbscrbr_subscribe_message_sender_template_id', $this->options['subscribe_message_sender_template_id'] );
									} ?>
								</div>
								<label><input class="bws_option_affect" data-affect-hide="#sbscrbr-subscribe-message-text-sender" data-affect-show="#sbscrbr-subscribe-message-text-custom" type="radio" name="sbscrbr_subscribe_message_use_sender" value="0"<?php checked( empty( $this->options['subscribe_message_use_sender'] ) || ! $sender_pro_active ); ?> /><?php _e( "Custom template", 'subscriber' ); ?></label>
							</fieldset>
							<div id="sbscrbr-subscribe-message-text-custom">
								<input type="text" class="sbscrbr-input-text sbscrbr-message-to-admin" name="sbscrbr_subscribe_message_subject" maxlength="250" value="<?php echo esc_attr( $this->options['subscribe_message_subject'] ); ?>"/>
								<span class="bws_info"><?php _e( "Subject", 'subscriber' ); ?></span>
                                <br/>
                                <div class="sbscrbr-message-text">
                                    <textarea class="sbscrbr-input-text sbscrbr-message-text sbscrbr-message-to-admin" name="sbscrbr_subscribe_message_text"><?php echo $this->options['subscribe_message_text']; ?></textarea>
                                    <span class="bws_info sbscrbr_text"><?php _e( "Text", 'subscriber' ); ?></span>
                                </div>
                                <?php echo $available_shortcodes; ?>
                            </div>
						</div>
						<div class="sbscrbr-messages-settings">
							<strong><?php _e( 'Unsubscribe Confirmation', 'subscriber' ); ?>:</strong><br>
							<fieldset>
								<label><input class="bws_option_affect" data-affect-show="#sbscrbr-unsubscribe-message-text-sender" data-affect-hide="#sbscrbr-unsubscribe-message-text-custom" type="radio" name="sbscrbr_unsubscribe_message_use_sender" value="1" <?php disabled( $sender_pro_active, false ); checked( ! empty( $this->options['unsubscribe_message_use_sender'] ) && $sender_pro_active ); ?> /><?php echo __( "Sender plugin template", 'subscriber' ) . ' ' . $sender_pro_notice ?></label>
								<br />
								<div id="sbscrbr-unsubscribe-message-text-sender">
									<?php if ( $sender_pro_active ) {
										sbscrbr_sender_letters_list_select( 'sbscrbr_unsubscribe_message_sender_template_id', $this->options['unsubscribe_message_sender_template_id'] );
									} ?>
								</div>
								<label><input class="bws_option_affect" data-affect-hide="#sbscrbr-unsubscribe-message-text-sender" data-affect-show="#sbscrbr-unsubscribe-message-text-custom" type="radio" name="sbscrbr_unsubscribe_message_use_sender" value="0"<?php checked( empty( $this->options['unsubscribe_message_use_sender'] ) || ! $sender_pro_active ); ?> /><?php _e( "Custom template", 'subscriber' ); ?></label>
							</fieldset>
                            <div id="sbscrbr-unsubscribe-message-text-custom">
                                <input type="text" class="sbscrbr-input-text sbscrbr-message-to-admin" id="sbscrbr-unsubscribe-message-subject"  name="sbscrbr_unsubscribe_message_subject" maxlength="250" value="<?php echo esc_attr( $this->options['unsubscribe_message_subject'] ); ?>"/>
                                <span class="bws_info"><?php _e( "Subject", 'subscriber' ); ?></span>
                                <br />
                                <div class="sbscrbr-message-text">
                                    <textarea class="sbscrbr-input-text sbscrbr-message-text sbscrbr-message-to-admin" id="sbscrbr-unsubscribe-message-text" name="sbscrbr_unsubscribe_message_text"><?php echo $this->options['unsubscribe_message_text']; ?></textarea>
                                    <span class="bws_info sbscrbr_text"><?php _e( "Text", 'subscriber' ); ?></span>
                                </div>
                                <?php echo $available_shortcodes; ?>
                            </div>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th><?php _e( 'Additional Text', 'subscriber' ); ?></th>
					<td>
						<input class="bws_option_affect" data-affect-show="#sbscrbr-additional-text" type="checkbox" name="sbscrbr_additional_text" value="1" <?php disabled( $sender_pro_active, false ); checked( $this->options['additional_text'] && $sender_pro_active ); ?> />
						<span class="bws_info"><?php echo __( 'Enable to include additional text in all email messages. Sender plugin is required.', 'subscriber' ) . ' ' . $sender_pro_notice; ?></span>
						<br /><br />
						<div id="sbscrbr-additional-text" class="sbscrbr-messages-settings">
							<textarea class="sbscrbr-input-text sbscrbr-message-to-admin" id="sbscrbr-unsubscribe-link-text" name="sbscrbr_unsubscribe_link_text"><?php echo $this->options['unsubscribe_link_text']; ?></textarea>
							<?php echo $available_shortcodes; ?>
                        </div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Un/Subscription Links Redirect to', "subscriber" ); ?></th>
					<td>
						<fieldset>
							<label>
								<input class="bws_option_affect" data-affect-hide="#sbscrbr-display-shortcode-url" type="radio" name="sbscrbr_shortcode_link_type" value="text" <?php checked( $this->options['shortcode_link_type'], 'text' ); ?> />
								<?php _e( 'Default page', 'subscriber' ); ?>
							</label><br />
							<label>
								<input class="bws_option_affect" data-affect-show="#sbscrbr-display-shortcode-url" type="radio" name="sbscrbr_shortcode_link_type" value="url" <?php checked( $this->options['shortcode_link_type'], 'url' ); ?> />
								<?php _e( 'Custom page', 'subscriber' ); ?>
							</label>
						</fieldset>
						<div id="sbscrbr-display-shortcode-url">
							<input type="url" name="sbscrbr_shortcode_url" class="sbscrbr-input-shortcode" value="<?php echo $this->options['shortcode_url']; ?>" /><br />
							<span class="bws_info"><?php _e( 'Add a subscriber form to this page in order to see a successful unsubscription notification.', 'subscriber' ); ?></span>
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
                                    <label><input type="radio" disabled="disabled" name="sbscrbr_format_export" value="csv" checked="checked" /><?php _e( 'CSV file format', 'subscriber' ); ?></label><br />
                                    <label><input type="radio" disabled="disabled" name="sbscrbr_format_export" value="xml" /><?php _e( 'XML file format', 'subscriber' ); ?></label><br />
                                </fieldset>
                                <input type="submit" disabled="disabled" name="sbscrbr_export_submit" class="button" value="<?php _e( 'Export', 'subscriber' ) ?>" />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e( 'Import Data', 'subscriber' ); ?></th>
                            <td>
                                <fieldset>
                                    <label><input type="radio" disabled="disabled" name="sbscrbr_method_insert" value="missing_exists" checked="checked" /><?php _e( 'Keep existing data', 'subscriber' ); ?></label><br />
                                    <label><input type="radio" disabled="disabled" name="sbscrbr_method_insert" value="clear_data" /><?php _e( 'Delete existing data', 'subscriber' ); ?> </label><br />
                                </fieldset>
                                <label><input name="sbscrbr_import_file_upload" type="file" disabled="disabled" /></label><br /><br />
                                <input type="submit" disabled="disabled" name="sbscrbr_import_submit" class="button" value="<?php _e( 'Import', 'subscriber' ) ?>" />
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
						<p><?php _e( 'Add a subscription form to a widget.', 'subscriber' ); ?> <a href="widgets.php"><?php _e( 'Navigate to Widgets', 'subscriber' ); ?></a></p>
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