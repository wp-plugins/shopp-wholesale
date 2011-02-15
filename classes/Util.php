<?php

/**
 * General utility methods.
 *
 * Some are facades for procedural functions.
 *
 * @author Tyson
 */
class Util {

	/**
	 * Does the currently logged in user have a wholesale account?
	 */
	public static function isUserWholesale() {
		return Roles::isUserWholesale();
	}

	/**
	 * Copy properties.
	 *
	 * @param stdClass $from
	 * @param stdClass $to
	 */

	public static function copyProperties(stdClass $from, stdClass $to) {
		foreach (get_object_vars($from) as $name => $value) {
			$to->{$name} = $value;
		}
	}

	/**
	 * Print location for lists.
	 * @param $Address
	 */
	public static function printCustomerLocation($Address) {
		$location = '';
		$location = $Address->city;
		if (!empty($location) && !empty($Address->state)) $location .= ', ';
		$location .= $Address->state;
		if (!empty($location) && !empty($Address->country))
			$location .= ' &mdash; ';
		$location .= $Address->country;
		echo esc_html($location);
	}

	/**
	 * Capitalises first letter of string.
	 */
	public static function capitaliseFirst($str) {
		return strtoupper($str[0]) . substr($str, 1);
	}

	/**
	 * Is the supplied string a truth value?
	 */
	public static function isTrue($str) {
		return value_is_true($str);
	}

	/**
	 * Build on/off string from bool.
	 */
	public static function onOff($bool) {
		return $bool ? 'on' : 'off';
	}

}

/**
 * TEMP: Debug print
 */
function _dd($d, $return=false) {
	if (SWS_DEBUG) {
		$s = "<pre>". print_r($d, true) ."</pre>";
		if ($return) return $s; else echo $s;
	}
}

/**
 * TEMP: Debug print
 */
function _de($d, $level = E_USER_ERROR) {
	if (SWS_DEBUG) {
		$s = "<pre>". print_r($d, true) ."</pre>";
		error_log($s, $level);
	}
}
