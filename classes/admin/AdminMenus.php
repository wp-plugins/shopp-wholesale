<?php

/**
 * Sets up the admin menus.
 *
 * @author Tyson
 */
class AdminMenus {

	private $Controller;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $ShoppWholesale;
		$this->Controller = $ShoppWholesale->Admin;
		$this->initActions();
	}

	/**
	 * Initialise wp actions.
	 */
	private function initActions() {
		add_action('admin_menu', array($this, 'initAdminMenu'), 100); //init after shopp menus
	}

	/**
	 * Install our admin menu.
	 */
	function initAdminMenu() {

		//add our submenu
		$hook_main = $this->addMenu('settings');
		$this->addMenu('presentation', $hook_main);
		$this->addMenu('account-settings', $hook_main);

		$accounts_hook = $this->addMenu('accounts', ShoppWholesale::SHOPP_MENU_ROOT, 'Customer Account Requests', 'Account Requests');
		$this->addMenu('account-review', $accounts_hook);

		//reorder menus
		$this->sendToBottom('Settings');

		$this->__TEMP__dodge_up_adminMenus();

	}

	//FIXME: REMOVE
	function __TEMP__dodge_up_adminMenus() {
		global $menu;
		$current_user = wp_get_current_user();
		if ('admin' != $current_user->user_login) {
			foreach(array_keys($menu) as $temp => $key) {
				if ($menu[$key][0] == 'Settings') {
					unset ($menu[$key]);
					break;
				}
			}
		}
	}

	/**
	 * Add a menu.
	 *
	 * @param $name
	 * @param $parent_hook
	 * @return The new hook.
	 */
	function addMenu($name, $parent_hook = ShoppWholesale::SHOPP_MENU_ROOT, $title='Wholesale Administration', $menu='Wholesale') {

		//add menu
		$hook = add_submenu_page(
			$parent_hook,
			$title,
			$menu,
			apply_filters('sws-admin-capability', 'shopp_settings_wholesale', $name),
			'shopp-wholesale-'.$name,
			array($this->Controller, 'displayAdminPage')
		);

		//get help
		if (null != $this->Help) {
			$help = $this->Help->getAdminScreenContextualHelp($hook);
			if (false !== $help) {
				add_contextual_help($hook, $help);
			}
		}

		return $hook;

	}

	/**
	 * Put our submenu where we want it.
	 */
	function sendToBottom($title) {

		global $submenu;

		//do some sneaky re-ordering
		$shopp_menu = &$submenu['shopp-orders'];
		foreach ($shopp_menu as $index => $menu) {
			if ($title == $menu[0]) {
				break;
			}
		}

		$item = $shopp_menu[$index];
		unset($shopp_menu[$index]);
		array_push($shopp_menu, $item);

	}

}

?>