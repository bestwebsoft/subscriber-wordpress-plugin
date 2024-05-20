<?php
/**
 * Class Sbscrbr_Handle_Form_Data to handle data from subscribe form
 */

/**
 * Class Sbscrbr_Handle_Form_Data to handle data from subscribe form
 * and URL's from email for subscribe/unsubscribe users
 */
class Sbscrbr_Handle_Form_Data {
	/**
	 * Options for plugin
	 *
	 * @var array
	 */
	private $options;
	/**
	 * Prefix for DB
	 *
	 * @var string
	 */
	private $prefix;
	/**
	 * Default events array
	 *
	 * @var array
	 */
	private $default_events;
	/**
	 * Events
	 *
	 * @var array
	 */
	private $events;
	/**
	 * Error and message wrappers
	 *
	 * @var array
	 */
	private $events_wrapper;
	/**
	 * Last action string
	 *
	 * @var string
	 */
	public $last_action = 'init';
	/**
	 * Last responce
	 *
	 * @var array
	 */
	public $last_response = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb, $sbscrbr_options;

		if ( empty( $sbscrbr_options ) ) {
			sbscrbr_settings();
		}

		$this->options = $sbscrbr_options;
		$this->prefix = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;

		$this->default_events = array(
			'bad_request'               => $this->options['bad_request'],
			'empty_email'               => $this->options['empty_email'],
			'invalid_email'             => $this->options['invalid_email'],
			'error_subscribe'           => $this->options['error_subscribe'],
			'already_unsubscribe'       => $this->options['already_unsubscribe'],
			'not_exists_email'          => $this->options['not_exists_email'],
			'already_subscribe'         => $this->options['already_subscribe'],
			'denied_subscribe'          => $this->options['denied_subscribe'],
			'not_exists_unsubscribe'    => $this->options['not_exists_unsubscribe'],
			'done_subscribe'            => $this->options['done_subscribe'],
			'check_email_unsubscribe'   => $this->options['check_email_unsubscribe'],
			'done_unsubscribe'          => $this->options['done_unsubscribe'],
			'cannot_send_email'         => $this->options['cannot_send_email'],
		);

		$this->events = $this->default_events;

