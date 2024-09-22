<?php
namespace Elementor;

class AWeberElementorWidget extends Widget_Base {

	public function __construct($data = [], $args = null) {
		parent::__construct($data, $args);

		$nonces = [
			'get_aweber_lists' => wp_create_nonce('get_aweber_lists_for_elementor'),
			'get_aweber_custom_fields' => wp_create_nonce('get_aweber_custom_fields'),
			'get_aweber_webform_shortcodes' => wp_create_nonce('get_aweber_webform_shortcodes'),
		];

		wp_register_script( 'aweber-elementor-script-handle', plugins_url('../src/js/aweber-elementor-script.js', __FILE__),
			[ 'elementor-frontend' ], '1.0.0', true );

		if (is_admin()){
			wp_enqueue_style( 'aweber-elementor-style-handle', plugins_url('../src/css/aweber-elementor-style.css', __FILE__));
		}

		wp_localize_script( 'aweber-elementor-script-handle', 'php_vars', array(
				'plugin_connect_url' => admin_url('admin.php?page=aweber.php'),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonces' => $nonces
			));
   	}

   	/**
	 * Get script dependencies.
	 *
	 * Retrieve the list of script dependencies the element requires.
	 *
	 * @access public
	 *
	 * @return array Element scripts dependencies.
	 */
   	public function get_script_depends() {
   		return [ 'aweber-elementor-script-handle' ];
   	}

   	/**
	 * Get Need Help URL.
	 *
	 * Retrieve AWeber Need Help url
	 *
	 * @access public
	 *
	 * @return string Need Help URL.
	 */
   	public function get_help_url() {
   		return 'https://help.aweber.com/hc/en-us/articles/360047079534-How-do-I-integrate-Elementor-with-AWeber-';
   	}
	
	/**
	 * Get widget name.
	 *
	 * Retrieve AWeber widget name.
	 *
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'aweber';
	}
	
	/**
	 * Get widget title.
	 *
	 * Retrieve AWeber widget title.
	 *
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'AWeber', 'aweber' );
	}
	
	/**
	 * Get widget icon.
	 *
	 * Retrieve AWeber widget icon.
	 *
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'aw-logo';
	}
	
	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the AWeber widget belongs to.
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'basic' ];
	}
	
	/**
	 * Register AWeber widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'aweber_signup_form',
			[
				'label' => __( 'AWeber Configuration', 'aweber' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		global $aweber_webform_plugin;
		$pluginAdminOptions = get_option($aweber_webform_plugin->adminOptionsName);
		$oauth2TokensOptions = get_option($aweber_webform_plugin->oauth2TokensOptions);
		if ($aweber_webform_plugin->doAWeberTokenExists($pluginAdminOptions, $oauth2TokensOptions)):
			$this->add_control(
				'important_note',
				[
					'label' => __( '', 'aweber' ),
					'type' => Controls_Manager::RAW_HTML,
					'raw' => __( 'Loading the AWeber Sign Up Forms', 'aweber' ),
				]
			);

			$this->add_control(
				'aweber_list',
				[
					'label' => __( 'Lists', 'aweber' ),
					'label_block' => true,
					'type' => Controls_Manager::SELECT,
					'default' => '0',
					'options' => []
				]
			);

			$this->add_control(
				'aweber_form',
				[
					'label' => __( 'Sign Up Forms & Split Tests', 'aweber' ),
					'label_block' => true,
					'type' => Controls_Manager::SELECT,
					'default' => '0',
					'options' => []
				]
			);
		else:
			$this->add_control(
				'aweber_connection_closed_message',
				[
					'label' => __( '', 'aweber' ),
					'type' => Controls_Manager::RAW_HTML,
					'raw' => __( '<p style="text-align: center">Before using this element, please connect your AWeber account. <br><br><a href="'.admin_url('admin.php?page=aweber.php').'">Go to Plugin</a></p>', 'aweber' ),
				]
			);
		endif;
		$this->end_controls_section();
	}
	
	/**
	 * Render AWeber widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @access protected
	 */
	protected function render() {
        $settings = $this->get_settings_for_display();
        if (!empty($settings['aweber_list'])  
        	&& !empty($settings['aweber_form']) 
        	&& strpos($settings['aweber_form'], $settings['aweber_list']) === 0) {
        	
        	$signup_form = explode('-', $settings['aweber_form']);

        	global $aweber_webform_plugin;
        	echo $aweber_webform_plugin->aweberShortcodeHandler(array(
        		'listid'	=> $signup_form[0],
        		'formid'	=> $signup_form[1],
        		'formtype'	=> $signup_form[2]
        	));

        	echo '<div style="display: none">
        		[aweber listid='.$signup_form[0].' formid='.$signup_form[1].' formtype='.$signup_form[2].']
            </div>';
        }
	}

	protected function content_template() {

	}
}
