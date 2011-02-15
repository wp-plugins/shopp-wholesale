<?php

require_once('AbstractAdminController.php');

/**
 * Controller that lists multiple items and allows a single item to be edited.
 *
 * @author Tyson
 */
abstract class AbstractAdminListController extends AbstractAdminController {

	/**
	 * The object currently being edited, or null.
	 * @var stdClass
	 */
	protected $Object = null;

	/**
	 * Handle a submitted form.
	 *
	 * @param array $data The request data.
	 */
	public abstract function handleSubmit($data);

	/**
	 * Handle a form submission from the list page.
	 *
	 * By default this is implemented to simply call
	 * the normal handleSubmit() method.
	 *
	 * @param array $data The request data.
	 */
	public function handleBulkSubmit($data) {
		return $this->handleSubmit($data);
	}

	/**
	 * Return the SQL used to get a list from the DB.
	 *
	 * This method could inspect the request and apply
	 * dynamic sorting if required.
	 */
	public abstract function getListQuery();

	/**
	 * Print a table row.
	 *
	 * @param Object $object
	 */
	public abstract function printTableRow(stdClass $Object);

	/**
	 * The the column headers.
	 *
	 * @param boolean $header (Optional, default: true) Whether this is being used to print header or footer columns.
	 */
	public abstract function printColumns($header = true);

	/**
	 * Load an object from the database with the given id.
	 *
	 * @param $id
	 */
	public abstract function loadObject($id);

	/**
	 * Print the search box.
	 *
	 * Defaults to empty
	 */
	public function printSearchBox() {}

	/**
	 * Print the bulk actions.
	 *
	 * Defaults to empty.
	 */
	public function printBulkActions() {}

	/**
	 * Checks for a current object representing the
	 * record being edited. Calls on the child class
	 * to load one if the object is null.
	 */
	public function getObject() {
		if (null == $this->Object) {
			if (isset($_REQUEST[$this->getIdName()])) {
				$id = $_REQUEST[$this->getIdName()];
				$this->Object = $this->loadObject($id);
			}
		}
		return $this->Object;
	}

	/**
	 * Returns an array of data objects that will
	 * be used to populate the table.
	 */
	public function getListData() {
		global $wpdb;
		return $wpdb->get_results($this->getListQuery());
	}

	/**
	 * Alias to display().
	 */
	public function listPage() {
		$this->display();
	}

	/**
	 * Include a generic list template.
	 *
	 * @see classes/admin/controller/AbstractAdminController::display()
	 */
	protected function display() {

		require_once(SWS_ABSPATH ."/admin/admin_header.php");
		settings_fields(ShoppWholesale::OPTION_GROUP);
		do_settings_sections($this->getSlug());
		require_once(SWS_ABSPATH ."/admin/list.php");
		require_once(SWS_ABSPATH ."/admin/admin_footer.php");

	}

	/**
	 * Show a generic edit form.
	 */
	public function editPage() {
		require_once(SWS_ABSPATH ."/admin/admin_header.php");
		settings_fields(ShoppWholesale::OPTION_GROUP);
		do_settings_sections($this->getSlug());
		require_once(SWS_ABSPATH ."/admin/edit.php");
		require_once(SWS_ABSPATH ."/admin/admin_footer.php");
	}

	/**
	 * The name of the record id.
	 *
	 * Presence of this key in the request will
	 * trigger a redirect to the edit page.
	 */
	public function getIdName() {
		return "id";
	}

	/**
	 * A field name indicates a form was submitted.
	 */
	public function getSubmitKey() {
		return ShoppWholesale::SUBMIT_KEY;
	}

	/**
	 * A field name indicates submit occurred on the list page.
	 */
	public function getBulkSubmitKey() {
		return ShoppWholesale::BULK_SUBMIT_KEY;
	}

	/**
	 * Do not print standard submit button on a list page.
	 *
	 * @see AbstractAdminController::printFormButtons()
	 */
	public function printFormButtons() {}

	/**
	 * Put a submit key on the list page to trigger handleSubmit().
	 *
	 * @see AbstractAdminController::printFormTag()
	 */
	public function printFormTag() {
		echo '<form method="post">';
		if (!$this->isEditRequest()) {
			echo "<input type='hidden' name='{$this->getBulkSubmitKey()}' value='1'/>";
		}
	}

	/**
	 * Inspect request and perform routing.
	 *
	 * @see classes/admin/controller/AbstractAdminController::handle()
	 */
	public function handle() {

		try {

			//inspect request and perform appropriate action
			if ($this->isBulkSubmit()) {
				$this->handleBulkSubmit($_REQUEST);
			} else if ($this->isFormSubmit()) {
				$this->handleSubmit($_REQUEST);
			} else {
				if ($this->isEditRequest()) {
					$this->editPage();
				} else {
					$this->listPage();
				}
			}

		} catch (Exception $e) {

			//show error and return to appropriate page
			$this->addError($e->getMessage());
			if ($this->isEditRequest()) {
				$this->editPage();
			} else {
				$this->listPage();
			}

		}

	}

	/**
	 * Whether the current request represents an edit request.
	 *
	 * Defaults to checking for the presence of $this->getIdName().
	 */
	protected function isEditRequest() {
		return isset($_REQUEST[$this->getIdName()]);
	}

	/**
	 * Whether the current request represents a form submission.
	 *
	 * Defaults to checking for the presence of $this->getSubmitKey().
	 */
	protected function isFormSubmit() {
		return isset($_REQUEST[$this->getSubmitKey()]);
	}

	/**
	 * Checks for submits from the list page.
	 */
	protected function isBulkSubmit() {
		return isset($_REQUEST[$this->getBulkSubmitKey()]);
	}

}

?>