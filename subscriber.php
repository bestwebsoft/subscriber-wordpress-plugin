<?php
/**
Plugin Name: Subscriber by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/subscriber/
Description: Add email newsletter sign up form to WordPress posts, pages and widgets. Collect data and subscribe your users.
Author: BestWebSoft
Text Domain: subscriber
Domain Path: /languages
Version: 1.4.9
Author URI: https://bestwebsoft.com/
License: GPLv2 or later
 */

/**  © Copyright 2021 BestWebSoft  ( https://support.bestwebsoft.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if ( ! function_exists( 'sbscrbr_add_admin_menu' ) ) {
	/**
	 * Function add menu pages
	 */
	function sbscrbr_add_admin_menu() {
		global $submenu, $sbscrbr_plugin_info, $wp_version;
		$settings = add_menu_page(
			__( 'Subscriber Settings', 'subscriber' ), /* $page_title */
			'Subscriber', /* $menu_title */
			'manage_options', /* $capability */
			'subscriber.php', /* $menu_slug */
			'sbscrbr_settings_page' /* $callable_function */
		);
		add_submenu_page(
			'subscriber.php',
			__( 'Subscriber Settings', 'subscriber' ),
			__( 'Settings', 'subscriber' ),
			'manage_options',
			'subscriber.php',
			'sbscrbr_settings_page'
		);
		$users = add_submenu_page(
			'subscriber.php',
			__( 'Subscribers', 'subscriber' ),
			__( 'Subscribers', 'subscriber' ),
			'manage_options',
			'subscriber-users.php',
			'sbscrbr_users'
		);
		add_submenu_page(
			'subscriber.php',
			'BWS Panel',
			'BWS Panel',
			'manage_options',
			'sbscrbr-bws-panel',
			'bws_add_menu_render'
		);
		if ( ! function_exists( 'sbscrbr_screen_options' ) ) {
			require_once( dirname( __FILE__ ) . '/includes/users.php' );
		}

		if ( isset( $submenu['subscriber.php'] ) ) {
			$submenu['subscriber.php'][] = array(
				'<span style="color:#d86463"> ' . __( 'Update to Pro', 'subscriber' ) . '</span>',
				'manage_options',
				'https://bestwebsoft.com/products/wordpress/plugins/subscriber/?k=d356381b0c3554404e34cdc4fe936455&amp;pn=122&amp;v=' . $sbscrbr_plugin_info['Version'] . '&wp_v=' . $wp_version,
			);
		}

		add_action( "load-{$settings}", 'sbscrbr_add_tabs' );
		add_action( "load-{$users}", 'sbscrbr_add_tabs' );
	}
}

