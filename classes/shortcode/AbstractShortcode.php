<?php

/**
 * Generic shortcode handler.
 *
 * @author Tyson
 */
abstract class AbstractShortcode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register();
	}

	/**
	 * The actual shortcode string.
	 *
	 * Can return an array of shortcodes, they will all be mapped.
	 */
	protected abstract function getShortcode();

	/**
	 * The default attributes.
	 */
	protected abstract function getDefaultAttributes();

	/**
	 * Handle the shortcode call.
	 *
	 * @param array $input The shortcode attributes, with defaults added.
	 */
	protected abstract function handle(array $input);

	/**
	 * Registers the shorcode.
	 */
	protected function register() {
		$codes = $this->getShortcode();
		if (!is_array($codes)) {
			$codes = array($codes);
		}
		foreach ($codes as $code) {
			add_shortcode($code, array($this, 'process'));
		}

	}

	/**
	 * Processes the shortcode call.
	 *
	 * @param $atts
	 */
	public function process($atts) {

		//get input and defaults
		$input = shortcode_atts($this->getDefaultAttributes(), $atts);

		//call concrete method
		return $this->handle($input);

	}

}