<?php

require_once("AbstractAdminController.php");

/**
 * Default controller.
 *
 * @author Tyson
 */
class DefaultAdminController extends AbstractAdminController {

	private $stub;

	/**
	 * Pass the stub for the slug.
	 *
	 * @param $stub
	 */
	public function __construct($stub) {
		$this->stub = self::SLUG_PREFIX . $stub;
	}

	/**
	 * Overrided to return static slug.
	 */
	public function getSlug() {
		return $this->stub;
	}

}

