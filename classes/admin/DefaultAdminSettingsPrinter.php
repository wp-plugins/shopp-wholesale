<?php

require_once("IAdminSettingsPrinter.php");

class DefaultAdminSettingsPrinter implements IAdminSettingsPrinter {

	private $Settings;
	private $Help;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $ShoppWholesale;
		$this->Settings = $ShoppWholesale->Settings;
		$this->Help = $ShoppWholesale->Help;
	}

	/**
	 *
	 * Enter description here ...
	 * @param $args
	 */
	function printSection($args) {
		$help = '';
		if (null != $this->Help) {
		  $help = $this->Help->getSectionHelp($args[0]);
    }
    return $help;
	}

	/**
	 * Print a standard text field.
	 *
	 * @param $args
	 */
	function printTextField($args) {

		$name = $args['name'];
		$value = $this->Settings->get($name);
		unset($args['name']);

		echo "<input type='text' name='".ShoppWholesale::OPTION_NAME."[$name]' id='$name' value='$value' />\n";
		$this->printFieldHelp($name);

	}

	/**
	 * Print a standard checkbox field.
	 *
	 * @param $args
	 */
	function printCheckboxField($args) {

		$name = $args['name'];
		$checkbox_text = $args['checkbox_text'];
		$value = $this->Settings->get($name);
		$checked = Util::isTrue($value) ? " checked='checked'" : '';

		echo "<input type='hidden' name='".ShoppWholesale::OPTION_NAME."[$name]' value='off' /> \n";
		echo "<input type='checkbox' name='".ShoppWholesale::OPTION_NAME."[$name]' id='$name' value='on' $checked /> \n";
		echo "<label for='$name'>$checkbox_text</label>";
		$this->printFieldHelp($name);

	}

	/**
	 * Print a standard option select field.
	 *
	 * @param $name
	 * @param $title
	 * @param $choices
	 */
	function printSelectField($args) {

		$name = $args['name'];
		unset($args['name']);

		$current = $this->Settings->get($name);
		echo "<select name='".ShoppWholesale::OPTION_NAME."[$name]' id='$name' >\n";
		foreach ($args as $key => $value) {
			$selected = '';
			if ($key == $current) {
				$selected = " selected='selected' ";
			}
			echo "<option $selected value='$key'>$value</option>\n";
		}
		echo "</select>\n";

		$this->printFieldHelp($name);

	}

	/**
	 * Print field help.
	 * @param $name
	 */
	protected function printFieldHelp($name) {
		if (null != $this->Help) {
			$help_text = $this->Help->getFieldHelp($name);
			if (!empty($help_text)) {
				echo "<br/><span class='sws-field-help'>$help_text</span>\n";
			}
		}
	}

}

?>