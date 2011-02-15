<?php

/**
 * Contains action and filter hooks to modify the way the Shopp Wholesale plugin works.
 */
class Hooks {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->initActions();
	}

	/**
	 * Init action hooks.
	 *
	 * In this case we are implementing one of our own filters.
	 */
	protected function initActions() {
		add_filter('sws-print-wholesale-price', array($this, 'printWholesalePrice'));
	}

	/**
	 * Changes the way wholesale prices are printed.
	 *
	 * Default version adds the text '(Wholesale)' after the price.
	 *
	 * @param $price
	 */
	public function printWholesalePrice($price) {

		global $ShoppWholesale;

		//get options
		$p = $ShoppWholesale->Settings->get('wholesale-price-prefix');
		$s = $ShoppWholesale->Settings->get('wholesale-price-suffix');

		//ensure sane spacing
		if (substr($p, -1) != ' ') $p .= ' ';
		if (substr($s, 1) != ' ') $s = " $s";

		return $p . $price . $s;

	}

}

?>