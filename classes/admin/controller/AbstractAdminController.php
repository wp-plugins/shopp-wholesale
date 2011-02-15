<?php

/**
 * This class can be overriden to create a basic admin page
 * for editing registered settings.
 *
 * The class name suffix after the '_' must match the slug suffix
 * after 'shopp-wholesale-'.
 *
 * @author Tyson
 */
abstract class AbstractAdminController {

	const SLUG_PREFIX = 'shopp-wholesale-';

	/**
	 * Messages to be displyed to the user.
	 * @var string
	 */
	private $messages = array('info'=>array(), 'warn'=>array(), 'error'=>array());

	/**
	 * Human-readable name of this record.
	 *
	 * Defaults to 'record'.
	 */
	public function getRecordLabel($plural = false) {
		return $plural ? 'records' : 'record';
	}

	/**
	 * Main entry point.
	 *
	 * Every request calls this method. It is responsible
	 * for inspecting the request and routing to the appropriate
	 * method.
	 */
	public function handle() {
		$this->display();
	}

	/**
	 * The title to show in the header.
	 */
	public function getAdminPageTitle() {
		return 'Wholesale Settings';
	}

	/**
	 * The icon to show in the header.
	 */
	public function getAdminPageIcon() {
		global $Shopp;
		return $Shopp->uri.'/core/ui/icons/shopp32.png';
	}

	/**
	 * Whether this admin page would like a form tag printed.
	 */
	public function shouldPrintForm() {
		return true;
	}

	/**
	 * Print the form tag required by this settings page.
	 */
	public function printFormTag() {
		echo '<form action="options.php" method="post">';
	}

	/**
	 * Print the submit buttons for this form.
	 */
	public function printFormButtons() {
?>
		<p class="submit">
    	<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
<?php
	}

	/**
	 * Print navigation links in admin header.
	 */
	public function printNavigationLinks() {
		require_once(SWS_ABSPATH ."/admin/navigation.php");
	}

	/**
	 * Adds an error message.
	 *
	 * @param $message
	 */
	protected function addError($message) {
		$this->addMessage($message, 'error');
	}

	/**
	 * Adds a warning message.
	 *
	 * @param $message
	 */
	protected function addWarning($message) {
		$this->addMessage($message, 'warn');
	}

	/**
	 * Set an admin message.
	 *
	 * @param string $message
	 * @param boolean $level (Optional, default: 'info') What level of message to show: info, warn, error.
	 */
	protected function addMessage($message, $level = 'info') {
		array_push($this->messages[$level], $message);
	}

	/**
	 * Get the current transient messages, and clear them.
	 *
	 * @param $level (Optional, default: info) What level of message to get: info, warn, or error.
	 */
	public function getMessages($level = 'info') {
		$result = $this->messages[$level];
		$this->messages[$level] = array();
		return $result;
	}

	/**
	 * The default is to display a generic admin page.
	 */
	protected function display() {

		//a generic admin page
		require_once(SWS_ABSPATH ."/admin/admin_header.php");
		settings_fields(ShoppWholesale::OPTION_GROUP);
		do_settings_sections($this->getSlug());
		require_once(SWS_ABSPATH ."/admin/admin_footer.php");

	}

	/**
	 * Get the unique slug that this class handles.
	 */
	public function getSlug() {
		$class_name = get_class($this);
		$arr = explode('_', $class_name, 2);
		return self::SLUG_PREFIX . $arr[1];
	}

}

?>