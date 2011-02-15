<?php

/**
 * Simple interface to mark classes which
 * can be late-initialised.
 *
 * This is necessary because during construction,
 * some objects are not fully available.
 *
 * @author Tyson
 */
interface IInitializable {

	/**
	 * Initialise this object.
	 */
	public function init();

}