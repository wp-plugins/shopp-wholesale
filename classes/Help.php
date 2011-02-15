<?php

/**
 * Handles all help functionality.
 *
 * @author Tyson
 */
class Help {

	private $field_help = array();
	private $section_help = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->parseHelp();
	}

	/**
	 * Parse the XML help file.
	 */
	protected function parseHelp() {
		$help_file_path = SWS_ABSPATH . 'help/help.xml';
		$doc = simplexml_load_file($help_file_path);

		foreach ($doc->fields->help as $help) {
			$this->field_help[(string) $help['key']] = (string) $help;
		}

	}

	/**
	 * Returns a short description of the field.
	 *
	 * @param $field_name
	 */
	public function getFieldHelp($field_name) {
		if (isset($this->field_help[$field_name])) {
			return apply_filters('sws-field-help', $this->field_help[$field_name], $field_name);
		}
	}

	/**
	 * Returns HTML for the admin page help.
	 *
	 * @param $page
	 */
	public function getAdminScreenContextualHelp($page) {

		$html = '';
		$page = substr($hook, strlen('shopp_page_shopp-wholesale-'));

		//look for help files
		$quick_path = SWS_ABSPATH . ShoppWholesale::HELP_DIR . ShoppWholesale::HELP_QUICK_DIR . $page . '.php';
		$manual_uri = ShoppWholesale::HELP_DIR . ShoppWholesale::HELP_MANUAL_DIR . $page . '.php';
		$manual_path = SWS_ABSPATH . $manual_uri;

		//get quick help if available
		if (file_exists($quick_path)) {
			ob_start();
			readfile($quick_path);
			$html .= ob_get_clean();
		}

		//add link to full manual if available
		if (file_exists($manual_path)) {
			$link = plugins_url($manual_uri, __FILE__);
			$html .= "<p class='sws-help-manual-link'><a href='$link' target='_blank'>Read the Manual Page</a></p>";
		}

		if ('' == trim($html)) {
			$html = false;
		}

		return $html;

	}

	/**
	 * Get help to show under the section title.
	 *
	 * @param $section
	 */
	public function getSectionHelp($section) {
		//TODO: section help
	}

}

?>