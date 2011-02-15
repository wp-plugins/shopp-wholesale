<?php

require_once("AbstractShortcode.php");

/**
 * Bulk order form.
 *
 * @author Tyson
 */
class BulkOrderFormShortcode extends AbstractShortcode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * The actual shortcode string.
	 *
	 * Can return an array of shortcodes, they will all be mapped.
	 */
	protected function getShortcode() {
		return array('shopp-wholesale-bulk-order-form', 'sws-order-form');
	}

	/**
	 * The default attributes.
	 */
	protected function getDefaultAttributes() {
		return array('blank_variation' => '(N/A)');
	}

	/**
	 * Handle the shortcode call.
	 *
	 * @param array $input The shortcode attributes, with defaults added.
	 */
	protected function handle(array $input) {

		//look for submit
		if (isset($_REQUEST[ShoppWholesale::SUBMIT_KEY])) {

			//process data
			return $this->processSubmit($_REQUEST);

		} else {

			//show form
			return $this->displayForm($input['blank_variation']);

		}

	}

	/**
	 * Process form submit.
	 *
	 * @param array $data
	 */
	protected function processSubmit($data) {

		global $Shopp;
		global $ShoppWholesale;
		
		$order_list = array();
		$backorder_list = array();
		
		//loop over submit vars
		foreach ($data as $key => $quantity) {

			//check for specially named, non-empty value
			if (!empty($quantity) && "qty" == substr($key, 0, 3)) {

				//get ids from key
				$arr = explode("_", $key);
				$pid = $arr[1];
				$vid = $arr[2];

				//load specified product and default to basic product price
				shopp("catalog", "product", "id=$pid&load=1");
				$unit_price = shopp('product', 'price', 'return=1&taxes=0&wholesale-print-filter=false');
				$tax = shopp('product', 'tax', 'return=1');
				if (empty($Shopp->Product->prices)) {
					$Shopp->Product->load_data(array("prices"));
				}
				$Price = $Shopp->Product->prices[0];
				$pricekey = $Price->id;
				$variation_name = ''; 
				
				//look for variation
				if (ShoppWholesale::EMPTY_VID != $vid) {

					//find variation
					while (shopp("product", "variations")) {
						
						if ($vid == $price = shopp('product','variation','return=1&id')) { 
						
							//load price data
							$unit_price = shopp('product','variation','return=1&price=1&taxes=0&wholesale-print-filter=false');
							$tax = shopp('product','variation','return=1&tax=1');
						
							$Price = $Shopp->Product->priceid[$vid];
							$variation_name = $Price->label; 
							$pricekey = $vid;
							
							break;
							
						}
						
					}

				}

				//build product line for display
				$item = new stdClass();
				$item->product_id = $pid;
				$item->price_id = $pricekey;
				$item->product_name = $Shopp->Product->name;
				$item->variation_name = $variation_name;
				$item->quantity = $quantity;
				$item->unit_price = $unit_price;
				$item->tax = $tax;
				
				//check for inventory items
				if (Util::isTrue($Price->inventory)) {
					
					//check quantity
					$available = $Price->stock;
					if ($available < $quantity) {

						//add unavailable items to backorder list
						$backorder_item = clone $item;
						$backorder_item->quantity = $quantity - $available;
						$this->calculateTotal($backorder_item);
						$backorder_list[] = $backorder_item;
						
						//update buy qty for normal item to prevent shopp errors
						$item->quantity = $available;
						
					}
					
				}
				
				//add item (possibly with updated qty) to the order list
				if ($item->quantity > 0) {
					$this->calculateTotal($item);
					$order_list[] = $item;
				}
				
				//add to cart
				$Shopp->Order->Cart->add($item->quantity, $Shopp->Product, $pricekey);
				
			}

		}

		//fire cart listener
		do_action('shopp_cart_updated', $Shopp->Order->Cart);
		
    //tell cart to recalculate total
    $Shopp->Order->Cart->changed(true);
    $Shopp->Order->Cart->retotal = true;
    $Shopp->Order->Cart->totals();
	    
    //TODO: include orders with calculated totals in a model rather than letting template calculate
    //TODO: Priceline should include option of whether this item can be backordered, and how many
    
		//include template
		require_once($ShoppWholesale->getTemplatePath("bulk-order-confirm.php"));

	}

	/**
	 * Calculate item total.
	 * 
	 * @param $Item
	 */
	protected function calculateTotal($Item) {
		$Item->total = money( ( floatvalue($Item->unit_price) + floatvalue($Item->tax) ) * $Item->quantity );
	}
	
	/**
	 * Display the order form.
	 *
	 * @param $blank_variation
	 */
	protected function displayForm($blank_variation) {

		global $Shopp;
		global $ShoppWholesale;
		
		//TODO: put in proper global data structure
		$bulk_order_data = array();

		//TODO: check if Shopp user is logged in and has Wholesale customer type (option-driven)
		//TODO: check whether to allow retail usage as well (but not mixed)
		$allow_retail = apply_filters('sws-bulk-order-form-allow-retail', !Util::isUserWholesale()); //TODO: option

		//loop categories
		while(shopp('catalog', 'categories')) {
			
			$category_name = shopp('category', 'name');
			
			if (shopp('category', 'has-products')) {

				//loop products
				while(shopp('category', 'products')) {

					$Product = $Shopp->Product;

					//make sure product is published
					if ("publish" == $Product->status) {

						$Price = $Product->prices[0];

						//check for no variations
						//TODO: check whether to show wholesale or retail price (option-driven)
						//TODO: check whether to only allow items with wholesale prices (option-driven)
						if (false == shopp("product", "has-variations") &&
							 ($allow_retail || Util::isTrue($Price->wholesale))) {

							$bulk_order_data[$category_name][] = $this->row($Product, $Price, $blank_variation);

						} else {

							//now print a row for each variation
							while (shopp("product", "variations")) {

								//TODO: check whether to show wholesale or retail price (option-driven)
								//TODO: check whether to only allow items with wholesale prices (option-driven)
								$variation = current($Product->prices);
								if (Util::isTrue($variation->wholesale)) {

									$bulk_order_data[$category_name][] = $this->row($Product, $variation, $blank_variation);

								}

							}

						} //end if: has variations

					} //end if: product published

				} //end foreach: products

			} //end if: cat has products

		} //end foreach: categories

		require_once($ShoppWholesale->getTemplatePath('bulk-order-form.php'));

	}

	/**
	 * Build a row for the bulk order form.
	 *
	 * NOTE: must be called inside a Shopp product loop.
	 *
	 * @param $Product
	 * @param $Price
	 * @param $blank_variation
	 */
	protected function row($Product, $Price, $blank_variation) {

		$row = new stdClass();
		
		if ('variation' == $Price->context) {
			
			$row->sku = $Price->sku;
			$row->name = $Product->name;
			$row->label = $Price->label;
			$row->stock = $Price->stock;
			$row->unit_price = shopp('product','variation','return=1&price=1&taxes=0&wholesale-print-filter=false');
			$row->tax = shopp('product','variation','return=1&tax=1');
			$row->price = shopp('product','variation','return=1&price=1&taxes=1&wholesale-print-filter=false');
			$row->field_name = "qty_{$Product->id}_{$Price->id}";
			$row->field_value = $_REQUEST[$field_name];

		} else if ('addon' == $Price->context) {

			//FIXME: support addons
			_de($Price);

		} else {

			$row->sku = $Product->price[0]->sku;
			$row->name = $Product->name;
			$row->label = $blank_variation;
			$row->stock = $Product->stock;
			$row->unit_price = shopp('product', 'price', 'return=1&taxes=0&wholesale-print-filter=false');
			$row->tax = shopp('product', 'tax', 'return=1');
			$row->price = shopp('product', 'price', 'return=1&taxes=1&wholesale-print-filter=false');
			$row->field_name = "qty_{$Product->id}_". ShoppWholesale::EMPTY_VID;
			$row->field_value = $_REQUEST[$field_name];
			
		}

		return $row;

	}


}

?>