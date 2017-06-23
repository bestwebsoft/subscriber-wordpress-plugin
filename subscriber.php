<?php
/*
Plugin Name: Subscriber by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/subscriber/
Description: Add email newsletter sign up form to WordPress posts, pages and widgets. Collect data and subscribe your users.
Author: BestWebSoft
Text Domain: subscriber
Domain Path: /languages
Version: 1.3.6
Author URI: https://bestwebsoft.com/
License: GPLv2 or later
*/

/*  © Copyright 2017 BestWebSoft  ( https://support.bestwebsoft.com )

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

/**
 * Function add menu pages
 * @return void
 */

if ( ! function_exists( 'sbscrbr_admin_menu' ) ) {
	function sbscrbr_admin_menu() {
		bws_general_menu();
		$settings = add_submenu_page( 'bws_panel', 'Subscriber', 'Subscriber', 'manage_options', 'sbscrbr_settings_page', 'sbscrbr_settings_page' );
		add_action( 'load-' . $settings, 'sbscrbr_add_tabs' );
	}
}

if ( ! function_exists( 'sbscrbr_plugins_loaded' ) ) {
	function sbscrbr_plugins_loaded() {
		/* load textdomain of plugin */
		load_plugin_textdomain( 'subscriber', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

/**
 * Plugin initialisation in backend and frontend
 * @return void
 */
if ( ! function_exists( 'sbscrbr_init' ) ) {
	function sbscrbr_init() {
		global $sbscrbr_plugin_info, $sbscrbr_options, $sbscrbr_handle_form_data;

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );

		if ( empty( $sbscrbr_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$sbscrbr_plugin_info = get_plugin_data( __FILE__ );
		}

		/* check version on WordPress */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $sbscrbr_plugin_info, '3.9' );

		/* add new user role */
		$capabilities = array(
			'read'         => true,
			'edit_posts'   => false,
			'delete_posts' => false
		);
		add_role( 'sbscrbr_subscriber', __( 'Mail Subscriber', 'subscriber' ), $capabilities );

		/* register plugin settings */
		if ( ! is_admin() || ( isset( $_GET['page'] ) && 'sbscrbr_settings_page' == $_GET['page'] ) )
			sbscrbr_settings();

		/* unsubscribe users from mailout if Subscribe Form  not displayed on home page */
		if ( ! is_admin() ) {
			$sbscrbr_handle_form_data = new Sbscrbr_Handle_Form_Data();
			if ( isset( $_GET['sbscrbr_unsubscribe'] ) && isset( $_GET['code'] ) &&  isset( $_GET['id'] ) ) {
				global $sbscrbr_response;
				$sbscrbr_response = $sbscrbr_handle_form_data->unsubscribe_from_email( $_GET['sbscrbr_unsubscribe'], $_GET['code'], $_GET['id'] );
				if ( 'url' != $sbscrbr_options['shortcode_link_type'] && ! empty( $sbscrbr_response['message'] ) ) {
					$sbscrbr_response['title'] = __( 'Unsubscribe Confirmation', 'subscriber' );
					$sbscrbr_handle_form_data->last_response = array();
					add_action( 'template_redirect', 'sbscrbr_template_redirect' );
					add_action( 'the_posts', 'sbscrbr_the_posts' );
				}
			}
		}
		if ( empty( $sbscrbr_options ) )
			$sbscrbr_options = ( is_multisite() ) ? get_site_option( 'sbscrbr_options' ) : get_option( 'sbscrbr_options' );
		if ( isset( $sbscrbr_options['contact_form'] ) && $sbscrbr_options['contact_form'] == 1 ) {
			add_filter( 'sbscrbr_cntctfrm_checkbox_add', 'sbscrbr_checkbox_add', 10, 1 );
			add_filter( 'sbscrbr_cntctfrm_checkbox_check', 'sbscrbr_checkbox_check', 10, 1 );
		}

		add_filter( 'sbscrbr_checkbox_add', 'sbscrbr_checkbox_add', 10, 1 );
		add_filter( 'sbscrbr_checkbox_check', 'sbscrbr_checkbox_check', 10, 1 );
	}
}

/**
 * Plugin initialisation in backend
 * @return void
 */
if ( ! function_exists( 'sbscrbr_admin_init' ) ) {
	function sbscrbr_admin_init() {
		global $bws_plugin_info, $sbscrbr_plugin_info, $bws_shortcode_list;

		if ( empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '122', 'version' => $sbscrbr_plugin_info["Version"] );

		/* add Subscriber to global $bws_shortcode_list  */
		$bws_shortcode_list['sbscrbr'] = array( 'name' => 'Subscriber' );
	}
}

/**
 * Default Plugin settings
 * @return void
 */
if ( ! function_exists( 'sbscrbr_settings' ) ) {
	function sbscrbr_settings() {
		global $sbscrbr_options, $sbscrbr_plugin_info;
		$db_version = "1.0";

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

		if ( ! isset( $sbscrbr_options['plugin_option_version'] ) || $sbscrbr_options['plugin_option_version'] != $sbscrbr_plugin_info["Version"] ) {
			/* array merge incase this version of plugin has added new options */
			$default_options = sbscrbr_get_default_options();
			$sbscrbr_options = array_merge( $default_options, $sbscrbr_options );
			/* show pro features */
			$sbscrbr_options['hide_premium_options'] = array();

			$sbscrbr_options['plugin_option_version'] = $sbscrbr_plugin_info["Version"];
			$update_option = true;
		}

		if ( ! isset( $sbscrbr_options['plugin_db_version'] ) || $sbscrbr_options['plugin_db_version'] != $db_version ) {
			sbscrbr_db();
			$sbscrbr_options['plugin_db_version'] = $db_version;
			$update_option = true;
		}

		if ( isset( $update_option ) ) {
			if ( is_multisite() )
				update_site_option( 'sbscrbr_options', $sbscrbr_options );
			else
				update_option( 'sbscrbr_options', $sbscrbr_options );
		}
	}
}

/**
 * Get Default Plugin options
 * @return array
 */
if ( ! function_exists( 'sbscrbr_get_default_options' ) ) {
	function sbscrbr_get_default_options() {
		global $sbscrbr_plugin_info;

		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		$from_email = 'wordpress@' . $sitename;

		$default_options = array(
			'plugin_option_version'			=> $sbscrbr_plugin_info["Version"],
			'first_install'					=> strtotime( "now" ),
			'suggest_feature_banner'        => 1,
			'display_settings_notice'		=> 1,
			/* form labels */
			'form_label'					=> '',
			'form_placeholder'				=> __( 'E-mail', 'subscriber' ),
			'form_checkbox_label'			=> __( 'unsubscribe', 'subscriber' ),
			'form_button_label'				=> __( 'Subscribe', 'subscriber' ),
			/* service messages */
			'bad_request'					=> __( 'System error. Please try later.', 'subscriber' ),
			'empty_email'					=> __( 'Please enter e-mail address.', 'subscriber' ),
			'invalid_email'					=> __( 'You must enter a valid e-mail address.', 'subscriber' ),
			'not_exists_email'				=> __( 'This e-mail address does not exist.', 'subscriber' ),
			'cannot_get_email'				=> __( 'Your e-mail information cannot be located.', 'subscriber' ),
			'cannot_send_email'				=> __( 'Unable to send the e-mail at this time. Please try later.', 'subscriber' ),
			'error_subscribe'				=> __( 'Error occurred during registration. Please try later.', 'subscriber' ),
			'done_subscribe'				=> __( 'Thank you for subscribing!', 'subscriber' ),
			'already_subscribe'				=> __( 'This e-mail address is already subscribed.' ),
			'denied_subscribe'				=> __( 'Sorry, but your request to subscribe has been denied.', 'subscriber' ),
			'already_unsubscribe'			=> __( 'You have successfully unsubscribed.', 'subscriber' ),
			'check_email_unsubscribe'		=> __( 'An unsubscribe link has been sent to you.', 'subscriber' ),
			'not_exists_unsubscribe'		=> __( 'Unsubscribe link failed. We respect your wishes. Please contact us to let us know.', 'subscriber' ),
			'done_unsubscribe'				=> __( 'You have successfully unsubscribed.', 'subscriber' ),
			/* mail settings */
			/* To email settings */
			'email_user'					=> 1,
			'email_custom'					=> array( get_option( 'admin_email' ) ),
			'to_email'						=> 'custom',
			/* "From" settings */
			'from_custom_name'				=> get_bloginfo( 'name' ),
			'from_email'					=> $from_email,
			'admin_message'					=> 1,
			/* subject settings */
			'admin_message_subject'			=> __( 'New subscriber', 'subscriber' ),
			'subscribe_message_subject'		=> __( 'Thanks for registration', 'subscriber' ),
			'unsubscribe_message_subject'	=> __( 'Link to unsubscribe', 'subscriber' ),
			/* message body settings */
			'admin_message_text'			=> sprintf(__( 'User with e-mail %s has subscribed to a newsletter.', 'subscriber' ), '{user_email}' ),
			'subscribe_message_text'		=> sprintf( __( "Thanks for registration. To change data of your profile go to %s If you want to unsubscribe from the newsletter from our site go to the link %s", 'subscriber' ), "{profile_page}\n", "\n{unsubscribe_link}" ),
			'unsubscribe_message_text'		=> sprintf( __( "Dear user. At your request, we send you a link to unsubscribe from our email messages. To unsubscribe please use the link below. If you change your mind, you can just ignore this letter.\nLink to unsubscribe:\n %s", 'subscriber' ), '{unsubscribe_link}' ),
			'admin_message_use_sender'					=> 0,
			'admin_message_sender_template_id' 			=> '',
			'subscribe_message_use_sender'				=> 0,
			'subscribe_message_sender_template_id'		=> '',
			'unsubscribe_message_use_sender'			=> 0,
			'unsubscribe_message_sender_template_id' 	=> '',
			/* another settings */
			'unsubscribe_link_text'			=> sprintf( __( "If you want to unsubscribe from the newsletter from our site go to the following link: %s", 'subscriber' ), "\n{unsubscribe_link}" ),
			'delete_users'					=> 0,
			'contact_form'		            => 0,
			/* settings for {unsubscribe_link} */
			'shortcode_link_type'			=> 'url', /* go to url or display text */
			'shortcode_url'					=> home_url(),
		);
		return $default_options;
	}
}

/**
 * Function is called during activation of plugin
 * @return void
 */
if ( ! function_exists( 'sbscrbr_db' ) ) {
	function sbscrbr_db() {
		/* add new table in database */
		global $wpdb;
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$sql_query =
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "sndr_mail_users_info` (
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
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		dbDelta( $sql_query );

		/* check if column "unsubscribe_code" is already exists */
		$column_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $prefix . "sndr_mail_users_info` LIKE 'unsubscribe_code'" );
		if ( empty( $column_exists ) ) {
			$wpdb->query( "ALTER TABLE `" . $prefix . "sndr_mail_users_info`
				ADD `unsubscribe_code` VARCHAR(100) NOT NULL,
				ADD `subscribe_time` INT UNSIGNED NOT NULL,
				ADD `unsubscribe_time` INT UNSIGNED NOT NULL,
				ADD `delete` INT UNSIGNED NOT NULL,
				ADD `black_list` INT UNSIGNED NOT NULL;"
			);
			$wpdb->query( "UPDATE `" . $prefix . "sndr_mail_users_info` SET `unsubscribe_code`= MD5(RAND());" );
			$wpdb->query( "UPDATE `" . $prefix . "sndr_mail_users_info` SET `subscribe_time`='" . time() . "' WHERE `subscribe`=1;" );
			$wpdb->query( "UPDATE `" . $prefix . "sndr_mail_users_info` SET `unsubscribe_time`='" . time() . "' WHERE `subscribe`=0;" );
		}
	}
}

/**
 * Fucntion load stylesheets and scripts in backend
 * @return void
 */
if ( ! function_exists( 'sbscrbr_admin_head' ) ) {
	function sbscrbr_admin_head() {
		if ( isset( $_REQUEST['page'] ) && 'sbscrbr_settings_page' == $_REQUEST['page'] ) {
			wp_enqueue_style( 'sbscrbr_style', plugins_url( 'css/style.css', __FILE__ ) );
			wp_enqueue_script( 'sbscrbr_scripts', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ) );
			bws_enqueue_settings_scripts();

			if ( isset( $_GET['tab'] ) && 'custom_code' == $_GET['tab'] )
				bws_plugins_include_codemirror();
		}
	}
}

/**
 * Load scripts in frontend
 * @return void
 */
if ( ! function_exists( 'sbscrbr_load_styles' ) ) {
	function sbscrbr_load_styles() {
		wp_enqueue_style( 'sbscrbr_style', plugins_url( 'css/frontend_style.css', __FILE__ ) );
	}
}

/**
 * Load scripts in frontend
 * @return void
 */
if ( ! function_exists( 'sbscrbr_load_scripts' ) ) {
	function sbscrbr_load_scripts() {
		if ( wp_script_is( 'sbscrbr_form_scripts', 'registered' ) && ! wp_script_is( 'sbscrbr_form_scripts', 'enqueued' ) ) {
			wp_enqueue_script( 'sbscrbr_form_scripts' );
			wp_localize_script( 'sbscrbr_form_scripts', 'sbscrbr_js_var', array( 'preloaderIconPath' => plugins_url( 'images/preloader.gif', __FILE__ ) ) );
		}
	}
}

/**
 * Function to redirect for displaying subscriber service message in a separate page
 */
if ( ! function_exists( 'sbscrbr_template_redirect' ) ) {
	function sbscrbr_template_redirect() {
		global $sbscrbr_response;
		if ( empty( $sbscrbr_response ) )
 			return;
		include( TEMPLATEPATH . "/page.php" );
		exit;
	}
}
/**
 * Function for displaying subscriber service message in a separate page
 */
if ( ! function_exists( 'sbscrbr_the_posts' ) ) {
	function sbscrbr_the_posts() {
		global $wp, $wp_query, $sbscrbr_response;

		if ( empty( $sbscrbr_response ) )
 			return;

 		remove_all_filters( 'the_content' );
		add_filter( 'the_content', 'capital_P_dangit', 11 );
		add_filter( 'the_content', 'wptexturize' );
		add_filter( 'the_content', 'convert_smilies' );
		add_filter( 'the_content', 'convert_chars' );
		add_filter( 'the_content', 'wpautop' );
		add_filter( 'the_content', 'do_shortcode' );

 		$content = '<div id="sbscrbr-page">' . $sbscrbr_response['message'] . '</div>';

		/* create a fake post intance */
		$post = new stdClass;
		/* fill properties of $post with everything a page in the database would have */
		$post->ID = -1;
		$post->post_author = 1;
		$post->post_date = current_time( 'mysql' );
		$post->post_date_gmt = current_time( 'mysql', 1 );
		$post->post_content = $content;
		$post->post_title = $sbscrbr_response['title'];
		$post->post_excerpt = $content;
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
		$wp_query->is_page = TRUE;
		$wp_query->is_singular = TRUE;
		$wp_query->is_home = FALSE;
		$wp_query->is_archive = FALSE;
		$wp_query->is_category = FALSE;
		unset( $wp_query->query['error'] );
		$wp_query->query_vars['error'] = '';
		$wp_query->is_404 = FALSE;

 		return ( $posts );
 	}
}

/**
 * Display settings page of plugin
 * @return void
 */
