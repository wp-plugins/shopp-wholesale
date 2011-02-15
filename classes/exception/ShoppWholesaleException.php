<?php

/**
 * Basic exception.
 *
 * @author Tyson
 */
class ShoppWholesaleException extends Exception {

	/**
   * Compulsary message.
   *
   * @param $message
   * @param $code
	 * @param $previous
	*/
	public function __construct($message, $code = 0, Exception $previous = null) {
  	parent::__construct($message, $code, $previous);
  	error_log('SHOPP_WHOLESALE: '. $message, E_USER_ERROR);
	}

}