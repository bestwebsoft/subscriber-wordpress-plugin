<?php
/**
 * Class for widget
 */

/**
 * Display Widget for plugin
 */
class Sbscrbr_Widget extends WP_Widget {
	/**
	 * Constructor of class
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
	 *
	 * @param array $args     Array with sidebar settings.
	 * @param array $instance Array with widget settings.
	 */
	public function widget( $args, $instance ) {
		global $sbscrbr_options, $sbscrbr_handle_form_data, $sbscrbr_display_message, $wp;

		if ( empty( $sbscrbr_options ) ) {
			sbscrbr_settings();
		}

		$action_form = '#sbscrbr-form-' . $args['widget_id'];

		if ( isset( $instance['widget_apply_settings'] ) && $instance['widget_apply_settings'] ) { /* load plugin settings */
			$widget_title           = $sbscrbr_options['form_title'];
			$widget_form_label      = $sbscrbr_options['form_label'];
			$widget_placeholder     = $sbscrbr_options['form_placeholder'];
			$widget_checkbox_label  = $sbscrbr_options['form_checkbox_label'];
			$widget_button_label    = $sbscrbr_options['form_button_label'];
		} else { /* load widget settings */
			$widget_title           = isset( $instance['widget_title'] ) ? $instance['widget_title'] : null;
			$widget_form_label      = isset( $instance['widget_form_label'] ) ? $instance['widget_form_label'] : null;
			$widget_placeholder     = isset( $instance['widget_placeholder'] ) ? $instance['widget_placeholder'] : __( 'E-mail', 'subscriber' );
			$widget_checkbox_label  = isset( $instance['widget_checkbox_label'] ) ? $instance['widget_checkbox_label'] : __( 'Unsubscribe', 'subscriber' );
			$widget_button_label    = isset( $instance['widget_button_label'] ) ? $instance['widget_button_label'] : __( 'Subscribe', 'subscriber' );
		}

		/* Get report message */
		$report_message = '';
		if ( 'unsubscribe_from_email' == $sbscrbr_handle_form_data->last_action && ! isset( $sbscrbr_display_message ) ) {
			$report_message = $sbscrbr_handle_form_data->last_response;
			$sbscrbr_display_message = true;
		}
		if ( isset( $_POST['sbscrbr_submit_email'] ) && isset( $_POST['sbscrbr_form_id'] ) && sanitize_text_field( wp_unslash( $_POST['sbscrbr_form_id'] ) ) == $args['widget_id'] ) {
			$report_message = $sbscrbr_handle_form_data->submit( sanitize_text_field( wp_unslash( $_POST['sbscrbr_email'] ) ), ( isset( $_POST['sbscrbr_unsubscribe'] ) && 'yes' == sanitize_text_field( wp_unslash( $_POST['sbscrbr_unsubscribe'] ) ) ) ? true : false );
		}

		if ( ! wp_script_is( 'sbscrbr_form_scripts', 'registered' ) ) {
			wp_register_script( 'sbscrbr_form_scripts', plugins_url( 'js/form_script.js', __FILE__ ), array( 'jquery' ), false, true );
		}

		if ( $sbscrbr_options['form_title_field'] && ! empty( $widget_title ) ) {
			echo wp_kses_post( $args['before_widget'] . $args['before_title'] . $widget_title . $args['after_title'] );
		}
		?>

		<form id="sbscrbr-form-<?php echo esc_attr( $args['widget_id'] ); ?>" method="post" action="<?php echo esc_url( $action_form ); ?>" id="subscrbr-form-<?php echo esc_attr( $args['widget_id'] ); ?>" class="subscrbr-sign-up-form" style="position: relative;">
			<?php
			if ( $sbscrbr_options['form_label_field'] && ! empty( $widget_form_label ) ) {
				echo '<p class="sbscrbr-label-wrap">' . esc_html( $widget_form_label ) . '</p>';}

			if ( ! empty( $report_message ) ) {
				echo wp_kses_post( $report_message['message'] );
			}
			?>

			<p class="sbscrbr-email-wrap">
				<input type="text" name="sbscrbr_email" value="" placeholder="<?php echo esc_html( $widget_placeholder ); ?>"/>
			</p>
			<p class="sbscrbr-unsubscribe-wrap">
				<label>
					<input id="sbscrbr-<?php echo esc_attr( $args['widget_id'] ); ?>" type="checkbox" name="sbscrbr_unsubscribe" value="yes" style="vertical-align: middle;"/>
					<?php echo esc_html( $widget_checkbox_label ); ?>
				</label>
			</p>
			<?php if ( $sbscrbr_options['gdpr'] ) { ?>
				<p class="sbscrbr-GDPR-wrap">
					<label>
						<input id="sbscrbr-GDPR-checkbox" required type="checkbox" name="sbscrbr_GDPR" style="vertical-align: middle;"/>
						<?php
						echo esc_html( $sbscrbr_options['gdpr_cb_name'] );
						if ( ! empty( $sbscrbr_options['gdpr_link'] ) ) {
							?>
							<a href="<?php echo esc_url( $sbscrbr_options['gdpr_link'] ); ?>" target="_blank"><?php echo esc_html( $sbscrbr_options['gdpr_text'] ); ?></a>
						<?php } else { ?>
							<span><?php echo esc_html( $sbscrbr_options['gdpr_text'] ); ?></span>
						<?php } ?>
					</label>
				</p>
				<?php
			}
			echo apply_filters( 'sbscrbr_add_field', '', 'bws_subscriber' );
			?>
			<p class="sbscrbr-submit-block" style="position: relative;">
				<input type="submit" value="<?php echo esc_attr( $widget_button_label ); ?>" name="sbscrbr_submit_email" class="submit" />
				<input type="hidden" value="<?php echo esc_attr( $args['widget_id'] ); ?>" name="sbscrbr_form_id" />
			</p>
		</form>
		<?php
		echo wp_kses_post( $args['after_widget'] );
	}

