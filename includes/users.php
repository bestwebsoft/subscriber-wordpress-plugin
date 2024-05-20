<?php
/**
 * Display 'Subscribers', 'Add Subscriber', 'Edit Subscriber' pages
 *
 * @subpackage Subscriber
 * @since 1.1.3
 */

/**
 * Create class Sbscrbr_User_List
 * for displaying page with users
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
if ( ! class_exists( 'Sbscrbr_User_List' ) ) {
	/**
	 * Class for displayt User list
	 */
	class Sbscrbr_User_List extends WP_List_Table {
		/**
		 * Constructor of class
		 */
		public function __construct() {
			parent::__construct(
				array(
					'singular'  => __( 'user', 'subscriber' ),
					'plural'    => __( 'users', 'subscriber' ),
					'ajax'      => true,
				)
			);
		}

		/**
		 * Function to prepare data before display
		 */
		public function prepare_items() {
			$search                = ( isset( $_REQUEST['s'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
			$columns               = $this->get_columns();
			$hidden                = array();
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );
			$this->items           = $this->users_list();
			$per_page              = $this->get_items_per_page( 'subscribers_per_page', 30 );
			$current_page          = $this->get_pagenum();
			$total_items           = $this->items_count();
			$this->set_pagination_args(
				array(
					'total_items' => $total_items,
					'per_page'    => $per_page,
				)
			);
		}

		/**
		 * Function to show message if no users found
		 */
		public function no_items() { ?>
			<p style="color:red;"><?php esc_html_e( 'Users have not been found', 'subscriber' ); ?></p>
			<?php
		}

		/**
		 * Get a list of columns.
		 *
		 * @return array list of columns and titles
		 */
		public function get_columns() {
			$columns = array(
				'cb'         => '<input type="checkbox" />',
				'name'       => __( 'Name', 'subscriber' ),
				'email'      => __( 'E-mail', 'subscriber' ),
				'status'     => __( 'Status', 'subscriber' ),
			);
			return $columns;
		}

		/**
		 * Get a list of sortable columns.
		 *
		 * @return array list of sortable columns
		 */
		public function get_sortable_columns() {
			$sortable_columns = array(
				'name'     => array( 'name', false ),
				'email'    => array( 'email', false ),
			);
			return $sortable_columns;
		}

		/**
		 * Fires when the default column output is displayed for a single row.
		 *
		 * @param array  $item        The custom column info.
		 * @param string $column_name The custom column's name.
		 */
		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'name':
				case 'email':
				case 'status':
					return $item[ $column_name ];
				default:
					return print_r( $item, true );
			}
		}

		/**
		 * Function to add column of checboxes
		 *
		 * @param array $item The custom column info.
		 * @return string With html-structure of <input type=['checkbox']>.
		 */
		public function column_cb( $item ) {
			return sprintf( '<input id="cb_%1s" type="checkbox" name="user_id[]" value="%2s" />', $item['id'], $item['id'] );
		}

		/**
		 * Function to add action links to username column depenting on request
		 *
		 * @param array $item The custom column info.
		 * @return string   With action links.
		 */
		public function column_name( $item ) {
			$users_status = isset( $_REQUEST['users_status'] ) ? '&users_status=' . sanitize_text_field( wp_unslash( $_REQUEST['users_status'] ) ) : '';
			$actions = array();
			if ( '0' == $item['status_marker'] ) { /* if user not subscribed */
				if ( ! ( isset( $_REQUEST['users_status'] ) && in_array( $_REQUEST['users_status'], array( 'subscribed', 'trashed', 'black_list' ) ) ) ) {
					$actions['subscribe_user'] = '<a class="sbscrbr-subscribe-user" href="' . esc_url( wp_nonce_url( '?page=subscriber-users.php&action=subscribe_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_subscribe_users' . $item['id'] ) ) . '">' . _x( 'Subscribe', 'Action in WP_List_Table', 'subscriber' ) . '</a>';
				}
			}
			if ( '1' == $item['status_marker'] ) { /* if user subscribed */
				if ( ! ( isset( $_REQUEST['users_status'] ) && in_array( $_REQUEST['users_status'], array( 'unsubscribed', 'trashed', 'black_list' ) ) ) ) {
					$actions['unsubscribe_user'] = '<a class="sbscrbr-unsubscribe-user" href="' . esc_url( wp_nonce_url( '?page=subscriber-users.php&action=unsubscribe_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_unsubscribe_users' . $item['id'] ) ) . '">' . _x( 'Unsubscribe', 'Action in WP_List_Table', 'subscriber' ) . '</a>';
				}
			}
			if ( isset( $_REQUEST['users_status'] ) && 'black_list' == $_REQUEST['users_status'] ) {
				$actions['restore_from_black_list_user'] = '<a class="sbscrbr-restore-user" href="' . esc_url( wp_nonce_url( '?page=subscriber-users.php&action=restore_from_black_list_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_restore_from_black_list_users' . $item['id'] ) ) . '">' . __( 'Restore From Black List', 'subscriber' ) . '</a>';
			} else {
				$actions['to_black_list_user'] = '<a class="sbscrbr-delete-user" href="' . esc_url( wp_nonce_url( '?page=subscriber-users.php&action=to_black_list_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_to_black_list_users' . $item['id'] ) ) . '">' . __( 'Black List', 'subscriber' ) . '</a>';
			}
			if ( isset( $_REQUEST['users_status'] ) && 'trashed' == $_REQUEST['users_status'] ) {
				$actions['restore_user'] = '<a class="sbscrbr-restore-user" href="' . esc_url( wp_nonce_url( '?page=subscriber-users.php&action=restore_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_restore_users' . $item['id'] ) ) . '">' . __( 'Restore', 'subscriber' ) . '</a>';
				$actions['delete_user'] = '<a class="sbscrbr-delete-user" href="' . esc_url( wp_nonce_url( '?page=subscriber-users.php&action=delete_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_delete_users' . $item['id'] ) ) . '">' . __( 'Delete Permanently', 'subscriber' ) . '</a>';
			} else {
				$actions['trash_user'] = '<a class="sbscrbr-delete-user" href="' . esc_url( wp_nonce_url( '?page=subscriber-users.php&action=trash_user&user_id=' . $item['id'] . $users_status, 'sbscrbr_trash_users' . $item['id'] ) ) . '">' . __( 'Trash', 'subscriber' ) . '</a>';
			}

			return sprintf( '%1$s %2$s', $item['name'], $this->row_actions( $actions ) );
		}

		/**
		 * Function to add filters below and above users list
		 *
		 * @return array $status_links
		 */
		public function get_views() {
			global $wpdb;
			$status_links = array();
			$prefix       = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;

			$all_count          = 0;
			$subscribed_count   = 0;
			$unsubscribed_count = 0;
			/* get count of users by status */
			$filters_count = $wpdb->get_results(
				'SELECT COUNT(`id_user`) AS `all`,
					( SELECT COUNT(`id_user`) FROM `' . $prefix . 'sndr_mail_users_info` WHERE `subscribe`=1  AND `delete`=0 AND `black_list`=0 ) AS `subscribed`,
					( SELECT COUNT(`id_user`) FROM `' . $prefix . 'sndr_mail_users_info` WHERE `subscribe`=0  AND `delete`=0 AND `black_list`=0 ) AS `unsubscribed`,
					( SELECT COUNT(`id_user`) FROM `' . $prefix . 'sndr_mail_users_info` WHERE `delete`=1 ) AS `trash`,
					( SELECT COUNT(`id_user`) FROM `' . $prefix . 'sndr_mail_users_info` WHERE `delete`=0 AND `black_list`=1 ) AS `black_list`
				FROM `' . $prefix . 'sndr_mail_users_info` WHERE `delete`=0 AND `black_list`=0;'
			);
			foreach ( $filters_count as $count ) {
				$all_count          = empty( $count->all ) ? 0 : $count->all;
				$subscribed_count   = empty( $count->subscribed ) ? 0 : $count->subscribed;
				$unsubscribed_count = empty( $count->unsubscribed ) ? 0 : $count->unsubscribed;
				$trash_count        = empty( $count->trash ) ? 0 : $count->trash;
				$black_list_count   = empty( $count->black_list ) ? 0 : $count->black_list;
			}
			/* get class for action links */
			$all_class          = ( ! isset( $_REQUEST['users_status'] ) ) ? ' current' : '';
			$subscribed_class   = ( isset( $_REQUEST['users_status'] ) && 'subscribed' == sanitize_text_field( wp_unslash( $_REQUEST['users_status'] ) ) ) ? ' current' : '';
			$unsubscribed_class = ( isset( $_REQUEST['users_status'] ) && 'unsubscribed' == sanitize_text_field( wp_unslash( $_REQUEST['users_status'] ) ) ) ? ' current' : '';
			$black_list_class   = ( isset( $_REQUEST['users_status'] ) && 'black_list' == sanitize_text_field( wp_unslash( $_REQUEST['users_status'] ) ) ) ? ' current' : '';
			$trash_class        = ( isset( $_REQUEST['users_status'] ) && 'trashed' == sanitize_text_field( wp_unslash( $_REQUEST['users_status'] ) ) ) ? ' current' : '';
			/* get array with action links */
			$status_links['all']          = '<a class="sbscrbr-filter' . $all_class . '" href="?page=subscriber-users.php">' . __( 'All', 'subscriber' ) . '<span class="sbscrbr-count"> ( ' . $all_count . ' )</span></a>';
			$status_links['subscribed']   = '<a class="sbscrbr-filter' . $subscribed_class . '" href="?page=subscriber-users.php&users_status=subscribed">' . __( 'Subscribed', 'subscriber' ) . '<span class="sbscrbr-count"> ( ' . $subscribed_count . ' )</span></a>';
			$status_links['unsubscribed'] = '<a class="sbscrbr-filter' . $unsubscribed_class . '" href="?page=subscriber-users.php&users_status=unsubscribed">' . __( 'Unsubscribed', 'subscriber' ) . '<span class="sndr-count"> ( ' . $unsubscribed_count . ' )</span></a>';
			$status_links['black_list']   = '<a class="sbscrbr-filter' . $black_list_class . '" href="?page=subscriber-users.php&users_status=black_list">' . __( 'Black List', 'subscriber' ) . '<span class="sbscrbr-count"> ( ' . $black_list_count . ' )</span></a>';
			$status_links['trash']        = '<a class="sbscrbr-filter' . $trash_class . '" href="?page=subscriber-users.php&users_status=trashed">' . __( 'Trash', 'subscriber' ) . '<span class="sbscrbr-count"> ( ' . $trash_count . ' )</span></a>';
			return $status_links;
		}

		/**
		 * Function to add action links to drop down menu before and after reports list
		 *
		 * @return array of actions
		 */
		public function get_bulk_actions() {
			$actions = array();
			if ( ! ( isset( $_REQUEST['users_status'] ) && in_array( $_REQUEST['users_status'], array( 'subscribed', 'trashed', 'black_list' ) ) ) ) {
				$actions['subscribe_users'] = __( 'Subscribe', 'subscriber' );
			}
			if ( ! ( isset( $_REQUEST['users_status'] ) && in_array( $_REQUEST['users_status'], array( 'unsubscribed', 'trashed', 'black_list' ) ) ) ) {
				$actions['unsubscribe_users'] = __( 'Unsubscribe', 'subscriber' );
			}
			if ( isset( $_REQUEST['users_status'] ) && 'black_list' == $_REQUEST['users_status'] ) {
				$actions['restore_from_black_list_users'] = __( 'Restore From Black List', 'subscriber' );
			} else {
				$actions['to_black_list_users'] = __( 'Black List', 'subscriber' );
			}
			if ( isset( $_REQUEST['users_status'] ) && 'trashed' == $_REQUEST['users_status'] ) {
				$actions['restore_users'] = __( 'Restore', 'subscriber' );
				$actions['delete_users']  = __( 'Delete Permanently', 'subscriber' );
			} else {
				$actions['trash_users'] = __( 'Delete', 'subscriber' );

			}
			return $actions;
		}

		/**
		 * Function to add necessary class and id to table row
		 *
		 * @param array $user With user data.
		 */
		public function single_row( $user ) {
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
			echo '<tr id="user-' . esc_attr( $user['id'] ) . '" class="' . esc_html( $row_class ) . '">';
				$this->single_row_columns( $user );
			echo "</tr>\n";
		}

		/**
		 * Function to get users list
		 *
		 * @return array   $users_list   list of subscribers
		 */
		public function users_list() {
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
			$order = ( isset( $_REQUEST['order'] ) && 'ASC' == strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) ) ? 'ASC' : 'DESC';
			$sql_query = 'SELECT * FROM `' . $prefix . 'sndr_mail_users_info` ';
			if ( isset( $_REQUEST['s'] ) && '' != sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) {
				$sql_query .= $wpdb->prepare(
					'WHERE `user_email` LIKE %s
						OR `user_display_name` LIKE %s',
					'%' . esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) . '%',
					'%' . esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) . '%'
				);
			} else {
				if ( isset( $_REQUEST['users_status'] ) ) {
					switch ( $_REQUEST['users_status'] ) {
						case 'subscribed':
							$sql_query .= 'WHERE `subscribe`=1 AND `delete`=0 AND `black_list`=0';
							break;
						case 'unsubscribed':
							$sql_query .= 'WHERE `subscribe`=0 AND `delete`=0 AND `black_list`=0';
							break;
						case 'black_list':
							$sql_query .= 'WHERE `delete`=0 AND `black_list`=1';
							break;
						case 'trashed':
							$sql_query .= 'WHERE `delete`=1';
							break;
						default:
							$sql_query .= 'WHERE `delete`=0  AND `black_list`=0';
							break;
					}
				} else {
					$sql_query .= 'WHERE `delete`=0  AND `black_list`=0';
				}
			}
			$sql_query   .= ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT ' . $per_page . ' OFFSET ' . $start_row . ';';
			$users_data = $wpdb->get_results( $sql_query, ARRAY_A );
			foreach ( $users_data as $user ) {
				$users_list[ $i ]                  = array();
				$users_list[ $i ]['id']            = $user['mail_users_info_id'];
				$users_list[ $i ]['name']          = get_avatar( $user['id_user'], 32 ) . '<strong>' . $user['user_display_name'] . '</strong>';

				if ( isset( $_REQUEST['s'] ) && '' != $_REQUEST['s'] ) {
					if ( '1' == $user['black_list'] && '0' == $user['delete'] ) {
						$users_list[ $i ]['name'] .= ' - ' . __( 'in blacklist', 'subscriber' );
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
		 *
		 * @return sting users number
		 */
		public function items_count() {
			global $wpdb;
			$prefix    = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
			$sql_query = 'SELECT COUNT(`id_user`) FROM `' . $prefix . 'sndr_mail_users_info`';
			if ( isset( $_REQUEST['s'] ) && '' != sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) {
				$sql_query .= $wpdb->prepare(
					'WHERE `user_email` LIKE %s OR `user_display_name` LIKE %s',
					'%' . esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) . '%',
					'%' . esc_sql( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) . '%'
				);
			} else {
				if ( isset( $_REQUEST['users_status'] ) ) {
					switch ( $_REQUEST['users_status'] ) {
						case 'subscribed':
							$sql_query .= ' WHERE `subscribe`=1 AND `delete`=0 AND `black_list`=0;';
							break;
						case 'unsubscribed':
							$sql_query .= ' WHERE `subscribe`=0 AND `delete`=0 AND `black_list`=0;';
							break;
						case 'trashed':
							$sql_query .= 'WHERE `delete`=1';
							break;
						case 'black_list':
							$sql_query .= 'WHERE `delete`=0 AND `black_list`=1';
							break;
						default:
							break;
					}
				} else {
					$sql_query .= 'WHERE `delete`=0  AND `black_list`=0';
				}
			}
			$items_count  = $wpdb->get_var( $sql_query );
			return $items_count;
		}
	}
}
