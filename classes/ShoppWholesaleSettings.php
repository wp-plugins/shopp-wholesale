<?php

/**
 * Settings and constants.
 *
 * @author Tyson
 */
class ShoppWholesaleSettings {

	private static $instance;

	private $defaults = array(
		'customer-permission-system' => 'shopp',
		'prevent-duplicate-customers' => 'on',
		'wholesale-price-prefix' => '',
		'wholesale-price-suffix' => '(Wholesale)',
		'default-account-request-role' => ShoppWholesale::WHOLESALE_ACCOUNT_ROLE,
		'default-account-request-customer-type' => ShoppWholesale::SHOPP_WHOLESALE_CUSTOMER_TYPE
	);

	/**
	 * Get an option value
	 * @param $name
	 */
	public function get($name) {
		$options = get_option(ShoppWholesale::OPTION_NAME);
		if (!isset($options[$name])) {
			$options[$name] = $this->defaults[$name];
		}
		return $options[$name];
	}

	/**
	 * Write default options to the database.
	 */
	public function install() {
		add_option(ShoppWholesale::OPTION_NAME, $this->defaults);
	}

	/**
	 * Validate callback for option forms.
   *
	 * @param array $input
	 */
	public function validate(array $input) {
		//TODO: NOT IMPL
		return $input;
	}

	/**
	 * Prevent cloning.
	 */
	public function __clone() {}

		/**
	 * Singleton factory method.
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
    	$c = __CLASS__;
      self::$instance = new $c;
    }
    return self::$instance;
	}

}

?>