	/**
	 * Function to displaying widget settings in back end
	 *
	 * @param array $instance Array with widget settings.
	 */
	public function form( $instance ) {
		global $sbscrbr_options;

		$widget_title           = isset( $instance['widget_title'] ) ? stripslashes( esc_attr( $instance['widget_title'] ) ) : null;
		$widget_form_label      = isset( $instance['widget_form_label'] ) ? stripslashes( esc_attr( $instance['widget_form_label'] ) ) : null;
		$widget_placeholder     = isset( $instance['widget_placeholder'] ) ? stripslashes( esc_attr( $instance['widget_placeholder'] ) ) : __( 'E-mail', 'subscriber' );
		$widget_checkbox_label  = isset( $instance['widget_checkbox_label'] ) ? stripslashes( esc_attr( $instance['widget_checkbox_label'] ) ) : __( 'Unsubscribe', 'subscriber' );
		$widget_button_label    = isset( $instance['widget_button_label'] ) ? stripslashes( esc_attr( $instance['widget_button_label'] ) ) : __( 'Subscribe', 'subscriber' );
		$widget_apply_settings  = isset( $instance['widget_apply_settings'] ) && '1' == $instance['widget_apply_settings'] ? '1' : '0';

		if ( $sbscrbr_options['form_title_field'] ) {
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>">
					<?php esc_html_e( 'Title', 'subscriber' ); ?>:
					<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'widget_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_title' ) ); ?>" type="text" value="<?php echo esc_attr( $widget_title ); ?>"/>
				</label>
			</p>
			<?php
		}
		if ( $sbscrbr_options['form_label_field'] ) {
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'widget_form_label' ) ); ?>">
					<?php esc_html_e( 'Text above the subscribe form', 'subscriber' ); ?>:
					<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'widget_form_label' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_form_label' ) ); ?>"><?php echo esc_html( $widget_form_label ); ?></textarea>
				</label>
			</p>
		<?php } ?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'widget_placeholder' ) ); ?>">
				<?php esc_html_e( 'Placeholder for text field "E-mail"', 'subscriber' ); ?>:
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'widget_placeholder' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_placeholder' ) ); ?>" type="text" value="<?php echo esc_attr( $widget_placeholder ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'widget_checkbox_label' ) ); ?>">
				<?php esc_html_e( 'Label for "Unsubscribe" checkbox', 'subscriber' ); ?>:
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'widget_checkbox_label' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_checkbox_label' ) ); ?>" type="text" value="<?php echo esc_attr( $widget_checkbox_label ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'widget_button_label' ) ); ?>">
				<?php esc_html_e( 'Label for "Subscribe" button', 'subscriber' ); ?>:
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'widget_button_label' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_button_label' ) ); ?>" type="text" value="<?php echo esc_attr( $widget_button_label ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'widget_apply_settings' ) ); ?>">
				<input id="<?php echo esc_attr( $this->get_field_id( 'widget_apply_settings' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'widget_apply_settings' ) ); ?>" type="checkbox" value="1" <?php checked( '1' == $widget_apply_settings, true ); ?> />
				<?php esc_html_e( 'apply plugin settings', 'subscriber' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Function to save widget settings
	 *
	 * @param array $new_instance Array with new settings.
	 * @param array $old_instance Array with old settings.
	 * @return array  $instance   Array with updated settings.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['widget_title']           = ( ! empty( $new_instance['widget_title'] ) ) ? strip_tags( $new_instance['widget_title'] ) : null;
		$instance['widget_form_label']      = ( ! empty( $new_instance['widget_form_label'] ) ) ? strip_tags( $new_instance['widget_form_label'] ) : null;
		$instance['widget_placeholder']     = ( ! empty( $new_instance['widget_placeholder'] ) ) ? strip_tags( $new_instance['widget_placeholder'] ) : null;
		$instance['widget_checkbox_label']  = ( ! empty( $new_instance['widget_checkbox_label'] ) ) ? strip_tags( $new_instance['widget_checkbox_label'] ) : null;
		$instance['widget_button_label']    = ( ! empty( $new_instance['widget_button_label'] ) ) ? strip_tags( $new_instance['widget_button_label'] ) : null;
		$instance['widget_apply_settings']  = ( ! empty( $new_instance['widget_apply_settings'] ) ) ? strip_tags( $new_instance['widget_apply_settings'] ) : null;

		return $instance;
	}
}