if ( ! function_exists( 'sbscrbr_plugins_loaded' ) ) {
	/**
	 * Load textdomain of plugin
	 */
	function sbscrbr_plugins_loaded() {
		load_plugin_textdomain( 'subscriber', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

if ( ! function_exists( 'sbscrbr_init' ) ) {
	/**
	 * Plugin initialisation in backend and frontend
	 */
	function sbscrbr_init() {
		global $sbscrbr_plugin_info, $sbscrbr_options, $sbscrbr_handle_form_data;

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );

		sbscrbr_get_plugin_info();

		/* check version on WordPress */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $sbscrbr_plugin_info, '4.5' );

		/* add new user role */
		$capabilities = array(
			'read'          => true,
			'edit_posts'    => false,
			'delete_posts'  => false,
		);
		add_role( 'sbscrbr_subscriber', __( 'Mail Subscriber', 'subscriber' ), $capabilities );

		/* register plugin settings */
		sbscrbr_settings();

		/* unsubscribe users from mailout if Subscribe Form  not displayed on home page */
		if ( ! is_admin() ) {
			if ( ! class_exists( 'Sbscrbr_Handle_Form_Data' ) ) {
				require_once( dirname( __FILE__ ) . '/includes/class-sbscrbr-handle-form-data.php' );
			}
			$sbscrbr_handle_form_data = new Sbscrbr_Handle_Form_Data();
			if ( isset( $_GET['sbscrbr_unsubscribe'] ) && isset( $_GET['code'] ) && isset( $_GET['subscriber_id'] ) ) {
				global $sbscrbr_response;
				$sbscrbr_response = $sbscrbr_handle_form_data->unsubscribe_from_email( sanitize_text_field( wp_unslash( $_GET['sbscrbr_unsubscribe'] ) ), sanitize_text_field( wp_unslash( $_GET['code'] ) ), absint( $_GET['subscriber_id'] ) );
				if ( 'url' != $sbscrbr_options['shortcode_link_type'] && ! empty( $sbscrbr_response['message'] ) ) {
					$sbscrbr_response['title'] = __( 'Unsubscribe Confirmation', 'subscriber' );
					$sbscrbr_handle_form_data->last_response = array();
					add_action( 'template_redirect', 'sbscrbr_template_redirect' );
					add_action( 'the_posts', 'sbscrbr_the_posts' );
				}
			}
		}

		if ( isset( $sbscrbr_options['contact_form'] ) && 1 === $sbscrbr_options['contact_form'] ) {
			add_filter( 'sbscrbr_cntctfrm_checkbox_add', 'sbscrbr_checkbox_add', 10, 1 );
			add_filter( 'sbscrbr_cntctfrm_checkbox_check', 'sbscrbr_checkbox_check', 10, 1 );
		}

		add_filter( 'sbscrbr_checkbox_add', 'sbscrbr_checkbox_add', 10, 1 );
		add_filter( 'sbscrbr_checkbox_check', 'sbscrbr_checkbox_check', 10, 1 );
	}
}

if ( ! function_exists( 'sbscrbr_admin_init' ) ) {
	/**
	 * Plugin initialisation in backend
	 */
	function sbscrbr_admin_init() {
		global $bws_plugin_info, $sbscrbr_plugin_info, $bws_shortcode_list, $pagenow, $sbscrbr_options;

		if ( empty( $bws_plugin_info ) ) {
			$bws_plugin_info = array(
				'id' => '122',
				'version' => $sbscrbr_plugin_info['Version'],
			);
		}

		/* add Subscriber to global $bws_shortcode_list  */
		$bws_shortcode_list['sbscrbr'] = array( 'name' => 'Subscriber' );

		if ( 'plugins.php' == $pagenow ) {
			/* Install the option defaults */
			if ( function_exists( 'bws_plugin_banner_go_pro' ) ) {
				bws_plugin_banner_go_pro( $sbscrbr_options, $sbscrbr_plugin_info, 'sbscrbr', 'subscriber', '95812391951699cd5a64397cfb1b0557', '122', 'subscriber' );
			}
		}

	}
}

if ( ! function_exists( 'sbscrbr_settings' ) ) {
	/**
	 * Default Plugin settings
	 */
	function sbscrbr_settings() {
		global $sbscrbr_options, $sbscrbr_plugin_info;
		$db_version = '1.0';

		sbscrbr_get_plugin_info();

		/* install the default options */
		if ( is_multisite() ) {
			if ( ! get_site_option( 'sbscrbr_options' ) ) {
				$default_options = sbscrbr_get_default_options();
				add_site_option( 'sbscrbr_options', $default_options );
			}
			$sbscrbr_options = get_site_option( 'sbscrbr_options' );
		} else {
			if ( ! get_option( 'sbscrbr_options' ) ) {
				$default_options = sbscrbr_get_default_options();
				add_option( 'sbscrbr_options', $default_options );
			}
			$sbscrbr_options = get_option( 'sbscrbr_options' );
		}

		if ( ! isset( $sbscrbr_options['plugin_option_version'] ) || $sbscrbr_options['plugin_option_version'] != $sbscrbr_plugin_info['Version'] ) {
			/* array merge incase this version of plugin has added new options */
			$default_options = sbscrbr_get_default_options();
			$sbscrbr_options = array_merge( $default_options, $sbscrbr_options );
			/* show pro features */
			$sbscrbr_options['hide_premium_options'] = array();

			$sbscrbr_options['plugin_option_version'] = $sbscrbr_plugin_info['Version'];
			$update_option = true;
		}

		if ( ! isset( $sbscrbr_options['plugin_db_version'] ) || $sbscrbr_options['plugin_db_version'] != $db_version ) {
			sbscrbr_db();
			$sbscrbr_options['plugin_db_version'] = $db_version;
			$update_option = true;
		}

		if ( isset( $update_option ) ) {
			if ( is_multisite() ) {
				update_site_option( 'sbscrbr_options', $sbscrbr_options );
			} else {
				update_option( 'sbscrbr_options', $sbscrbr_options );
			}
		}
	}
}

if ( ! function_exists( 'sbscrbr_get_default_options' ) ) {
	/**
	 * Get Default Plugin options
	 *
	 * @return array
	 */
	function sbscrbr_get_default_options() {
		global $sbscrbr_plugin_info;

		$sitename = isset( $_SERVER['SERVER_NAME'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) ) : '';
		if ( 'www.' == substr( $sitename, 0, 4 ) ) {
			$sitename = substr( $sitename, 4 );
		}
		$from_email = 'wordpress@' . $sitename;

		$default_options = array(
			'plugin_option_version'         => $sbscrbr_plugin_info['Version'],
			'first_install'                 => strtotime( 'now' ),
			'suggest_feature_banner'        => 1,
			'display_settings_notice'       => 1,
			/* form labels */
			'form_title'                    => '',
			'form_label'                    => '',
			'gdpr_text'                     => '',
			'gdpr_link'                     => '',
			'form_placeholder'              => __( 'E-mail', 'subscriber' ),
			'form_checkbox_label'           => __( 'Unsubscribe', 'subscriber' ),
			'form_button_label'             => __( 'Subscribe', 'subscriber' ),
			'unsubscribe_button_name'       => __( 'Unsubscribe', 'subscriber' ),
			'gdpr_cb_name'                  => __( 'I consent to having this site collect my personal data.', 'subscriber' ),
			/* service messages */
			'bad_request'                   => __( 'System error. Please try later.', 'subscriber' ),
			'empty_email'                   => __( 'Please enter e-mail address.', 'subscriber' ),
			'invalid_email'                 => __( 'You must enter a valid e-mail address.', 'subscriber' ),
			'not_exists_email'              => __( 'This e-mail address does not exist.', 'subscriber' ),
			'cannot_get_email'              => __( 'Your e-mail information cannot be located.', 'subscriber' ),
			'cannot_send_email'             => __( 'Unable to send the e-mail at this time. Please try later.', 'subscriber' ),
			'error_subscribe'               => __( 'Error occurred during registration. Please try later.', 'subscriber' ),
			'done_subscribe'                => __( 'Thank you for subscribing!', 'subscriber' ),
			'already_subscribe'             => __( 'This e-mail address is already subscribed.', 'subscriber' ),
			'denied_subscribe'              => __( 'Sorry, but your request to subscribe has been denied.', 'subscriber' ),
			'already_unsubscribe'           => __( 'You have successfully unsubscribed.', 'subscriber' ),
			'check_email_unsubscribe'       => __( 'An unsubscribe link has been sent to you.', 'subscriber' ),
			'not_exists_unsubscribe'        => __( 'Unsubscribe link failed. We respect your wishes. Please contact us to let us know.', 'subscriber' ),
			'done_unsubscribe'              => __( 'You have successfully unsubscribed.', 'subscriber' ),
			/* mail settings */
			'notification'                  => 1,
			/* To email settings */
			'email_user'                    => 1,
			'gdpr'                          => 0,
			'email_custom'                  => array( get_option( 'admin_email' ) ),
			'to_email'                      => '',
			/* "From" settings */
			'from_custom_name'              => get_bloginfo( 'name' ),
			'from_email'                    => $from_email,
			'admin_message'                 => 0,
			'user_message'                  => 1,
			/* subject settings */
			'admin_message_subject'         => __( 'New subscriber', 'subscriber' ),
			'subscribe_message_subject'     => __( 'Thanks for registration', 'subscriber' ),
			'unsubscribe_message_subject'   => __( 'Link to unsubscribe', 'subscriber' ),
			/* message body settings */
			'admin_message_text'            => sprintf( __( 'User with e-mail %s has subscribed to a newsletter.', 'subscriber' ), '{user_email}' ),
			'subscribe_message_text'        => sprintf( __( 'Thanks for registration. To change data of your profile go to %1$s If you want to unsubscribe from the newsletter from our site go to the link %2$s', 'subscriber' ), "{profile_page}\n", "\n{unsubscribe_link}" ),
			'unsubscribe_message_text'      => sprintf( __( "Dear user. At your request, we send you a link to unsubscribe from our email messages. To unsubscribe please use the link below. If you change your mind, you can just ignore this letter.\nLink to unsubscribe:\n %s", 'subscriber' ), '{unsubscribe_link}' ),
			'admin_message_use_sender'                  => 0,
			'admin_message_sender_template_id'          => '',
			'subscribe_message_use_sender'              => 0,
			'subscribe_message_sender_template_id'      => '',
			'unsubscribe_message_use_sender'            => 0,
			'unsubscribe_message_sender_template_id'    => '',
			/* another settings */
			'additional_text'               => 1,
			'unsubscribe_link_text'         => sprintf( __( 'If you want to unsubscribe from the newsletter from our site go to the following link: %s', 'subscriber' ), "\n{unsubscribe_link}" ),
			'delete_users'                  => 0,
			'form_title_field'              => 0,
			'form_label_field'              => 0,
			'contact_form'                  => 0,
			/* settings for {unsubscribe_link} */
			'shortcode_link_type'           => 'url', /* go to url or display text */
			'shortcode_url'                 => home_url(),
			'form_one_line'                 => 0,
		);
		return $default_options;
	}
}

if ( ! function_exists( 'sbscrbr_db' ) ) {
	/**
	 * Function is called during activation of plugin
	 */
	function sbscrbr_db() {
		/* add new table in database */
		global $wpdb;
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$sql_query =
			'CREATE TABLE IF NOT EXISTS `' . $prefix . "sndr_mail_users_info` (
			`mail_users_info_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`id_user` INT NOT NULL,
			`user_email` VARCHAR( 255 ) NOT NULL,
			`user_display_name` VARCHAR( 255 ) NOT NULL,
			`subscribe` INT( 1 ) NOT NULL DEFAULT '1',
			`unsubscribe_code` VARCHAR(100) NOT NULL,
			`subscribe_time` INT UNSIGNED NOT NULL,
			`unsubscribe_time` INT UNSIGNED NOT NULL,
			`delete` INT UNSIGNED NOT NULL,
			`black_list` INT UNSIGNED NOT NULL,
			PRIMARY KEY ( `mail_users_info_id` )
			) DEFAULT CHARSET=utf8;";
		dbDelta( $sql_query );

		/* check if column "unsubscribe_code" is already exists */
		$column_exists = $wpdb->query( 'SHOW COLUMNS FROM `' . $prefix . 'sndr_mail_users_info` LIKE "unsubscribe_code"' );
		if ( empty( $column_exists ) ) {
			$wpdb->query(
				'ALTER TABLE `' . $prefix . 'sndr_mail_users_info`
				ADD `unsubscribe_code` VARCHAR(100) NOT NULL,
				ADD `subscribe_time` INT UNSIGNED NOT NULL,
				ADD `unsubscribe_time` INT UNSIGNED NOT NULL,
				ADD `delete` INT UNSIGNED NOT NULL,
				ADD `black_list` INT UNSIGNED NOT NULL;'
			);
			$wpdb->query( 'UPDATE `' . $prefix . 'sndr_mail_users_info` SET `unsubscribe_code`= MD5(' . wp_generate_password() . ');' );
			$wpdb->query( 'UPDATE `' . $prefix . 'sndr_mail_users_info` SET `subscribe_time`=' . time() . ' WHERE `subscribe`=1;' );
			$wpdb->query( 'UPDATE `' . $prefix . 'sndr_mail_users_info` SET `unsubscribe_time`=' . time() . ' WHERE `subscribe`=0;' );
		}
	}
}

if ( ! function_exists( 'sbscrbr_admin_head' ) ) {
	/**
	 * Fucntion load stylesheets and scripts in backend
	 */
	function sbscrbr_admin_head() {
		global $sbscrbr_plugin_info;
		wp_enqueue_style( 'sbscrbr_icon_style', plugins_url( 'css/admin-icon.css', __FILE__ ), array(), $sbscrbr_plugin_info['Version'] );

		if ( isset( $_GET['page'] ) && 'subscriber.php' === $_GET['page'] ) {
			wp_enqueue_style( 'sbscrbr_style', plugins_url( 'css/style.css', __FILE__ ), array(), $sbscrbr_plugin_info['Version'] );
			wp_enqueue_script( 'sbscrbr_scripts', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ), $sbscrbr_plugin_info['Version'], true );
			bws_enqueue_settings_scripts();
			bws_plugins_include_codemirror();
		}
	}
}

if ( ! function_exists( 'sbscrbr_load_styles' ) ) {
	/**
	 * Load scripts in frontend
	 */
	function sbscrbr_load_styles() {
		global $sbscrbr_plugin_info;
		wp_enqueue_style( 'sbscrbr_style', plugins_url( 'css/frontend_style.css', __FILE__ ), array(), $sbscrbr_plugin_info['Version'] );
	}
}

if ( ! function_exists( 'sbscrbr_load_scripts' ) ) {
	/**
	 * Load scripts in frontend
	 */
	function sbscrbr_load_scripts() {
		global $sbscrbr_options;
		if ( wp_script_is( 'sbscrbr_form_scripts', 'registered' ) && ! wp_script_is( 'sbscrbr_form_scripts', 'enqueued' ) ) {
			wp_enqueue_script( 'sbscrbr_form_scripts' );
			wp_localize_script(
				'sbscrbr_form_scripts',
				'sbscrbr_js_var',
				array(
					'preloaderIconPath'         => plugins_url( 'images/preloader.gif', __FILE__ ),
					'unsubscribe_button_name'   => $sbscrbr_options['unsubscribe_button_name'],
					'subscribe_button_name'     => $sbscrbr_options['form_button_label'],
				)
			);
		}
	}
}

if ( ! function_exists( 'sbscrbr_template_redirect' ) ) {
	/**
	 * Function to redirect for displaying subscriber service message in a separate page
	 */
	function sbscrbr_template_redirect() {
		global $sbscrbr_response;
		if ( empty( $sbscrbr_response ) ) {
			return;
		}
		$files = array(
			'page.php',
			'singular.php',
			'index.php',
		);
		include( locate_template( $files ) );
		exit;
	}
}

if ( ! function_exists( 'sbscrbr_the_posts' ) ) {
	/**
	 * Function for displaying subscriber service message in a separate page
	 */
	function sbscrbr_the_posts() {
		global $wp, $wp_query, $sbscrbr_response;

		if ( empty( $sbscrbr_response ) ) {
			return;
		}

		remove_all_filters( 'the_content' );
		add_filter( 'the_content', 'capital_P_dangit', 11 );
		add_filter( 'the_content', 'wptexturize' );
		add_filter( 'the_content', 'convert_smilies' );
		add_filter( 'the_content', 'convert_chars' );
		add_filter( 'the_content', 'wpautop' );
		add_filter( 'the_content', 'do_shortcode' );

		$content = '<div id="sbscrbr-page">' . $sbscrbr_response['message'] . '</div>';

		/* create a fake post intance */
		$post = new stdClass();
		/* fill properties of $post with everything a page in the database would have */
		$post->ID = -1;
		$post->post_author = 1;
		$post->post_date = current_time( 'mysql' );
		$post->post_date_gmt = current_time( 'mysql', 1 );
		$post->post_content = $content;
		$post->post_title = $sbscrbr_response['title'];
		$post->post_excerpt = '';
		$post->post_status = 'publish';
		$post->comment_status = 'closed';
		$post->ping_status = 'closed';
		$post->post_password = '';
		$post->post_name = '';
		$post->to_ping = '';
		$post->pinged = '';
		$post->modified = $post->post_date;
		$post->modified_gmt = $post->post_date_gmt;
		$post->post_content_filtered = '';
		$post->post_parent = 0;
		$post->guid = get_home_url( '/' );
		$post->menu_order = 0;
		$post->post_type = 'page';
		$post->post_mime_type = '';
		$post->comment_count = 0;
		/* set filter results */
		$posts = array( $post );
		/* reset wp_query properties to simulate a found page */
		$wp_query->is_page = true;
		$wp_query->is_singular = true;
		$wp_query->is_home = false;
		$wp_query->is_archive = false;
		$wp_query->is_category = false;
		unset( $wp_query->query['error'] );
		$wp_query->query_vars['error'] = '';
		$wp_query->is_404 = false;

		return ( $posts );
	}
}

if ( ! function_exists( 'sbscrbr_settings_page' ) ) {
	/**
	 * Display settings page of plugin
	 */
	function sbscrbr_settings_page() {
		if ( ! class_exists( 'Bws_Settings_Tabs' ) ) {
			require_once( dirname( __FILE__ ) . '/bws_menu/class-bws-settings.php' );
		}
		require_once( dirname( __FILE__ ) . '/includes/class-sbscrbr-settings.php' );
		$page = new Sbscrbr_Settings_Tabs( plugin_basename( __FILE__ ) );
		if ( method_exists( $page, 'add_request_feature' ) ) {
			$page->add_request_feature();
		}?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Subscriber Settings', 'subscriber' ); ?></h1>
			<noscript>
				<div class="error below-h2">
					<p><strong><?php esc_html_e( 'WARNING', 'subscriber' ); ?>
							:</strong> <?php esc_html_e( 'The plugin works correctly only if JavaScript is enabled.', 'subscriber' ); ?>
					</p>
				</div>
			</noscript>
			<?php $page->display_content(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'sbscrbr_users' ) ) {
	/**
	 * Subscribers page
	 */
	function sbscrbr_users() {
		global $sbscrbr_plugin_info;
		$message = '';
		$error   = '';
		require_once( dirname( __FILE__ ) . '/includes/users.php' );
		?>
		 
		<div class="wrap">
			<h1><?php esc_html_e( 'Subscribers', 'subscriber' ); ?></h1>
			<?php
			sbscrbr_display_advertising();
			$action_message = sbscrbr_report_actions();
			if ( $action_message['error'] ) {
				$error = $action_message['error'];
			} elseif ( $action_message['done'] ) {
				$message = $action_message['done'];
			}
			?>
		</div>
		<?php if ( ! empty( $notice ) ) { ?>
			<div class="error below-h2"><p><strong><?php esc_html_e( 'Notice:', 'subscriber' ); ?></strong> <?php echo wp_kses_post( $notice ); ?></p></div>
		<?php } ?>
		<div class="updated below-h2 fade" <?php echo empty( $message ) ? 'style="display:none"' : ''; ?>><p><strong><?php echo wp_kses_post( $message ); ?></strong></p></div>
		<div class="error below-h2" <?php echo empty( $error ) ? 'style="display:none"' : ''; ?>><p><strong><?php echo wp_kses_post( $error ); ?></strong></p></div>
		<?php $sbscrbr_users_list = new Sbscrbr_User_List(); ?>

		<div id="sbscrbr_settings_block_subscribers">
			<div class="wrap sbscrbr-users-list-page">
				<?php
				if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
					printf( '<span class="subtitle">' . sprintf( esc_html__( 'Search results for &#8220;%s&#8221;', 'subscriber' ), wp_html_excerpt( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ), 50 ) ) . '</span>' );
				}
				echo '<h2 class="screen-reader-text">' . esc_html__( 'Filter subscribers list', 'subscriber' ) . '</h2>';
				$sbscrbr_users_list->views();
				?>
				<form method="post">
					<?php
					$sbscrbr_users_list->prepare_items();
					$sbscrbr_users_list->search_box( __( 'search', 'subscriber' ), 'sbscrbr' );
					$sbscrbr_users_list->display();
					wp_nonce_field( plugin_basename( __FILE__ ), 'sbscrbr_list_nonce_name' );
					?>
				</form>
			<?php bws_plugin_reviews_block( $sbscrbr_plugin_info['Name'], 'subscriber' ); ?>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'sbscrbr_sender_letters_list_select' ) ) {
	/**
	 * Display list of letters
	 *
	 * @param string $name            Name for select.
	 * @param int    $letters_list_id ID of selected letters.
	 */
	function sbscrbr_sender_letters_list_select( $name, $letters_list_id = '' ) {
		global $wpdb;
		$count_selected = 0;
		$error = '<select name="' . $name . '" disabled="disabled"><option>' . __( 'Letters not found', 'subscriber' ) . '</option>';

		$list_data = $wpdb->get_results( 'SELECT `mail_send_id`, `subject`, `letter_in_trash` FROM `' . $wpdb->prefix . 'sndr_mail_send` ORDER BY `subject`;', ARRAY_A );
		if ( ! empty( $list_data ) ) {
			$html = '<select name="' . $name . '">';
			foreach ( $list_data as $list ) {
				if ( 0 == $list['letter_in_trash'] ) {
					$count_selected ++;
					$selected = ( ! empty( $letters_list_id ) && $list['mail_send_id'] == $letters_list_id ) ? ' selected="selected"' : '';
					$item_title   = ( empty( $list['subject'] ) ) ? ' - ' . __( 'empty title', 'subscriber' ) . ' - ' : $list['subject'];
					$html .= '<option value="' . $list['mail_send_id'] . '"' . $selected . '>' . $item_title . '</option>';
				}
			}
			if ( 0 == $count_selected ) {
				$html = $error;
			} else {
				$count_selected = 0;
			}
			$html .= '</select>';
		} else {
			/* display error message */
			$html = $error . '</select>';
		}
		echo $html;
	}
}

if ( ! function_exists( 'sbscrbr_checkbox_add' ) ) {
	/**
	 * Add checkbox "Subscribe" to the custom form
	 *
	 * @param array $args Array with settings.
	 * @return array $params
	 */
	function sbscrbr_checkbox_add( $args ) {

		$params = array(
			'form_id' => 'custom',
			'label'   => __( 'Subscribe', 'subscriber' ),
			'display' => false,
			'content' => '',
		);

		if ( is_array( $args ) ) {
			$params = array_merge( $params, $args );
			$params = array_map( 'stripslashes_deep', $params );
		}

		$display_message = '';
		if ( isset( $params['display']['type'] ) && isset( $params['display']['message'] ) ) {
			$display_message = sprintf( '<div class="sbscrbr-cb-message"><div class="sbscrbr-form-%s">%s</div></div>', wp_strip_all_tags( $params['display']['type'] ), wp_strip_all_tags( $params['display']['message'] ) );
		}

		$attr_checked = '';
		if ( isset( $_POST['sbscrbr_form_id'] ) && $_POST['sbscrbr_form_id'] == $params['form_id'] && isset( $_POST['sbscrbr_checkbox_subscribe'] ) && 1 == $_POST['sbscrbr_checkbox_subscribe'] ) {
			$attr_checked = 'checked="checked"';
		}

		$params['content'] = sprintf(
			'<div class="sbscrbr-cb">
				%s
				<label><input type="checkbox" name="sbscrbr_checkbox_subscribe" value="1" %s /> %s</label>
				<input type="hidden" name="sbscrbr_submit_email" value="sbscrbr_submit_email" />
				<input type="hidden" name="sbscrbr_form_id" value="%s" />
			</div>',
			$display_message,
			$attr_checked,
			$params['label'],
			$params['form_id']
		);

		return $params;
	}
}

if ( ! function_exists( 'sbscrbr_checkbox_check' ) ) {
	/**
	 * Result of checking when adding an email from custom form
	 *
	 * @param array $args Array with settings.
	 * @return array $params - Result from Sbscrbr_Handle_Form_Data.
	 */
	function sbscrbr_checkbox_check( $args ) {
		global $sbscrbr_handle_form_data;

		if ( isset( $_POST['sbscrbr_checkbox_subscribe'] ) && 1 == $_POST['sbscrbr_checkbox_subscribe'] ) {

			$params = array(
				'form_id'       => 'custom',
				'email'         => '',
				'unsubscribe'   => false,
				'skip_captcha'  => true,
				'custom_events' => array(),
			);

			if ( is_array( $args ) ) {
				$params = array_merge( $params, $args );
				$params = array_map( 'stripslashes_deep', $params );
			}

			if ( isset( $_POST['sbscrbr_form_id'] ) && $_POST['sbscrbr_form_id'] == $params['form_id'] ) {
				if ( ! empty( $params['custom_events'] ) && is_array( $params['custom_events'] ) ) {
					$sbscrbr_handle_form_data->custom_events( $params['custom_events'] );
				}
				$params['response'] = $sbscrbr_handle_form_data->submit( $params['email'], $params['unsubscribe'], $params['skip_captcha'] );
			} else {
				$params['response'] = array(
					'action'    => 'checkbox_check',
					'type'      => 'error',
					'reason'    => 'DOES_NOT_MATCH_FORMS_IDS',
					'message'   => sprintf( '<p class="sbscrbr-form-error">%s</p>', __( 'The ID of the verifiable form does not match the ID of the sending form.', 'subscriber' ) ),
				);
			}
		} else {
			$params = $args;
		}

		return $params;
	}
}

if ( ! function_exists( 'sbscrbr_widgets_init' ) ) {
	/**
	 * Class extends WP class WP_Widget, and create new widget
	 */
	function sbscrbr_widgets_init() {
		if ( ! class_exists( 'Sbscrbr_Widget' ) ) {
			require_once( dirname( __FILE__ ) . '/includes/class-sbscrbr-widget.php' );
		}
		register_widget( 'Sbscrbr_Widget' );
	}
}

if ( ! function_exists( 'sbscrbr_subscribe_form' ) ) {
	/**
	 * Add shortcode
	 *
	 * @return string $content Content of subscribe form.
	 */
	function sbscrbr_subscribe_form() {
		global $sbscrbr_options, $sbscrbr_handle_form_data, $sbscrbr_display_message, $sbscrbr_shortcode_count, $wp, $sbscrbr_plugin_info;

		$sbscrbr_shortcode_count = empty( $sbscrbr_shortcode_count ) ? 1 : $sbscrbr_shortcode_count + 1;
		$form_id = 1 === $sbscrbr_shortcode_count ? '' : '-' . $sbscrbr_shortcode_count;

		if ( ! wp_script_is( 'sbscrbr_form_scripts', 'registered' ) ) {
			wp_register_script( 'sbscrbr_form_scripts', plugins_url( 'js/form_script.js', __FILE__ ), array( 'jquery' ), $sbscrbr_plugin_info['Version'], true );
		}

		$action_form = ( is_front_page() ) ? home_url( add_query_arg( array(), $wp->request ) ) : '';
		$action_form .= '#sbscrbr-form' . $form_id;

		if ( empty( $sbscrbr_options ) ) {
			sbscrbr_settings();
		}

		/* get report message */
		$report_message = '';
		if ( ! empty( $sbscrbr_handle_form_data ) ) {
			if ( 'unsubscribe_from_email' == $sbscrbr_handle_form_data->last_action && ! isset( $sbscrbr_display_message ) ) {
				$report_message          = $sbscrbr_handle_form_data->last_response;
				$sbscrbr_display_message = true;
			}
			if ( isset( $_POST['sbscrbr_submit_email'] ) && isset( $_POST['sbscrbr_form_id'] ) && 'sbscrbr_shortcode_' . $sbscrbr_shortcode_count === $_POST['sbscrbr_form_id'] ) {
				$report_message = $sbscrbr_handle_form_data->submit(
					isset( $_POST['sbscrbr_email'] ) ? sanitize_email( wp_unslash( $_POST['sbscrbr_email'] ) ) : '',
					( isset( $_POST['sbscrbr_unsubscribe'] ) && 'yes' == sanitize_text_field( wp_unslash( $_POST['sbscrbr_unsubscribe'] ) ) ) ? true : false
				);
			}
		}

		$content = '';

		if ( $sbscrbr_options['form_title_field'] && ! empty( $sbscrbr_options['form_title'] ) ) {
			$content .= '<h4>' . $sbscrbr_options['form_title'] . '</h4>';
		}

		$content .= '<form id="sbscrbr-form' . $form_id . '" method="post" action="' . $action_form . '" class="subscrbr-sign-up-form">';

		if ( $sbscrbr_options['form_label_field'] && ! empty( $sbscrbr_options['form_label'] ) ) {
			$content .= '<p class="sbscrbr-label-wrap">' . $sbscrbr_options['form_label'] . '</p>';
		}

		if ( ! empty( $report_message ) ) {
			$content .= $report_message['message'];
		}

		if ( 0 == $sbscrbr_options['form_one_line'] ) {
			$content .= '<p class="sbscrbr-email-wrap">
				<input type="text" name="sbscrbr_email" value="" placeholder="' . $sbscrbr_options['form_placeholder'] . '"/>
			</p>';
		}
		if ( 1 == $sbscrbr_options['form_one_line'] ) {
			$content .= '<div class="sbscrbr-block-one-line"><p class="sbscrbr-email-wrap sbscrbr-form-one-line">
				<span class="dashicons dashicons-email"></span><input type="text" name="sbscrbr_email" value="" placeholder="' . $sbscrbr_options['form_placeholder'] . '"/>
			</p>
			<p class="sbscrbr-submit-block sbscrbr-form-one-line" style="position: relative;">
				<input type="submit" value="' . $sbscrbr_options['form_button_label'] . '" name="sbscrbr_submit_email" class="submit" />
				<input type="hidden" value="sbscrbr_shortcode_' . $sbscrbr_shortcode_count . '" name="sbscrbr_form_id" />
			</p></div>';
		}
		$content .= '<p class="sbscrbr-unsubscribe-wrap">
				<label>
					<input id="sbscrbr-checkbox" type="checkbox" name="sbscrbr_unsubscribe" value="yes" style="vertical-align: middle;"/> ' .
					$sbscrbr_options['form_checkbox_label'] .
				'</label>
			</p>';
		if ( ! empty( $sbscrbr_options['gdpr'] ) ) {
			$content .= '<div class="sbscrbr_field_form">
				<p class="sbscrbr-GDPR-wrap">
					<label>
						<input id="sbscrbr-GDPR-checkbox" required type="checkbox" name="sbscrbr_GDPR" style="vertical-align: middle;"/>'
						. $sbscrbr_options['gdpr_cb_name'];
			if ( ! empty( $sbscrbr_options['gdpr_link'] ) ) {
				$content .= ' <a href="' . $sbscrbr_options['gdpr_link'] . '" target="_blank">' . $sbscrbr_options['gdpr_text'] . '</a>';
			} else {
				$content .= '<span> ' . $sbscrbr_options['gdpr_text'] . '</span>';
			}
					$content .= '</label>
				</p>';
		}
		$content = apply_filters( 'sbscrbr_add_field', $content, 'bws_subscriber' );
		if ( 0 == $sbscrbr_options['form_one_line'] ) {
			$content .= '<p class="sbscrbr-submit-block" style="position: relative;">
				<input type="submit" value="' . $sbscrbr_options['form_button_label'] . '" name="sbscrbr_submit_email" class="submit" />
				<input type="hidden" value="sbscrbr_shortcode_' . $sbscrbr_shortcode_count . '" name="sbscrbr_form_id" />
			</p>';
		}
		$content .= '</form>';
		return $content;
	}
}

if ( ! function_exists( 'sbscrbr_get_user_by_email' ) ) {
	/**
	 * The result of checking the existence email in Social login field
	 *
	 * @param string $email User email.
	 * @return object $user - WP_User | false.
	 */
	function sbscrbr_get_user_by_email( $email = false ) {
		$sbscrbr_email = apply_filters( 'sbscrbr_get_user_email', $email );

		$user = ( $sbscrbr_email ) ? get_user_by( 'email', $sbscrbr_email ) : false;

		if ( ! $user ) {
			return false;
		}

		$user = apply_filters( 'sbscrbr_get_user_by_email', $user, $email );

		return $user;
	}
}

if ( ! function_exists( 'sbscrbr_check_status' ) ) {
	/**
	 * Check user status
	 *
	 * @param string $email User e-mail.
	 * @return string User status.
	 */
	function sbscrbr_check_status( $email ) {
		global $wpdb;
		$prefix    = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		$user_data = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM `' . $prefix . 'sndr_mail_users_info` WHERE `user_email` = %s',
				trim( $email )
			),
			ARRAY_A
		);
		if ( empty( $user_data ) ) {
			return 'not_exists';
		} elseif ( '1' == $user_data['subscribe'] && '0' == $user_data['delete'] && '0' == $user_data['black_list'] ) {
			return 'subscribed';
		} elseif ( '0' == $user_data['subscribe'] && '0' == $user_data['delete'] && '0' == $user_data['black_list'] ) {
			return 'not_subscribed';
		} elseif ( '1' == $user_data['black_list'] && '0' == $user_data['delete'] ) {
			return 'in_black_list';
		} elseif ( '1' == $user_data['delete'] ) {
			return 'in_trash';
		}

		return '';
	}
}

if ( ! function_exists( 'sbscrbr_send_mails' ) ) {
	/**
	 * Function to send mails to administrator and to user
	 *
	 * @param string $email         User email.
	 * @param srting $user_password User password.
	 */
	function sbscrbr_send_mails( $email, $user_password ) {
		global $sbscrbr_options, $wpdb;
		$is_multisite = is_multisite();
		$headers = '';

		if ( empty( $sbscrbr_options ) ) {
			sbscrbr_settings();
		}

		$from_name  = ( empty( $sbscrbr_options['from_custom_name'] ) ) ? get_bloginfo( 'name' ) : $sbscrbr_options['from_custom_name'];
		if ( empty( $sbscrbr_options['from_email'] ) ) {
			$sitename = isset( $_SERVER['SERVER_NAME'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) ) : '';
			if ( 'www.' == substr( $sitename, 0, 4 ) ) {
				$sitename = substr( $sitename, 4 );
			}
			$from_email = 'wordpress@' . $sitename;
		} else {
			$from_email = $sbscrbr_options['from_email'];
		}

		$prefix = $is_multisite ? $wpdb->base_prefix : $wpdb->prefix;

		/* send message to user */
		if ( 1 == $sbscrbr_options['notification'] && 1 == $sbscrbr_options['user_message'] ) {
			$headers = 'From: ' . $from_name . ' <' . $from_email . '>';
			$subject = wp_specialchars_decode( $sbscrbr_options['subscribe_message_subject'], ENT_QUOTES );
			$line_break = "\n";

			if ( function_exists( 'sndr_replace_shortcodes' ) && 1 == $sbscrbr_options['subscribe_message_use_sender'] && ! empty( $sbscrbr_options['subscribe_message_sender_template_id'] ) ) {

				if ( $is_multisite ) {
					switch_to_blog( 1 );
				}
				$letter_data = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT *
						FROM `' . $wpdb->prefix . 'sndr_mail_send`
						WHERE `mail_send_id`= %d',
						$sbscrbr_options['subscribe_message_sender_template_id']
					),
					ARRAY_A
				);
				if ( $is_multisite ) {
					restore_current_blog();
				}

				if ( ! empty( $letter_data ) ) {
					$user_info = $wpdb->get_row(
						$wpdb->prepare(
							'SELECT `mail_users_info_id`, `id_user`, `user_display_name`, `unsubscribe_code`
							FROM `' . $prefix . 'sndr_mail_users_info`
							WHERE `user_email` = %s',
							$email
						),
						ARRAY_A
					);

					/* get neccessary data */
					$current_user_data = array(
						'id_user'           => ! empty( $user_info ) ? $user_info['mail_users_info_id'] : '',
						'user_email'        => $email,
						'user_display_name' => ! empty( $user_info ) ? $user_info['user_display_name'] : '',
						'unsubscribe_code'  => ! empty( $user_info ) ? $user_info['unsubscribe_code'] : '',
						'mailout_id'        => '',
					);
					$line_break = '<br />';

					$message = sndr_replace_shortcodes( $current_user_data, $letter_data );

					$subject    = $letter_data['subject'];
					$headers    = 'MIME-Version: 1.0' . "\n";
					$headers    .= 'Content-type: text/html; charset=utf-8' . "\n";
					$headers    .= 'From: ' . $from_name . ' <' . $from_email . ">\n";
				} else {
					$message = sbscrbr_replace_shortcodes( $sbscrbr_options['subscribe_message_text'], $email );
				}
			} else {
				$message = sbscrbr_replace_shortcodes( $sbscrbr_options['subscribe_message_text'], $email );
			}
			if ( ! empty( $user_password ) ) {
				$message .= $line_break . __( 'Your login:', 'subscriber' ) . ' ' . $email . $line_break . __( 'Your password:', 'subscriber' ) . ' ' . $user_password;
			}

			$message = wp_specialchars_decode( $message, ENT_QUOTES );

			wp_mail( $email, $subject, $message, $headers );
		}
		/* send message to admin */
		if ( 1 == $sbscrbr_options['notification'] && ( 1 == $sbscrbr_options['admin_message'] || 'custom' == $sbscrbr_options['to_email'] ) ) {
			$subject = wp_specialchars_decode( $sbscrbr_options['admin_message_subject'], ENT_QUOTES );

			if ( function_exists( 'sndr_replace_shortcodes' ) && 1 == $sbscrbr_options['admin_message_use_sender'] && ! empty( $sbscrbr_options['admin_message_sender_template_id'] ) ) {

				if ( $is_multisite ) {
					switch_to_blog( 1 );
				}
				$letter_data = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT *
						FROM `' . $wpdb->prefix . 'sndr_mail_send`
						WHERE `mail_send_id` = %d',
						$sbscrbr_options['admin_message_sender_template_id']
					),
					ARRAY_A
				);
				if ( $is_multisite ) {
					restore_current_blog();
				}

				if ( ! empty( $letter_data ) ) {
					if ( ! isset( $user_info ) ) {
						$user_info = $wpdb->get_row(
							$wpdb->prepare(
								'SELECT `mail_users_info_id`, `id_user`, `user_display_name`, `unsubscribe_code`
								FROM `' . $prefix . 'sndr_mail_users_info`
								WHERE `user_email` = %s',
								$email
							),
							ARRAY_A
						);
					}
					/* get neccessary data */
					$current_user_data = array(
						'id_user'           => ! empty( $user_info ) ? $user_info['mail_users_info_id'] : '',
						'user_email'        => $email,
						'user_display_name' => ! empty( $user_info ) ? $user_info['user_display_name'] : '',
						'unsubscribe_code'  => ! empty( $user_info ) ? $user_info['unsubscribe_code'] : '',
						'mailout_id'        => '',
					);

					$message = sndr_replace_shortcodes( $current_user_data, $letter_data );

					$subject    = $letter_data['subject'];
					$headers    = 'MIME-Version: 1.0' . "\n";
					$headers    .= 'Content-type: text/html; charset=utf-8' . "\n";
					$headers    .= 'From: ' . $from_name . ' <' . $from_email . ">\n";
				} else {
					$message = sbscrbr_replace_shortcodes( $sbscrbr_options['admin_message_text'], $email );
				}
			} else {
				$message = sbscrbr_replace_shortcodes( $sbscrbr_options['admin_message_text'], $email );
			}

			$email = array();
			if ( $sbscrbr_options['admin_message'] ) {
				$sbscrbr_userlogin = get_user_by( 'login', $sbscrbr_options['email_user'] );
				$email[] = $sbscrbr_userlogin->data->user_email;
			}

			if ( 'custom' == $sbscrbr_options['to_email'] ) {
				$email[] = $sbscrbr_options['email_custom'];
			}

			$message = wp_specialchars_decode( $message, ENT_QUOTES );
			$errors = 0;
			foreach ( $email as $value ) {
				if ( ! wp_mail( $value, $subject, $message, $headers ) ) {
					$errors ++;
				}
			}
			return $errors;
		}
	}
}

if ( ! function_exists( 'sbscrbr_sent_unsubscribe_mail' ) ) {
	/**
	 * Function to send unsubscribe link to user
	 *
	 * @param  string $email  User_email.
	 * @return array  $report Report message.
	 */
	function sbscrbr_sent_unsubscribe_mail( $email = '' ) {
		global $wpdb, $sbscrbr_options, $sbscrbr_send_unsubscribe_mail;
		$sbscrbr_send_unsubscribe_mail = '';
		$is_multisite = is_multisite();
		if ( empty( $sbscrbr_options ) ) {
			sbscrbr_settings();
		}
		$prefix = $is_multisite ? $wpdb->base_prefix : $wpdb->prefix;
		$report = array(
			'done'  => false,
			'error' => false,
		);
		$user_info = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT `mail_users_info_id`, `id_user`, `user_display_name`, `unsubscribe_code`
				FROM `' . $prefix . 'sndr_mail_users_info`
				WHERE `user_email` = %s',
				$email
			),
			ARRAY_A
		);
		if ( empty( $user_info ) ) {
			$report['error'] = $sbscrbr_options['cannot_get_email'];
		} else {
			$from_name  = ( empty( $sbscrbr_options['from_custom_name'] ) ) ? get_bloginfo( 'name' ) : $sbscrbr_options['from_custom_name'];
			if ( empty( $sbscrbr_options['from_email'] ) ) {
				$sitename = isset( $_SERVER['SERVER_NAME'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) ) : '';
				if ( 'www.' == substr( $sitename, 0, 4 ) ) {
					$sitename = substr( $sitename, 4 );
				}
				$from_email = 'wordpress@' . $sitename;
			} else {
				$from_email = $sbscrbr_options['from_email'];
			}

			$headers = 'From: ' . $from_name . ' <' . $from_email . '>';
			$subject = wp_specialchars_decode( $sbscrbr_options['unsubscribe_message_subject'], ENT_QUOTES );

			if ( function_exists( 'sndr_replace_shortcodes' ) && 1 == $sbscrbr_options['unsubscribe_message_use_sender'] && ! empty( $sbscrbr_options['unsubscribe_message_sender_template_id'] ) ) {

				if ( $is_multisite ) {
					switch_to_blog( 1 );
				}
				$letter_data = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT *
						FROM `' . $wpdb->prefix . 'sndr_mail_send`
						WHERE `mail_send_id`= %d',
						$sbscrbr_options['unsubscribe_message_sender_template_id']
					),
					ARRAY_A
				);
				if ( $is_multisite ) {
					restore_current_blog();
				}

				if ( ! empty( $letter_data ) ) {
					/* get neccessary data */
					$current_user_data = array(
						'id_user'           => ! empty( $user_info ) ? $user_info['mail_users_info_id'] : '',
						'user_email'        => $email,
						'user_display_name' => ! empty( $user_info ) ? $user_info['user_display_name'] : '',
						'unsubscribe_code'  => $user_info['unsubscribe_code'],
						'mailout_id'        => '',
					);

					$message = sndr_replace_shortcodes( $current_user_data, $letter_data );

					$subject    = $letter_data['subject'];
					$headers    = 'MIME-Version: 1.0' . "\n";
					$headers    .= 'Content-type: text/html; charset=utf-8' . "\n";
					$headers    .= 'From: ' . $from_name . ' <' . $from_email . ">\n";
				} else {
					$message = sbscrbr_replace_shortcodes( $sbscrbr_options['unsubscribe_message_text'], $email );
				}
			} else {
				$message = sbscrbr_replace_shortcodes( $sbscrbr_options['unsubscribe_message_text'], $email );
			}

			$message = wp_specialchars_decode( $message, ENT_QUOTES );

			if ( wp_mail( $email, $subject, $message, $headers ) ) {
				$sbscrbr_send_unsubscribe_mail = true;
				$report['done'] = 'check mail';
			} else {
				$report['error'] = $sbscrbr_options['cannot_send_email'];
			}
		}
		return $report;
	}
}

if ( ! function_exists( 'sbscrbr_unsubscribe_link' ) ) {
	/**
	 * Add unsubscribe link to mail
	 *
	 * @param string $message   Text of message.
	 * @param array  $user_data Subscriber data.
	 * @return string $message  Text of message with unsubscribe link.
	 */
	function sbscrbr_unsubscribe_link( $message, $user_data ) {
		global $sbscrbr_options;
		if ( empty( $sbscrbr_options ) ) {
			sbscrbr_settings();
		}
		if ( ! ( empty( $message ) && empty( $user_data['user_email'] ) ) ) {
			$message = $message . sbscrbr_replace_shortcodes( $sbscrbr_options['unsubscribe_link_text'], $user_data['user_email'] );
		}
		return $message;
	}
}

if ( ! function_exists( 'sbscrbr_replace_shortcodes' ) ) {
	/**
	 * Function to replace shortcodes in text of sended messages
	 *
	 * @param string $text  Text of message.
	 * @param string $email User e-mail.
	 * @return string $text Text of message.
	 */
	function sbscrbr_replace_shortcodes( $text, $email ) {
		global $wpdb, $sbscrbr_options;
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		$user_info = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT `mail_users_info_id`, `user_display_name`, `unsubscribe_code`
				FROM `' . $prefix . 'sndr_mail_users_info`
				WHERE `user_email`= %s',
				$email
			),
			ARRAY_A
		);
		if ( ! empty( $user_info ) ) {
			if ( 'url' == $sbscrbr_options['shortcode_link_type'] ) {
				$unsubscribe_link = $sbscrbr_options['shortcode_url'];
			} else {
				$unsubscribe_link = home_url( '/' );
			}
			$unsubscribe_link .= '?sbscrbr_unsubscribe=true&code=' . $user_info['unsubscribe_code'] . '&subscriber_id=' . $user_info['mail_users_info_id'];
			$profile_page     = admin_url( 'profile.php' );
			$text = preg_replace( '/\{unsubscribe_link\}/', $unsubscribe_link, $text );
			$text = preg_replace( '/\{profile_page\}/', $profile_page, $text );
			$text = preg_replace( '/\{user_email\}/', $email, $text );
		}
		return $text;
	}
}

if ( ! function_exists( 'sbscrbr_register_user' ) ) {
	/**
	 * Function register of users
	 *
	 * @param int $user_id User ID.
	 */
	function sbscrbr_register_user( $user_id ) {
		global $wpdb;
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		$wpdb->update(
			$prefix . 'sndr_mail_users_info',
			array(
				'unsubscribe_code' => MD5( wp_generate_password() ),
				'subscribe_time' => time(),
			),
			array( 'id_user' => $user_id )
		);
	}
}

if ( ! function_exists( 'sbscrbr_delete_user' ) ) {
	/**
	 * Delete a subscriber from a subscibers DB if the user deleted from dashboard users page
	 *
	 * @param int $user_id User ID.
	 */
	function sbscrbr_delete_user( $user_id ) {
		global $wpdb;
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM `' . $prefix . 'sndr_mail_users_info`
				WHERE `id_user` = %d',
				$user_id
			)
		);
	}
}

if ( ! function_exists( 'sbscrbr_mail_send' ) ) {
	/**
	 * Function to show "subscribe" checkbox for users.
	 *
	 * @param array $user User data.
	 */
	function sbscrbr_mail_send( $user ) {
		global $wpdb, $current_user, $sbscrbr_options;
		if ( empty( $sbscrbr_options ) ) {
			sbscrbr_settings();
		}
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		/* deduce form the subscribe */
		$current_user = wp_get_current_user();
		$mail_message = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT `subscribe`, `black_list`
				FROM `' . $prefix . 'sndr_mail_users_info`
				WHERE `id_user` = %d',
				$current_user->ID
			),
			ARRAY_A
		);
		$disabled     = ( 1 == $mail_message['black_list'] ) ? 'disabled="disabled"' : '';
		$confirm      = ( ( 1 == $mail_message['subscribe'] ) && ( empty( $disabled ) ) ) ? 'checked="checked"' : '';
		?>
		<table class="form-table" id="mail_user">
			<tr>
				<th><?php esc_html_e( 'Subscribe on newsletters', 'subscriber' ); ?> </th>
				<td>
					<input type="checkbox" name="sbscrbr_mail_subscribe" <?php echo esc_html( $confirm ); ?> <?php echo esc_html( $disabled ); ?> value="1"/>
					<?php
					if ( ! empty( $disabled ) ) {
						echo '<span class="description">' . wp_kses_post( $sbscrbr_options['denied_subscribe'] ) . '</span>';
					}
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}

if ( ! function_exists( 'sbscrbr_update' ) ) {
	/**
	 * Function update user data
	 *
	 * @param int   $user_id       User ID.
	 * @param array $old_user_data Old user data array.
	 */
	function sbscrbr_update( $user_id, $old_user_data ) {
		global $wpdb, $current_user;
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		if ( ! function_exists( 'get_userdata' ) ) {
			require_once( ABSPATH . 'wp-includes/pluggable.php' );
		}
		$current_user = get_userdata( $user_id );
		$user_exists = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT `id_user`
				FROM `' . $prefix . 'sndr_mail_users_info`
				WHERE `id_user`= %d',
				$current_user->ID
			)
		);

		if ( $user_exists ) {
			$subscriber = ( isset( $_POST['sbscrbr_mail_subscribe'] ) && 1 === absint( $_POST['sbscrbr_mail_subscribe'] ) ) ? '1' : '0';
			$wpdb->update(
				$prefix . 'sndr_mail_users_info',
				array(
					'user_email'        => $current_user->user_email,
					'user_display_name' => $current_user->display_name,
					'subscribe'         => $subscriber,
				),
				array(
					'id_user' => $current_user->ID,
					'user_email' => $old_user_data->user_email,
				)
			);
		} else {
			if ( isset( $_POST['sbscrbr_mail_subscribe'] ) && 1 === absint( $_POST['sbscrbr_mail_subscribe'] ) ) {
				$wpdb->insert(
					$prefix . 'sndr_mail_users_info',
					array(
						'id_user'           => $current_user->ID,
						'user_email'        => $current_user->user_email,
						'user_display_name' => $current_user->display_name,
						'subscribe'         => 1,
					)
				);
			}
		}
	}
}

if ( ! function_exists( 'sbscrbr_add_tabs' ) ) {
	/**
	 * Display screen options on 'Subscribers' page
	 */
	function sbscrbr_add_tabs() {
		sbscrbr_help_tab();
		if ( isset( $_GET['page'] ) && 'subscriber-users.php' == $_GET['page'] ) {
			require_once( dirname( __FILE__ ) . '/includes/pro-tab.php' );
			sbscrbr_screen_options();
		}
	}
}

if ( ! function_exists( 'sbscrbr_screen_options' ) ) {
	/**
	 * Add screen options
	 */
	function sbscrbr_screen_options() {
		$option = 'per_page';
		$args   = array(
			'label'   => __( 'users per page', 'subscriber' ),
			'default' => 30,
			'option'  => 'subscribers_per_page',
		);
		add_screen_option( $option, $args );
	}
}

if ( ! function_exists( 'sbscrbr_help_tab' ) ) {
	/**
	 * Add help tab
	 */
	function sbscrbr_help_tab() {
		$screen = get_current_screen();
		$args = array(
			'id'            => 'sbscrbr',
			'section'       => '200538739',
		);
		bws_help_tab( $screen, $args );
	}
}

if ( ! function_exists( 'sbscrbr_table_set_option' ) ) {
	/**
	 * Function to save and load settings from screen options
	 *
	 * @param string $status Status.
	 * @param array  $option Option.
	 * @param string $value  Value.
	 */
	function sbscrbr_table_set_option( $status, $option, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'sbscrbr_report_actions' ) ) {
	/**
	 * Function to handle actions from "Subscribers" page
	 *
	 * @return array with messages about action results
	 */
	function sbscrbr_report_actions() {
		$action_message = array(
			'error' => false,
			'done'  => false,
		);

		if ( ( isset( $_REQUEST['page'] ) && 'subscriber-users.php' == $_REQUEST['page'] ) && ( isset( $_REQUEST['action'] ) || isset( $_REQUEST['action2'] ) ) ) {
			global $wpdb;
			$prefix  = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
			$counter = 0;
			$errors  = 0;
			$result  = 0;
			$user_id = null;
			$action  = null;

			$user_status  = isset( $_REQUEST['users_status'] ) ? '&users_status=' . sanitize_text_field( wp_unslash( $_REQUEST['users_status'] ) ) : '';
			$message_list = array(
				'unknown_action'     => __( 'Unknown action.', 'subscriber' ),
				'users_not_selected' => __( 'Select the users to apply the necessary actions.', 'subscriber' ),
				'not_updated'        => __( 'No user was updated.', 'subscriber' ),
			);
			if ( isset( $_REQUEST['action'] ) && '-1' != sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) ) {
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
			} elseif ( isset( $_REQUEST['action2'] ) && '-1' != sanitize_text_field( wp_unslash( $_REQUEST['action2'] ) ) ) {
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action2'] ) );
			}
			if ( ! empty( $action ) ) {
				switch ( $action ) {
					case 'subscribe_users':
					case 'subscribe_user':
						$result = sbscrbr_admin_referer( $action, 'sbscrbr_subscribe_users' );
						if ( true === $result ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								$user_ids = sbscrbr_request_get_users();
								foreach ( $user_ids as $id ) {
									$result = $wpdb->update(
										$prefix . 'sndr_mail_users_info',
										array(
											'subscribe'      => 1,
											'subscribe_time' => time(),
										),
										array(
											'mail_users_info_id'   => $id,
											'subscribe' => 0,
										)
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
										$user_id .= empty( $user_id ) ? $id : ',' . $id;
									}
								}
								if ( ! empty( $counter ) ) {
									$action_message['done'] = sprintf( _n( 'One user was subscribed on newsletter.', '%s users were subscribed on newsletter.', $counter, 'subscriber' ), number_format_i18n( $counter ) ) . ' <a href="' . esc_url( wp_nonce_url( '?page==subscriber-users.php&action=unsubscribe_users&user_id=' . $user_id . $user_status, 'sbscrbr_unsubscribe_users' . $user_id ) ) . '">' . __( 'Undo.', 'subscriber' ) . '</a>';
								} else {
									$action_message['error'] = $message_list['not_updated'];
								}
							}
						}
						break;
					case 'unsubscribe_users':
					case 'unsubscribe_user':
						$result = sbscrbr_admin_referer( $action, 'sbscrbr_unsubscribe_users' );
						if ( true === $result ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								$user_ids = sbscrbr_request_get_users();
								foreach ( $user_ids as $id ) {
									$result = $wpdb->update(
										$prefix . 'sndr_mail_users_info',
										array(
											'subscribe'        => 0,
											'unsubscribe_time' => time(),
										),
										array(
											'mail_users_info_id'   => $id,
											'subscribe' => 1,
										)
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
										$user_id .= empty( $user_id ) ? $id : ',' . $id;
									}
								}
								if ( ! empty( $counter ) ) {
									$action_message['done'] = sprintf( _n( 'One user was unsubscribed from newsletter.', '%s users were unsubscribed from newsletter.', $counter, 'subscriber' ), number_format_i18n( $counter ) ) . ' <a href="' . esc_url( wp_nonce_url( '?page=subscriber-users.php&action=subscribe_users&user_id=' . $user_id . $user_status, 'sbscrbr_subscribe_users' . $user_id ) ) . '">' . __( 'Undo.', 'subscriber' ) . '</a>';
								} else {
									$action_message['error'] = $message_list['not_updated'];
								}
							}
						}
						break;
					case 'to_black_list_users':
					case 'to_black_list_user':
						$result = sbscrbr_admin_referer( $action, 'sbscrbr_to_black_list_users' );
						if ( true === $result ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								$user_ids = sbscrbr_request_get_users();
								foreach ( $user_ids as $id ) {
									$result = $wpdb->update(
										$prefix . 'sndr_mail_users_info',
										array(
											'black_list' => 1,
											'delete'     => 0,
										),
										array(
											'mail_users_info_id' => $id,
										)
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
										$user_id .= empty( $user_id ) ? $id : ',' . $id;
									}
								}
								if ( ! empty( $counter ) ) {
									$action_message['done'] = sprintf( _n( 'One user was moved to black list.', '%s users were moved to black list.', $counter, 'subscriber' ), number_format_i18n( $counter ) ) . ' <a href="' . esc_url( wp_nonce_url( '?page=subscriber-users.php&action=restore_from_black_list_users&user_id=' . $user_id . $user_status, 'sbscrbr_restore_from_black_list_users' . $user_id ) ) . '">' . __( 'Undo.', 'subscriber' ) . '</a>';
								} else {
									$action_message['error'] = $message_list['not_updated'];
								}
							}
						}
						break;
					case 'restore_from_black_list_users':
					case 'restore_from_black_list_user':
						$result = sbscrbr_admin_referer( $action, 'sbscrbr_restore_from_black_list_users' );
						if ( true === $result ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								$user_ids = sbscrbr_request_get_users();
								foreach ( $user_ids as $id ) {
									$result = $wpdb->update(
										$prefix . 'sndr_mail_users_info',
										array( 'black_list' => 0 ),
										array( 'mail_users_info_id' => $id )
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
										$user_id .= empty( $user_id ) ? $id : ',' . $id;
									}
								}
								if ( ! empty( $counter ) ) {
									$action_message['done'] = sprintf( _n( 'One user was restored from black list.', '%s users were restored from black list.', $counter, 'subscriber' ), number_format_i18n( $counter ) ) . ' <a href="' . esc_url( wp_nonce_url( '?page=subscriber-users.php&action=to_black_list_users&user_id=' . $user_id . $user_status, 'sbscrbr_to_black_list_users' . $user_id ) ) . '">' . __( 'Undo.', 'subscriber' ) . '</a>';
								} else {
									$action_message['error'] = $message_list['not_updated'];
								}
							}
						}
						break;
					case 'trash_users':
					case 'trash_user':
						$result = sbscrbr_admin_referer( $action, 'sbscrbr_trash_users' );
						if ( true === $result ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								$user_ids = sbscrbr_request_get_users();
								foreach ( $user_ids as $id ) {
									$result = $wpdb->update(
										$prefix . 'sndr_mail_users_info',
										array( 'delete' => 1 ),
										array( 'mail_users_info_id' => $id )
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
										$user_id .= empty( $user_id ) ? $id : ',' . $id;
									}
								}
								if ( ! empty( $counter ) ) {
									$previous_action        = preg_match( '/black_list/', $user_status ) ? 'to_black_list_users' : 'restore_users';
									$action_message['done'] = sprintf( _n( 'One user was moved to trash.', '%s users were moved to trash.', $counter, 'subscriber' ), number_format_i18n( $counter ) ) . ' <a href="' . esc_url( wp_nonce_url( '?page=subscriber-users.php&action=' . $previous_action . '&user_id=' . $user_id . $user_status, 'sbscrbr_' . $previous_action . $user_id ) ) . '">' . __( 'Undo.', 'subscriber' ) . '</a>';
								} else {
									$action_message['error'] = $message_list['not_updated'];
								}
							}
						}
						break;
					case 'delete_users':
					case 'delete_user':
						$result = sbscrbr_admin_referer( $action, 'sbscrbr_delete_users' );
						if ( true === $result ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								$user_ids = sbscrbr_request_get_users();
								foreach ( $user_ids as $id ) {
									$result = $wpdb->query(
										$wpdb->prepare(
											'DELETE FROM `' . $prefix . 'sndr_mail_users_info`
											WHERE `mail_users_info_id`= %d',
											$id
										)
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
									}
								}
								if ( ! empty( $counter ) ) {
									$action_message['done'] = sprintf( _n( 'One user was deleted permanently.', '%s users were deleted permanently.', $counter, 'subscriber' ), number_format_i18n( $counter ) );
								} else {
									$action_message['error'] = $message_list['not_updated'];
								}
							}
						}
						break;
					case 'restore_users':
					case 'restore_user':
						$result = sbscrbr_admin_referer( $action, 'sbscrbr_list_nonce_name', 'sbscrbr_restore_users' );
						if ( true === $result ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								$user_ids = sbscrbr_request_get_users();
								foreach ( $user_ids as $id ) {
									$result = $wpdb->update(
										$prefix . 'sndr_mail_users_info',
										array( 'delete' => 0 ),
										array( 'mail_users_info_id' => $id )
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
										$user_id .= empty( $user_id ) ? $id : ',' . $id;
									}
								}
								if ( ! empty( $counter ) ) {
									$action_message['done'] = sprintf( _n( 'One user was restored.', '%s users were restored.', $counter, 'subscriber' ), number_format_i18n( $counter ) ) . ' <a href="' . esc_url( wp_nonce_url( '?page=subscriber-users.php&action=trash_users&user_id=' . $user_id . $user_status, 'sbscrbr_trash_users' . $user_id ) ) . '">' . __( 'Undo.', 'subscriber' ) . '</a>';
								} else {
									$action_message['error'] = $message_list['not_updated'];
								}
							}
						}
						break;
					default:
						$action_message['error'] = $message_list['unknown_action'];
						break;
				}
			}
		}
		return $action_message;
	}
}

if ( ! function_exists( 'sbscrbr_admin_referer' ) ) {
	/**
	 * Check admin referer for action
	 *
	 * @param string $action  Action for check.
	 * @param string $referer Rreferer string for check.
	 * @return bool true if admin referer is correct.
	 */
	function sbscrbr_admin_referer( $action, $referer ) {
		$result = false;
		if ( isset( $_POST['action'] ) || isset( $_POST['action2'] ) ) {
			if ( sanitize_text_field( wp_unslash( $_POST['action'] ) ) == $action || sanitize_text_field( wp_unslash( $_POST['action2'] ) ) == $action ) {
				$result = check_admin_referer( plugin_basename( __FILE__ ), 'sbscrbr_list_nonce_name' );
			}
		} elseif ( isset( $_GET['action'] ) ) {
			if ( sanitize_text_field( wp_unslash( $_GET['action'] ) ) == $action && isset( $_REQUEST['user_id'] ) && check_admin_referer( $referer . absint( $_REQUEST['user_id'] ) ) ) {
				$result = true;
			}
		}
		return $result;
	}
}

if ( ! function_exists( 'sbscrbr_request_get_users' ) ) {
	/**
	 * Check users from request
	 *
	 * @return array $users Array of users.
	 */
	function sbscrbr_request_get_users() {
		$users = array();
		if ( isset( $_REQUEST['user_id'] ) && is_array( $_REQUEST['user_id'] ) ) {
			$user_ids = $_REQUEST['user_id'];
			array_walk( $user_ids, 'absint' );
		} else {
			if ( isset( $_REQUEST['user_id'] ) && preg_match( '|,|', sanitize_text_field( wp_unslash( $_REQUEST['user_id'] ) ) ) ) {
				$user_ids = explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['user_id'] ) ) );
			} elseif ( isset( $_REQUEST['user_id'] ) ) {
				$user_ids[0] = absint( $_REQUEST['user_id'] );
			}
		}
		return $users;
	}
}

if ( ! function_exists( 'sbscrbr_check_plugin_install' ) ) {
	/**
	 * Check if plugin is installed
	 *
	 * @param array $plugins_array Array with plugins.
	 * @return bool true if Sender is installed
	 */
	function sbscrbr_check_plugin_install( $plugins_array ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugins_list = get_plugins();
		foreach ( $plugins_array as $plugin ) {
			if ( array_key_exists( $plugin, $plugins_list ) ) {
				return true;
			}
		}
		return false;
	}
}


if ( ! function_exists( 'sbscrbr_plugin_action_links' ) ) {
	/**
	 * Add action links on plugin page in to Plugin Name block
	 *
	 * @param array  $links Action links.
	 * @param string $file  Relative path to pugin "subscriber/subscriber.php".
	 * @return array $links Action links.
	 */
	function sbscrbr_plugin_action_links( $links, $file ) {
		/* Static so we don't call plugin_basename on every plugin row. */
		if ( ! is_multisite() || is_network_admin() ) {
			static $this_plugin;
			if ( ! $this_plugin ) {
				$this_plugin = plugin_basename( __FILE__ );
			}

			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=subscriber.php">' . __( 'Settings', 'subscriber' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

if ( ! function_exists( 'sbscrbr_register_plugin_links' ) ) {
	/**
	 * Add action links on plugin page in to Plugin Description block
	 *
	 * @param array  $links Action links.
	 * @param string $file  Relative path to pugin "subscriber/subscriber.php".
	 * @return array $links Action links.
	 */
	function sbscrbr_register_plugin_links( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			if ( ( is_multisite() && is_network_admin() ) || ( ! is_multisite() && is_admin() ) ) {
				$links[] = '<a href="admin.php?page=subscriber.php">' . __( 'Settings', 'subscriber' ) . '</a>';
			}
			$links[] = '<a href="https://support.bestwebsoft.com/hc/en-us/sections/200538739" target="_blank">' . __( 'FAQ', 'subscriber' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'subscriber' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists( 'sbscrbr_show_notices' ) ) {
	/**
	 * Display notice for admin page
	 **/
	function sbscrbr_show_notices() {
		global $hook_suffix, $sbscrbr_plugin_info;

		if ( 'plugins.php' == $hook_suffix ) {
			bws_plugin_banner_to_settings( $sbscrbr_plugin_info, 'sbscrbr_options', 'subscriber', 'admin.php?page=subscriber.php' );

			if ( is_multisite() && ! is_network_admin() && is_admin() ) {
				?>
				<div class="update-nag"><strong><?php esc_html_e( 'Notice:', 'subscriber' ); ?></strong>
					<?php
					if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
						esc_html_e( 'Due to the peculiarities of the multisite work, Subscriber plugin has only', 'subscriber' );
						?>
						<a target="_blank" href="<?php echo esc_url( network_admin_url( 'admin.php?page=subscriber.php' ) ); ?>"><?php esc_html_e( 'Network settings page', 'subscriber' ); ?></a>
						<?php
					} else {
						esc_html_e( 'Due to the peculiarities of the multisite work, Subscriber plugin has the network settings page only and it should be Network Activated. Please', 'subscriber' );
						?>
						<a target="_blank" href="<?php echo esc_url( network_admin_url( 'plugins.php' ) ); ?>"><?php esc_html_e( 'Activate Subscriber for Network', 'subscriber' ); ?></a>
					<?php } ?>
				</div>
				<?php
			}
		}

		if ( isset( $_REQUEST['page'] ) && 'subscriber.php' == $_REQUEST['page'] ) {
			bws_plugin_suggest_feature_banner( $sbscrbr_plugin_info, 'sbscrbr_options', 'subscriber' );
		}
	}
}

if ( ! function_exists( 'sbscrbr_shortcode_button_content' ) ) {
	/**
	 * Add shortcode content
	 *
	 * @param string $content Content for shortcode.
	 */
	function sbscrbr_shortcode_button_content( $content ) {
		?>
		<div id="sbscrbr" style="display:none;">
			<input class="bws_default_shortcode" type="hidden" name="default" value="[sbscrbr_form]" />
			<div class="clear"></div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'sbscrbr_get_data_objects' ) ) {
	/**
	 * Get subscriber data objects
	 */
	function sbscrbr_get_data_objects() {
		global $sbscrbr_options, $sbscrbr_handle_form_data, $wp, $sbscrbr_display_message;

		$report_message = '';
		$form_label     = '';
		$email_wrap     = '';
		$gdpr           = '';

		if ( empty( $sbscrbr_options ) ) {
			sbscrbr_settings();
		}

		if ( ! class_exists( 'Sbscrbr_Handle_Form_Data' ) ) {
			require_once( dirname( __FILE__ ) . '/includes/class-sbscrbr-handle-form-data.php' );
		}

		/* Count subscriber forms */
		static $sbscrbr_count;
		$sbscrbr_count = ( empty( $sbscrbr_count ) ) ? 1 : $sbscrbr_count + 1;

		$form_id = 1 === $sbscrbr_count ? '' : '-' . $sbscrbr_count;

		/*  get form with id */
		$action_form = '#sbscrbr-form' . $form_id;
		$form = '<form id="sbscrbr-form' . $form_id . '" method="post" action="' . $action_form . '" class="subscrbr-sign-up-form">';

		$sbscrbr_handle_form_data = new Sbscrbr_Handle_Form_Data();

		/* Get report message */
		if ( ( 'unsubscribe_from_email' == $sbscrbr_handle_form_data->last_action || 'subscribe_from_email' == $sbscrbr_handle_form_data->last_action ) && ! isset( $sbscrbr_display_message ) ) {
			$report_message = $sbscrbr_handle_form_data->last_response;
			$sbscrbr_display_message = true;
		}
		if ( isset( $_POST['sbscrbr_submit_email'] ) && isset( $_POST['sbscrbr_form_id'] ) && sanitize_text_field( wp_unslash( $_POST['sbscrbr_form_id'] ) ) === 'sbscrbr_shortcode_' . $sbscrbr_count ) {
			$report_message = $sbscrbr_handle_form_data->submit( sanitize_text_field( wp_unslash( $_POST['sbscrbr_email'] ) ), ( isset( $_POST['sbscrbr_name'] ) ) ? sanitize_text_field( wp_unslash( $_POST['sbscrbr_name'] ) ) : '', ( isset( $_POST['sbscrbr_unsubscribe'] ) && 'yes' == sanitize_text_field( wp_unslash( $_POST['sbscrbr_unsubscribe'] ) ) ) ? true : false, 'sbscrbr_shortcode' );
		}

		/* Get form label */
		if ( empty( $report_message ) ) {
			if ( ! empty( $sbscrbr_options['form_label'] ) ) {
				$form_label = '<p class="sbscrbr-label-wrap">' . $sbscrbr_options['form_label'] . '</p>';
			}
		} else {
			$form_label = $report_message['message'];
		}

		/* Output email-wrap in one line or not */
		if ( $sbscrbr_options['form_one_line'] ) {
			$email_wrap =
				'<div class="sbscrbr-block-one-line">
					<p class="sbscrbr-email-wrap sbscrbr-form-one-line">
						<span class="dashicons dashicons-email"></span>
						<input type="text" name="sbscrbr_email" value="" placeholder="' . $sbscrbr_options['form_placeholder'] . '"/>
					</p>
					<p class="sbscrbr-submit-block sbscrbr-form-one-line" style="position: relative;">
						<input type="submit" value="' . $sbscrbr_options['form_button_label'] . '" name="sbscrbr_submit_email" class="submit" />
						<input type="hidden" value="sbscrbr_shortcode_' . $sbscrbr_count . '" name="sbscrbr_form_id" />
					</p>
				</div>';
		} else {
			$email_wrap =
				'<p class="sbscrbr-email-wrap">
					<input type="text" name="sbscrbr_email" value="" placeholder="' . $sbscrbr_options['form_placeholder'] . '"/>
				</p>
				<p class="sbscrbr-submit-block" style="position: relative;">
					<input type="submit" value="' . $sbscrbr_options['form_button_label'] . '" name="sbscrbr_submit_email" class="submit" />
					<input type="hidden" value="sbscrbr_shortcode_' . $sbscrbr_count . '" name="sbscrbr_form_id" />
				</p>';
		}

		/* Gget form checkbox label */
		$form_checkbox_label =
			'<p class="sbscrbr-unsubscribe-wrap">
				<label>
					<input id="sbscrbr-checkbox" type="checkbox" name="sbscrbr_unsubscribe" value="yes" style="vertical-align:middle;"/>' . $sbscrbr_options['form_checkbox_label'] .
			'</label>
			</p>';

		/* Get gdpr */
		if ( ! empty( $sbscrbr_options['gdpr'] ) ) {
			$gdpr =
				'<div class="sbscrbr_field_form">
					<p class="sbscrbr-GDPR-wrap">
						<label>
							<input id="sbscrbr-GDPR-checkbox" required type="checkbox" name="sbscrbr_GDPR" style="vertical-align:middle;"/>' . $sbscrbr_options['gdpr_cb_name'];
			if ( ! empty( $sbscrbr_options['gdpr_link'] ) ) {
				$gdpr .= ' <a href="' . $sbscrbr_options['gdpr_link'] . '" target="_blank">' . $sbscrbr_options['gdpr_text'] . '</a>';
			} else {
				$gdpr .= '<span> ' . $sbscrbr_options['gdpr_text'] . '</span>';
			}
			$gdpr .= '</label>
					</p>
				</div>';
		}

		$subscriber_array = array(
			'form'                  => $form,
			'report_message'        => $report_message,
			'form_label'            => $form_label,
			'email_wrap'            => $email_wrap,
			'form_checkbox_label'   => $form_checkbox_label,
			'gdpr'                  => $gdpr,
		);

		$subscriber_objects = (object) $subscriber_array;

		return $subscriber_objects;
	}
}

if ( ! function_exists( 'sbscrbr_uninstall' ) ) {
	/**
	 * Function is called during deinstallation of plugin
	 */
	function sbscrbr_uninstall() {
		require_once( ABSPATH . 'wp-includes/user.php' );
		global $wpdb, $sbscrbr_options;
		$all_plugins = get_plugins();

		if ( ! array_key_exists( 'subscriber-pro/subscriber-pro.php', $all_plugins ) ) {

			if ( empty( $sbscrbr_options ) ) {
				$sbscrbr_options = is_multisite() ? get_site_option( 'sbscrbr_options' ) : get_option( 'sbscrbr_options' );
			}

			$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
			/* delete tables from database, users with role Mail Subscriber */
			$sbscrbr_sender_installed = sbscrbr_check_plugin_install( array( 'sender/sender.php', 'sender-pro/sender-pro.php' ) );

			if ( $sbscrbr_sender_installed ) { /* if Sender plugin installed */
				$wpdb->query(
					'ALTER TABLE `' . $prefix . 'sndr_mail_users_info`
					DROP COLUMN `unsubscribe_code`,
					DROP COLUMN `subscribe_time`,
					DROP COLUMN `unsubscribe_time`,
					DROP COLUMN `black_list`,
					DROP COLUMN `delete`;'
				);
			} else {
				$wpdb->query( 'DROP TABLE `' . $prefix . 'sndr_mail_users_info`' );
				if ( '1' == $sbscrbr_options['delete_users'] ) {
					$args       = array( 'role' => 'sbscrbr_subscriber' );
					$role       = get_role( $args['role'] );
					$users_list = get_users( $args );
					if ( ! empty( $users_list ) ) {
						foreach ( $users_list as $user ) {
							wp_delete_user( $user->ID );
						}
					}
					if ( ! empty( $role ) ) {
						remove_role( 'sbscrbr_subscriber' );
					}
				}
			}
			/* delete plugin options */
			if ( is_multisite() ) {
				delete_site_option( 'sbscrbr_options' );
			} else {
				delete_option( 'sbscrbr_options' );
			}
		}

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

if ( ! function_exists( 'sbscrbr_get_plugin_info' ) ) {
	/**
	 * Get Plugin Info
	 */
	function sbscrbr_get_plugin_info() {
		global $sbscrbr_plugin_info;

		if ( empty( $sbscrbr_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$sbscrbr_plugin_info = get_plugin_data( __FILE__ );
		}
	}
}

/**
 *  Add all hooks
 */
if ( function_exists( 'is_multisite' ) ) {
	/* Add plugin pages admin panel */
	if ( is_multisite() ) {
		add_action( 'network_admin_menu', 'sbscrbr_add_admin_menu' );
	} else {
		add_action( 'admin_menu', 'sbscrbr_add_admin_menu' );
	}
}
/* initialization */
add_action( 'plugins_loaded', 'sbscrbr_plugins_loaded' );

add_action( 'init', 'sbscrbr_init', 9 );
add_action( 'admin_init', 'sbscrbr_admin_init' );
/* Include js- and css-files  */
add_action( 'admin_enqueue_scripts', 'sbscrbr_admin_head' );
add_action( 'wp_enqueue_scripts', 'sbscrbr_load_styles' );
add_action( 'wp_footer', 'sbscrbr_load_scripts' );
/* Add "subscribe"-checkbox on user profile page */
if ( ! function_exists( 'sndr_mail_send' ) && ! function_exists( 'sndr_mail_send' ) ) {
	add_action( 'profile_personal_options', 'sbscrbr_mail_send' );
	add_action( 'profile_update', 'sbscrbr_update', 10, 2 );
}
/* Register widget */
add_action( 'widgets_init', 'sbscrbr_widgets_init' );
/* Register shortcode */
add_shortcode( 'sbscrbr_form', 'sbscrbr_subscribe_form' );
add_filter( 'widget_text', 'do_shortcode' );
/* Add unsubscribe link to the each letter from mailout */
add_filter( 'sbscrbr_add_unsubscribe_link', 'sbscrbr_unsubscribe_link', 10, 2 );
/* Add unsubscribe code and time, when user was registered */
add_action( 'user_register', 'sbscrbr_register_user' );
/* Delete a subscriber, when user was deleted */
add_action( 'delete_user', 'sbscrbr_delete_user' );
/* Add screen options on Subscribers List Page */
add_filter( 'set-screen-option', 'sbscrbr_table_set_option', 10, 3 );
/* Display additional links on plugins list page */
add_filter( 'plugin_action_links', 'sbscrbr_plugin_action_links', 10, 2 );
if ( function_exists( 'is_multisite' ) ) {
	if ( is_multisite() ) {
		add_filter( 'network_admin_plugin_action_links', 'sbscrbr_plugin_action_links', 10, 2 );
	}
}
add_filter( 'plugin_row_meta', 'sbscrbr_register_plugin_links', 10, 2 );

add_action( 'admin_notices', 'sbscrbr_show_notices' );

/* Custom filter for bws button in tinyMCE */
add_filter( 'bws_shortcode_button_content', 'sbscrbr_shortcode_button_content' );

register_uninstall_hook( __FILE__, 'sbscrbr_uninstall' );