		$this->events_wrapper = array(
			'error' => '<p class="sbscrbr-form-error">%s</p>',
			'done'  => '<p class="sbscrbr-form-done">%s</p>',
		);
	}

	/**
	 * Update events
	 *
	 * @param array $events Events.
	 */
	public function custom_events( $events = array() ) {
		if ( $events && is_array( $events ) ) {
			$this->events = array_merge( $this->events, $events );
		}
	}

	/**
	 * Get events from default_events
	 */
	public function default_events() {
		$this->events = $this->default_events;
	}

	/**
	 * Submit for plugin form
	 *
	 * @param string $email Email.
	 * @param bool   $unsubscribe Flag for action.
	 * @param bool   $skip_captcha Flag fort captcha.
	 */
	public function submit( $email, $unsubscribe = false, $skip_captcha = false ) {

		if ( has_filter( 'sbscrbr_check' ) && false === $skip_captcha ) {
			$check_result = apply_filters( 'sbscrbr_check', true );
			if ( false === $check_result || ( is_string( $check_result ) && ! empty( $check_result ) ) ) {
				$this->last_response = array(
					'action'  => $this->last_action,
					'type'    => 'error',
					'reason'  => 'CPTCH_CHECK_FALSE',
					'message' => sprintf( $this->events_wrapper['error'], $check_result ),
				);

				return $this->last_response;
			}
		}

		if ( empty( $email ) ) {
			$this->last_response = array(
				'action'    => $this->last_action,
				'type'      => 'error',
				'reason'    => 'EMPTY_EMAIL',
				'message'   => sprintf( $this->events_wrapper['error'], $this->events['empty_email'] ),
			);

			return $this->last_response;
		}

		if ( ! is_email( $email ) ) {

			$this->last_response = array(
				'action'    => $this->last_action,
				'type'      => 'error',
				'reason'    => 'INVALID_EMAIL',
				'message'   => sprintf( $this->events_wrapper['error'], $this->events['invalid_email'] ),
			);

			return $this->last_response;
		}

		if ( true === $unsubscribe ) {
			return $this->unsubscribe_from_form( $email );
		} else {
			return $this->subscribe_from_form( $email );
		}

	}

	/**
	 * Submit form
	 *
	 * @param string $email Email.
	 */
	private function subscribe_from_form( $email ) {
		global $wpdb;
		$this->last_action = 'subscribe_from_form';

		$user_with_meta = sbscrbr_get_user_by_email( $email );
		$user_exists    = email_exists( $email );
		$user_status    = sbscrbr_check_status( $email );

		if ( $user_with_meta instanceof WP_User || $user_exists ) { /* if user already registered */
			if ( ! empty( $user_status ) ) {
				switch ( $user_status ) {
					case 'not_exists': /* add user data to database table of plugin */
						$user = get_user_by( 'email', $email );

						if ( $user_with_meta instanceof WP_User ) {
							$user = $user_with_meta;
						}

						$wpdb->insert(
							$this->prefix . 'sndr_mail_users_info',
							array(
								'id_user'           => $user->ID,
								'user_email'        => $email,
								'user_display_name' => $user->display_name,
								'subscribe'         => 1,
								'unsubscribe_code'  => md5( wp_generate_password() ),
								'subscribe_time'    => time(),
							)
						);
						if ( $wpdb->last_error ) {
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'error',
								'reason'  => 'ERROR_SUBSCRIBE',
								'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] ),
							);
						} else {
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'done',
								'reason'  => 'done_subscribe',
								'message' => sprintf( $this->events_wrapper['done'], $this->events['done_subscribe'] ),
							);
							$send_mails = sbscrbr_send_mails( $email, '' );
							if ( ! empty( $send_mails ) ) { /* send letters to admin and new registerd user*/
								$this->last_response = array(
									'action'  => $this->last_action,
									'type'    => 'error',
									'reason'  => 'cannot_send_email',
									'message' => sprintf( $this->events_wrapper['error'], $this->events['cannot_send_email'] ),
								);
							}
						}
						break;
					case 'subscribed':
						$this->last_response = array(
							'action'  => $this->last_action,
							'type'    => 'error',
							'reason'  => 'ALREADY_SUBSCRIBE',
							'message' => sprintf( $this->events_wrapper['error'], $this->events['already_subscribe'] ),
						);
						break;
					case 'not_subscribed':
					case 'in_trash':
						$wpdb->update(
							$this->prefix . 'sndr_mail_users_info',
							array(
								'subscribe' => '1',
								'delete'    => '0',
							),
							array(
								'user_email' => $email,
							)
						);
						if ( $wpdb->last_error ) {
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'error',
								'reason'  => 'ERROR_SUBSCRIBE',
								'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] ),
							);
						} else {
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'done',
								'reason'  => 'done_subscribe',
								'message' => sprintf( $this->events_wrapper['done'], $this->events['done_subscribe'] ),
							);
							$send_mails = sbscrbr_send_mails( $email, '' );
							if ( ! empty( $send_mails ) ) { /* send letters to admin and new registerd user*/
								$this->last_response = array(
									'action'  => $this->last_action,
									'type'    => 'error',
									'reason'  => 'cannot_send_email',
									'message' => sprintf( $this->events_wrapper['error'], $this->events['cannot_send_email'] ),
								);
							}
						}
						break;
					case 'in_black_list':
						$this->last_response = array(
							'action'  => $this->last_action,
							'type'    => 'error',
							'reason'  => 'DENIED_SUBSCRIBE',
							'message' => sprintf( $this->events_wrapper['error'], $this->events['denied_subscribe'] ),
						);
						break;
					default:
						$this->last_response = array(
							'action'  => $this->last_action,
							'type'    => 'error',
							'reason'  => 'ERROR_SUBSCRIBE',
							'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] ),
						);
						break;
				}
			} else {
				$this->last_response = array(
					'action'  => $this->last_action,
					'type'    => 'error',
					'reason'  => 'ERROR_SUBSCRIBE',
					'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] ),
				);
			}
		} else {
			/* register new user */
			if ( ! $user_with_meta instanceof WP_User ) {
				$user_password = wp_generate_password( 12, false );
				$userdata = array(
					'user_login'    => $email,
					'nickname'      => $email,
					'user_pass'     => $user_password,
					'user_email'    => $email,
					'display_name'  => $email,
					'role'          => 'sbscrbr_subscriber',
				);
				$user_id = wp_insert_user( $userdata );
			} else {
				$user_id = $user_with_meta->ID;
			}

			if ( is_wp_error( $user_id ) ) {
				$this->last_response = array(
					'action'  => $this->last_action,
					'type'    => 'error',
					'reason'  => 'ERROR_SUBSCRIBE',
					'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] ),
				);
			} else {
				/* if "Sender" plugin by BWS is not installed and activated */
				if ( ! function_exists( 'sndr_mail_register_user' ) && ! function_exists( 'sndr_mail_register_user' ) ) {
					if ( ! empty( $user_status ) ) {
						switch ( $user_status ) {
							case 'not_exists': /* add user data to database table of plugin */
								$wpdb->insert(
									$this->prefix . 'sndr_mail_users_info',
									array(
										'id_user'           => $user_id,
										'user_email'        => $email,
										'user_display_name' => $email,
										'subscribe'         => 1,
										'unsubscribe_code'  => md5( wp_generate_password() ),
										'subscribe_time'    => time(),
									)
								);
								break;
							case 'subscribed':
								$this->last_response = array(
									'action'  => $this->last_action,
									'type'    => 'done',
									'reason'  => 'done_subscribe',
									'message' => sprintf( $this->events_wrapper['done'], $this->events['done_subscribe'] ),
								);
								break;
							case 'not_subscribed':
							case 'in_trash':
								$wpdb->update(
									$this->prefix . 'sndr_mail_users_info',
									array(
										'subscribe' => '1',
										'delete'    => '0',
									),
									array(
										'user_email' => $email,
									)
								);
								break;
							case 'in_black_list':
								$this->last_response = array(
									'action'  => $this->last_action,
									'type'    => 'error',
									'reason'  => 'DENIED_SUBSCRIBE',
									'message' => sprintf( $this->events_wrapper['error'], $this->events['denied_subscribe'] ),
								);
								break;
							default:
								$this->last_response = array(
									'action'  => $this->last_action,
									'type'    => 'error',
									'reason'  => 'ERROR_SUBSCRIBE',
									'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] ),
								);
								break;
						}
					} else {
						$wpdb->insert(
							$this->prefix . 'sndr_mail_users_info',
							array(
								'id_user'           => $user_id,
								'user_email'        => $email,
								'user_display_name' => $email,
								'subscribe'         => 1,
								'unsubscribe_code'  => md5( wp_generate_password() ),
								'subscribe_time'    => time(),
							)
						);
					}
				}

				if ( $wpdb->last_error ) {
					$this->last_response = array(
						'action'  => $this->last_action,
						'type'    => 'error',
						'reason'  => 'ERROR_SUBSCRIBE',
						'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] ),
					);
				} else {
					$this->last_response = array(
						'action'  => $this->last_action,
						'type'    => 'done',
						'reason'  => 'done_subscribe',
						'message' => sprintf( $this->events_wrapper['done'], $this->events['done_subscribe'] ),
					);
					$send_mails = sbscrbr_send_mails( $email, $user_password );
					if ( ! empty( $send_mails ) ) { /* send letters to admin and new registerd user*/
						$this->last_response = array(
							'action'  => $this->last_action,
							'type'    => 'error',
							'reason'  => 'cannot_send_email',
							'message' => sprintf( $this->events_wrapper['error'], $this->events['cannot_send_email'] ),
						);
					}
				}
			}
		}
		return $this->last_response;
	}

	/**
	 * Unsubscribe form
	 *
	 * @param string $email Email.
	 */
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
							'message' => sprintf( $this->events_wrapper['error'], $this->events['already_unsubscribe'] ),
						);
						break;
					case 'subscribed':
					case 'in_trash':
					case 'in_black_list':
						if ( true !== $sbscrbr_send_unsubscribe_mail ) {
							$result = sbscrbr_sent_unsubscribe_mail( $email ); /* send email with unsubscribe link */
							if ( ! empty( $result ) ) { /* show report message */
								if ( $result['done'] ) {
									$this->last_response = array(
										'action'  => $this->last_action,
										'type'    => 'done',
										'reason'  => 'CHECK_EMAIL_UNSUBSCRIBE',
										'message' => sprintf( $this->events_wrapper['done'], $this->events['check_email_unsubscribe'] ),
									);
								} else {
									$this->last_response = array(
										'action'  => $this->last_action,
										'type'    => 'error',
										'reason'  => 'CHECK_EMAIL_UNSUBSCRIBE',
										'message' => sprintf( $this->events_wrapper['error'], $result['error'] ),
									);
								}
							} else {
								$this->last_response = array(
									'action'  => $this->last_action,
									'type'    => 'error',
									'reason'  => 'BAD_REQUEST',
									'message' => sprintf( $this->events_wrapper['error'], $this->events['bad_request'] ),
								);
							}
						}
						break;
					default:
						$this->last_response = array(
							'action'  => $this->last_action,
							'type'    => 'error',
							'reason'  => 'ERROR_SUBSCRIBE',
							'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] ),
						);
						break;
				}
			} else {
				$this->last_response = array(
					'action'  => $this->last_action,
					'type'    => 'error',
					'reason'  => 'ERROR_SUBSCRIBE',
					'message' => sprintf( $this->events_wrapper['error'], $this->events['error_subscribe'] ),
				);
			}
		} else {
			/**
			 * If no user with this e-mail
			 * check user status
			 */
			if ( 'subscribed' == $user_status ) {
				if ( true !== $sbscrbr_send_unsubscribe_mail ) {
					$result = sbscrbr_sent_unsubscribe_mail( $email ); /* send email with unsubscribe link */
					if ( ! empty( $result ) ) { /* show report message */
						if ( $result['done'] ) {
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'done',
								'reason'  => 'CHECK_EMAIL_UNSUBSCRIBE',
								'message' => sprintf( $this->events_wrapper['done'], $this->events['check_email_unsubscribe'] ),
							);
						} else {
							$this->last_response = array(
								'action'  => $this->last_action,
								'type'    => 'error',
								'reason'  => 'CHECK_EMAIL_UNSUBSCRIBE',
								'message' => sprintf( $this->events_wrapper['error'], $result['error'] ),
							);
						}
					} else {
						$this->last_response = array(
							'action'  => $this->last_action,
							'type'    => 'error',
							'reason'  => 'BAD_REQUEST',
							'message' => sprintf( $this->events_wrapper['error'], $this->events['bad_request'] ),
						);
					}
				}
			} else {
				$this->last_response = array(
					'action'  => $this->last_action,
					'type'    => 'error',
					'reason'  => 'NOT_EXISTS_EMAIL',
					'message' => sprintf( $this->events_wrapper['error'], $this->events['not_exists_email'] ),
				);
			}
		}
		return $this->last_response;
	}

	/**
	 * Unsubscribe from email
	 *
	 * @param string $unsubscribe Flag for action.
	 * @param string $code        Code for unsubscribe.
	 * @param string $id          User ID.
	 */
	public function unsubscribe_from_email( $unsubscribe, $code, $id ) {
		global $wpdb;
		$this->last_action = 'unsubscribe_from_email';

		if ( 'true' == $unsubscribe ) {
			$user_data = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT `subscribe`
					FROM `' . $this->prefix . 'sndr_mail_users_info`
					WHERE `mail_users_info_id` = %d
						AND `unsubscribe_code` = %s',
					$id,
					$code
				),
				ARRAY_A
			);

			if ( empty( $user_data ) ) {
				$this->last_response = array(
					'action'  => $this->last_action,
					'type'    => 'error',
					'reason'  => 'NOT_EXISTS_UNSUBSCRIBE',
					'message' => sprintf( $this->events_wrapper['error'], $this->events['not_exists_unsubscribe'] ),
				);
			} else {
				if ( '0' == $user_data['subscribe'] ) {
					$this->last_response = array(
						'action'  => $this->last_action,
						'type'    => 'error',
						'reason'  => 'ALREADY_UNSUBSCRIBE',
						'message' => sprintf( $this->events_wrapper['error'], $this->events['already_unsubscribe'] ),
					);
				} else {
					$wpdb->update(
						$this->prefix . 'sndr_mail_users_info',
						array(
							'subscribe'           => '0',
							'unsubscribe_time'    => time(),
						),
						array(
							'mail_users_info_id' => $id,
						)
					);
					if ( $wpdb->last_error ) {
						$this->last_response = array(
							'action'  => $this->last_action,
							'type'    => 'error',
							'reason'  => 'BAD_REQUEST',
							'message' => sprintf( $this->events_wrapper['error'], $this->events['bad_request'] ),
						);
					} else {
						$this->last_response = array(
							'action'  => $this->last_action,
							'type'    => 'done',
							'reason'  => 'DONE_UNSUBSCRIBE',
							'message' => sprintf( $this->events_wrapper['done'], $this->events['done_unsubscribe'] ),
						);
					}
				}
			}
		} else {
			$this->last_response = array(
				'action'  => $this->last_action,
				'type'    => 'error',
				'reason'  => 'BAD_REQUEST',
				'message' => sprintf( $this->events_wrapper['error'], $this->events['bad_request'] ),
			);
		}
		return $this->last_response;
	}
}