if ( ! function_exists( 'sbscrbr_settings_page' ) ) {
	function sbscrbr_settings_page() {
		global $wp_version, $wpdb, $sbscrbr_options, $sbscrbr_plugin_info;
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		/* get list of administrators */
		$admin_list = $wpdb->get_results(
			"SELECT DISTINCT `user_login` , `display_name` FROM `" . $prefix . "users`
				LEFT JOIN `" . $prefix . "usermeta` ON `" . $prefix . "usermeta`.`user_id` = `" . $prefix . "users`.`ID`
			WHERE `meta_value` LIKE  '%administrator%'",
			ARRAY_A
		);
		$error = $message = $notice = '';
		$plugin_basename = plugin_basename( __FILE__ );

		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$all_plugins = get_plugins();


		/* captcha compatibility */
		/**
		 * something is done to make a compatibility with the BWS CAPTCHA Pro plugin v1.6.9 and older
		 * @todo remove compatibility with older plugin version after 01.03.2017
		 */
		$captcha_pro_is_old_version = false;
		$captcha_pro_options = is_multisite() ? get_site_option( 'cptchpr_options' ) : get_option( 'cptchpr_options' );
		if ( ! $captcha_pro_options ) {
			$captcha_pro_options = is_multisite() ? get_site_option( 'cptch_options' ) : get_option( 'cptch_options' );
			$captcha_pro_enabled = ( isset( $captcha_pro_options['forms']['bws_subscriber']['enable'] ) && true == $captcha_pro_options['forms']['bws_subscriber']['enable'] ) ? true : false;
		} else {
			$captcha_pro_is_old_version = true;
			$captcha_pro_enabled = ( isset( $captcha_pro_options["cptchpr_subscriber"] ) && 1 == $captcha_pro_options["cptchpr_subscriber"] ) ? true : false;
		}

		if ( isset( $_POST['sbscrbr_form_submit'] ) && check_admin_referer( $plugin_basename, 'sbscrbr_nonce_name' ) ) {
			$default_options = sbscrbr_get_default_options();
			$sbscrbr_options_submit = array();
			if ( isset( $_POST['bws_hide_premium_options'] ) ) {
				$hide_result = bws_hide_premium_options( $sbscrbr_options_submit );
				$sbscrbr_options_submit = $hide_result['options'];
			}
			/* form labels */
			$sbscrbr_options_submit['form_label']				= isset( $_POST['sbscrbr_form_label'] ) ? esc_html( $_POST['sbscrbr_form_label'] ) : $sbscrbr_options['form_label'];
			$sbscrbr_options_submit['form_placeholder']			= isset( $_POST['sbscrbr_form_placeholder'] ) ? esc_html( $_POST['sbscrbr_form_placeholder'] ) : $sbscrbr_options['form_placeholder'];
			$sbscrbr_options_submit['form_checkbox_label']		= isset( $_POST['sbscrbr_form_checkbox_label'] ) ? esc_html( $_POST['sbscrbr_form_checkbox_label'] ) : $sbscrbr_options['form_checkbox_label'];
			$sbscrbr_options_submit['form_button_label']		= isset( $_POST['sbscrbr_form_button_label'] ) ? esc_html( $_POST['sbscrbr_form_button_label'] ) : $sbscrbr_options['form_button_label'];

			/* service messages  */
			$sbscrbr_options_submit['bad_request']				= isset( $_POST['sbscrbr_bad_request'] ) ? esc_html( $_POST['sbscrbr_bad_request'] ) : $sbscrbr_options['bad_request'];
			$sbscrbr_options_submit['empty_email']				= isset( $_POST['sbscrbr_empty_email'] ) ? esc_html( $_POST['sbscrbr_empty_email'] ) : $sbscrbr_options['empty_email'];
			$sbscrbr_options_submit['invalid_email']			= isset( $_POST['sbscrbr_invalid_email'] ) ? esc_html( $_POST['sbscrbr_invalid_email'] ) : $sbscrbr_options['invalid_email'];
			$sbscrbr_options_submit['not_exists_email']			= isset( $_POST['sbscrbr_not_exists_email'] ) ? esc_html( $_POST['sbscrbr_not_exists_email'] ) : $sbscrbr_options['not_exists_email'];
			$sbscrbr_options_submit['cannot_get_email']			= isset( $_POST['sbscrbr_cannot_get_email'] ) ? esc_html( $_POST['sbscrbr_cannot_get_email'] ) : $sbscrbr_options['cannot_get_email'];
			$sbscrbr_options_submit['cannot_send_email']		= isset( $_POST['sbscrbr_cannot_send_email'] ) ? esc_html( $_POST['sbscrbr_cannot_send_email'] ) : $sbscrbr_options['cannot_send_email'];
			$sbscrbr_options_submit['error_subscribe']			= isset( $_POST['sbscrbr_error_subscribe'] ) ? esc_html( $_POST['sbscrbr_error_subscribe'] ) : $sbscrbr_options['error_subscribe'];
			$sbscrbr_options_submit['done_subscribe']			= isset( $_POST['sbscrbr_done_subscribe'] ) ? esc_html( $_POST['sbscrbr_done_subscribe'] ) : $sbscrbr_options['done_subscribe'];
			$sbscrbr_options_submit['already_subscribe']		= isset( $_POST['sbscrbr_already_subscribe'] ) ? esc_html( $_POST['sbscrbr_already_subscribe'] ) : $sbscrbr_options['already_subscribe'];
			$sbscrbr_options_submit['denied_subscribe']			= isset( $_POST['sbscrbr_denied_subscribe'] ) ? esc_html( $_POST['sbscrbr_denied_subscribe'] ) : $sbscrbr_options['denied_subscribe'];
			$sbscrbr_options_submit['already_unsubscribe']		= isset( $_POST['sbscrbr_already_unsubscribe'] ) ? esc_html( $_POST['sbscrbr_already_unsubscribe'] ) : $sbscrbr_options['already_unsubscribe'];
			$sbscrbr_options_submit['check_email_unsubscribe']	= isset( $_POST['sbscrbr_check_email_unsubscribe'] ) ? esc_html( $_POST['sbscrbr_check_email_unsubscribe'] ) : $sbscrbr_options['check_email_unsubscribe'];
			$sbscrbr_options_submit['done_unsubscribe']			= isset( $_POST['sbscrbr_done_unsubscribe'] ) ? esc_html( $_POST['sbscrbr_done_unsubscribe'] ) : $sbscrbr_options['done_unsubscribe'];
			$sbscrbr_options_submit['not_exists_unsubscribe']	= isset( $_POST['sbscrbr_not_exists_unsubscribe'] ) ? esc_html( $_POST['sbscrbr_not_exists_unsubscribe'] ) : $sbscrbr_options['not_exists_unsubscribe'];

			/* To email settings */
			$sbscrbr_options_submit['to_email']	= isset( $_POST['sbscrbr_to_email'] ) ? esc_html( $_POST['sbscrbr_to_email'] ) : $sbscrbr_options['to_email'];

			if ( isset( $_POST['sbscrbr_email_user'] ) && get_user_by( 'login', $_POST['sbscrbr_email_user'] ) ) {
				$sbscrbr_options_submit['email_user'] = $_POST['sbscrbr_email_user'];
			} else {
				$sbscrbr_options_submit['email_user'] = ( ! empty( $sbscrbr_options['to_email'] ) ) ? $sbscrbr_options['to_email'] : $default_options['email_user'];
				if ( empty( $sbscrbr_options_submit['email_user'] ) && $sbscrbr_options_submit['email_user'] == 'user' ) {
					$sbscrbr_options_submit['to_email'] = $default_options['to_email'];
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
					$sbscrbr_options_submit['email_custom'] = $sbscrbr_email_list;
				} else {
					$sbscrbr_options_submit['email_custom'] = ( $sbscrbr_options['email_custom'] ) ? $sbscrbr_options['email_custom'] : $default_options['email_custom'];
				}
			}

			/* "From" settings */
			if ( isset( $_POST['sbscrbr_from_email'] ) && is_email( trim( $_POST['sbscrbr_from_email'] ) ) ) {
				if ( $sbscrbr_options['from_email'] != trim( $_POST['sbscrbr_from_email'] ) )
					$notice = __( "Email 'FROM' field option was changed, which may cause email messages being moved to the spam folder or email delivery failures.", 'subscriber' );
				$sbscrbr_options_submit['from_email']  = trim( $_POST['sbscrbr_from_email'] );
			} else {
				$sbscrbr_options_submit['from_email'] = $default_options['from_email'];
			}

			$sbscrbr_options_submit['from_custom_name']	= isset( $_POST['sbscrbr_from_custom_name'] ) ? esc_html( $_POST['sbscrbr_from_custom_name'] ) : $sbscrbr_options['from_custom_name'];
			if ( '' == $sbscrbr_options_submit['from_custom_name'] )
				$sbscrbr_options_submit['from_custom_name'] = $default_options['from_custom_name'];

			$sbscrbr_options_submit['admin_message'] = isset( $_POST['sbscrbr_admin_message'] ) ? 1 : 0;
			/* subject settings */
			$sbscrbr_options_submit['admin_message_subject']       = isset( $_POST['sbscrbr_admin_message_subject'] ) ? esc_html( $_POST['sbscrbr_admin_message_subject'] ) : $sbscrbr_options['admin_message_subject'];
			$sbscrbr_options_submit['subscribe_message_subject']   = isset( $_POST['sbscrbr_subscribe_message_subject'] ) ? esc_html( $_POST['sbscrbr_subscribe_message_subject'] ) : $sbscrbr_options['subscribe_message_subject'];
			$sbscrbr_options_submit['unsubscribe_message_subject'] = isset( $_POST['sbscrbr_unsubscribe_message_subject'] ) ? esc_html( $_POST['sbscrbr_unsubscribe_message_subject'] ) : $sbscrbr_options['unsubscribe_message_subject'];
			/* message body settings */
			$sbscrbr_options_submit['admin_message_text']          = isset( $_POST['sbscrbr_admin_message_text'] ) ? esc_html( $_POST['sbscrbr_admin_message_text'] ) : $sbscrbr_options['admin_message_text'];
			$sbscrbr_options_submit['subscribe_message_text']      = isset( $_POST['sbscrbr_subscribe_message_text'] ) ? esc_html( $_POST['sbscrbr_subscribe_message_text'] ) : $sbscrbr_options['subscribe_message_text'];
			$sbscrbr_options_submit['unsubscribe_message_text']    = isset( $_POST['sbscrbr_unsubscribe_message_text'] ) ? esc_html( $_POST['sbscrbr_unsubscribe_message_text'] ) : $sbscrbr_options['unsubscribe_message_text'];

			$sbscrbr_options_submit['admin_message_use_sender'] = isset( $_POST['sbscrbr_admin_message_use_sender'] ) && 1 == $_POST['sbscrbr_admin_message_use_sender'] ? 1 : 0;
			$sbscrbr_options_submit['subscribe_message_use_sender'] = isset( $_POST['sbscrbr_subscribe_message_use_sender'] ) && 1 == $_POST['sbscrbr_subscribe_message_use_sender'] ? 1 : 0;
			$sbscrbr_options_submit['unsubscribe_message_use_sender'] = isset( $_POST['sbscrbr_unsubscribe_message_use_sender'] ) && 1 == $_POST['sbscrbr_unsubscribe_message_use_sender'] ? 1 : 0;

			$sbscrbr_options_submit['admin_message_sender_template_id'] = isset( $_POST['sbscrbr_admin_message_sender_template_id'] ) ? intval( $_POST['sbscrbr_admin_message_sender_template_id'] ) : '';
			$sbscrbr_options_submit['subscribe_message_sender_template_id'] = isset( $_POST['sbscrbr_subscribe_message_sender_template_id'] ) ? intval( $_POST['sbscrbr_subscribe_message_sender_template_id'] ) : '';
			$sbscrbr_options_submit['unsubscribe_message_sender_template_id'] = isset( $_POST['sbscrbr_unsubscribe_message_sender_template_id'] ) ? intval( $_POST['sbscrbr_unsubscribe_message_sender_template_id'] ) : '';

			/* settings for {unsubscribe_link} */
			$sbscrbr_options_submit['shortcode_link_type'] = esc_attr( $_POST['sbscrbr_shortcode_link_type'] );
			$sbscrbr_options_submit['shortcode_url'] = strtok( esc_url( trim( $_POST['sbscrbr_shortcode_url'] ) ), '?' );

			if ( 'url' == $sbscrbr_options_submit['shortcode_link_type'] && empty( $sbscrbr_options_submit['shortcode_url'] ) )
				$sbscrbr_options_submit['shortcode_link_type'] = 'text';

			/*  another settings  */
			$sbscrbr_options_submit['unsubscribe_link_text']       = isset( $_POST['sbscrbr_unsubscribe_link_text'] ) ? esc_html( $_POST['sbscrbr_unsubscribe_link_text'] ) : $sbscrbr_options['unsubscribe_link_text'];
			$sbscrbr_options_submit['delete_users']                = ( isset( $_POST['sbscrbr_delete_users'] ) && '1' == $_POST['sbscrbr_delete_users'] ) ? 1 : 0;
			$sbscrbr_options_submit['contact_form'] = ( isset( $_POST['sbscrbr_contact_form'] ) && '1' == $_POST['sbscrbr_contact_form'] ) ? 1 : 0;

			$sbscrbr_options_submit = array_map( 'stripslashes_deep', $sbscrbr_options_submit );

			/* captcha compatibility */
			if ( ! empty( $captcha_pro_options ) ) {
				/**
				 * something is done to make a compatibility with the BWS CAPTCHA Pro plugin v1.6.9 and older
				 * @todo remove compatibility with older plugin version after 01.03.2017
				 */
				if ( isset( $captcha_pro_options['forms']['bws_subscriber']['enable'] ) )
					$captcha_pro_options['forms']['bws_subscriber']['enable'] = ( isset( $_POST['sbscrbr_display_captcha'] ) ) ? true : false;
				elseif ( isset( $captcha_pro_options['cptchpr_subscriber'] ) )
					$captcha_pro_options['cptchpr_subscriber'] = ( isset( $_POST['sbscrbr_display_captcha'] ) ) ? 1 : 0;

				$captcha_pro_enabled = ( isset( $_POST['sbscrbr_display_captcha'] ) ) ? true : false;

				if ( is_multisite() ) {
					if ( $captcha_pro_is_old_version )
						update_site_option( 'cptchpr_options', $captcha_pro_options );
					else
						update_site_option( 'cptch_options', $captcha_pro_options );

					if ( ( isset( $captcha_pro_options['cptchpr_network_apply'] ) && 'all' == $captcha_pro_options['cptchpr_network_apply'] ) ||
						( isset( $captcha_pro_options['network_apply'] ) && 'all' == $captcha_pro_options['network_apply'] ) ) {
						/* Get all blog ids */
						$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
						$old_blog = $wpdb->blogid;
						foreach ( $blogids as $blog_id ) {
							switch_to_blog( $blog_id );
							if ( $captcha_pro_is_old_version ) {
								if ( $captcha_pro_single_options = get_option( 'cptchpr_options' ) ) {
									$captcha_pro_single_options['cptchpr_subscriber'] = $captcha_pro_options['cptchpr_subscriber'];
									update_option( 'cptchpr_options', $captcha_pro_single_options );
								}
							} else {
								if ( $captcha_pro_single_options = get_option( 'cptch_options' ) ) {
									$captcha_pro_single_options['forms']['bws_subscriber']['enable'] = $captcha_pro_options['forms']['bws_subscriber']['enable'];
									update_option( 'cptch_options', $captcha_pro_single_options );
								}
							}
						}
						switch_to_blog( $old_blog );
					}
				} else {
					if ( $captcha_pro_is_old_version )
						update_option( 'cptchpr_options', $captcha_pro_options );
					else
						update_option( 'cptch_options', $captcha_pro_options );
				}
			}

			/* update options of plugin in database */
			if ( empty( $error ) ) {
				$sbscrbr_options = array_merge( $sbscrbr_options, $sbscrbr_options_submit );
				if ( is_multisite() )
					update_site_option( 'sbscrbr_options', $sbscrbr_options );
				else
					update_option( 'sbscrbr_options', $sbscrbr_options );
				$message = __( 'Settings Saved', 'subscriber' );
			}
		}

		/* add restore function */
		if ( isset( $_REQUEST['bws_restore_confirm'] ) && check_admin_referer( $plugin_basename, 'bws_settings_nonce_name' ) ) {
			$sbscrbr_options = sbscrbr_get_default_options();
			if ( is_multisite() )
				update_site_option( 'sbscrbr_options', $sbscrbr_options );
			else
				update_option( 'sbscrbr_options', $sbscrbr_options );

			$message = __( 'All plugin settings were restored.', 'subscriber' );
		}

		$bws_hide_premium_options_check = bws_hide_premium_options_check( $sbscrbr_options );

		if ( isset( $_GET['tab'] ) || ( isset( $_GET['tab'] ) && 'sbscrbr_users' == $_GET['tab'] ) ) {
			/* actions in subscribers tab  */
			$action_message = sbscrbr_report_actions();
			if ( $action_message['error'] ) {
				$error = $action_message['error'];
			} elseif ( $action_message['done'] ) {
				$message = $action_message['done'];
			}
		}

		/* GO PRO */
		if ( isset( $_GET['tab'] ) && 'go_pro' == $_GET['tab'] ) {
			$go_pro_result = bws_go_pro_tab_check( $plugin_basename, 'sbscrbr_options' );
			if ( ! empty( $go_pro_result['error'] ) )
				$error = $go_pro_result['error'];
			elseif ( ! empty( $go_pro_result['message'] ) )
				$message = $go_pro_result['message'];
		} ?>
		<div class="wrap" id="sbscrbr-settings-page">
			<h1><?php _e( "Subscriber Settings", 'subscriber' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab <?php if ( ! isset( $_GET['tab'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=sbscrbr_settings_page"><?php _e( 'Settings', 'subscriber' ); ?></a>
				<a class="nav-tab <?php if ( isset( $_GET['tab'] ) && 'sbscrbr_email_notifications' == $_GET['tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=sbscrbr_settings_page&amp;tab=sbscrbr_email_notifications"><?php _e( 'Email Notifications', 'subscriber' ); ?></a>
				<a class="nav-tab <?php if ( isset( $_GET['tab'] ) && 'sbscrbr_users' == $_GET['tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=sbscrbr_settings_page&amp;tab=sbscrbr_users"><?php _e( 'Subscribers', 'subscriber' ); ?></a>
				<a class="nav-tab <?php if ( isset( $_GET['tab'] ) && 'custom_code' == $_GET['tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=sbscrbr_settings_page&amp;tab=custom_code"><?php _e( 'Custom code', 'subscriber' ); ?></a>
				<a class="nav-tab bws_go_pro_tab<?php if ( isset( $_GET['tab'] ) && 'go_pro' == $_GET['tab'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=sbscrbr_settings_page&amp;tab=go_pro"><?php _e( 'Go PRO', 'subscriber' ); ?></a>
			</h2>
			<?php if ( ! empty( $notice ) ) { ?>
				<div class="error below-h2"><p><strong><?php _e( 'Notice:', 'subscriber' ); ?></strong> <?php echo $notice; ?></p></div>
			<?php }
			bws_show_settings_notice();
			if ( ! empty( $hide_result['message'] ) ) { ?>
				<div class="updated below-h2 fade"><p><strong><?php echo $hide_result['message']; ?></strong></p></div>
			<?php } ?>
			<div class="updated below-h2 fade" <?php if ( empty( $message ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error below-h2" <?php if ( empty( $error ) ) echo "style=\"display:none\""; ?>><p><strong><?php echo $error; ?></strong></p></div>
			<?php if ( ! isset( $_GET['tab'] ) || ( isset( $_GET['tab'] ) && 'sbscrbr_email_notifications' == $_GET['tab'] ) ) { /* Showing settings tab */
				if ( isset( $_REQUEST['bws_restore_default'] ) && check_admin_referer( $plugin_basename, 'bws_settings_nonce_name' ) ) {
					bws_form_restore_default_confirm( $plugin_basename );
				} else { ?>
					<p><?php printf( __( 'If you would like to display the Subscribe Form with a widget, you need to add the widget "Subscriber Sign Up Form" in %s.', 'subscriber' ), '<a href="' . admin_url( 'widgets.php' ) . '">' . __( 'the Widgets tab', 'subscriber' ) . '</a>' ); ?></p>
					<div>
						<?php printf( __( "If you would like to add the Subscribe Form to your page or post, please use %s button", 'subscriber' ),
							'<span class="bws_code"><span class="bwsicons bwsicons-shortcode"></span></span>'
						); ?>
						<div class="bws_help_box bws_help_box_right dashicons dashicons-editor-help">
							<div class="bws_hidden_help_text" style="min-width: 260px;">
								<?php printf(
									__( "You can add the Subscribe Form to your page or post by clicking on %s button in the content edit block using the Visual mode. If the button isn't displayed, please use the shortcode %s.", 'subscriber' ),
									'<span class="bws_code"><span class="bwsicons bwsicons-shortcode"></span></span>',
									'<code>[sbscrbr_form]</code>'
								); ?>
							</div>
						</div>
					</div>
					<?php $sbscrbr_tab_form_action = ( isset( $_GET['tab'] ) )  ? '&tab=' . wp_strip_all_tags( $_GET['tab'] ) : ''; ?>
					<form class="bws_form" method="post" action="<?php echo esc_url( 'admin.php?page=sbscrbr_settings_page' . $sbscrbr_tab_form_action ); ?>">
						<div id="sbscrbr_settings_block" style="display: <?php echo ( ! isset( $_GET['tab'] ) ) ? 'block' : 'none'; ?>;">
							<table id="sbscrbr-settings-table" class="form-table">
								<tr valign="top">
									<th><?php _e( 'Subscribe form labels', 'subscriber' ); ?></th>
									<td colspan="2">
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-form-label" name="sbscrbr_form_label" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['form_label'] ); ?>"/>
										<span class="bws_info"><?php _e( 'Text above the subscribe form', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-form-placeholder" name="sbscrbr_form_placeholder" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['form_placeholder'] ); ?>"/>
										<span class="bws_info"><?php _e( 'Placeholder for text field "E-mail"', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-form-checkbox-label" name="sbscrbr_form_checkbox_label" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['form_checkbox_label'] ); ?>"/>
										<span class="bws_info"><?php _e( 'Label for "Unsubscribe" checkbox', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-form-button-label" name="sbscrbr_form_button_label" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['form_button_label'] ); ?>"/>
										<span class="bws_info
										"><?php _e( 'Label for "Subscribe" button', 'subscriber' ); ?></span>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e( 'Add to the subscribe form', 'subscriber' ); ?></th>
									<td colspan="2">
										<?php if ( array_key_exists( 'captcha-pro/captcha_pro.php', $all_plugins ) ) {
											if ( is_plugin_active( 'captcha-pro/captcha_pro.php' ) ) { ?>
												<label><input type="checkbox" name="sbscrbr_display_captcha" value="1" <?php if ( $captcha_pro_enabled ) echo 'checked="checked"'; ?> /> Captcha by BestWebSoft</label>
											<?php } else { ?>
												<label>
													<input disabled="disabled" type="checkbox" name="sbscrbr_display_captcha" value="1" <?php if ( $captcha_pro_enabled ) echo 'checked="checked"'; ?> /> Captcha by BestWebSoft
												</label>
												<span class="bws_info"><a href="<?php echo self_admin_url( 'plugins.php' ); ?>"><?php _e( 'Activate', 'subscriber' ); ?> Captcha</a></span>
											<?php }
										} else { ?>
											<label>
												<input disabled="disabled" type="checkbox" name="sbscrbr_display_captcha" value="1" /> Captcha by BestWebSoft
											</label>
											<span class="bws_info"><a href="https://bestwebsoft.com/products/wordpress/plugins/captcha/?k=d045de4664b2e847f2612a815d838e60&pn=122&v=<?php echo $sbscrbr_plugin_info["Version"]; ?>&wp_v=<?php echo $wp_version; ?>" target="_blank"><?php _e( 'Download', 'subscriber' ); ?> Captcha</a></span>
										<?php } ?>
									</td>
								</tr>
								<tr valign="top">
									<th><?php _e( 'Add checkbox "Subscribe" to', 'subscriber' ); ?></th>
									<td colspan="2">
										<?php $sbscrbr_cntcfrm_name = $sbscrbr_cntcfrm_notice = $sbscrbr_cntcfrm_attr = '';
										$sbscrbr_cntcfrm_installed = $sbscrbr_cntcfrm_activated = false;

										if ( array_key_exists( 'contact-form-plugin/contact_form.php', $all_plugins ) ) {
											$sbscrbr_cntcfrm_name = 'Contact Form';
											$sbscrbr_cntcfrm_installed = true;
											if ( $all_plugins['contact-form-plugin/contact_form.php']['Version'] <= '3.97' ) {
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

										if ( $sbscrbr_cntcfrm_activated == false && array_key_exists( 'contact-form-pro/contact_form_pro.php', $all_plugins ) ) {
											$sbscrbr_cntcfrm_name = 'Contact Form Pro';
											$sbscrbr_cntcfrm_installed = true;
											if ( $all_plugins['contact-form-pro/contact_form_pro.php']['Version'] <= '2.1.0' ) {
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

										if ( $sbscrbr_cntcfrm_activated == true )
											$sbscrbr_cntcfrm_notice = $sbscrbr_cntcfrm_attr = '';

										if ( $sbscrbr_cntcfrm_installed == false ) {
											$sbscrbr_cntcfrm_name = 'Contact Form';
											$sbscrbr_cntcfrm_notice = '<a href="https://bestwebsoft.com/products/wordpress/plugins/contact-form/?k=507a200ccc60acfd5731b09ba88fb355&pn=122&v=' . $sbscrbr_plugin_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">' . __( 'Download', 'subscriber' ) . ' ' . $sbscrbr_cntcfrm_name . '</a>';
											$sbscrbr_cntcfrm_attr = 'disabled="disabled"';
										} ?>
										<label>
											<input <?php echo $sbscrbr_cntcfrm_attr; ?> type="checkbox" name="sbscrbr_contact_form" value="1" <?php if ( empty( $sbscrbr_cntcfrm_notice ) && $sbscrbr_options["contact_form"] == 1 ) echo 'checked="checked"'; ?> /> Contact Form by BestWebSoft</label>
											<span class="bws_info">
												<?php echo $sbscrbr_cntcfrm_notice;
												if ( $sbscrbr_cntcfrm_activated == true && ( is_plugin_active( 'contact-form-multi-pro/contact-form-multi-pro.php' ) || is_plugin_active( 'contact-form-multi/contact-form-multi.php' ) ) )
													echo ' (' . __( 'Check off for adding captcha to forms on their settings pages.', 'subscriber' ) . ')'; ?>
											</span>
										<br />
										<span class="bws_info"><?php _e( 'If you would like to add checkbox "Subscribe" to a custom form, please see', 'subscriber' ); ?>&nbsp;<a href="https://support.bestwebsoft.com/hc/en-us/sections/200538739" target="_blank">FAQ</a></span>
									</td>
								</tr>
								<tr valign="top" class="sbscrbr-service-messages">
									<th>
										<?php _e( 'Service messages', 'subscriber' ); ?>
										<span class="bws_help_box dashicons dashicons-editor-help">
											<span class="bws_hidden_help_text" style="width: 240px;">
												<?php _e( 'These messages will be displayed in the frontend of your site.', 'subscriber' ); ?>
											</span>
										</span>
									</th>
									<td colspan="2">
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-bad-request" name="sbscrbr_bad_request" maxlength="250" value="<?php echo $sbscrbr_options['bad_request'] ; ?>"/>
										<span class="bws_info"><?php _e( 'Unknown error', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-empty-email" name="sbscrbr_empty_email" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['empty_email'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If user has not entered e-mail', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-invalid-email" name="sbscrbr_invalid_email" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['invalid_email'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If user has entered invalid e-mail', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-not-exists-email" name="sbscrbr_not_exists_email" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['not_exists_email'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If the user has entered a non-existent e-mail', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-not-exists-email" name="sbscrbr_cannot_get_email" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['cannot_get_email'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If it is impossible to get the data about the entered e-mail', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-cannot-send-email" name="sbscrbr_cannot_send_email" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['cannot_send_email'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If it is impossible to send a letter', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-error-subscribe" name="sbscrbr_error_subscribe" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['error_subscribe'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If some errors occurred while user registration', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-done-subscribe" name="sbscrbr_done_subscribe" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['done_subscribe'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If user registration was succesfully finished', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-already-subscribe" name="sbscrbr_already_subscribe" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['already_subscribe'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If the user has already subscribed', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-denied-subscribe" name="sbscrbr_denied_subscribe" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['denied_subscribe'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If subscription has been denied', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-already-unsubscribe" name="sbscrbr_already_unsubscribe" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['already_unsubscribe'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If the user has already unsubscribed', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-check-email-unsubscribe" name="sbscrbr_check_email_unsubscribe" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['check_email_unsubscribe'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If the user has been sent a letter with a link to unsubscribe', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-done-unsubscribe" name="sbscrbr_done_unsubscribe" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['done_unsubscribe'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If user was unsubscribed', 'subscriber' ); ?></span>
										<br/>
										<input type="text" class="sbscrbr-input-text" id="sbscrbr-not-exists-unsubscribe" name="sbscrbr_not_exists_unsubscribe" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['not_exists_unsubscribe'] ); ?>"/>
										<span class="bws_info"><?php _e( 'If the user clicked on a non-existent "unsubscribe"-link', 'subscriber' ); ?></span>
									</td><!-- .sbscrbr-service-messages -->
								</tr>
							</table>
							<?php if ( ! $bws_hide_premium_options_check ) { ?>
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
													<label><input disabled="disabled" type="checkbox" name="sbscrbr_register_page_checkbox" value="1" /> <?php _e( 'Registration form', 'subscriber' );?></label>
												</td>
											</tr>
											<tr valign="top">
												<th><?php _e( 'Subscription confirmation', 'subscriber' ); ?></th>
												<td colspan="2">
													<input disabled="disabled" type="checkbox" name="sbscrbr_subscription_confirmation" value="1" />
													<span class="bws_info"><?php _e( 'Subscription confirmation via email before user registration.', 'subscriber' ); ?></span>
												</td>
											</tr>
											<tr valign="top">
												<th><?php _e( 'Delete a user data if the subscription has not been confirmed in', 'subscriber' ); ?></th>
												<td colspan="2">
													<input disabled="disabled" type="number" name="sbscrbr_hours_for_delete_user" min="1" max="10000" value="6" /> <?php _e( 'hours', 'subscriber' ); ?>
													<?php _e( 'every', 'subscriber' ); ?> <input disabled="disabled" type="number" name="sbscrbr_clear_data_hours" min="1" max="10000" value="24" /> <?php _e( 'hours', 'subscriber' ); ?>.<br/>
													<span class="bws_info"><?php _e( 'Please set 0 if you do not want to delete a user data.', 'subscriber' ); ?></span>
												</td>
											</tr>
										</table>
									</div>
									<div class="bws_pro_version_tooltip">
										<a class="bws_button" href="https://bestwebsoft.com/products/wordpress/plugins/subscriber/?k=d356381b0c3554404e34cdc4fe936455&pn=122&v=<?php echo $sbscrbr_plugin_info["Version"] . '&wp_v=' . $wp_version; ?>" target="_blank" title="Subscriber Pro"><?php _e( "Learn More", 'subscriber' ); ?></a>
										<div class="clear"></div>
									</div>
								</div>
							<?php } ?>
							<table class="form-table">
								<tr valign="top">
									<th><?php _e( 'Delete users while plugin removing', 'subscriber' ); ?></th>
									<td colspan="2">
										<input type="checkbox" id="sbscrbr-delete-user" name="sbscrbr_delete_users" value="1" <?php if ( '1' == $sbscrbr_options['delete_users'] ) echo 'checked="checked"'; ?> />
										<span class="bws_info"><?php _e( 'If this option enabled, when you remove plugin, all users with role "Mail Subscribed" will be removed from users list.', 'subscriber' ); ?></span>
									</td>
								</tr>
							</table>
							<?php if ( ! array_key_exists( 'sender/sender.php', $all_plugins ) && ! array_key_exists( 'sender-pro/sender-pro.php', $all_plugins ) ) {
								echo '<p>' . __( 'If you want to send mailout to the users who have subscribed for newsletters use', 'subscriber' ) . ' <a href="https://bestwebsoft.com/products/wordpress/plugins/sender/" target="_blank">Sender plugin</a> ' . __( 'that sends mail to registered users. There is also a premium version of the plugin', 'subscriber' ) . ' - <a href="https://bestwebsoft.com/products/wordpress/plugins/sender/?k=01665f668edd3310e8c5cf13e9cb5181&pn=122&v=' . $sbscrbr_plugin_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">Sender Pro</a>, ' . __( 'allowing to create and save templates for letters, edit the content of messages with a visual editor TinyMce, set priority оf mailing, create and manage mailing lists.', 'subscriber' ) . '</p>';
							} ?>
						</div><!-- #sbscrbr_settings_block -->
						<div id="sbscrbr_settings_block_email_notifications" style="display: <?php echo ( isset( $_GET['tab'] ) && 'sbscrbr_email_notifications' == $_GET['tab'] ) ? 'block' : 'none'; ?>;">
							<table id="sbscrbr-settings-table" class="form-table">
								<tr>
									<th><?php _e( 'Email admin about new subscribed users', 'subscriber' ); ?></th>
									<td colspan="2">
										<input type="checkbox" name="sbscrbr_admin_message" value="1" <?php if ( '1' == $sbscrbr_options['admin_message'] ) echo 'checked="checked"'; ?> />
									</td>
								</tr>
								<tr valign="top" class="sbscrbr_for_admin_message">
									<th scope="row"><?php _e( "Recipient email address (To:)", 'subscriber' ); ?></th>
									<td colspan="2">
										<fieldset>
												<input type="radio" id="sbscrbr_to_email_user" name="sbscrbr_to_email" value="user" <?php if ( $sbscrbr_options['to_email'] == 'user' ) echo 'checked="checked" '; ?>/>
												<select class="sbscrbr-admin-email-settings" name="sbscrbr_email_user">
													<option disabled><?php _e( "Select a username", 'subscriber' ); ?></option>
														<?php $sbscrbr_userslogin = get_users( 'blog_id=' . $GLOBALS['blog_id'] . '&role=administrator' );
														foreach ( $sbscrbr_userslogin as $key => $value ) {
															if ( $value->data->user_email != '' ) { ?>
																<option value="<?php echo $value->data->user_login; ?>" <?php if ( $sbscrbr_options['email_user'] == $value->data->user_login ) echo 'selected="selected" '; ?>><?php echo $value->data->user_login; ?></option>
															<?php }
														} ?>
												</select><br>
												<input type="radio" id="sbscrbr_to_email_custom" name="sbscrbr_to_email" value="custom" <?php if ( 'custom' == $sbscrbr_options['to_email'] ) echo 'checked="checked" '; ?>/>
												<input type="text" class="sbscrbr-admin-email-settings" name="sbscrbr_email_custom" value="<?php echo implode( $sbscrbr_options['email_custom'], ', ' ); ?>" maxlength="500" />
												<span class="bws_info sbscrbr_floating_info"><?php _e( 'If necessary you can specify several email addresses separated by comma (For example: email1@example.com, email2@example.com).', 'subscriber' );?></span>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e( "'FROM' field", 'subscriber' ); ?></th>
									<td style="vertical-align: top;">
										<div><?php _e( "Name", 'subscriber' ); ?></div>
										<div>
											<input type="text" name="sbscrbr_from_custom_name" maxlength="250" value="<?php echo $sbscrbr_options['from_custom_name']; ?>"/>
										</div>
									</td>
									<td>
										<div><?php _e( "Email", 'subscriber' ); ?></div>
										<div>
											<input type="text" name="sbscrbr_from_email" maxlength="250" value="<?php echo $sbscrbr_options['from_email']; ?>"/>
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
													<li><strong>{user_email}</strong> - <?php _e( 'this shortcode will be replaced with the e-mail of a current user;', 'subscriber' ); ?></li>
													<li><strong>{profile_page}</strong> - <?php _e( 'this shortcode will be replaced with the link to profile page of current user;', 'subscriber' ); ?></li>
													<li><strong>{unsubscribe_link}</strong> - <?php _e( 'this shortcode will be replaced with the link to unsubscribe.', 'subscriber' ); ?></li>
												</ul>
												<p><?php _e( 'There must be a space after the shortcode otherwise the link will be incorrect.', 'subscriber' ); ?></p>
											</span>
										</span>
									</th>
									<td colspan="2">
										<?php /* check sender pro activation */
										$sender_pro_active = false;

										if ( array_key_exists( 'sender-pro/sender-pro.php', $all_plugins ) ) {
											if ( ! is_plugin_active( 'sender-pro/sender-pro.php' ) ) {
												$sender_for = ( is_multisite() ) ? __( 'Activate for network', 'subscriber' ) : __( 'Activate', 'subscriber' );
												$sender_pro_notice = sprintf( '<a href="%s">%s %s</a>', self_admin_url( 'plugins.php' ), $sender_for, 'Sender Pro by BestWebSoft' );
											} else {
												$sender_pro_active = true;
											}
										} else {
											$sender_pro_notice = sprintf( __( 'Install %s plugin in order to use HTML letters created with TinyMce visual editor', 'subscriber' ), ' <a href="https://bestwebsoft.com/products/wordpress/plugins/sender/?k=01665f668edd3310e8c5cf13e9cb5181&pn=122&v=' . $sbscrbr_plugin_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">Sender Pro by BestWebSoft</a>' );
										} ?>
										<div class="sbscrbr-messages-settings">
											<label><strong><?php _e( 'Message to admin about new subscribed users', 'subscriber' ); ?>:</strong></label><br>
											<span class="description"><?php _e( "Subject", 'subscriber' ); ?>:</span>
											<input type="text" class="sbscrbr-input-text" id="sbscrbr-admin-message-subject" name="sbscrbr_admin_message_subject" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['admin_message_subject'] ); ?>"/>
											<br/>
											<span class="description"><?php _e( "Text", 'subscriber' ); ?>:</span>
											<div class="sbscrbr-message-text">
												<input type="radio" name="sbscrbr_admin_message_use_sender" value="0"<?php if ( ! $sender_pro_active ) echo ' disabled'; if ( 0 == $sbscrbr_options['admin_message_use_sender'] || ! $sender_pro_active ) echo ' checked="checked" '; ?> />
												<textarea class="sbscrbr-input-text" id="sbscrbr-admin-message-text" name="sbscrbr_admin_message_text"><?php echo $sbscrbr_options['admin_message_text']; ?></textarea>
												<br/>
												<input type="radio" name="sbscrbr_admin_message_use_sender" value="1"<?php if ( ! $sender_pro_active ) echo ' disabled'; if ( 1 == $sbscrbr_options['admin_message_use_sender'] && $sender_pro_active ) echo ' checked="checked" '; ?> />
												<?php if ( $sender_pro_active ) {
													sbscrbr_sender_letters_list_select( 'sbscrbr_admin_message_sender_template_id', $sbscrbr_options['admin_message_sender_template_id'] );
												} else { ?>
													<span class="bws_info"><?php echo $sender_pro_notice; ?></span>
												<?php } ?>
											</div>
										</div>
										<div class="sbscrbr-messages-settings">
											<label><strong><?php _e( 'Message to subscribed users', 'subscriber' ); ?></strong>:</label><br>
											<span class="description"><?php _e( "Subject", 'subscriber' ); ?>:</span>
											<input type="text" class="sbscrbr-input-text" id="sbscrbr-subscribe-message-subject" name="sbscrbr_subscribe_message_subject" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['subscribe_message_subject'] ); ?>"/>
											<br/>
											<span class="description"><?php _e( "Text", 'subscriber' ); ?>:</span>
											<div class="sbscrbr-message-text">
												<input type="radio" name="sbscrbr_subscribe_message_use_sender" value="0"<?php if ( ! $sender_pro_active ) echo ' disabled'; if ( 0 == $sbscrbr_options['subscribe_message_use_sender'] || ! $sender_pro_active ) echo ' checked="checked" '; ?> />
												<textarea class="sbscrbr-input-text" id="sbscrbr-subscribe-message-text" name="sbscrbr_subscribe_message_text"><?php echo $sbscrbr_options['subscribe_message_text']; ?></textarea>
												<br/>
												<input type="radio" name="sbscrbr_subscribe_message_use_sender" value="1"<?php if ( ! $sender_pro_active ) echo ' disabled'; if ( 1 == $sbscrbr_options['subscribe_message_use_sender'] && $sender_pro_active ) echo ' checked="checked" '; ?> />
												<?php if ( $sender_pro_active ) {
													sbscrbr_sender_letters_list_select( 'sbscrbr_subscribe_message_sender_template_id', $sbscrbr_options['subscribe_message_sender_template_id'] );
												} else { ?>
													<span class="bws_info"><?php echo $sender_pro_notice; ?></span>
												<?php } ?>
											</div>
										</div>
										<div class="sbscrbr-messages-settings">
											<label><strong><?php _e( 'Message with unsubscribe link', 'subscriber' ); ?></strong>:</label><br>
											<span class="description"><?php _e( "Subject", 'subscriber' ); ?>:</span>
											<input type="text" class="sbscrbr-input-text" id="sbscrbr-unsubscribe-message-subject"  name="sbscrbr_unsubscribe_message_subject" maxlength="250" value="<?php echo esc_attr( $sbscrbr_options['unsubscribe_message_subject'] ); ?>"/>
											<br/>
											<span class="description"><?php _e( "Text", 'subscriber' ); ?>:</span>
											<div class="sbscrbr-message-text">
												<input type="radio" name="sbscrbr_unsubscribe_message_use_sender" value="0"<?php if ( ! $sender_pro_active ) echo ' disabled'; if ( 0 == $sbscrbr_options['unsubscribe_message_use_sender'] || ! $sender_pro_active ) echo ' checked="checked" '; ?> />
												<textarea class="sbscrbr-input-text" id="sbscrbr-unsubscribe-message-text" name="sbscrbr_unsubscribe_message_text"><?php echo $sbscrbr_options['unsubscribe_message_text']; ?></textarea>
												<br/>
												<input type="radio" name="sbscrbr_unsubscribe_message_use_sender" value="1"<?php if ( ! $sender_pro_active ) echo ' disabled'; if ( 1 == $sbscrbr_options['unsubscribe_message_use_sender'] && $sender_pro_active ) echo ' checked="checked" '; ?> />
												<?php if ( $sender_pro_active ) {
													sbscrbr_sender_letters_list_select( 'sbscrbr_unsubscribe_message_sender_template_id', $sbscrbr_options['unsubscribe_message_sender_template_id'] );
												} else { ?>
													<span class="bws_info"><?php echo $sender_pro_notice; ?></span>
												<?php } ?>
											</div>
										</div>
										<div class="sbscrbr-messages-settings">
											<label><strong><?php _e( 'Text to be attached to letters', 'subscriber' ); ?></strong>:</label>
											<br>
											<textarea class="sbscrbr-input-text" id="sbscrbr-unsubscribe-link-text" name="sbscrbr_unsubscribe_link_text"><?php echo $sbscrbr_options['unsubscribe_link_text']; ?></textarea>
											<br/>
											<span class="bws_info"><?php printf( __( 'This text will be attached to each letter of the mailing, which was created with plugin %s.', 'subscriber' ), '<a href="https://bestwebsoft.com/products/wordpress/plugins/sender/?k=01665f668edd3310e8c5cf13e9cb5181&pn=122&v=' . $sbscrbr_plugin_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">Sender by BestWebSoft</a>' ); ?></span>
										</div>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php printf( __( "Shortcode %s refers to", 'subscriber' ), "{unsubscribe_link}" ); ?></th>
									<td colspan="2">
										<fieldset>
											<label>
												<input type="radio" name="sbscrbr_shortcode_link_type" value="url" <?php if ( $sbscrbr_options['shortcode_link_type'] == 'url' ) echo 'checked="checked" '; ?> />
												URL
												<input type="url" name="sbscrbr_shortcode_url" value="<?php echo $sbscrbr_options['shortcode_url']; ?>" />
												<span class="bws_help_box dashicons dashicons-editor-help">
													<span class="bws_hidden_help_text" style="width: 240px;">
														<p><?php _e( 'Service message is displayed in a subscriber form if the last one was added on the page.', 'subscriber' ); ?></p>
													</span>
												</span>
											</label>
											<br />
											<label>
												<input type="radio" name="sbscrbr_shortcode_link_type" value="text" <?php if ( $sbscrbr_options['shortcode_link_type'] == 'text' ) echo 'checked="checked" '; ?> />
												<?php _e( 'Separate page with appropriate service message', 'subscriber' ); ?>
											</label>
										</fieldset>
									</td>
								</tr>
							</table>
						</div><!-- #sbscrbr_settings_block_email_notifications -->
						<p class="submit">
							<input type="submit" id="bws-submit-button" class="button-primary" value="<?php _e( 'Save Changes', 'subscriber' ) ?>" />
							<input type="hidden" name="sbscrbr_form_submit" value="submit" />
							<?php wp_nonce_field( $plugin_basename, 'sbscrbr_nonce_name' ); ?>
						</p>
					</form>
					<?php bws_form_restore_default_settings( $plugin_basename );
				}
			} elseif ( isset( $_GET['tab'] ) && 'sbscrbr_users' == $_GET['tab'] ) {
				$sbscrbr_users_list = new Sbscrbr_User_List(); ?>
				<div class="sbscrbr-users-list-page" style="margin: 10px 0 0;">
					<?php if ( isset( $_REQUEST['s'] ) && $_REQUEST['s'] ) {
						printf( '<span class="subtitle">' . sprintf( __( 'Search results for &#8220;%s&#8221;', 'subscriber' ), wp_html_excerpt( esc_html( stripslashes( $_REQUEST['s'] ) ), 50 ) ) . '</span>' );
					}
					echo '<h2 class="screen-reader-text">' . __( 'Filter subscribers list', 'subscriber' ) . '</h2>';
					$sbscrbr_users_list->views(); ?>
					<form method="post">
						<?php $sbscrbr_users_list->prepare_items();
						$sbscrbr_users_list->search_box( __( 'search', 'subscriber' ), 'sbscrbr' );
						$sbscrbr_users_list->display();
						wp_nonce_field( plugin_basename( __FILE__ ), 'sbscrbr_list_nonce_name' ); ?>
					</form>
				</div><!-- .wrap .sbscrbr-users-list-page -->
			<?php } elseif ( isset( $_GET['tab'] ) && 'go_pro' == $_GET['tab'] ) {
				bws_go_pro_tab_show( $bws_hide_premium_options_check, $sbscrbr_plugin_info, $plugin_basename, 'sbscrbr_settings_page', 'sbscrbrpr_settings_page', 'subscriber-pro/subscriber-pro.php', 'subscriber', 'd356381b0c3554404e34cdc4fe936455', '122', isset( $go_pro_result['pro_plugin_is_activated'] ) );
			} elseif ( isset( $_GET['tab'] ) && 'custom_code' == $_GET['tab'] ) {
				bws_custom_code_tab();
			}
			bws_plugin_reviews_block( $sbscrbr_plugin_info['Name'], 'subscriber' ); ?>
		</div><!-- .wrap -->
	<?php }
}

/**
 * Display list of letters
 * @param   string  name for select
 * @param   int     $letters_list_id of selected letters
 * @return  void
 */
if ( ! function_exists( 'sbscrbr_sender_letters_list_select' ) ) {
	function sbscrbr_sender_letters_list_select( $name, $letters_list_id = "" ) {
		global $wpdb;
		$count_selected = 0;
		$error = '<select name="' . $name . '" disabled="disabled"><option>' . __( 'Letters not found', 'subscriber' ) . '</option>';

		$list_data = $wpdb->get_results( "SELECT `mail_send_id`, `subject`, `letter_in_trash` FROM `" . $wpdb->prefix . "sndr_mail_send` ORDER BY `subject`;", ARRAY_A );
		if ( ! empty( $list_data ) ) {
			$html = '<select name="' . $name . '">';
			foreach ( $list_data as $list ) {
				if ( 0 == $list['letter_in_trash'] ) {
					$count_selected ++;
					$selected = ( ! empty( $letters_list_id ) && $list['mail_send_id'] == $letters_list_id ) ? ' selected="selected"' : '';
					$item_title   = ( empty( $list['subject'] ) ) ? ' - ' . __( 'empty title', 'subscriber' ) . ' - ' : $list['subject'] ;
					$html .= '<option value="' . $list['mail_send_id'] . '"' . $selected . '>' . $item_title . '</option>';
				}
			}
			if ( $count_selected == 0 ) {
				$html = $error;
			} else {
				$count_selected = 0;
			}
			$html .= '</select> <span class="bws_info">' . __( 'Choose a letter', 'subscriber' ) . '</span>';
		} else {
			/* display error message */
			$html = $error . '</select> <span class="bws_info">' . __( 'Choose a letter', 'subscriber' ) . '</span>';
		}
		echo $html;
	}
}

/**
 * Add checkbox "Subscribe" to the custom form
 * @param array() $args array with settings
 * @return array() $params
 */
if ( ! function_exists( 'sbscrbr_checkbox_add' ) ) {
	function sbscrbr_checkbox_add( $args ) {

		$params = array(
			'form_id' => 'custom',
			'label'   => __( 'Subscribe', 'subscriber' ),
			'display' => false,
			'content' => ''
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
		if ( isset( $_POST['sbscrbr_form_id'] ) && $_POST['sbscrbr_form_id'] == $params['form_id'] && isset( $_POST['sbscrbr_checkbox_subscribe'] ) && $_POST['sbscrbr_checkbox_subscribe'] == 1 ) {
			$attr_checked = 'checked="checked"';
		}

		$params['content'] = sprintf(
			'<div class="sbscrbr-cb">
				%s
				<label><input type="checkbox" name="sbscrbr_checkbox_subscribe" value="1" %s /> %s</label>
				<input type="hidden" name="sbscrbr_submit_email" value="sbscrbr_submit_email" />
				<input type="hidden" name="sbscrbr_form_id" value="%s" />
			</div>',
			$display_message, $attr_checked, $params['label'], $params['form_id']
		);

		return $params;
	}
}

/**
 * Result of checking when adding an email from custom form
 * @param array() $args array with settings
 * @return array() $params - Result from Sbscrbr_Handle_Form_Data
 */
if ( ! function_exists( 'sbscrbr_checkbox_check' ) ) {
	function sbscrbr_checkbox_check( $args ) {
		global $sbscrbr_handle_form_data;

		if ( isset( $_POST['sbscrbr_checkbox_subscribe'] ) && $_POST['sbscrbr_checkbox_subscribe'] == 1 ) {

			$params = array(
				'form_id'       => 'custom',
				'email'         => '',
				'unsubscribe'   => false,
				'skip_captcha'  => true,
				'custom_events' => array()
			);

			if ( is_array( $args ) ) {
				$params = array_merge( $params, $args );
				$params = array_map( 'stripslashes_deep', $params );
			}

			if ( isset( $_POST['sbscrbr_form_id'] ) && $_POST['sbscrbr_form_id'] == $params['form_id'] ) {
				if( ! empty( $params['custom_events'] ) && is_array( $params['custom_events'] ) ) {
					$sbscrbr_handle_form_data->custom_events( $params['custom_events'] );
				}
				$params['response'] = $sbscrbr_handle_form_data->submit( $params['email'], $params['unsubscribe'], $params['skip_captcha'] );
			} else {
				$params['response'] = array(
					'action'  => 'checkbox_check',
					'type'    => 'error',
					'reason'  => 'DOES_NOT_MATCH_FORMS_IDS',
					'message' => sprintf( '<p class="sbscrbr-form-error">%s</p>', __( 'The ID of the verifiable form does not match the ID of the sending form.', 'subscriber' ) )
				);
			}
		} else {
			$params = $args;
		}

		return $params;
	}
}

if ( ! function_exists( 'sbscrbr_widgets_init' ) ) {
	function sbscrbr_widgets_init() {
		register_widget( "Sbscrbr_Widget" );
	}
}

/**
 * Class extends WP class WP_Widget, and create new widget
 *
 */
if ( ! class_exists( 'Sbscrbr_Widget' ) ) {
	class Sbscrbr_Widget extends WP_Widget {
		/**
		 * constructor of class
		 */
	 	public function __construct() {
	 		parent::__construct(
	 			'sbscrbr_widget',
	 			__( 'Subscriber Sign Up Form', 'subscriber' ),
	 			array( 'description' => __( 'Displaying the registration form for newsletter subscribers.', 'subscriber' ) )
			);
		}

		/**
		 * Function to displaying widget in front end
		 * @param  array()     $args      array with sidebar settings
		 * @param  array()     $instance  array with widget settings
		 * @return void
		 */
		public function widget( $args, $instance ) {
			global $sbscrbr_handle_form_data, $sbscrbr_display_message;

			$widget_title = ( ! empty( $instance['widget_title'] ) ) ? apply_filters( 'widget_title', $instance['widget_title'], $instance, $this->id_base ) : '';

			$action_form = ( is_front_page() ) ? home_url( '/' ) : '';
			$action_form .= '#sbscrbr-form-' . $args['widget_id'];

			if ( isset( $instance['widget_apply_settings'] ) && '1' == $instance['widget_apply_settings'] ) { /* load plugin settings */
				global $sbscrbr_options;
				if ( empty( $sbscrbr_options ) )
					$sbscrbr_options = is_multisite() ? get_site_option( 'sbscrbr_options' ) : get_option( 'sbscrbr_options' );

				$widget_form_label		= $sbscrbr_options['form_label'];
				$widget_placeholder		= $sbscrbr_options['form_placeholder'];
				$widget_checkbox_label	= $sbscrbr_options['form_checkbox_label'];
				$widget_button_label	= $sbscrbr_options['form_button_label'];
			} else { /* load widget settings */
				$widget_form_label		= isset( $instance['widget_form_label'] ) ? $instance['widget_form_label'] : null;
				$widget_placeholder		= isset( $instance['widget_placeholder'] ) ? $instance['widget_placeholder'] : __( 'E-mail', 'subscriber' );
				$widget_checkbox_label	= isset( $instance['widget_checkbox_label'] ) ? $instance['widget_checkbox_label'] : __( 'unsubscribe', 'subscriber' );
				$widget_button_label	= isset( $instance['widget_button_label'] ) ? $instance['widget_button_label'] : __( 'Subscribe', 'subscriber' );
			}

			/* get report message */
			$report_message = '';
			if ( $sbscrbr_handle_form_data->last_action == 'unsubscribe_from_email' && ! isset( $sbscrbr_display_message ) ) {
				$report_message = $sbscrbr_handle_form_data->last_response;
				$sbscrbr_display_message = true;
			}
			if ( isset( $_POST['sbscrbr_submit_email'] ) && isset( $_POST['sbscrbr_form_id'] ) && $_POST['sbscrbr_form_id'] == $args['widget_id'] ) {
				$report_message = $sbscrbr_handle_form_data->submit( $_POST['sbscrbr_email'], ( isset( $_POST['sbscrbr_unsubscribe'] ) && $_POST['sbscrbr_unsubscribe'] == 'yes' ) ? true : false );
			}

			if ( ! wp_script_is( 'sbscrbr_form_scripts', 'registered' ) )
				wp_register_script( 'sbscrbr_form_scripts', plugins_url( 'js/form_script.js', __FILE__ ), array( 'jquery' ), false, true );

			echo $args['before_widget'] . $args['before_title'] . $widget_title . $args['after_title']; ?>
			<form id="sbscrbr-form-<?php echo $args['widget_id']; ?>" method="post" action="<?php echo $action_form; ?>" id="subscrbr-form-<?php echo $args['widget_id']; ?>" class="subscrbr-sign-up-form" style="position: relative;">
				<?php if ( empty( $report_message ) ) {
					if ( ! empty( $widget_form_label ) )
						echo '<p class="sbscrbr-label-wrap">' . $widget_form_label . '</p>';
				} else {
					echo $report_message['message'];
				} ?>
				<p class="sbscrbr-email-wrap">
					<input type="text" name="sbscrbr_email" value="" placeholder="<?php echo $widget_placeholder; ?>"/>
				</p>
				<p class="sbscrbr-unsubscribe-wrap">
					<label for="sbscrbr-<?php echo $args['widget_id']; ?>">
						<input id="sbscrbr-<?php echo $args['widget_id']; ?>" type="checkbox" name="sbscrbr_unsubscribe" value="yes" style="vertical-align: middle;"/>
						<?php echo $widget_checkbox_label; ?>
					</label>
				</p>
				<?php echo apply_filters( 'sbscrbr_add_field', '', 'bws_subscriber' ); ?>
				<p class="sbscrbr-submit-block" style="position: relative;">
					<input type="submit" value="<?php echo $widget_button_label; ?>" name="sbscrbr_submit_email" class="submit" />
					<input type="hidden" value="<?php echo $args['widget_id']; ?>" name="sbscrbr_form_id" />
				</p>
			</form>
			<?php echo $args['after_widget'];
		}

		/**
		 * Function to displaying widget settings in back end
		 * @param  array()     $instance  array with widget settings
		 * @return void
		 */
		public function form( $instance ) {
			$widget_title			= isset( $instance['widget_title'] ) ? stripslashes( esc_html( $instance['widget_title'] ) ) : null;
			$widget_form_label		= isset( $instance['widget_form_label'] ) ? stripslashes( esc_html( $instance['widget_form_label'] ) ) : null;
			$widget_placeholder		= isset( $instance['widget_placeholder'] ) ? stripslashes( esc_html( $instance['widget_placeholder'] ) ) : __( 'E-mail', 'subscriber' );
			$widget_checkbox_label	= isset( $instance['widget_checkbox_label'] ) ? stripslashes( esc_html( $instance['widget_checkbox_label'] ) ) : __( 'unsubscribe', 'subscriber' );
			$widget_button_label	= isset( $instance['widget_button_label'] ) ? stripslashes( esc_html( $instance['widget_button_label'] ) ) : __( 'Subscribe', 'subscriber' );
			$widget_apply_settings	= isset( $instance['widget_apply_settings'] ) && '1' == $instance['widget_apply_settings'] ? '1' : '0'; ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'widget_title' ); ?>">
					<?php _e( 'Title', 'subscriber' ); ?>:
					<input class="widefat" id="<?php echo $this->get_field_id( 'widget_title' ); ?>" name="<?php echo $this->get_field_name( 'widget_title' ); ?>" type="text" value="<?php echo esc_attr( $widget_title ); ?>"/>
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'widget_form_label' ); ?>">
					<?php _e( 'Text above the subscribe form', 'subscriber' ); ?>:
					<textarea class="widefat" id="<?php echo $this->get_field_id( 'widget_form_label' ); ?>" name="<?php echo $this->get_field_name( 'widget_form_label' ); ?>"><?php echo $widget_form_label; ?></textarea>
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'widget_placeholder' ); ?>">
					<?php _e( 'Placeholder for text field "E-mail"', 'subscriber' ); ?>:
					<input class="widefat" id="<?php echo $this->get_field_id( 'widget_placeholder' ); ?>" name="<?php echo $this->get_field_name( 'widget_placeholder' ); ?>" type="text" value="<?php echo esc_attr( $widget_placeholder ); ?>"/>
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'widget_checkbox_label' ); ?>">
					<?php _e( 'Label for "Unsubscribe" checkbox', 'subscriber' ); ?>:
					<input class="widefat" id="<?php echo $this->get_field_id( 'widget_checkbox_label' ); ?>" name="<?php echo $this->get_field_name( 'widget_checkbox_label' ); ?>" type="text" value="<?php echo esc_attr( $widget_checkbox_label ); ?>"/>
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'widget_button_label' ); ?>">
					<?php _e( 'Label for "Subscribe" button', 'subscriber' ); ?>:
					<input class="widefat" id="<?php echo $this->get_field_id( 'widget_button_label' ); ?>" name="<?php echo $this->get_field_name( 'widget_button_label' ); ?>" type="text" value="<?php echo esc_attr( $widget_button_label ); ?>"/>
				</label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'widget_apply_settings' ); ?>">
					<input id="<?php echo $this->get_field_id( 'widget_apply_settings' ); ?>" name="<?php echo $this->get_field_name( 'widget_apply_settings' ); ?>" type="checkbox" value="1" <?php if ( '1' == $widget_apply_settings ) { echo 'checked="checked"'; } ?>/>
					<?php _e( 'apply plugin settings', 'subscriber' ); ?>
				</label>
			</p>
		<?php }

		/**
		 * Function to save widget settings
		 * @param array()    $new_instance  array with new settings
		 * @param array()    $old_instance  array with old settings
		 * @return array()   $instance      array with updated settings
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = array();

			$instance['widget_title']			= ( ! empty( $new_instance['widget_title'] ) ) ? strip_tags( $new_instance['widget_title'] ) : null;
			$instance['widget_form_label']		= ( ! empty( $new_instance['widget_form_label'] ) ) ? strip_tags( $new_instance['widget_form_label'] ) : null;
			$instance['widget_placeholder']		= ( ! empty( $new_instance['widget_placeholder'] ) ) ? strip_tags( $new_instance['widget_placeholder'] ) : null;
			$instance['widget_checkbox_label']	= ( ! empty( $new_instance['widget_checkbox_label'] ) ) ? strip_tags( $new_instance['widget_checkbox_label'] ) : null;
			$instance['widget_button_label']	= ( ! empty( $new_instance['widget_button_label'] ) ) ? strip_tags( $new_instance['widget_button_label'] ) : null;
			$instance['widget_apply_settings']	= ( ! empty( $new_instance['widget_apply_settings'] ) ) ? strip_tags( $new_instance['widget_apply_settings'] ) : null;

			return $instance;
		}
	}
}

/**
 * Add shortcode
 * @param    array()   $instance
 * @return   string    $content     content of subscribe form
 */
if ( ! function_exists( 'sbscrbr_subscribe_form' ) ) {
	function sbscrbr_subscribe_form() {
		global $sbscrbr_options, $sbscrbr_handle_form_data, $sbscrbr_display_message, $sbscrbr_shortcode_count;

		$sbscrbr_shortcode_count = empty( $sbscrbr_shortcode_count ) ? 1 : $sbscrbr_shortcode_count + 1;
		$form_id = $sbscrbr_shortcode_count == 1 ? '' : '-' . $sbscrbr_shortcode_count;

		if ( ! wp_script_is( 'sbscrbr_form_scripts', 'registered' ) )
			wp_register_script( 'sbscrbr_form_scripts', plugins_url( 'js/form_script.js', __FILE__ ), array( 'jquery' ), false, true );

		$action_form = ( is_front_page() ) ? home_url( '/' ) : '';
		$action_form .= '#sbscrbr-form' . $form_id;

		if ( empty( $sbscrbr_options ) )
			$sbscrbr_options = is_multisite() ? get_site_option( 'sbscrbr_options' ) : get_option( 'sbscrbr_options' );

		/* get report message */
		$report_message = '';
		if ( $sbscrbr_handle_form_data->last_action == 'unsubscribe_from_email' && ! isset( $sbscrbr_display_message ) ) {
			$report_message = $sbscrbr_handle_form_data->last_response;
			$sbscrbr_display_message = true;
		}
		if ( isset( $_POST['sbscrbr_submit_email'] ) && isset( $_POST['sbscrbr_form_id'] ) && $_POST['sbscrbr_form_id'] == 'sbscrbr_shortcode_' . $sbscrbr_shortcode_count ) {
			$report_message = $sbscrbr_handle_form_data->submit( $_POST['sbscrbr_email'], ( isset( $_POST['sbscrbr_unsubscribe'] ) && $_POST['sbscrbr_unsubscribe'] == 'yes' ) ? true : false );
		}
		$content        = '<form id="sbscrbr-form' . $form_id . '" method="post" action="' . $action_form . '" class="subscrbr-sign-up-form">';
		if ( empty( $report_message ) ) {
			if ( ! empty( $sbscrbr_options['form_label'] ) ) {
				$content .= '<p class="sbscrbr-label-wrap">' . $sbscrbr_options['form_label'] . '</p>';
			}
		} else {
			$content .= $report_message['message'];
		}
		$content .= '
			<p class="sbscrbr-email-wrap">
				<input type="text" name="sbscrbr_email" value="" placeholder="' . $sbscrbr_options['form_placeholder'] . '"/>
			</p>
			<p class="sbscrbr-unsubscribe-wrap">
				<label for="sbscrbr-checkbox">
					<input id="sbscrbr-checkbox" type="checkbox" name="sbscrbr_unsubscribe" value="yes" style="vertical-align: middle;"/> ' .
					$sbscrbr_options['form_checkbox_label'] .
				'</label>
			</p>';
		$content .= apply_filters( 'sbscrbr_add_field', false, '', 'bws_subscriber' );
		$content .= '<p class="sbscrbr-submit-block" style="position: relative;">
				<input type="submit" value="' . $sbscrbr_options['form_button_label'] . '" name="sbscrbr_submit_email" class="submit" />
				<input type="hidden" value="sbscrbr_shortcode_' . $sbscrbr_shortcode_count . '" name="sbscrbr_form_id" />
			</p>
		</form>';
		return $content;
	}
}

/**
 * Class Sbscrbr_Handle_Form_Data to handle data from subscribe form
 * and URL's from email for subscribe/unsubscribe users
 */
if ( ! class_exists( 'Sbscrbr_Handle_Form_Data' ) ) {
	class Sbscrbr_Handle_Form_Data {

		protected $wpdb;
		private $options;
		private $prefix;
		private $default_events;
		private $events;
		private $events_wrapper;
		public $last_action = 'init';
		public $last_response = array();

		function __construct() {
			global $wpdb;

			$this->wpdb = $wpdb;
			$this->options = ( is_multisite() ) ? get_site_option( 'sbscrbr_options' ) : get_option( 'sbscrbr_options' );
			$this->prefix = is_multisite() ? $this->wpdb->base_prefix :$this->wpdb->prefix;

			$this->default_events = array(
				'bad_request'             => $this->options['bad_request'],
				'empty_email'             => $this->options['empty_email'],
				'invalid_email'           => $this->options['invalid_email'],
				'error_subscribe'         => $this->options['error_subscribe'],
				'already_unsubscribe'     => $this->options['already_unsubscribe'],
				'not_exists_email'		  => $this->options['not_exists_email'],
				'already_subscribe'       => $this->options['already_subscribe'],
				'denied_subscribe'        => $this->options['denied_subscribe'],
				'not_exists_unsubscribe'  => $this->options['not_exists_unsubscribe'],
				'done_subscribe'          => $this->options['done_subscribe'],
				'check_email_unsubscribe' => $this->options['check_email_unsubscribe'],
				'done_unsubscribe'        => $this->options['done_unsubscribe'],
				'cannot_send_email'		  => $this->options['cannot_send_email']
			);

			$this->events = $this->default_events;

			$this->events_wrapper = array(
				'error' => '<p class="sbscrbr-form-error">%s</p>',
				'done'  => '<p class="sbscrbr-form-done">%s</p>'
			);
		}

		public function custom_events( $events = array() ) {
			if ( $events && is_array( $events ) ) {
				$this->events = array_merge( $this->events, $events );
			}
		}

		public function default_events() {
			$this->events = $this->default_events;
		}

		public function submit( $email, $unsubscribe = false, $skip_captcha = false ) {

			if ( has_filter( 'sbscrbr_check' ) && $skip_captcha == false ) {
				$check_result = apply_filters( 'sbscrbr_check', true );
				if ( false === $check_result || ( is_string( $check_result ) && ! empty( $check_result ) ) ) {
					$this->last_response = array(
						'action'  => $this->last_action,
						'type'    => 'error',
						'reason'  => 'CPTCH_CHECK_FALSE',
						'message' => sprintf( $this->events_wrapper['error'], $check_result )
					);

					return $this->last_response;
				}
			}

			if ( empty( $email ) ) {
				$this->last_response = array(
					'action'  => $this->last_action,
					'type'    => 'error',
					'reason'  => 'EMPTY_EMAIL',
					'message' => sprintf( $this->events_wrapper['error'], $this->events['empty_email'] )
				);

				return $this->last_response;
			}

			if ( ! is_email( $email ) ) {

				$this->last_response = array(
					'action'  => $this->last_action,
					'type'    => 'error',
					'reason'  => 'INVALID_EMAIL',
					'message' => sprintf( $this->events_wrapper['error'], $this->events['invalid_email'] )
				);

				return $this->last_response;
			}

			if ( $unsubscribe == true ) {
				return $this->unsubscribe_from_form( $email );
			} else {
				return $this->subscribe_from_form( $email );
			}

		}

		private function subscribe_from_form( $email ) {
			$this->last_action = 'subscribe_from_form';

			$user_exists = email_exists( $email );
			$user_status = sbscrbr_check_status( $email );

			if ( $user_exists ) { /* if user already registered */
				if ( ! empty( $user_status ) ) {
					switch ( $user_status ) {
						case 'not_exists': /* add user data to database table of plugin */
							$user = get_user_by( 'email', $email );
							$this->wpdb->insert( $this->prefix . 'sndr_mail_users_info',
								array(
									'id_user'           => $user->ID,
									'user_email'        => $email,
									'user_display_name' => $user->display_name,
									'subscribe'         => 1,
									'unsubscribe_code'  => md5( rand( 0, 10 ) / 10 ),
									'subscribe_time'    => time()
								)
							);
							if ( $this->wpdb->last_error ) {
								$this->last_response = array(
									'action'  => $this->last_action,
									'type'    => 'error',
									'reason'  => 'ERROR_SUBSCRIBE',
									'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] )
								);
							} else {
								$this->last_response = array(
									'action'  => $this->last_action,
									'type'    => 'done',
									'reason'  => 'done_subscribe',
									'message' => sprintf( $this->events_wrapper['done'], $this->events['done_subscribe'] )
								);
								if( ! sbscrbr_send_mails( $email, '' ) ) { /* send letters to admin and new registerd user*/
									$this->last_response = array(
										'action'  => $this->last_action,
										'type'    => 'error',
										'reason'  => 'cannot_send_email',
										'message' => sprintf( $this->events_wrapper['error'], $this->events['cannot_send_email'] )
									);
								}
							}
							break;
						case 'subscribed':
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'error',
								'reason'  => 'ALREADY_SUBSCRIBE',
								'message' => sprintf( $this->events_wrapper['error'], $this->events['already_subscribe'] )
							);
							break;
						case 'not_subscribed':
						case 'in_trash':
							$this->wpdb->update( $this->prefix . 'sndr_mail_users_info',
								array(
									'subscribe' => '1',
									'delete'    => '0'
								),
								array(
									'user_email' => $email
								)
							);
							if ( $this->wpdb->last_error ) {
								$this->last_response = array(
									'action'  => $this->last_action,
									'type'    => 'error',
									'reason'  => 'ERROR_SUBSCRIBE',
									'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] )
								);
							} else {
								$this->last_response = array(
									'action'  => $this->last_action,
									'type'    => 'done',
									'reason'  => 'done_subscribe',
									'message' => sprintf( $this->events_wrapper['done'], $this->events['done_subscribe'] )
								);
								if( ! sbscrbr_send_mails( $email, '' ) ) { /* send letters to admin and new registerd user*/
									$this->last_response = array(
										'action'  => $this->last_action,
										'type'    => 'error',
										'reason'  => 'cannot_send_email',
										'message' => sprintf( $this->events_wrapper['error'], $this->events['cannot_send_email'] )
									);
								}
							}
							break;
						case 'in_black_list':
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'error',
								'reason'  => 'DENIED_SUBSCRIBE',
								'message' => sprintf( $this->events_wrapper['error'], $this->events['denied_subscribe'] )
							);
							break;
						default:
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'error',
								'reason'  => 'ERROR_SUBSCRIBE',
								'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] )
							);
							break;
					}
				} else {
					$this->last_response = array(
						'action'  => $this->last_action,
						'type'    => 'error',
						'reason'  => 'ERROR_SUBSCRIBE',
						'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] )
					);
				}
			} else {
				$user_password = wp_generate_password( $length = 12, $include_standard_special_chars = false );
				/* register new user */
				$userdata = array(
					'user_login'    => $email,
					'nickname'      => $email,
					'user_pass'     => $user_password,
					'user_email'    => $email,
					'display_name'  => $email,
					'role'          => 'sbscrbr_subscriber'
				);
				$user_id = wp_insert_user( $userdata );
				if ( is_wp_error( $user_id ) ) {
					$this->last_response = array(
						'action'  => $this->last_action,
						'type'    => 'error',
						'reason'  => 'ERROR_SUBSCRIBE',
						'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] )
					);
				} else {
					/* if "Sender" plugin by BWS is not installed and activated */
					if ( ! function_exists( 'sndr_mail_register_user' ) && ! function_exists( 'sndrpr_mail_register_user' ) ) {
						if ( ! empty( $user_status ) ) {
							switch ( $user_status ) {
								case 'not_exists': /* add user data to database table of plugin */
									$this->wpdb->insert( $this->prefix . 'sndr_mail_users_info',
										array(
											'id_user'           => $user_id,
											'user_email'        => $email,
											'user_display_name' => $email,
											'subscribe'         => 1,
											'unsubscribe_code'  => md5( rand( 0, 10 ) / 10 ),
											'subscribe_time'    => time()
										)
									);
									break;
								case 'subscribed':
									$this->last_response = array(
										'action'  => $this->last_action,
										'type'    => 'done',
										'reason'  => 'done_subscribe',
										'message' => sprintf( $this->events_wrapper['done'], $this->events['done_subscribe'] )
									);
									break;
								case 'not_subscribed':
								case 'in_trash':
									$this->wpdb->update( $this->prefix . 'sndr_mail_users_info',
										array(
											'subscribe' => '1',
											'delete'    => '0'
										),
										array(
											'user_email' => $email
										)
									);
									break;
								case 'in_black_list':
									$this->last_response = array(
										'action'  => $this->last_action,
										'type'    => 'error',
										'reason'  => 'DENIED_SUBSCRIBE',
										'message' => sprintf( $this->events_wrapper['error'], $this->events['denied_subscribe'] )
									);
									break;
								default:
									$this->last_response = array(
										'action'  => $this->last_action,
										'type'    => 'error',
										'reason'  => 'ERROR_SUBSCRIBE',
										'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] )
									);
									break;
							}
						} else {
							$this->wpdb->insert( $this->prefix . 'sndr_mail_users_info',
								array(
									'id_user'           => $user_id,
									'user_email'        => $email,
									'user_display_name' => $email,
									'subscribe'         => 1,
									'unsubscribe_code'  => md5( rand( 0, 10 ) / 10 ),
									'subscribe_time'    => time()
								)
							);
						}
					}

					if ( $this->wpdb->last_error ) {
						$this->last_response = array(
							'action'  => $this->last_action,
							'type'    => 'error',
							'reason'  => 'ERROR_SUBSCRIBE',
							'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] )
						);
					} else {
						$this->last_response = array(
							'action'  => $this->last_action,
							'type'    => 'done',
							'reason'  => 'done_subscribe',
							'message' => sprintf( $this->events_wrapper['done'], $this->events['done_subscribe'] )
						);
						if( ! sbscrbr_send_mails( $email, $user_password ) ) { /* send letters to admin and new registerd user*/
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'error',
								'reason'  => 'cannot_send_email',
								'message' => sprintf( $this->events_wrapper['error'], $this->events['cannot_send_email'] )
							);
						}
					}
				}
			}
			return $this->last_response;
		}

		private function unsubscribe_from_form( $email ) {
			global $sbscrbr_send_unsubscribe_mail;

			$this->last_action = 'unsubscribe_from_form';

			$user_exists = email_exists( $email );
			$user_status = sbscrbr_check_status( $email );

			if ( $user_exists ) {
				if ( ! empty( $user_status ) ) {
					switch ( $user_status ) {
						case 'not_exists':
						case 'not_subscribed':
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'error',
								'reason'  => 'ALREADY_UNSUBSCRIBE',
								'message' => sprintf( $this->events_wrapper['error'], $this->events['already_unsubscribe'] )
							);
							break;
						case 'subscribed':
						case 'in_trash':
						case 'in_black_list':
							if ( $sbscrbr_send_unsubscribe_mail !== true ) {
								$result = sbscrbr_sent_unsubscribe_mail( $email ); /* send email with unsubscribe link */
								if ( ! empty( $result ) ) { /* show report message */
									if ( $result['done'] ) {
										$this->last_response = array(
											'action'  => $this->last_action,
											'type'    => 'done',
											'reason'  => 'CHECK_EMAIL_UNSUBSCRIBE',
											'message' => sprintf( $this->events_wrapper['done'], $this->events['check_email_unsubscribe'] )
										);
									} else {
										$this->last_response = array(
											'action'  => $this->last_action,
											'type'    => 'error',
											'reason'  => 'CHECK_EMAIL_UNSUBSCRIBE',
											'message' => sprintf( $this->events_wrapper['error'], $result['error'] )
										);
									}
								} else {
									$this->last_response = array(
										'action'  => $this->last_action,
										'type'    => 'error',
										'reason'  => 'BAD_REQUEST',
										'message' => sprintf( $this->events_wrapper['error'], $this->events['bad_request'] )
									);
								}
							}
							break;
						default:
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'error',
								'reason'  => 'ERROR_SUBSCRIBE',
								'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] )
							);
							break;
					}
				} else {
					$this->last_response = array(
						'action'  => $this->last_action,
						'type'    => 'error',
						'reason'  => 'ERROR_SUBSCRIBE',
						'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] )
					);
				}
			} else {
				/* if no user with this e-mail */
				/* check user status */
				if ( 'subscribed' == $user_status ) {
					if ( $sbscrbr_send_unsubscribe_mail !== true ) {
						$result = sbscrbr_sent_unsubscribe_mail( $email ); /* send email with unsubscribe link */
						if ( ! empty( $result ) ) { /* show report message */
							if ( $result['done'] ) {
								$this->last_response = array(
									'action'  => $this->last_action,
									'type'    => 'done',
									'reason'  => 'CHECK_EMAIL_UNSUBSCRIBE',
									'message' => sprintf( $this->events_wrapper['done'], $this->events['check_email_unsubscribe'] )
								);
							} else {
								$this->last_response = array(
									'action'  => $this->last_action,
									'type'    => 'error',
									'reason'  => 'CHECK_EMAIL_UNSUBSCRIBE',
									'message' => sprintf( $this->events_wrapper['error'], $result['error'] )
								);
							}
						} else {
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'error',
								'reason'  => 'BAD_REQUEST',
								'message' => sprintf( $this->events_wrapper['error'], $this->events['bad_request'] )
							);
						}
					}
				} else {
					$this->last_response = array(
						'action'  => $this->last_action,
						'type'    => 'error',
						'reason'  => 'NOT_EXISTS_EMAIL',
						'message' => sprintf( $this->events_wrapper['error'], $this->events['not_exists_email'] )
					);
				}
			}
			return $this->last_response;
		}

		public function unsubscribe_from_email( $unsubscribe, $code, $id ) {
			$this->last_action = 'unsubscribe_from_email';

			if ( $unsubscribe == 'true' ) {
				$user_data = $this->wpdb->get_row( "SELECT `subscribe` FROM `" . $this->prefix . "sndr_mail_users_info` WHERE `id_user`='" . $id . "' AND `unsubscribe_code`='" . $code . "'", ARRAY_A );

				if ( empty( $user_data ) ) {
					$this->last_response = array(
						'action'  => $this->last_action,
						'type'    => 'error',
						'reason'  => 'NOT_EXISTS_UNSUBSCRIBE',
						'message' => sprintf( $this->events_wrapper['error'], $this->events['not_exists_unsubscribe'] )
					);
				} else {
					if ( '0' ==  $user_data['subscribe'] ) {
						$this->last_response = array(
							'action'  => $this->last_action,
							'type'    => 'error',
							'reason'  => 'ALREADY_UNSUBSCRIBE',
							'message' => sprintf( $this->events_wrapper['error'], $this->events['already_unsubscribe'] )
						);
					} else {
						$this->wpdb->update( $this->prefix . 'sndr_mail_users_info',
							array(
								'subscribe'           => '0',
								'unsubscribe_time'    => time()
							),
							array(
								'id_user' => $id
							)
						);
						if ( $this->wpdb->last_error ) {
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'error',
								'reason'  => 'BAD_REQUEST',
								'message' => sprintf( $this->events_wrapper['error'], $this->events['bad_request'] )
							);
						} else {
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'done',
								'reason'  => 'DONE_UNSUBSCRIBE',
								'message' => sprintf( $this->events_wrapper['done'], $this->events['done_unsubscribe'] )
							);
						}
					}
				}
			} else {
				$this->last_response = array(
					'action'  => $this->last_action,
					'type'    => 'error',
					'reason'  => 'BAD_REQUEST',
					'message' => sprintf( $this->events_wrapper['error'], $this->events['bad_request'] )
				);
			}
			return $this->last_response;
		}
	}
}

/**
 * Check user status
 * @param string $email user e-mail
 * @return string user status
 */
if ( ! function_exists( 'sbscrbr_check_status' ) ) {
	function sbscrbr_check_status( $email ) {
		global $wpdb;
		$prefix    = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		$user_data = $wpdb->get_row( "SELECT * FROM `" . $prefix . "sndr_mail_users_info` WHERE `user_email`='" . trim( $email ) . "'", ARRAY_A );
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
	}
}

/**
 * Function to send mails to administrator and to user
 * @param  srting  $email    user e-mail
 * @return void
 */
if ( ! function_exists( 'sbscrbr_send_mails' ) ) {
	function sbscrbr_send_mails( $email, $user_password ) {
		global $sbscrbr_options, $wpdb;
		$is_multisite = is_multisite();
		if ( empty( $sbscrbr_options ) )
			$sbscrbr_options = $is_multisite ? get_site_option( 'sbscrbr_options' ) : get_option( 'sbscrbr_options' );

		$from_name	= ( empty( $sbscrbr_options['from_custom_name'] ) ) ? get_bloginfo( 'name' ) : $sbscrbr_options['from_custom_name'];
		if ( empty( $sbscrbr_options['from_email'] ) ) {
			$sitename = strtolower( $_SERVER['SERVER_NAME'] );
			if ( substr( $sitename, 0, 4 ) == 'www.' ) {
				$sitename = substr( $sitename, 4 );
			}
			$from_email = 'wordpress@' . $sitename;
		} else {
			$from_email	= $sbscrbr_options['from_email'];
		}

		$prefix = $is_multisite ? $wpdb->base_prefix : $wpdb->prefix;

		/* send message to user */
		$headers = 'From: ' . $from_name . ' <' . $from_email . '>';
		$subject = wp_specialchars_decode( $sbscrbr_options['subscribe_message_subject'], ENT_QUOTES );

		if ( function_exists( 'sndrpr_replace_shortcodes' ) && 1 == $sbscrbr_options['subscribe_message_use_sender'] && ! empty( $sbscrbr_options['subscribe_message_sender_template_id'] ) ) {

			if ( $is_multisite )
				switch_to_blog( 1 );
			$letter_data = $wpdb->get_row( "SELECT * FROM `" . $wpdb->prefix . "sndr_mail_send` WHERE `mail_send_id`=" . $sbscrbr_options['subscribe_message_sender_template_id'], ARRAY_A );
			if ( $is_multisite )
				restore_current_blog();

			if ( ! empty( $letter_data ) ) {
				$user_info = $wpdb->get_row( "SELECT `id_user`, `user_display_name`, `unsubscribe_code` FROM `" . $prefix . "sndr_mail_users_info` WHERE `user_email`='" . $email . "'", ARRAY_A );

				/* get neccessary data */
				$current_user_data = array(
					'id_user'           => ! empty( $user_info ) ? $user_info['id_user'] : '',
					'user_email'        => $email,
					'user_display_name' => ! empty( $user_info ) ? $user_info['user_display_name'] : '',
					'unsubscribe_code'  => ! empty( $user_info ) ? $user_info['unsubscribe_code'] : '',
					'mailout_id'        => ''
				);
				remove_filter( 'sbscrbr_add_unsubscribe_link', 'sbscrbr_unsubscribe_link' );
				$message = sndrpr_replace_shortcodes( $current_user_data, $letter_data );
				add_filter( 'sbscrbr_add_unsubscribe_link', 'sbscrbr_unsubscribe_link', 10, 2 );

				$headers	= 'MIME-Version: 1.0' . "\n";
				$headers	.= 'Content-type: text/html; charset=utf-8' . "\n";
				$headers	.= "From: " .  $from_name . " <" . $from_email . ">\n";
			} else {
				$message = sbscrbr_replace_shortcodes( $sbscrbr_options['subscribe_message_text'], $email );
			}
		} else {
			$message = sbscrbr_replace_shortcodes( $sbscrbr_options['subscribe_message_text'], $email );
		}
		if ( ! empty( $user_password ) )
			$message .= __( "\nYour login:", 'subscriber' ) . ' ' . $email . __( "\nYour password:", 'subscriber' ) . ' ' . $user_password;

		$message = wp_specialchars_decode( $message, ENT_QUOTES );

		if ( function_exists( 'mlq_if_mail_plugin_is_in_queue' ) && mlq_if_mail_plugin_is_in_queue( plugin_basename( __FILE__ ) ) ) {
			/* if email-queue plugin is active and this plugin's "in_queue" status is 'ON' */
			do_action( 'sbscrbr_get_mail_data', plugin_basename( __FILE__ ), $email, $subject, $message, $headers );
		} else {
			wp_mail( $email, $subject, $message, $headers );
		}

		/* send message to admin */
		if ( '1' == $sbscrbr_options['admin_message'] ) {
			$subject = wp_specialchars_decode( $sbscrbr_options['admin_message_subject'], ENT_QUOTES );

			if ( function_exists( 'sndrpr_replace_shortcodes' ) && 1 == $sbscrbr_options['admin_message_use_sender'] && ! empty( $sbscrbr_options['admin_message_sender_template_id'] ) ) {

				if ( $is_multisite )
					switch_to_blog( 1 );
				$letter_data = $wpdb->get_row( "SELECT * FROM `" . $wpdb->prefix . "sndr_mail_send` WHERE `mail_send_id`=" . $sbscrbr_options['admin_message_sender_template_id'], ARRAY_A );
				if ( $is_multisite )
					restore_current_blog();

				if ( ! empty( $letter_data ) ) {
					if ( ! isset( $user_info ) )
						$user_info = $wpdb->get_row( "SELECT `id_user`, `user_display_name`, `unsubscribe_code` FROM `" . $prefix . "sndr_mail_users_info` WHERE `user_email`='" . $email . "'", ARRAY_A );
					/* get neccessary data */
					$current_user_data = array(
						'id_user'           => ! empty( $user_info ) ? $user_info['id_user'] : '',
						'user_email'        => $email,
						'user_display_name' => ! empty( $user_info ) ? $user_info['user_display_name'] : '',
						'unsubscribe_code'  => ! empty( $user_info ) ? $user_info['unsubscribe_code'] : '',
						'mailout_id'        => ''
					);

					remove_filter( 'sbscrbr_add_unsubscribe_link', 'sbscrbr_unsubscribe_link' );
					$message = sndrpr_replace_shortcodes( $current_user_data, $letter_data );
					add_filter( 'sbscrbr_add_unsubscribe_link', 'sbscrbr_unsubscribe_link', 10, 2 );

					$headers	= 'MIME-Version: 1.0' . "\n";
					$headers	.= 'Content-type: text/html; charset=utf-8' . "\n";
					$headers	.= "From: " .  $from_name . " <" . $from_email . ">\n";
				} else {
					$message = sbscrbr_replace_shortcodes( $sbscrbr_options['admin_message_text'], $email );
				}
			} else {
				$message = sbscrbr_replace_shortcodes( $sbscrbr_options['admin_message_text'], $email );
			}
			$email = array();

			if ( $sbscrbr_options['to_email'] == 'user' ) {
				$sbscrbr_userlogin = get_user_by( 'login', $sbscrbr_options['email_user'] );
				$email[] = $sbscrbr_userlogin->data->user_email;
			} else {
				$email = $sbscrbr_options[ 'email_custom' ];
			}

			$message = wp_specialchars_decode( $message, ENT_QUOTES );
			$errors = 0;
			foreach ( $email as $value ) {
				if ( function_exists( 'mlq_if_mail_plugin_is_in_queue' ) && mlq_if_mail_plugin_is_in_queue( plugin_basename( __FILE__ ) ) ) {
					/* if email-queue plugin is active and this plugin's "in_queue" status is 'ON' */
					do_action( 'sbscrbr_get_mail_data', plugin_basename( __FILE__ ), $value, $subject, $message, $headers );
				} else {
					if ( ! wp_mail( $value, $subject, $message, $headers ) )
								$errors ++;
				}
			}
			return ! $errors;
		}
	}
}

/**
 * Function to send unsubscribe link to user
 * @param  string    $email     user_email
 * @return array()   $report    report message
 */
if ( ! function_exists( 'sbscrbr_sent_unsubscribe_mail' ) ) {
	function sbscrbr_sent_unsubscribe_mail( $email ) {
		global $wpdb, $sbscrbr_options, $sbscrbr_send_unsubscribe_mail;
		$sbscrbr_send_unsubscribe_mail = "";
		$is_multisite = is_multisite();
		if ( empty( $sbscrbr_options ) ) {
			$sbscrbr_options = $is_multisite ? get_site_option( 'sbscrbr_options' ) : get_option( 'sbscrbr_options' );
		}
		$prefix = $is_multisite ? $wpdb->base_prefix : $wpdb->prefix;
		$report = array(
			'done'  => false,
			'error' => false
		);
		$user_info = $wpdb->get_row( "SELECT `id_user`, `user_display_name`, `unsubscribe_code` FROM `" . $prefix . "sndr_mail_users_info` WHERE `user_email`='" . $email . "'", ARRAY_A );
		if ( empty( $user_info ) ) {
			$report['error'] = $sbscrbr_options['cannot_get_email'];
		} else {
			$from_name	= ( empty( $sbscrbr_options['from_custom_name'] ) ) ? get_bloginfo( 'name' ) : $sbscrbr_options['from_custom_name'];
			if ( empty( $sbscrbr_options['from_email'] ) ) {
				$sitename = strtolower( $_SERVER['SERVER_NAME'] );
				if ( substr( $sitename, 0, 4 ) == 'www.' ) {
					$sitename = substr( $sitename, 4 );
				}
				$from_email = 'wordpress@' . $sitename;
			} else
				$from_email	= $sbscrbr_options['from_email'];

			$headers    = 'From: ' . $from_name . ' <' . $from_email . '>';
			$subject    = wp_specialchars_decode( $sbscrbr_options['unsubscribe_message_subject'], ENT_QUOTES );

			if ( function_exists( 'sndrpr_replace_shortcodes' ) && 1 == $sbscrbr_options['unsubscribe_message_use_sender'] && ! empty( $sbscrbr_options['unsubscribe_message_sender_template_id'] ) ) {

				if ( $is_multisite )
					switch_to_blog( 1 );
				$letter_data = $wpdb->get_row( "SELECT * FROM `" . $wpdb->prefix . "sndr_mail_send` WHERE `mail_send_id`=" . $sbscrbr_options['unsubscribe_message_sender_template_id'], ARRAY_A );
				if ( $is_multisite )
					restore_current_blog();

				if ( ! empty( $letter_data ) ) {
					/* get neccessary data */
					$current_user_data = array(
						'id_user'           => ! empty( $user_info ) ? $user_info['id_user'] : '',
						'user_email'        => $email,
						'user_display_name' => ! empty( $user_info ) ? $user_info['user_display_name'] : '',
						'unsubscribe_code'  => $user_info['unsubscribe_code'],
						'mailout_id'        => ''
					);
					remove_filter( 'sbscrbr_add_unsubscribe_link', 'sbscrbr_unsubscribe_link' );
					$message = sndrpr_replace_shortcodes( $current_user_data, $letter_data );
					add_filter( 'sbscrbr_add_unsubscribe_link', 'sbscrbr_unsubscribe_link', 10, 2 );

					$headers = 'MIME-Version: 1.0' . "\n";
					$headers .= 'Content-type: text/html; charset=utf-8' . "\n";
					$headers .= "From: " .  $from_name . " <" . $from_email . ">\n";
				} else {
					$message = sbscrbr_replace_shortcodes( $sbscrbr_options['unsubscribe_message_text'], $email );
				}
			} else {
				$message = sbscrbr_replace_shortcodes( $sbscrbr_options['unsubscribe_message_text'], $email );
			}

			$message = wp_specialchars_decode( $message, ENT_QUOTES );

			if ( function_exists( 'mlq_if_mail_plugin_is_in_queue' ) && mlq_if_mail_plugin_is_in_queue( plugin_basename( __FILE__ ) ) ) {
				/* if email-queue plugin is active and this plugin's "in_queue" status is 'ON' */
				global $mlq_mail_result, $mlqpr_mail_result;
				do_action( 'sbscrbr_get_mail_data', plugin_basename( __FILE__ ), $email, $subject, $message, $headers );
				if ( $mlq_mail_result || $mlqpr_mail_result ) {
					$sbscrbr_send_unsubscribe_mail = true;
					$report['done'] = 'check mail';
				} else {
					$report['error'] = $sbscrbr_options['cannot_send_email'];
				}
			} else {
				if ( wp_mail( $email, $subject, $message, $headers ) ) {
					$sbscrbr_send_unsubscribe_mail = true;
					$report['done'] = 'check mail';
				} else {
					$report['error'] = $sbscrbr_options['cannot_send_email'];
				}
			}
		}
		return $report;
	}
}

/**
 * Function that is used by email-queue to check for compatibility
 * @return void
 */
if ( ! function_exists( 'sbscrbr_check_for_compatibility_with_mlq' ) ) {
	function sbscrbr_check_for_compatibility_with_mlq() {
		return false;
	}
}

/**
 * Add unsubscribe link to mail
 * @param     string     $message   text of message
 * @param     array      $user_info subscriber data
 * @return    string     $message    text of message with unsubscribe link
 */
if ( ! function_exists( 'sbscrbr_unsubscribe_link' ) ) {
	function sbscrbr_unsubscribe_link( $message, $user_info ) {
		global $sbscrbr_options;
		if ( empty( $sbscrbr_options ) ) {
			$sbscrbr_options = ( is_multisite() ) ? get_site_option( 'sbscrbr_options' ) : get_option( 'sbscrbr_options' );
		}
		if ( ! ( empty( $message ) && empty( $user_info ) ) ) {
			$message = $message . "\n" . sbscrbr_replace_shortcodes( $sbscrbr_options['unsubscribe_link_text'], $user_info['user_email'] );
		}
		return $message;
	}
}

/**
 * Function to replace shortcodes in text of sended messages
 * @param    string     $text      text of message
 * @param    string     $email     user e-mail
 * @return   string     $text  text of message
 */
if ( ! function_exists( 'sbscrbr_replace_shortcodes' ) ) {
	function sbscrbr_replace_shortcodes( $text, $email ) {
		global $wpdb, $sbscrbr_options;
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		$user_info = $wpdb->get_row( "SELECT `id_user`, `user_display_name`, `unsubscribe_code` FROM `" . $prefix . "sndr_mail_users_info` WHERE `user_email`='" . $email . "'", ARRAY_A );
		if ( ! empty( $user_info ) ) {
			if ( 'url' == $sbscrbr_options['shortcode_link_type'] ) {
				$unsubscribe_link = $sbscrbr_options['shortcode_url'];
			} else {
				$unsubscribe_link = home_url( '/' );
			}
			$unsubscribe_link .= '?sbscrbr_unsubscribe=true&code=' . $user_info['unsubscribe_code'] . '&id=' . $user_info['id_user'];
			$profile_page     = admin_url( 'profile.php' );
			$text = preg_replace( "/\{unsubscribe_link\}/", $unsubscribe_link, $text );
			$text = preg_replace( "/\{profile_page\}/", $profile_page , $text );
			$text = preg_replace( "/\{user_email\}/", $email , $text );
		}
		return $text;
	}
}

/**
 * Function register of users.
 * @param int $user_id user ID
 * @return void
 */
if ( ! function_exists( 'sbscrbr_register_user' ) ) {
	function sbscrbr_register_user( $user_id ) {
		global $wpdb;
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		$wpdb->update( $prefix . 'sndr_mail_users_info',
			array(
				'unsubscribe_code' => MD5( RAND() ),
				'subscribe_time' => time()
			),
			array( 'id_user' => $user_id )
		);
	}
}

/**
 * Delete a subscriber from a subscibers DB if the user deleted from dashboard users page.
 * @return void
 */
if ( ! function_exists( 'sbscrbr_delete_user' ) ) {
	function sbscrbr_delete_user( $user_id ) {
		global $wpdb;
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $prefix . "sndr_mail_users_info` WHERE `id_user` = %d", $user_id ) );
	}
}

/**
 * Function to show "subscribe" checkbox for users.
 * @param array $user user data
 * @return void
 */
if ( ! function_exists( 'sbscrbr_mail_send' ) ) {
	function sbscrbr_mail_send( $user ) {
		global $wpdb, $current_user, $sbscrbr_options;
		if ( empty( $sbscrbr_options ) ) {
			$sbscrbr_options = ( is_multisite() ) ? get_site_option( 'sbscrbr_options' ) : get_option( 'sbscrbr_options' );
		}
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		/* deduce form the subscribe */
		$current_user = wp_get_current_user();
		$mail_message = $wpdb->get_row( "SELECT `subscribe`, `black_list` FROM `" . $prefix . "sndr_mail_users_info` WHERE `id_user` = '" . $current_user->ID . "'", ARRAY_A );
		$disabled     = ( 1 == $mail_message['black_list'] ) ? 'disabled="disabled"' : "";
		$confirm      = ( ( 1 == $mail_message['subscribe'] ) && ( empty( $disabled ) ) ) ? 'checked="checked"' : ""; ?>
		<table class="form-table" id="mail_user">
			<tr>
				<th><?php _e( 'Subscribe on newsletters', 'subscriber' ); ?> </th>
				<td>
					<input type="checkbox" name="sbscrbr_mail_subscribe" <?php echo $confirm; ?> <?php echo $disabled; ?> value="1"/>
					<?php if ( ! empty( $disabled ) ) {
						echo '<span class="description">' . $sbscrbr_options['denied_subscribe'] . '</span>';
					} ?>
				</td>
			</tr>
		</table>
		<?php
	}
}

/**
 * Function update user data.
 * @param $user_id         integer
 * @param $old_user_data   array()
 * @return void
 */
if ( ! function_exists( 'sbscrbr_update' ) ) {
	function sbscrbr_update( $user_id, $old_user_data ) {
		global $wpdb, $current_user;
		$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
		if ( ! function_exists( 'get_userdata' ) ) {
			require_once( ABSPATH . "wp-includes/pluggable.php" );
		}
		$current_user = get_userdata( $user_id );
		$user_exists  = $wpdb->get_row( "SELECT `id_user` FROM `" . $prefix . "sndr_mail_users_info` WHERE `id_user`=" . $current_user->ID );

		if ( $user_exists ) {
			$subscriber = ( isset( $_POST['sbscrbr_mail_subscribe'] ) && '1' == $_POST['sbscrbr_mail_subscribe'] ) ? '1' : '0';
			$wpdb->update( $prefix . 'sndr_mail_users_info',
				array(
					'user_email'        => $current_user->user_email,
					'user_display_name' => $current_user->display_name,
					'subscribe'         => $subscriber
				),
				array( 'id_user' => $current_user->ID )
			);
		} else {
			if ( isset( $_POST['sbscrbr_mail_subscribe'] ) && '1' == $_POST['sbscrbr_mail_subscribe'] ) {
				$wpdb->insert( $prefix . 'sndr_mail_users_info',
					array(
						'id_user'           => $current_user->ID,
						'user_email'        => $current_user->user_email,
						'user_display_name' => $current_user->display_name,
						'subscribe'         => 1
					)
				);
			}
		}
	}
}

/**
 * Class SRSCRBR_User_List to display
 * subscribed/unsubscribed users
 */
if ( ! class_exists( 'Sbscrbr_User_List' ) ) {
	if ( ! class_exists( 'WP_List_Table' ) )
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

	class Sbscrbr_User_List extends WP_List_Table {

		/**
		 * constructor of class
		 */
		function __construct() {
			parent::__construct( array(
				'singular'  => __( 'user', 'subscriber' ),
				'plural'    => __( 'users', 'subscriber' ),
				'ajax'      => true
				)
			);
		}

		/**
		* Function to prepare data before display
		* @return void
		*/
		function prepare_items() {
			global $wpdb;
			$search                = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : '';
			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );
			$this->items           = $this->users_list();
			$per_page              = $this->get_items_per_page( 'subscribers_per_page', 30 );
			$current_page          = $this->get_pagenum();
			$total_items           = $this->items_count();
			$this->set_pagination_args( array(
					'total_items' => $total_items,
					'per_page'    => $per_page
				)
			);
		}

		/**
		* Function to show message if no users found
		* @return void
		*/
		function no_items() { ?>
			<p style="color:red;"><?php _e( 'Users not found', 'subscriber' ); ?></p>
		<?php }

		/**
		 * Get a list of columns.
		 * @return array list of columns and titles
		 */
		function get_columns() {
			$columns = array(
				'cb'         => '<input type="checkbox" />',
				'name'       => __( 'Name', 'subscriber' ),
				'email'      => __( 'E-mail', 'subscriber' ),
				'status'     => __( 'Status', 'subscriber' )
			);
			return $columns;
		}

		/**
		 * Get a list of sortable columns.
		 * @return array list of sortable columns
		 */
		function get_sortable_columns() {
			$sortable_columns = array(
				'name'     => array( 'name', false ),
				'email'    => array( 'email', false )
			);
			return $sortable_columns;
		}

		/**
		 * Fires when the default column output is displayed for a single row.
		 * @param string $column_name      The custom column's name.
		 * @param int    $item->comment_ID The custom column's unique ID number.
		 * @return void
		 */
		function column_default( $item, $column_name ) {
			switch( $column_name ) {
				case 'name'  :
				case 'email' :
				case 'status':
					return $item[ $column_name ];
				default:
					return print_r( $item, true ) ;
			}
		}

		/**
		 * Function to add column of checboxes
		 * @param int    $item->comment_ID The custom column's unique ID number.
		 * @return string                  with html-structure of <input type=['checkbox']>
		 */
		function column_cb( $item ) {
			return sprintf( '<input id="cb_%1s" type="checkbox" name="user_id[]" value="%2s" />', $item['id'], $item['id'] );
		}

		/**
		 * Function to add action links to username column depenting on request
		 * @param int      $item->comment_ID The custom column's unique ID number.
		 * @return string                     with action links
		 */
		function column_name( $item ) {
			$users_status = isset( $_REQUEST['users_status'] ) ? '&users_status=' . $_REQUEST['users_status'] : '';
			$actions = array();
			if ( '0' == $item['status_marker'] ) { /* if user not subscribed */
				if( ! ( isset( $_REQUEST['users_status'] ) && in_array( $_REQUEST['users_status'], array( "subscribed", "trashed", "black_list" ) ) ) ) {
					$actions['subscribe_user'] = '<a class="sbscrbr-subscribe-user" href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=subscribe_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_subscribe_users' . $item['id'] ) ) . '">' . _x( 'Subscribe', 'Action in WP_List_Table', 'subscriber' ) . '</a>';
				}
			}
			if ( '1' == $item['status_marker'] ) { /* if user subscribed */
				if( ! ( isset( $_REQUEST['users_status'] ) && in_array( $_REQUEST['users_status'], array( "unsubscribed", "trashed", "black_list" ) ) ) ) {
					$actions['unsubscribe_user'] = '<a class="sbscrbr-unsubscribe-user" href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=unsubscribe_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_unsubscribe_users' . $item['id'] ) ) . '">' . _x( 'Unsubscribe', 'Action in WP_List_Table', 'subscriber' ) . '</a>';
				}
			}
			if ( isset( $_REQUEST['users_status'] ) && 'black_list' == $_REQUEST['users_status'] ) {
				$actions['restore_from_black_list_user'] = '<a class="sbscrbr-restore-user" href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=restore_from_black_list_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_restore_from_black_list_users' . $item['id'] ) ) . '">' . __( 'Restore From Black List', 'subscriber' ) . '</a>';
			} else {
				$actions['to_black_list_user'] = '<a class="sbscrbr-delete-user" href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=to_black_list_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_to_black_list_users' . $item['id'] ) ) . '">' . __( 'Black List', 'subscriber' ) . '</a>';
			}
			if ( isset( $_REQUEST['users_status'] ) && "trashed" == $_REQUEST['users_status'] ) {
				$actions['restore_user'] = '<a class="sbscrbr-restore-user" href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=restore_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_restore_users' . $item['id'] ) ) . '">' . __( 'Restore', 'subscriber' ) . '</a>';
				$actions['delete_user'] = '<a class="sbscrbr-delete-user" href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=delete_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_delete_users' . $item['id'] ) ) . '">' . __( 'Delete Permanently', 'subscriber' ) . '</a>';
			} else {
				$actions['trash_user'] = '<a class="sbscrbr-delete-user" href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=trash_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_trash_users' . $item['id'] ) ) . '">' . __( 'Trash', 'subscriber' ) . '</a>';
			}

			return sprintf( '%1$s %2$s', $item['name'], $this->row_actions( $actions ) );
		}

		/**
		* Function to add filters below and above users list
		* @return array $status_links
		*/
		function get_views() {
			global $wpdb;
			$status_links  = array();
			$prefix        = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
			$all_count     = $subscribed_count = $unsubscribed_count = 0;
			/* get count of users by status */
			$filters_count = $wpdb->get_results (
				"SELECT COUNT(`id_user`) AS `all`,
					( SELECT COUNT(`id_user`) FROM `" . $prefix . "sndr_mail_users_info` WHERE `subscribe`=1  AND `delete`=0 AND `black_list`=0 ) AS `subscribed`,
					( SELECT COUNT(`id_user`) FROM `" . $prefix . "sndr_mail_users_info` WHERE `subscribe`=0  AND `delete`=0 AND `black_list`=0 ) AS `unsubscribed`,
					( SELECT COUNT(`id_user`) FROM `" . $prefix . "sndr_mail_users_info` WHERE `delete`=1 ) AS `trash`,
					( SELECT COUNT(`id_user`) FROM `" . $prefix . "sndr_mail_users_info` WHERE `delete`=0 AND `black_list`=1 ) AS `black_list`
				FROM `" . $prefix . "sndr_mail_users_info` WHERE `delete`=0 AND `black_list`=0;"
			);
			foreach( $filters_count as $count ) {
				$all_count          = empty( $count->all ) ? 0 : $count->all;
				$subscribed_count   = empty( $count->subscribed ) ? 0 : $count->subscribed;
				$unsubscribed_count = empty( $count->unsubscribed ) ? 0 : $count->unsubscribed;
				$trash_count        = empty( $count->trash ) ? 0 : $count->trash;
				$black_list_count   = empty( $count->black_list ) ? 0 : $count->black_list;
			}
			/* get class for action links */
			$all_class          = ( ! isset( $_REQUEST['users_status'] ) ) ? ' current': '';
			$subscribed_class   = ( isset( $_REQUEST['users_status'] ) && "subscribed" == $_REQUEST['users_status'] ) ? ' current': '';
			$unsubscribed_class = ( isset( $_REQUEST['users_status'] ) && "unsubscribed" == $_REQUEST['users_status'] ) ? ' current': '';
			$black_list_class   = ( isset( $_REQUEST['users_status'] ) && "black_list" == $_REQUEST['users_status'] ) ? ' current': '';
			$trash_class        = ( isset( $_REQUEST['users_status'] ) && "trashed" == $_REQUEST['users_status'] ) ? ' current': '';
			/* get array with action links */
			$status_links['all']          = '<a class="sbscrbr-filter' . $all_class . '" href="?page=sbscrbr_settings_page&tab=sbscrbr_users">' . __( 'All', 'subscriber' ) . '<span class="sbscrbr-count"> ( ' . $all_count . ' )</span></a>';
			$status_links['subscribed']   = '<a class="sbscrbr-filter' . $subscribed_class . '" href="?page=sbscrbr_settings_page&tab=sbscrbr_users&users_status=subscribed">' . __( 'Subscribed', 'subscriber' ) . '<span class="sbscrbr-count"> ( ' . $subscribed_count . ' )</span></a>';
			$status_links['unsubscribed'] = '<a class="sbscrbr-filter' . $unsubscribed_class . '" href="?page=sbscrbr_settings_page&tab=sbscrbr_users&users_status=unsubscribed">' . __( 'Unsubscribed', 'subscriber' ) . '<span class="sndr-count"> ( ' . $unsubscribed_count . ' )</span></a>';
			$status_links['black_list']   = '<a class="sbscrbr-filter' . $black_list_class . '" href="?page=sbscrbr_settings_page&tab=sbscrbr_users&users_status=black_list">' . __( 'Black List', 'subscriber' ) . '<span class="sbscrbr-count"> ( ' . $black_list_count . ' )</span></a>';
			$status_links['trash']        = '<a class="sbscrbr-filter' . $trash_class . '" href="?page=sbscrbr_settings_page&tab=sbscrbr_users&users_status=trashed">' . __( 'Trash', 'subscriber' ) . '<span class="sbscrbr-count"> ( ' . $trash_count . ' )</span></a>';
			return $status_links;
		}

		/**
		 * Function to add action links to drop down menu before and after reports list
		 * @return array of actions
		 */
		function get_bulk_actions() {
			$actions = array();
			if ( ! ( isset( $_REQUEST['users_status'] ) && in_array( $_REQUEST['users_status'], array( "subscribed", "trashed", "black_list" ) ) ) ) {
				$actions['subscribe_users'] = __( 'Subscribe', 'subscriber' );
			}
			if ( ! ( isset( $_REQUEST['users_status'] ) && in_array( $_REQUEST['users_status'], array( "unsubscribed", "trashed", "black_list" ) ) ) ) {
				$actions['unsubscribe_users'] = __( 'Unsubscribe', 'subscriber' ) ;
			}
			if ( isset( $_REQUEST['users_status'] ) && 'black_list' == $_REQUEST['users_status'] ) {
				$actions['restore_from_black_list_users'] = __( 'Restore From Black List', 'subscriber' );
			} else {
				$actions['to_black_list_users'] = __( 'Black List', 'subscriber' );
			}
			if ( isset( $_REQUEST['users_status'] ) && "trashed" == $_REQUEST['users_status'] ) {
				$actions['restore_users'] = __( 'Restore', 'subscriber' );
				$actions['delete_users']  = __( 'Delete Premanently', 'subscriber' );
			} else {
				$actions['trash_users'] = __( 'Delete', 'subscriber' );

			}
			return $actions;
		}

		/**
		 * Function to add necessary class and id to table row
		 * @param array $user with user data
		 * @return void
		 */
		function single_row( $user ) {
			switch ( $user['status_marker'] ) {
				case '0':
					$row_class = 'unsubscribed';
					break;
				case '1':
					$row_class = 'subscribed';
					break;
				default:
					$row_class = '';
					break;
			}
			echo '<tr id="user-' . $user['id'] . '" class="' . $row_class . '">';
				$this->single_row_columns( $user );
			echo "</tr>\n";
		}

		/**
		 * Function to get users list
		 * @return array   $users_list   list of subscribers
		 */
		function users_list() {
			global $wpdb;
			$prefix     = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
			$i          = 0;
			$users_list = array();
			$per_page   = intval( get_user_option( 'subscribers_per_page' ) );
			if ( empty( $per_page ) || $per_page < 1 ) {
				$per_page = 30;
			}
			$start_row = ( isset( $_REQUEST['paged'] ) && '1' != $_REQUEST['paged'] ) ? $per_page * ( absint( $_REQUEST['paged'] - 1 ) ) : 0;
			if ( isset( $_REQUEST['orderby'] ) ) {
				switch ( $_REQUEST['orderby'] ) {
					case 'name':
						$order_by = 'user_display_name';
						break;
					case 'email':
						$order_by = 'user_email';
						break;
					default:
						$order_by = 'id_user';
						break;
				}
			} else {
				$order_by = 'id_user';
			}
			$order = ( isset( $_REQUEST['order'] ) && strtoupper( $_REQUEST['order'] ) == 'ASC' ) ? 'ASC' : 'DESC';
			$sql_query = "SELECT * FROM `" . $prefix . "sndr_mail_users_info` ";
			if ( isset( $_REQUEST['s'] ) && '' != $_REQUEST['s'] ) {
				$sql_query .= "WHERE `user_email` LIKE '%" . $_REQUEST['s'] . "%' OR `user_display_name` LIKE '%" . $_REQUEST['s'] . "%'";
			} else {
				if ( isset( $_REQUEST['users_status'] ) ) {
					switch ( $_REQUEST['users_status'] ) {
						case 'subscribed':
							$sql_query .= "WHERE `subscribe`=1 AND `delete`=0 AND `black_list`=0";
							break;
						case 'unsubscribed':
							$sql_query .= "WHERE `subscribe`=0 AND `delete`=0 AND `black_list`=0";
							break;
						case 'black_list':
							$sql_query .= "WHERE `delete`=0 AND `black_list`=1";
							break;
						case 'trashed':
							$sql_query .= "WHERE `delete`=1";
							break;
						default:
							$sql_query .= "WHERE `delete`=0  AND `black_list`=0";
							break;
					}
				} else {
					$sql_query .= "WHERE `delete`=0  AND `black_list`=0";
				}
			}
			$sql_query   .= " ORDER BY " . $order_by . " " . $order . " LIMIT " . $per_page . " OFFSET " . $start_row . ";";
			$users_data = $wpdb->get_results( $sql_query, ARRAY_A );
			foreach ( $users_data as $user ) {
				$users_list[ $i ]                  = array();
				$users_list[ $i ]['id']            = $user['id_user'];
				$users_list[ $i ]['name']          = get_avatar( $user['id_user'], 32 ) . '<strong>' . $user['user_display_name'] . '</strong>';

				if ( isset( $_REQUEST['s'] ) && '' != $_REQUEST['s'] ) {
					if ( '1' == $user['black_list'] && '0' == $user['delete'] ) {
						$users_list[ $i ]['name'] .=  ' - ' . __( 'in blacklist', 'subscriber' );
					} elseif ( '1' == $user['delete'] ) {
						$users_list[ $i ]['name'] .= ' - ' . __( 'in trash', 'subscriber' );
					}
				}
				$users_list[ $i ]['email']         = '<a href=mailto:' . $user['user_email'] . ' title="' . __( 'E-mail:', 'subscriber' ) . ' ' . $user['user_email'] . '">' . $user['user_email'] . '</a>';
				$users_list[ $i ]['status_marker'] = $user['subscribe'];
				if ( '1' == $user['subscribe'] ) {
					$users_list[ $i ]['status']    = '<span>' . __( 'Subscribed from', 'subscriber' ) . '<br/>' . date_i18n( get_option( 'date_format' ), $user['subscribe_time'] ) . '</span>';
				} else {
					$users_list[ $i ]['status']    = '<span>' . __( 'Unsubscribed from', 'subscriber' ) . '<br/>' . date_i18n( get_option( 'date_format' ), $user['unsubscribe_time'] ) . '</span>';
				}
				$i ++;
			}
			return $users_list;
		}

		/**
		 * Function to get number of all users
		 * @return sting users number
		 */
		function items_count() {
			global $wpdb;
			$prefix    = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
			$sql_query = "SELECT COUNT(`id_user`) FROM `" . $prefix . "sndr_mail_users_info`";
			if ( isset( $_REQUEST['s'] ) && '' != $_REQUEST['s'] ) {
				$sql_query .= "WHERE `user_email` LIKE '%" . esc_sql( $_REQUEST['s'] ) . "%' OR `user_display_name` LIKE '%" . esc_sql( $_REQUEST['s'] ) . "%'";
			} else {
				if ( isset( $_REQUEST['users_status'] ) ) {
					switch ( $_REQUEST['users_status'] ) {
						case 'subscribed':
							$sql_query .= " WHERE `subscribe`=1 AND `delete`=0 AND `black_list`=0;";
							break;
						case 'unsubscribed':
							$sql_query .= " WHERE `subscribe`=0 AND `delete`=0 AND `black_list`=0;";
							break;
						case 'trashed':
							$sql_query .= "WHERE `delete`=1";
							break;
						case 'black_list':
							$sql_query .= "WHERE `delete`=0 AND `black_list`=1";
							break;
						default:
							break;
					}
				} else {
					$sql_query .= "WHERE `delete`=0  AND `black_list`=0";
				}
			}
			$items_count  = $wpdb->get_var( $sql_query );
			return $items_count;
		}

	} /* end of class SRSCRBR_User_List definition */
}

