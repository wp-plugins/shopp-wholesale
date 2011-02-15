<?php

require_once("AdminMenus.php");
require_once("AdminSettingsRegister.php");
require_once("DefaultAdminSettingsPrinter.php");
require_once("controller/DefaultAdminController.php");

/**
 * Admin controller.
 *
 * @author Tyson
 */
class ShoppWholesaleAdmin implements IInitializable {

	public $Help;
	public $Settings;
	public $Menus;
	public $SettingsRegister;

	private $controllers = array();

	/**
	 * The current admin controller.
	 * @var AbstractAdminController
	 */
	public $Controller;

	/**
	 * Late init.
	 */
	public function init() {

		global $ShoppWholesale;

		$this->Settings = $ShoppWholesale->Settings;
		$this->Help = $ShoppWholesale->Help;
		$this->Menus = new AdminMenus();
		$this->SettingsRegister = new AdminSettingsRegister( new DefaultAdminSettingsPrinter($this->Help) );
	}

	private function initActions() {
		add_action('admin_enqueue_scripts',	array($this, 'adminScripts'));
	}

	function adminScripts() {
		//TODO: review
//		shopp_enqueue_script('shopp');
//		wp_enqueue_style('shopp.colorbox',SHOPP_ADMIN_URI.'/styles/colorbox.css',array(),SHOPP_VERSION,'screen');
//		wp_enqueue_style('shopp.admin',SHOPP_ADMIN_URI.'/styles/admin.css',array(),SHOPP_VERSION,'screen');
//		wp_enqueue_script('postbox');
	}

	/**
	 * Display an admin page.
	 */
	function displayAdminPage() {

		//check permissions
		if ( !current_user_can('shopp_settings_wholesale') ) {
			wp_die(ShoppWholesale::_e('You do not have sufficient permissions to access this page.'));
		}

		//find controller based on menu slug
		$this->getControllerBySlug($_REQUEST['page']);
		$this->Controller->handle();

	}

	/**
	 * Return the appropriate controller for this section slug.
	 * @param $section
	 */
	public function getControllerBySlug($section = 'shopp-wholesale-settings') {

		//find unique page part
		$stub = substr($section, strlen('shopp-wholesale-'));

		//get controlle object
		return $this->getController($stub);

	}

	/**
	 * Get an admin controller.
	 *
	 * @param string $stub Eg, 'accounts' for Controller_accounts.
	 */
	public function getController($stub) {

		//is it already loaded?
		if (!isset($this->controllers[$stub])) {

			//look for a class
			$safe_stub = str_replace('-', '_', $stub);
			$class_name = "Controller_{$safe_stub}";
			$class_path = SWS_ABSPATH ."classes/admin/controller/{$class_name}.php";

			if (!file_exists($class_path)) {
				$this->Controller = new DefaultAdminController($stub);
			} else {
				require_once("$class_path");
				$this->Controller = new $class_name;
			}

			$this->controllers[$stub] = $this->Controller;

		}

		//return object
		return $this->controllers[$stub];

	}

}

?>