<?php
class AWeberElementorFormAction extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	public function get_name() {
		return 'aweber-form';
	}

	public function get_label() {
		return __( 'AWeber', 'aweber' );
	}

	public function run( $record, $ajax_handler ) {
		$settings = $record->get('form_settings');
		// If the list is not selected, then ignore dont call create subscriber.
		if (empty($settings['aweber_form_list'])) {
			return;
		}

		$raw_fields = $record->get('fields');
		// Normalize the Form Data
		$fields = [];
		foreach ( $raw_fields as $id => $field ) {
			$fields[$id] = $field['value'];
		}

		// Check key exists, before accessing the value.
		$name 	= isset($settings['aweber_form_name_static_field'], $fields) ? $fields[$settings['aweber_form_name_static_field']] : "NA";
		$email 	= isset($settings['aweber_form_email_static_field'], $fields) ? $fields[$settings['aweber_form_email_static_field']] : "NA";
		$ip_address = null;

		// If the Email field is empty or not valid, then dont add the subscriber
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			error_log('Create Subscriber Failed. (Invalid EmailID) Name: "' . $name . '" Email: "' . $email. '"');
			return;
		}

		$aweber_fields = array();
		for ($i =0; $i < 25; $i++) {
			$key = 'aweber_form_custom_dynamic_field_'.$i;
			if (!empty($settings[$key])) {
				array_push($aweber_fields, $settings[$key]);
			}
		}

		$custom_fields = array();
		foreach ($settings['form_fields'] as $form) {
			foreach ($aweber_fields as $key => $value) {
				if ( stripos($value, $form['custom_id']) !== false ) {
					$cvalue = $fields[$form['custom_id']];
					$ckey = explode('-', $value, 2);
					if (isset($ckey[1])) {
						$ckey = trim($ckey[1], '()');
						$custom_fields[$ckey] = $cvalue;
					}
				}
			}
		}

		// Logging the error in the Log file.
		error_log("Create Subscriber: Elementor Form Submit: Email: " . $email . " List: " . $settings['aweber_form_list']);

		global $aweber_webform_plugin;
		// Call create subscriber
		$aweber_webform_plugin->create_subscriber($email, $ip_address,
			$settings['aweber_form_list'], $name, $settings['aweber_form_tags'],
			$custom_fields);
	}

	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'aweber_form_action',
			[
				'label' => __( 'AWeber', 'aweber' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		global $aweber_webform_plugin;
		$pluginAdminOptions = get_option($aweber_webform_plugin->adminOptionsName);
		$oauth2TokensOptions = get_option($aweber_webform_plugin->oauth2TokensOptions);
		if ($aweber_webform_plugin->doAWeberTokenExists($pluginAdminOptions, $oauth2TokensOptions)):
			$widget->add_control(
				'important_note',
				[
					'label' => __( '', 'aweber' ),
					'type' => \Elementor\Controls_Manager::RAW_HTML,
					'raw' => __( 'Loading the AWeber lists', 'aweber' ),
				]
			);

			$widget->add_control(
				'aweber_form_list',
				[
					'label' => __( 'List', 'aweber' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'label_block' => true,
					'options'	=> []
				]
			);

			$widget->add_control(
				'aweber_form_tags',
				[
					'label' => __( 'Tags', 'aweber' ),
					'type' => \Elementor\Controls_Manager::TEXT,
					'label_block' => true,
					'description' => __( 'Add comma separated tags.', 'aweber' ),
				]
			);

			$widget->add_control(
				'aweber_form_more_options',
				[
					'label' => __( 'Field Mapping', 'aweber' ),
					'type' => \Elementor\Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);

			$widget->add_control(
				'aweber_form_name_static_field',
				[
					'label' => __( 'Name', 'aweber' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options'	=> []
				]
			);

			$widget->add_control(
				'aweber_form_email_static_field',
				[
					'label' => __( 'Email', 'aweber' ),
					'type' => \Elementor\Controls_Manager::SELECT,
					'options'	=> []
				]
			);

			$widget->add_control(
				'aweber_custom_fields_message',
				[
					'label' => __( '', 'aweber' ),
					'type' => \Elementor\Controls_Manager::RAW_HTML,
					'raw' => __( 'Loading custom fields', 'aweber' ),
				]
			);

			for($i = 0; $i < 25; $i++) {
				$widget->add_control(
					'aweber_form_custom_dynamic_field_'.$i,
					[
						'label' => __( 'Custom Fields', 'aweber' ),
						'type' => \Elementor\Controls_Manager::SELECT,
						'options'	=> []
					]
				);
			}
		else:
			$widget->add_control(
				'aweber_connection_closed_message',
				[
					'label' => __( '', 'aweber' ),
					'type' => \Elementor\Controls_Manager::RAW_HTML,
					'raw' => __( '<p style="text-align: center">Before using this element, please connect your AWeber account. <br><br><a href="'.admin_url('admin.php?page=aweber.php').'">Go to Plugin</a></p>', 'aweber' ),
				]
			);
		endif;

		$widget->end_controls_section();
	}

	public function on_export( $element ) {
		// Unset the Static fields.
		unset(
			$element['aweber_form_list'],
			$element['aweber_form_tags'],
			$element['aweber_form_name_static_field'],
			$element['aweber_form_email_static_field']
		);
		// Unset all the Dynamic custom fields.
		for ($i =0; $i < 25; $i++) {
			unset($element['aweber_form_custom_dynamic_field_'.$i]);
		}
    }
}
