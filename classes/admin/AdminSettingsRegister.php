<?php

require_once("IAdminSettingsPrinter.php");

class AdminSettingsRegister {

	private $SettingsPrinter;

	/**
	 * Constructor.
	 */
	public function __construct(IAdminSettingsPrinter $SettingsPrinter = null) {
		if (null == $SettingsPrinter) {
			$Help = new Help();
			$SettingsPrinter = new DefaultAdminSettingsPrinter($Help);
		}
		$this->SettingsPrinter = $SettingsPrinter;
		$this->initActions();
	}

	/**
	 * Get default settings.
	 */
	public function getDefaults() {
		return $this->defaults;
	}

	/**
	 * Initialise wp actions.
	 */
	private function initActions() {
		add_action('admin_init', array($this, 'registerSettings'));
	}

	/**
	 * Register settings.
	 */
	function registerSettings() {

		global $wp_roles;

		//main option
		register_setting(ShoppWholesale::OPTION_GROUP, ShoppWholesale::OPTION_NAME, array($this, 'validate'));

		///SETTINGS
		$section = 'shopp-wholesale-settings';
		$this->addSettingsSection($section, 'Settings');
		$this->addSelectField($section, 'customer-permission-system', 'Permission Check',
			array(
				'roles'=>'Use Wordpress role system (\'Wholesale Account\' role)',
				'shopp'=>'Use Shopp customer type (\'Wholesale\' customer type)'
			)
		);

		///PRESENTATION
		$section = 'shopp-wholesale-presentation';
		$this->addSettingsSection($section, 'Presentation');
		$this->addTextField($section, 'wholesale-price-prefix', 'Wholesale Price Prefix');
		$this->addTextField($section, 'wholesale-price-suffix', 'Wholesale Price Suffix');
 
		///ACCOUNTS
		$lookup = Lookup::customer_types();
		$customer_types = array_combine($lookup, $lookup); //use text value for key and value
		$role_options = array_merge(array(ShoppWholesale::WP_DEFAULT_ROLE=>'(Wordpress Default Role)'), $wp_roles->get_names());
		
		$section = 'shopp-wholesale-account-settings';
		$this->addSettingsSection($section, 'Account Request Settings');
		$this->addSelectField($section, 'default-account-request-role', 'Default Role', $role_options);
		$this->addSelectField($section, 'default-account-request-customer-type', 'Default Customer Type', $customer_types);
		$this->addCheckboxField($section, 'prevent-duplicate-customers', 'Duplicate Customers', 'Do not allow duplicate customer accounts (based on email)');

	}

	/**
	 * Validate settings.
	 *
	 * @param $data
	 */
	function validate($data) {
		return $data;
	}

	/**
	 * Add a settings section.
	 *
	 * @param $section_name
	 * @param $section_title
	 */
	function addSettingsSection($section_name, $section_title) {
		add_settings_section($section_name, $section_title, array($this->SettingsPrinter, 'printSection'), $section_name);
	}

	/**
	 * Convenience method for checkbox fields.
	 *
	 * @param $section_name
	 * @param $setting_name
	 * @param $setting_title
	 */
	function addCheckboxField($section_name, $setting_name, $setting_title, $checkbox_text) {
		$this->addSettingsField($section_name, $setting_name, $setting_title, array($this->SettingsPrinter, 'printCheckboxField'), array('checkbox_text'=>$checkbox_text));
	}


	/**
	 * Convenience method for text fields.
	 *
	 * @param $section_name
	 * @param $setting_name
	 * @param $setting_title
	 */
	function addTextField($section_name, $setting_name, $setting_title) {
		$this->addSettingsField($section_name, $setting_name, $setting_title, array($this->SettingsPrinter, 'printTextField'));
	}

	/**
	 * Convenience method for select fields.
	 *
	 * @param $section_name
	 * @param $setting_name
	 * @param $setting_title
	 */
	function addSelectField($section_name, $setting_name, $setting_title, $choices) {
		$this->addSettingsField($section_name, $setting_name, $setting_title, array($this->SettingsPrinter, 'printSelectField'), $choices);
	}

	/**
	 * Add a settings field.
	 *
	 * @param $section_name
	 * @param $setting_name
	 * @param $setting_title
	 * @param $callback
	 * @param $args
	 */
	function addSettingsField($section_name, $setting_name, $setting_title, $callback, $args = array()) {
		if (!is_array($args)) $args = array();
		$args['name'] = $setting_name;
		add_settings_field($setting_name, $setting_title, $callback, $section_name, $section_name, $args);
	}

}

?>