<?php

require_once("AbstractAdminListController.php");
require_once(ABSPATH."/wp-includes/capabilities.php");

/**
 * Accounts page.
 *
 * @author Tyson
 */
class Controller_accounts extends AbstractAdminListController {

	const APPROVED 	= 'Approved';
	const REJECTED 	= 'Rejected';

	/**
	 * Our database table.
	 * @var string
	 */
	private $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $ShoppWholesale;
		$this->table = $ShoppWholesale->Database->tablename(ShoppWholesale::CUSTOMER_QUEUE_TABLE);
	}

	/**
	 * Process submitted data.
	 *
	 * Returns to list page by default.
	 */
	public function handleSubmit($data) {

		$verb = $data['account-review-action'];

		//check form bulk submit
		if ($this->isBulkSubmit()) {

			//get action
			$id_list = $data['selected'];

			//message
			$message = "The specified account requests have been <b>$verb</b>. The applicants have been notified.";

		} else {

			//get action
			$id_list = array($data['id']);

			//message
			$not = value_is_true($data['notify-applicant']) ? '' : ' NOT';
			$message = "Account request has been <b>$verb</b>. The applicant has{$not} been notified.";

		}

		//check data
		if (empty($verb)) {
			throw new ShoppWholesaleException('Please select an action to perform.');
		} else if (empty($id_list)) {
			throw new ShoppWholesaleException('Please select one or more account requests to act upon.');
		}

		//process all accounts
		foreach($id_list as $id) {
			switch ($verb) {
				case self::APPROVED: $this->approve($id); break;
				case self::REJECTED: $this->reject($id); break;
			}
		}

		//user message
		//TODO: email notifications not done yet
		$this->addMessage($message .' (TODO: email notifications not done yet)');

		//go back to list view
		$this->listPage();

	}

	/**
	 * Store an account request in the queue.
	 *
	 * @param array $data
	 */
	public function createAccountRequest($data) {

		global $wpdb;
		global $ShoppWholesale;

		//custom validation filter
		$message = apply_filters('sws-account-request-validate', null, $data);
		if (null != $message) {
			return $message;
		}

		//first, encrypt the password with a filter
		$data['password'] = apply_filters('sws-encrypt-account-request-password', $data['password']);
		unset($data['confirm-password']);

		//grab out addresses
		$billing_info = $data['billing'];
		if (value_is_true($data['sameshipaddress'])) {
			$shipping_info = $billing_info;
		} else {
			$shipping_info = $data['shipping'];
		}

		unset($data[ShoppWholesale::SUBMIT_KEY]);
		unset($data['shipping']);
		unset($data['billing']);

		//some normalisation
		$data['sameshipaddress'] = value_is_true($data['sameshipaddress']) ? 'yes' : 'no';
		$data['marketing'] = value_is_true($data['marketing']) ? 'yes' : 'no';

		//check for given email queue, wpusers (if applicable) and customers (if optioned)
		$Controller = $ShoppWholesale->Admin->getController('accounts');
		$message = $Controller->checkExistingUsers($data['email']);
		if (false !== $message) {
			//already filtered in above method
			echo $message;
			return;
		}

		//insert into queue
		if (false === $wpdb->insert($this->table, $data)) {
			echo apply_filters('sws-customer-queue-insert-error', 'Sorry, but there was an error creating your account. Please contact us directly.');
			return;
		}
		$queue_id = $wpdb->insert_id;

		//create billing and shipping addresses
		$shipping = new MetaObject();
		$shipping->parent = $queue_id;
		$shipping->context = ShoppWholesale::CUSTOMER_QUEUE_META_CONTEXT;
		$shipping->type = 'address';
		$shipping->name = 'shipping';
		$shipping->value = $shipping_info;
		$shipping->save();

		$billing = new MetaObject();
		$billing->parent = $queue_id;
		$billing->context = ShoppWholesale::CUSTOMER_QUEUE_META_CONTEXT;
		$billing->type = 'address';
		$billing->name = 'billing';
		$billing->value = $billing_info;
		$billing->save();

		//show confirmation text
		echo apply_filters('sws-customer-application-success', '<h3>Thank you</h3><p>Your application has been received and is currently being processed.</p>');


	}

	/**
	 * Approve the account request.
	 *
	 * @param $id
	 * @throws Exception
	 */
	private function approve($id) {

		global $Shopp;

		try {

			//load request details
			$data = $this->loadObject($id);

			//pre-check for existing users
			$this->checkExistingUsers($data->email);

			//create a customer
			$this->createCustomer($data);

			//delete queue record
			$this->delete($id);

		} catch (ShoppWholesaleException $e) {

			//safe to rethrow these
			throw $e;

		} catch (Exception $e) {

			//look for specific shopp error
			foreach($Shopp->Errors->get() as $name => $error) {
				if ('Customer' == $error->source) {
					throw new ShoppWholesaleException($error->messages[0]);
				}
			}

			//default generic message
			throw new ShoppWholesaleException("Account Request for '{$data->email}' could not be approved due to an internal error.");
		}

	}

	/**
	 * Make sure there are no existing users like this.
	 *
	 * Throws an exception if an account already exists.
	 *
	 * @param $email
	 * @param $skip_queue_check (Optional, default: false) Don't check queue for duplicates.
	 * @throws Exception
	 */
	public function checkExistingUsers($email, $skip_queue_check = false) {

		require_once(ABSPATH."/wp-includes/registration.php");

		global $wpdb;
		global $Shopp;
		global $ShoppWholesale;

		$filter_name = 'sws-email-already-registered';
		$message_email = '<p>That email address is already registered. Please try again using a different email address.</p>';

		//look for already in queue
		if (!$skip_queue_check) {
			$result = $wpdb->get_row("select email from {$this->table} where email = '$email'");
			if (null !== $result) {
				return apply_filters($filter_name, $message_email);
			}
		}

		//already a wp user, if applicable
		//TODO: this is pretty strict, maybe review
		if ('wordpress' == $Shopp->Settings->get('account_system')) {
			if (username_exists($email)) {
				return apply_filters($filter_name, $message_email);
			}
			if (email_exists($email)) {
				return apply_filters($filter_name, $message_email);
			}
		}

		//already a customer
		if (Util::isTrue($ShoppWholesale->Settings->get('prevent-duplicate-customers'))) {
			$Customer = new Customer($email, 'email');
			if ($Customer->exists()) {
				return apply_filters($filter_name, $message_email);
			}
		}

		//all good
		return false;

	}

	/**
	 * Reject the account request.
	 *
	 * @param $id
	 */
	private function reject($id) {
		return $this->delete($id);
	}

	/**
	 * Delete the account request.
	 *
	 * @param $id
	 * @throws Exception
	 */
	private function delete($id) {
		global $wpdb;

		if (false === $wpdb->query("delete from $this->table where id = $id")) {
			throw new ShoppWholesaleException("Account request id:$id could not be deleted due to an internal error.");
		}

		//delete stored addresses (don't stress about errors)
		$wpdb->query($this->metaSql($id, 'delete'));

	}

	/**
	 * Create a customer.
	 *
	 * @param $data
	 */
	private function createCustomer(stdClass $accountRequest) {

		global $Shopp;
		$requested_role = $accountRequest->role;

		unset($accountRequest->sameshipaddress);
		unset($accountRequest->id);
		unset($accountRequest->role);

		//create customer record
		$Customer = new Customer();
		foreach ($accountRequest as $key => $value) {
			$Customer->{$key} = $value;
		}

		//apply password filter
		$Customer->password = apply_filters('sws-decrypt-account-request-password', $accountRequest->password);
		$Customer->loginname = $Customer->email;

		//create user, if required
		if ("wordpress" == $Customer->accounts) {
			$this->createCustomerWpUser($Customer);
		}

		//save customer
		$Customer->save();
		if (!isset($Customer->id)) {
			throw new ShoppWholesaleException("A new Customer account for '{$Customer->email}' could not be created due to an internal error.");
		}

		//create address objects
		$Billing = new Billing();
		$Billing->customer = $Customer->id;
		foreach($accountRequest->Billing as $key => $value) {
			$Billing->{$key} = $value;
		}
		$Billing->save();

		//error check
		if (!isset($Billing->id)) {
			throw new ShoppWholesaleException("The Customer billing address for '{$Customer->email}' could not be created due to an internal error.");
		}

		$Shipping = new Shipping();
		$Shipping->customer = $Customer->id;
		foreach($accountRequest->Shipping as $key => $value) {
			$Shipping->{$key} = $value;
		}
		$Shipping->save();

		if (!isset($Shipping->id)) {
			throw new ShoppWholesaleException("The Customer shipping address for '{$Customer->email}' could not be created due to an internal error.");
		}

	}

	/**
	 * Create the wpuser for this customer.
	 *
	 * @param $Customer
	 */
	private function createCustomerWpUser(Customer $Customer) {

		if ($Customer->create_wpuser()) {

			//set role for new user
			$wpuser = new WP_User($Customer->wpuser);
			$role = $this->lookupRole($requested_role);
			$wpuser->set_role($role);

		} else {

			//dang!
			$errors = $Shopp->Errors->get(SHOPP_ERR);
			if (!empty($errors)) {
				throw new ShoppWholesaleException($errors[0]->messages[0]);
			} else {
				throw new ShoppWholesaleException("A new Wordpress login for '{$Customer->email}' could not be created due to an internal error.");
			}

		}

	}

	/**
	 * Load an object from the database with the given id.
	 *
	 * @param $id
	 */
	public function loadObject($id) {

		global $wpdb;

		$Customer = $wpdb->get_row("select * from {$this->table} where {$this->getIdName()} = $id");
		$Customer->Shipping = (object) unserialize($wpdb->get_var($this->metaSql($id, 'select', 'shipping')));
		$Customer->Billing = (object) unserialize($wpdb->get_var($this->metaSql($id, 'select', 'billing')));

		return $Customer;

	}

	/**
	 * Build sql for interacting with the shopp meta table.
	 *
	 * @param $parent_id
	 * @param $action
	 * @param $address_type
	 */
	private function metaSql($parent_id, $action = 'select', $address_type = 'shipping') {

		$meta_table = DatabaseObject::tablename(MetaObject::$table);

		$pre = 'select value from ';
		$post = "and name='$address_type'";
		if ('delete' == $action) {
			$pre = 'delete from ';
			$post = '';
		}

		return "$pre $meta_table " .
					 "where context='".ShoppWholesale::CUSTOMER_QUEUE_META_CONTEXT."' ".
					 "and type='address' and parent=$parent_id $post";

	}

	/**
	 * Lookup the role.
	 *
	 * @param string $requested_role
	 * @throws ShoppWholesaleException
	 */
	private function lookupRole($requested_role) {

		global $wp_roles;
		global $ShoppWholesale;

		//check for empty role
		if (empty($requested_role)) {
			//get default role from SWS settings
			$requested_role = $ShoppWholesale->Settings->get('default-account-request-role');
		}

		//see if they requested the default wordpress role
		if ('wp_default' == $requested_role) {
			return get_option('default_role');
		}
		
		//check role by id
		if ($wp_roles->is_role($requested_role)) {
			return $requested_role;
		}

		//look for role by name
		foreach($wp_roles->get_names() as $role_id => $name) {
			if ($name == $requested_role) {
				return $role_id;
			}
		}

		//if still null, we have a problem
		if (null == $role) {
			throw new ShoppWholesaleException("Specified role '{$requested_role}' does not exist.");
		}

	}

	/**
	 * Adds meta boxes.
	 */
	public function editPage() {

		$Customer = $this->getObject();

		if (value_is_true($Customer->sameshipaddress)) {
			$billing_address_title = 'Billing/Shipping Address';
		} else {
			$billing_address_title = 'Billing Address';
		}

		add_meta_box(
			'shopp-wholesale-account-review-action',
			'Actions',
			array($this, 'printSubmitBox'),
			$this->getSlug(),
			'side'
		);

		add_meta_box(
			'shopp-wholesale-account-review-details',
			'Account Details',
			array($this, 'printAccountDetailsBox'),
			$this->getSlug(),
			'normal',
			'high'
		);

		add_meta_box(
			'shopp-wholesale-account-review-billing',
			$billing_address_title,
			array($this, 'printAddressBox'),
			$this->getSlug(),
			'normal',
			'default',
			array('address'=>'Billing')
		);

		if (!value_is_true($Customer->sameshipaddress)) {
			add_meta_box(
				'shopp-wholesale-account-review-shipping',
				'Shipping Address',
				array($this, 'printAddressBox'),
				$this->getSlug(),
				'normal',
				'low',
				array('address'=>'Shipping')
			);
		}

		//call real editPage()
		parent::editPage();

	}

	//TODO: blue bit at bottom doesn't fit in round corners
	function printSubmitBox($Customer) {
?>
	<form>

		<input type='hidden' name='page' value='<?php echo $_REQUEST['page']; ?>' />
		<input type='hidden' name='id' value='<?php echo $_REQUEST['id']; ?>' />

		<div id='misc-publishing-actions'>

			<div id='misc-pub-section misc-pub-section-last'>
				<select name='account-review-action'>
					<option value='<?php echo self::APPROVED; ?>'>Approve Request</option>
					<option value='<?php echo self::REJECTED; ?>'>Reject Request</option>
				</select>
			</div>

			<div id='misc-pub-section misc-pub-section-last'>
				<br/>
				<input type='checkbox' checked='checked' id='notify-applicant' name='notify-applicant'/> <label for='notify-applicant'>Notify Applicant</label>
			</div>

		</div>

		<div id="major-publishing-actions">
			<input type="submit" class="button-primary" name="<?php echo ShoppWholesale::SUBMIT_KEY; ?>" value="Apply Action">
		</div>

	</form>
<?php
	}

	function printAccountDetailsBox($Customer) {
?>

<br class='clear' />
<table class='widefat' cellspacing='0'>

	<tr>
		<th width="35%">Application Date</th>
		<td><?php echo date(ShoppWholesale::DATETIME_FORMAT, strtotime($Customer->created)); ?></td>
	</tr>

	<tr>
		<th>Name</th>
		<td><?php echo $Customer->firstname .' '. $Customer->lastname; ?></td>
	</tr>

	<tr>
		<th>Email</th>
		<td><?php echo $Customer->email; ?></td>
	</tr>

	<tr>
		<th>Company</th>
		<td><?php echo $Customer->company; ?></td>
	</tr>

	<tr>
		<th>Marketing</th>
		<td><?php echo $Customer->marketing; ?></td>
	</tr>

	<tr>
		<th>Role</th>
		<td><?php $this->printRoleName($Customer->role); ?></td>
	</tr>

	<tr>
		<th>Account Type</th>
		<td><?php echo $Customer->type; ?></td>
	</tr>

</table>

<?php
	}

	function printAddressBox($Customer, $args) {
		$field = $args['args']['address'];
		$Address = $Customer->{$field};

		//some transforms
		$countries = Lookup::countries();
		$states = Lookup::country_zones();

		$country = $countries[$Address->country]['name'];
		$state = $states[$Address->country][$Address->state];

?>

<br class='clear' />
<table class='widefat' cellspacing='0'>


	<tr>
		<th width="35%">Street Address</th>
		<td><?php
    		echo $Address->address;
    		if (!empty($Address->xaddress)) {
    			echo "<br/>". $Address->xaddress;
    		}
    	?>
    </td>
	</tr>

	<tr>
		<th>City</th>
		<td><?php echo $Address->city; ?></td>
	</tr>

	<tr>
		<th>State</th>
		<td><?php echo $state; ?></td>
	</tr>

	<tr>
		<th>Postcode</th>
		<td><?php echo $Address->postcode; ?></td>
	</tr>

	<tr>
		<th>Country</th>
		<td><?php echo $country; ?></td>
	</tr>

</table>

<?php
	}

	/**
	 * Overridden to do nothing.
	 * @see classes/admin/controller/AbstractAdminController::printNavigationLinks()
	 */
	public function printNavigationLinks() {}

	/**
	 * Overide page title.
	 *
	 * @see classes/admin/controller/AbstractAdminController::getAdminPageTitle()
	 */
	public function getAdminPageTitle() {
		return 'Account Requests';
	}

	/**
	 * Return list SQL.
	 */
	public function getListQuery() {
		return "select * from {$this->table} order by created";
	}

	/**
	 * Print the bulk actions.
	 */
	public function printBulkActions() {
?>

		<div class="tablenav">
			<div class="alignleft actions">
				<select name="account-review-action" id="account-review-action">
					<option value="" selected="selected">Bulk Actions</option>
					<option value='<?php echo self::APPROVED; ?>'>Approve Request</option>
					<option value='<?php echo self::REJECTED; ?>'>Reject Request</option>
				</select>
				<input type="submit" value="Apply" name="doaction" id="doaction" class="button-secondary action" onClick="return checkSubmit(this.form);"/>
			</div>
			<div class="clear"></div>
		</div>
		<div class="clear"></div>

		<script type="text/javascript">
			function checkSubmit(frm) {
				var sel = document.getElementById('account-review-action');

				if (0 == sel.selectedIndex) {
					alert('Please select an action to perform.');
					return false;
				} else {

					var noneChecked = true;
					var elems = frm.elements['selected[]'];

					if (elems) {
						if (!elems.length) {
							noneChecked = !elems.checked;
						} else {
							for (i=0; i<elems.length; i++) {
								if (elems[i].checked) {
									noneChecked = false;
									break;
								}
							}
						}
					}

					var text = 'reject';
					if ('Approved' == sel.options[sel.selectedIndex].value) {
						text = 'approve';
					}
					if (noneChecked) {
						alert('Please select at least one item to '+ text +'.');
						return false;
					} else {
						return confirm('Are you sure you want to '+ text +' the selected items?');
					}
				}
			}
		</script>

<?php
	}

	/**
	 * Print nice role name.
	 *
	 * @param $role_id OR $role_name
	 */
	private function printRoleName($role_id) {

		global $wp_roles;
		$names = $wp_roles->get_names();

		//try direct lookup
		if (isset($names[$role_id])) {
			echo $names[$role_id];
		} else {
			//just return the input, it's probably a role name
			echo $role_id;
		}

	}

	/**
	 * Print a table row.
	 *
	 * @param Object $object
	 */
	public function printTableRow(stdClass $Object) {

		static $alternate = true;
		if ($alternate) {
			$row_class = 'class="alternate"';
			$alternate = false;
		} else {
			$row_class = '';
		}

		$name = $Object->firstname .' '. $Object->lastname;
		$view_href = '?page=shopp-wholesale-accounts&id='. $Object->id;
		$approve_href = add_query_arg(array('account-review-action'=>self::APPROVED, ShoppWholesale::SUBMIT_KEY=>'1', 'notify-applicant'=>1), $view_href);
		$reject_href = add_query_arg(array('account-review-action'=>self::REJECTED, ShoppWholesale::SUBMIT_KEY=>'1', 'notify-applicant'=>1), $view_href);
?>

		<tr <?php echo $row_class;?>>
			<th scope="row" class="check-column"><input type="checkbox" name="selected[]" value="<?php echo $Object->id; ?>"></th>
			<td class="name column-name"><a class="row-title" href="<?php echo $view_href; ?>"
				title="Review &quot;<?php echo $name; ?>&quot;">
					<?php echo $name; ?></a>
					<div class="row-actions">
						<span class="edit"><a href="<?php echo $approve_href; ?>" title="Approve &quot;<?php echo $name; ?>&quot;">Approve</a> | </span>
						<span class="delete"><a class="submitdelete" onClick="return confirm('Are you sure you want to reject this account request?');" title="Reject &quot;<?php echo $name; ?>&quot;" href="<?php echo $reject_href; ?>" rel="<?php echo $Object->id; ?>">Reject</a> | </span>
						<span class="view"><a href="<?php echo $view_href; ?>" title="View &quot;<?php echo $name; ?>&quot;">View</a></span>
					</div>
				</td>
			<td class="email column-email"><a href="mailto:<?php echo $Object->email; ?>"><?php echo $Object->email; ?></a></td>
			<td class="company column-company"><?php echo $Object->company; ?></td>
			<td class="requested column-requested"><?php echo date(ShoppWholesale::DATETIME_FORMAT, strtotime($Object->created)); ?></td>
		</tr>
<?php
	}

	/**
	 * The the column headers.
	 *
	 * @param boolean $header (Optional, default: true) Whether this is being used to print header or footer columns.
	 */
	public function printColumns($header = true) {
?>
		<th scope="col" <?php if ($header):?>id="cb"<?php endif;?> class="manage-column column-cb check-column" style=""><input type="checkbox"></th>
		<th scope="col" <?php if ($header):?>id="name"<?php endif;?> class="manage-column column-name" style="">Name</th>
		<th scope="col" <?php if ($header):?>id="email"<?php endif;?> class="manage-column column-email" style="">Email</th>
		<th scope="col" <?php if ($header):?>id="company"<?php endif;?> class="manage-column column-company" style="">Company</th>
		<th scope="col" <?php if ($header):?>id="requested"<?php endif;?> class="manage-column column-requested" style="">Requested</th>
<?php
	}

}