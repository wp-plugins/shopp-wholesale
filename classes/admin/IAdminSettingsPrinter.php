<?php

/**
 * Interface for objects that handle printing of admin interface elements.
 *
 * @author Tyson
 */
interface IAdminSettingsPrinter {

	public function printSection($args);
	public function printTextField($args);
	public function printCheckboxField($args);
	public function printSelectField($args);

}

?>