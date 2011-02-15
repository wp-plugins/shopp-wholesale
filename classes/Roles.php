<?php

/**
 * Handles roles and permissions.
 *
 * @author Tyson
 */
class Roles {

	/**
	 * Does the currently logged in user have a wholesale account?
	 */
	public static function isUserWholesale() {

		global $Shopp;
		global $ShoppWholesale;

		$result = false;
		if ('roles' == $ShoppWholesale->Settings->get('customer-permission-system')) {
			$result = current_user_can(ShoppWholesale::WHOLESALE_ACCOUNT_CAPABILITY);
		} else {
			if (isset($Shopp->Order->Customer)) {
				$result = (ShoppWholesale::SHOPP_WHOLESALE_CUSTOMER_TYPE == $Shopp->Order->Customer->type);
			}
		}

		//return filtered
		return apply_filters('sws-user-is-wholesale', $result);

	}

	/**
	 * Install wholesale-related roles.
	 */
	public function install() {

		global $wp_roles;

		if(!$wp_roles) {
			$wp_roles = new WP_Roles();
		}

		//required caps
		$wholesale_caps = array(ShoppWholesale::WHOLESALE_ACCOUNT_CAPABILITY=>true, 'read'=>true);

		//create wholesale role
		$wp_roles->remove_role(ShoppWholesale::WHOLESALE_ACCOUNT_ROLE);
		$wp_roles->add_role(ShoppWholesale::WHOLESALE_ACCOUNT_ROLE, ShoppWholesale::WHOLESALE_ACCOUNT_ROLE_LABEL, $wholesale_caps);

		//add wholesale edit role to admin
		$wp_roles->add_cap('administrator', ShoppWholesale::WHOLESALE_SETTINGS_CAPABILITY);

		//add wholesale edit role to every role that currently has 'shopp_settings' cap
		foreach($wp_roles->get_names() as $role_name) {
			$role = &$wp_roles->get_role($role_name);
			if (null != $role && $role->has_cap('shopp_settings')) {
				$role->add_cap(ShoppWholesale::WHOLESALE_SETTINGS_CAPABILITY);
			}
		}

	}

}

/**
 * Convenience function.
 */
function shopp_user_is_wholesale() {
	return Roles::isUserWholesale();
}

?>