/* add help tab  */
if ( ! function_exists( 'sbscrbr_add_tabs' ) ) {
	function sbscrbr_add_tabs() {
		$screen = get_current_screen();
		$args = array(
			'id' 			=> 'sbscrbr',
			'section' 		=> '200538739'
		);
		bws_help_tab( $screen, $args );

		if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'sbscrbr_users' ) {
			$option = 'per_page';
			$args   = array(
				'label'   => __( 'users per page', 'subscriber' ),
				'default' => 30,
				'option'  => 'subscribers_per_page'
			);
			add_screen_option( $option, $args );
		}
	}
}

/**
 * Function to save and load settings from screen options
 * @return void
 */
if ( ! function_exists( 'sbscrbr_table_set_option' ) ) {
	function sbscrbr_table_set_option( $status, $option, $value ) {
		return $value;
	}
}

/**
 * Function to handle actions from "Subscribers" page
 * @return array with messages about action results
 */
if ( ! function_exists( 'sbscrbr_report_actions' ) ) {
	function sbscrbr_report_actions() {
		$action_message = array(
			'error' => false,
			'done'  => false
		);
		if ( ( isset( $_REQUEST['page'] ) && 'sbscrbr_settings_page' == $_REQUEST['page'] ) && ( isset( $_REQUEST['tab'] ) && 'sbscrbr_users' == $_REQUEST['tab'] ) && ( isset( $_REQUEST['action'] ) || isset( $_REQUEST['action2'] ) ) ) {
			global $wpdb;
			$prefix  = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
			$counter = $errors = $result = 0;
			$user_id = $action = null;
			$user_status  = isset( $_REQUEST['users_status'] ) ? '&users_status=' . $_REQUEST['users_status'] : '';
			$message_list = array(
				'unknown_action'     => __( 'Unknown action.', 'subscriber' ),
				'users_not_selected' => __( 'Select the users to apply the necessary actions.', 'subscriber' ),
				'not_updated'        => __( 'No user was updated.', 'subscriber' )
			);
			if ( isset( $_REQUEST['action'] ) && '-1' != $_REQUEST['action'] ) {
				$action = $_REQUEST['action'];
			} elseif ( isset( $_REQUEST['action2'] ) && '-1' != $_REQUEST['action2'] ) {
				$action = $_REQUEST['action2'];
			}
			if ( ! empty( $action ) ) {
				switch ( $action ) {
					case 'subscribe_users':
					case 'subscribe_user':
						if ( ( ( isset( $_POST['action'] ) || isset( $_POST['action2'] ) ) && ( $action == $_POST['action'] || $action == $_POST['action2'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'sbscrbr_list_nonce_name' ) ) || ( $action == $_GET['action'] && check_admin_referer( 'sbscrbr_subscribe_users' . $_REQUEST['user_id'] ) ) ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								if ( is_array( $_REQUEST['user_id'] ) ) {
									$user_ids = $_REQUEST['user_id'];
									array_walk( $user_ids, 'intval' );
								} else {
									if ( preg_match( '|,|', $_REQUEST['user_id'] ) ) {
										$user_ids = explode(  ',', intval( $_REQUEST['user_id'] ) );
									} else {
										$user_ids[0] = intval( $_REQUEST['user_id'] );
									}
								}
								foreach ( $user_ids as $id ) {
									$result = $wpdb->update( $prefix . 'sndr_mail_users_info',
										array(
											'subscribe'      => 1,
											'subscribe_time' => time()
										),
										array(
											'id_user'   => $id,
											'subscribe' => 0
										)
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
										$add_id   = empty( $user_id ) ? $id : ',' . $id;
										$user_id .= $add_id;
									}
								}
								if ( ! empty( $counter ) ) {
									$action_message['done'] = sprintf( _n( 'One user was subscribed on newsletter.', '%s users were subscribed on newsletter.', $counter, 'subscriber' ), number_format_i18n( $counter ) ) . ' <a href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=unsubscribe_users&user_id=' . $user_id . $user_status, 'sbscrbr_unsubscribe_users' . $user_id ) ) . '">' . __( 'Undo.', 'subscriber' ) . '</a>';
								} else {
									$action_message['error'] = $message_list['not_updated'];
								}
							}
						}
						break;
					case 'unsubscribe_users':
					case 'unsubscribe_user':
						if ( ( ( isset( $_POST['action'] ) || isset( $_POST['action2'] ) ) && ( $action == $_POST['action'] || $action == $_POST['action2'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'sbscrbr_list_nonce_name' ) ) || ( $action == $_GET['action'] && check_admin_referer( 'sbscrbr_unsubscribe_users' . $_REQUEST['user_id'] ) ) ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								if ( is_array( $_REQUEST['user_id'] ) ) {
									$user_ids = $_REQUEST['user_id'];
									array_walk( $user_ids, 'intval' );
								} else {
									if ( preg_match( '|,|', $_REQUEST['user_id'] ) ) {
										$user_ids = explode(  ',', intval( $_REQUEST['user_id'] ) );
									} else {
										$user_ids[0] = intval( $_REQUEST['user_id'] );
									}
								}
								foreach ( $user_ids as $id ) {
									$result = $wpdb->update( $prefix . 'sndr_mail_users_info',
										array(
											'subscribe'        => 0,
											'unsubscribe_time' => time()
										),
										array(
											'id_user'   => $id,
											'subscribe' => 1
										)
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
										$add_id   = empty( $user_id ) ? $id : ',' . $id;
										$user_id .= $add_id;
									}
								}
								if ( ! empty( $counter ) ) {
									$action_message['done'] = sprintf( _n( 'One user was unsubscribed from newsletter.', '%s users were unsubscribed from newsletter.', $counter, 'subscriber' ), number_format_i18n( $counter ) ) . ' <a href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=subscribe_users&user_id=' . $user_id . $user_status, 'sbscrbr_subscribe_users' . $user_id ) ) . '">' . __( 'Undo.', 'subscriber' ) . '</a>';
								} else {
									$action_message['error'] = $message_list['not_updated'];
								}
							}
						}
						break;
					case 'to_black_list_users':
					case 'to_black_list_user':
						if ( ( ( isset( $_POST['action'] ) || isset( $_POST['action2'] ) ) && ( $action == $_POST['action'] || $action == $_POST['action2'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'sbscrbr_list_nonce_name' ) ) || ( $action == $_GET['action'] && check_admin_referer( 'sbscrbr_to_black_list_users' . $_REQUEST['user_id'] ) ) ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								if ( is_array( $_REQUEST['user_id'] ) ) {
									$user_ids = $_REQUEST['user_id'];
									array_walk( $user_ids, 'intval' );
								} else {
									if ( preg_match( '|,|', $_REQUEST['user_id'] ) ) {
										$user_ids = explode(  ',', intval( $_REQUEST['user_id'] ) );
									} else {
										$user_ids[0] = intval( $_REQUEST['user_id'] );
									}
								}
								foreach ( $user_ids as $id ) {
									$result = $wpdb->update( $prefix . 'sndr_mail_users_info',
										array(
											'black_list' => 1,
											'delete'     => 0
										),
										array(
											'id_user' => $id
										)
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
										$add_id   = empty( $user_id ) ? $id : ',' . $id;
										$user_id .= $add_id;
									}
								}
								if ( ! empty( $counter ) ) {
									$action_message['done'] = sprintf( _n( 'One user was moved to black list.', '%s users were moved to black list.', $counter, 'subscriber' ), number_format_i18n( $counter ) ) . ' <a href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=restore_from_black_list_users&user_id=' . $user_id . $user_status, 'sbscrbr_restore_from_black_list_users' . $user_id ) ) . '">' . __( 'Undo.', 'subscriber' ) . '</a>';
								} else {
									$action_message['error'] = $message_list['not_updated'];
								}
							}
						}
						break;
					case 'restore_from_black_list_users':
					case 'restore_from_black_list_user':
						if ( ( ( isset( $_POST['action'] ) || isset( $_POST['action2'] ) ) && ( $action == $_POST['action'] || $action == $_POST['action2'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'sbscrbr_list_nonce_name' ) ) || ( $action == $_GET['action'] && check_admin_referer( 'sbscrbr_restore_from_black_list_users' . $_REQUEST['user_id'] ) ) ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								if ( is_array( $_REQUEST['user_id'] ) ) {
									$user_ids = $_REQUEST['user_id'];
									array_walk( $user_ids, 'intval' );
								} else {
									if ( preg_match( '|,|', $_REQUEST['user_id'] ) ) {
										$user_ids = explode(  ',', intval( $_REQUEST['user_id'] ) );
									} else {
										$user_ids[0] = intval( $_REQUEST['user_id'] );
									}
								}
								foreach ( $user_ids as $id ) {
									$result = $wpdb->update( $prefix . 'sndr_mail_users_info',
										array( 'black_list' => 0 ),
										array( 'id_user' => $id )
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
										$add_id   = empty( $user_id ) ? $id : ',' . $id;
										$user_id .= $add_id;
									}
								}
								if ( ! empty( $counter ) ) {
									$action_message['done'] = sprintf( _n( 'One user was restored from black list.', '%s users were restored from black list.', $counter, 'subscriber' ), number_format_i18n( $counter ) ) . ' <a href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=to_black_list_users&user_id=' . $user_id . $user_status, 'sbscrbr_to_black_list_users' . $user_id ) ) . '">' . __( 'Undo.', 'subscriber' ) . '</a>';
								} else {
									$action_message['error'] = $message_list['not_updated'];
								}
							}
						}
						break;
					case 'trash_users':
					case 'trash_user':
						if ( ( ( isset( $_POST['action'] ) || isset( $_POST['action2'] ) ) && ( $action == $_POST['action'] || $action == $_POST['action2'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'sbscrbr_list_nonce_name' ) ) || ( $action == $_GET['action'] && check_admin_referer( 'sbscrbr_trash_users' . $_REQUEST['user_id'] ) ) ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								if ( is_array( $_REQUEST['user_id'] ) ) {
									$user_ids = $_REQUEST['user_id'];
									array_walk( $user_ids, 'intval' );
								} else {
									if ( preg_match( '|,|', $_REQUEST['user_id'] ) ) {
										$user_ids = explode(  ',', intval( $_REQUEST['user_id'] ) );
									} else {
										$user_ids[0] = intval( $_REQUEST['user_id'] );
									}
								}
								foreach ( $user_ids as $id ) {
									$result = $wpdb->update( $prefix . 'sndr_mail_users_info',
										array( 'delete' => 1 ),
										array( 'id_user' => $id )
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
										$add_id   = empty( $user_id ) ? $id : ',' . $id;
										$user_id .= $add_id;
									}
								}
								if ( ! empty( $counter ) ) {
									$previous_action        = preg_match( '/black_list/', $user_status ) ? 'to_black_list_users' : 'restore_users';
									$action_message['done'] = sprintf( _n( 'One user was moved to trash.', '%s users were moved to trash.', $counter, 'subscriber' ), number_format_i18n( $counter ) ) . ' <a href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=' . $previous_action . '&user_id=' . $user_id . $user_status, 'sbscrbr_' . $previous_action . $user_id ) ) . '">' . __( 'Undo.', 'subscriber' ) . '</a>';
								} else {
									$action_message['error'] = $message_list['not_updated'];
								}
							}
						}
						break;
					case 'delete_users':
					case 'delete_user':
						if ( ( ( isset( $_POST['action'] ) || isset( $_POST['action2'] ) ) && ( $action == $_POST['action'] || $action == $_POST['action2'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'sbscrbr_list_nonce_name' ) ) || ( $action == $_GET['action'] && check_admin_referer( 'sbscrbr_delete_users' . $_REQUEST['user_id'] ) ) ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								if ( is_array( $_REQUEST['user_id'] ) ) {
									$user_ids = $_REQUEST['user_id'];
									array_walk( $user_ids, 'intval' );
								} else {
									if ( preg_match( '|,|', $_REQUEST['user_id'] ) ) {
										$user_ids = explode(  ',', intval( $_REQUEST['user_id'] ) );
									} else {
										$user_ids[0] = intval( $_REQUEST['user_id'] );
									}
								}
								foreach ( $user_ids as $id ) {
									$result = $wpdb->query( "DELETE FROM `" . $prefix . "sndr_mail_users_info` WHERE `id_user`=" . $id );
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
						if ( ( ( isset( $_POST['action'] ) || isset( $_POST['action2'] ) ) && ( $action == $_POST['action'] || $action == $_POST['action2'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'sbscrbr_list_nonce_name' ) ) || ( $action == $_GET['action'] && check_admin_referer( 'sbscrbr_restore_users' . $_REQUEST['user_id'] ) ) ) {
							if ( empty( $_REQUEST['user_id'] ) ) {
								$action_message['error'] = $message_list['users_not_selected'];
							} else {
								if ( is_array( $_REQUEST['user_id'] ) ) {
									$user_ids = $_REQUEST['user_id'];
									array_walk( $user_ids, 'intval' );
								} else {
									if ( preg_match( '|,|', $_REQUEST['user_id'] ) ) {
										$user_ids = explode(  ',', intval( $_REQUEST['user_id'] ) );
									} else {
										$user_ids[0] = intval( $_REQUEST['user_id'] );
									}
								}
								foreach ( $user_ids as $id ) {
									$result = $wpdb->update( $prefix . 'sndr_mail_users_info',
										array( 'delete' => 0 ),
										array( 'id_user' => $id )
									);
									if ( 0 < $result && ( ! $wpdb->last_error ) ) {
										$counter ++;
										$add_id   = empty( $user_id ) ? $id : ',' . $id;
										$user_id .= $add_id;
									}
								}
								if ( ! empty( $counter ) ) {
									$action_message['done'] = sprintf( _n( 'One user was restored.', '%s users were restored.', $counter, 'subscriber' ), number_format_i18n( $counter ) ) . ' <a href="' . esc_url( wp_nonce_url( '?page=sbscrbr_settings_page&tab=sbscrbr_users&action=trash_users&user_id=' . $user_id . $user_status, 'sbscrbr_trash_users' . $user_id ) ) . '">' . __( 'Undo.', 'subscriber' ) . '</a>';
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

/**
 * Check if plugin Sender by BestWebSoft is installed
 * @return bool  true if Sender is installed
 */
if ( ! function_exists( 'sbscrbr_check_sender_install' ) ) {
	function sbscrbr_check_sender_install() {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$plugins_list = get_plugins();
		if ( array_key_exists( 'sender/sender.php', $plugins_list ) || array_key_exists( 'sender-pro/sender-pro.php', $plugins_list ) ) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Add action links on plugin page in to Plugin Name block
 * @param $links array() action links
 * @param $file  string  relative path to pugin "subscriber/subscriber.php"
 * @return $links array() action links
 */
if ( ! function_exists( 'sbscrbr_plugin_action_links' ) ) {
	function sbscrbr_plugin_action_links( $links, $file ) {
		/* Static so we don't call plugin_basename on every plugin row. */
		if ( ! is_multisite() || is_network_admin() ) {
			static $this_plugin;
			if ( ! $this_plugin )
				$this_plugin = plugin_basename( __FILE__ );

			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=sbscrbr_settings_page">' . __( 'Settings', 'subscriber' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

/**
 * Add action links on plugin page in to Plugin Description block
 * @param $links array() action links
 * @param $file  string  relative path to pugin "subscriber/subscriber.php"
 * @return $links array() action links
 */
if ( ! function_exists( 'sbscrbr_register_plugin_links' ) ) {
	function sbscrbr_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			if ( ( is_multisite() && is_network_admin() ) || ( ! is_multisite() && is_admin() ) )
				$links[] = '<a href="admin.php?page=sbscrbr_settings_page">' . __( 'Settings', 'subscriber' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com/hc/en-us/sections/200538739" target="_blank">' . __( 'FAQ', 'subscriber' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'subscriber' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists( 'sbscrbr_show_notices' ) ) {
	function sbscrbr_show_notices() {
		global $hook_suffix, $sbscrbr_options, $sbscrbr_plugin_info;

		if ( 'plugins.php' == $hook_suffix ) {
			if ( empty( $sbscrbr_options ) )
				$sbscrbr_options = is_multisite() ? get_site_option( 'sbscrbr_options' ) : get_option( 'sbscrbr_options' );

			if ( isset( $sbscrbr_options['first_install'] ) && strtotime( '-1 week' ) > $sbscrbr_options['first_install'] )
				bws_plugin_banner( $sbscrbr_plugin_info, 'sbscrbr', 'subscriber', '95812391951699cd5a64397cfb1b0557', '122', '//ps.w.org/subscriber/assets/icon-128x128.png' );

			bws_plugin_banner_to_settings( $sbscrbr_plugin_info, 'sbscrbr_options', 'subscriber', 'admin.php?page=sbscrbr_settings_page' );

			if ( is_multisite() && ! is_network_admin() && is_admin() ) { ?>
				<div class="update-nag"><strong><?php _e( 'Notice:', 'subscriber' ); ?></strong>
					<?php if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
						_e( 'Due to the peculiarities of the multisite work, Subscriber plugin has only', 'subscriber' ); ?> <a target="_blank" href="<?php echo network_admin_url( 'admin.php?page=sbscrbr_settings_page' ); ?>"><?php _e( 'Network settings page', 'subscriber' ); ?></a>
					<?php } else {
						_e( 'Due to the peculiarities of the multisite work, Subscriber plugin has the network settings page only and it should be Network Activated. Please', 'subscriber' ); ?> <a target="_blank" href="<?php echo network_admin_url( 'plugins.php' ); ?>"><?php _e( 'Activate Subscriber for Network', 'subscriber' ); ?></a>
					<?php } ?>
				</div>
			<?php }
		}

		if ( isset( $_REQUEST['page'] ) && 'sbscrbr_settings_page' == $_REQUEST['page'] ) {
			bws_plugin_suggest_feature_banner( $sbscrbr_plugin_info, 'sbscrbr_options', 'subscriber' );
		}
	}
}

/* add shortcode content  */
if ( ! function_exists( 'sbscrbr_shortcode_button_content' ) ) {
	function sbscrbr_shortcode_button_content( $content ) { ?>
		<div id="sbscrbr" style="display:none;">
			<input class="bws_default_shortcode" type="hidden" name="default" value="[sbscrbr_form]" />
			<div class="clear"></div>
		</div>
	<?php }
}

/**
 * Function is called during deinstallation of plugin
 * @return void
 */
if ( ! function_exists( 'sbscrbr_uninstall' ) ) {
	function sbscrbr_uninstall() {
		require_once( ABSPATH . 'wp-includes/user.php' );
		global $wpdb, $sbscrbr_options;
		$all_plugins = get_plugins();

		if ( ! array_key_exists( 'subscriber-pro/subscriber-pro.php', $all_plugins ) ) {
			if ( empty( $sbscrbr_options ) )
				$sbscrbr_options = is_multisite() ? get_site_option( 'sbscrbr_options' ) : get_option( 'sbscrbr_options' );

			$prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
			/* delete tables from database, users with role Mail Subscriber */
			$sbscrbr_sender_installed = sbscrbr_check_sender_install();

			if ( $sbscrbr_sender_installed ) { /* if Sender plugin installed */
				$wpdb->query( "ALTER TABLE `" . $prefix . "sndr_mail_users_info`
					DROP COLUMN `unsubscribe_code`,
					DROP COLUMN `subscribe_time`,
					DROP COLUMN `unsubscribe_time`,
					DROP COLUMN `black_list`,
					DROP COLUMN `delete`;"
				);
			} else {
				$wpdb->query( "DROP TABLE `" . $prefix . "sndr_mail_users_info`" );
				if ( '1' == $sbscrbr_options['delete_users'] ) {
					$args       = array( 'role' => 'sbscrbr_subscriber' );
					$role       = get_role( $args['role'] );
					$users_list = get_users( $args );
					if ( ! empty( $users_list ) ) {
						foreach ( $users_list as $user ) {
							wp_delete_user( $user->ID );
						}
					}
					if ( ! empty( $role ) )
						remove_role( 'sbscrbr_subscriber' );
				}
			}
			/* delete plugin options */
			if ( is_multisite() )
				delete_site_option( 'sbscrbr_options' );
			else
				delete_option( 'sbscrbr_options' );
		}

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

/**
 *  Add all hooks
 */
register_activation_hook( __FILE__, 'sbscrbr_settings' );
/* add plugin pages admin panel */
if ( function_exists( 'is_multisite' ) ) {
	if ( is_multisite() )
		add_action( 'network_admin_menu', 'sbscrbr_admin_menu' );
	else
		add_action( 'admin_menu', 'sbscrbr_admin_menu' );
}
/* initialization */
add_action( 'plugins_loaded', 'sbscrbr_plugins_loaded' );

add_action( 'init', 'sbscrbr_init', 9 );
add_action( 'admin_init', 'sbscrbr_admin_init' );
/* include js- and css-files  */
add_action( 'admin_enqueue_scripts', 'sbscrbr_admin_head' );
add_action( 'wp_enqueue_scripts', 'sbscrbr_load_styles' );
add_action( 'wp_footer', 'sbscrbr_load_scripts' );
/* add "subscribe"-checkbox on user profile page */
if ( ! function_exists( 'sndr_mail_send' ) && ! function_exists( 'sndrpr_mail_send' ) ) {
	add_action( 'profile_personal_options', 'sbscrbr_mail_send' );
	add_action( 'profile_update','sbscrbr_update', 10, 2 );
}
/* register widget */
add_action( 'widgets_init', 'sbscrbr_widgets_init' );
/* register shortcode */
add_shortcode( 'sbscrbr_form', 'sbscrbr_subscribe_form' );
add_filter( 'widget_text', 'do_shortcode' );
/* add unsubscribe link to the each letter from mailout */
add_filter( 'sbscrbr_add_unsubscribe_link', 'sbscrbr_unsubscribe_link', 10, 2 );
/* add unsubscribe code and time, when user was registered */
add_action( 'user_register', 'sbscrbr_register_user' );
/* delete a subscriber, when user was deleted */
add_action( 'delete_user', 'sbscrbr_delete_user' );
/* add screen options on Subscribers List Page */
add_filter( 'set-screen-option', 'sbscrbr_table_set_option', 10, 3 );
/* display additional links on plugins list page */
add_filter( 'plugin_action_links', 'sbscrbr_plugin_action_links', 10, 2 );
if ( function_exists( 'is_multisite' ) ) {
	if ( is_multisite() )
		add_filter( 'network_admin_plugin_action_links', 'sbscrbr_plugin_action_links', 10, 2 );
}
add_filter( 'plugin_row_meta', 'sbscrbr_register_plugin_links', 10, 2 );

add_action( 'admin_notices', 'sbscrbr_show_notices' );

/* custom filter for bws button in tinyMCE */
add_filter( 'bws_shortcode_button_content', 'sbscrbr_shortcode_button_content' );

register_uninstall_hook( __FILE__, 'sbscrbr_uninstall' );