<?php

/**
 * Handles action and filter callbacks to extend Shopp.
 *
 * @author Tyson
 */
class ShoppHooks implements IInitializable {

	private $Convertor;
	private $Settings;

	/**
	 * Plugin action hooks.
	 */
	public function init() {

		global $ShoppWholesale;
		$this->Settings = $ShoppWholesale->Settings;
		$this->Convertor = new WholesaleConvertor();

		//register action hooks
		add_action('shopp_cart_add_item',   					array($this, 'cartAddItem'));
		add_action('admin_enqueue_scripts', 					array($this, 'replacePricelineMetabox'));
		//add_action('shopp_cart_updated', 							array($this, 'cartRequest'));
		//add_action('shopp_cart_request', 							array($this, 'cartRequest'));

		//register filters
		add_filter('shopp_tag_product_price',					array($this, 'filterProductPrice'), 10, 3);
		add_filter('shopp_tag_product_saleprice',			array($this, 'filterProductPrice'), 10, 3);
		add_filter('shopp_tag_product_tax',						array($this, 'filterProductTax'), 10, 3);
		add_filter('shopp_tag_product_onsale',				array($this, 'filterProductOnSale'), 10, 3);
		add_filter('shopp_tag_product_has-savings',		array($this, 'filterProductOnSale'), 10, 3);
		add_filter('shopp_tag_product_wholesale',			array($this, 'filterProductWholesale'), 10, 3);
		add_filter('shopp_tag_product_variation',			array($this, 'filterProductVariation'), 10, 3);

	}


	/**
	 * Checks whether current user needs wholesale prices and updates item cost accordingly.
	 *
	 * @param $item
	 */
	function cartAddItem($Item) {

		global $Shopp;

	  //if logged in user has wholesale role, update cart prices
	  if (Util::isUserWholesale()) {

	    //get price data for this item
	    $Product = new Product($Item->product);
	    if (empty($Product->prices)) {
	      $Product->load_data(array("prices"));
	    }
	   	$Price = $Product->priceid[$Item->priceline];

	    //update unit price to wholesale price
	    if (Util::isTrue($Price->wholesale)) {

	    	//convert product and all prices
	    	$this->Convertor->convert($Product);

				if (isset($Price->id)) {
					$Item->option = $Item->mapprice($Price);
				}

	    	//set base tax-free rate
	    	$Item->unitprice = $this->Convertor->wholesalePrice($Product, $Price);

	    	//check whether to apply tax
	    	$Item->taxable = $this->Convertor->isTaxable($Price);
	    	
	    	//refresh options
	    	if ($Product->variations == "on") {
	    		$Item->variations = array();
					$Item->variations($Product->prices);
	    	}

    		//refresh addons
    		//TODO: addon price not reflected in cart
				if (isset($Product->addons) && $Product->addons == "on") {

					//get addons before we clear them
 					$addons = $this->extractAddons($Item);

 					//update price
 					$Item->addonsum = 0;
 					$Item->addons = array();
					$Item->addons($Item->addonsum, $addons, $Product->prices);

					//update shipping fee
					if ($Price->type == "Shipped" && $Price->shipping == "on") {
						$Item->shipfee = $Price->shipfee;
						$Item->addons($Item->shipfee, $addons, $Product->prices, 'shipfee');
					}

				}

				//recalulate item totals
				$Item->retotal();

	    }

	    //run action for cart update
	    //NOTE: this is not ideal; these actions are run during the normal Shopp
	    //add-to-cart workflow so they are replicated here. However, if any
	    //changes are made to the Shopp workflow, they will not be reflected here.
	    do_action('shopp_cart_updated', $Shopp->Order->Cart);

	    //tell cart to recalculate total
	    $Shopp->Order->Cart->changed(true);
	    $Shopp->Order->Cart->retotal = true;
	    $Shopp->Order->Cart->totals();

	  }

	  return $Item;

	}

	/**
	 * Builds a simple addon id array that Item::__c() expects.
	 * @param unknown_type $Item
	 */
	private function extractAddons($Item) {
		$addons = array();
		foreach ($Item->addons as $price) {
		  $addons[] = $price->options;
		}
		return $addons;
	}

	/**
	 * Override price to show wholesale if applicable.
	 *
	 * @param $result
	 * @param $options
	 * @param $Product
	 */
	function filterProductPrice($result, $options, $Product) {

		$retail_price = $result;

		//is user wholesale?
		if (false !== $Product && Util::isUserWholesale()) {

			//set all prices to wholesale version
			$this->Convertor->convert($Product);

			//call tag again
			$result = $Product->tag("price", $options);

			//filter result
			if ($result != $retail_price) {
				$do_filter = true;
				if (isset($options['wholesale-print-filter']) && !Util::isTrue($options['wholesale-print-filter'])) {
					$do_filter = false;
				}
				if ($do_filter) {
					$result = apply_filters('sws-print-wholesale-price', $result);
				}
			}

		}

		//return result
		return $result;

	}

	/**
	 * Returns the tax amount on a product price.
	 *
	 * @param $result
	 * @param $options
	 * @param $Product
	 */
	function filterProductTax($result, $options, $Product) {

		if (false !== $Product) {

			//get options
			$defaults = array(
				'taxes' => null,
				'starting' => ''
			);
			$options = array_merge($defaults, $options);
			extract($options);

			//wholesale or retail mode?
			$field = 'price';
			if (Util::isUserWholesale()) {
				$this->Convertor->convert($Product);
			} else if ($Product->onsale) {
					$field = 'promoprice';
			}

			//get prices
			$min_price = $Product->min[$field];
			$min_taxon = $Product->min[$field.'_tax'];
			$max_price = $Product->max[$field];
			$max_taxon = $Product->max[$field.'_tax'];

			//calculate tax
			$taxrate = shopp_taxrate(null, true, $Product);
			$min_tax = $min_taxon ? $min_price * $taxrate : 0;
			$max_tax = $max_taxon ? $max_price * $taxrate : 0;

			//return tax or tax range
			if ($min_tax == $max_tax) {
				$result = money($min_tax);
			} else {
				if (!empty($starting)) {
					$result = "$starting ".money($min_tax);
				} else {
					$result = money($min_tax) ." &mdash; ". money($max_tax);
				}
			}

		}

		return $result;

	}

	/**
	 * Provide extra options for wholesale fields.
	 *
	 * @param $result
	 * @param $options
	 * @param $Product
	 */
	function filterProductVariation($result, $options, $Product) {

		//get selected variation
		$Price = current($Product->prices);

		//should we convert to wholesale?
		$wholesale = false;
		if (Util::isUserWholesale() && Util::isTrue($Price->wholesale)) {
			$this->Convertor->convert($Product, $Price);
			$wholesale = true;
		}

		//our custom wholesale tag
		if (array_key_exists('wholesale', $options)) {

			//does this variation have a wholesale price?
			$result = $wholesale;

		//our custom tax tag
		} else if (array_key_exists('tax', $options)) {

			//get tax (will return wholesale tax if Price object converted above)
			return $this->Convertor->getTax($Product, $Price);

		//variation wholesale pricing
		} else if (array_key_exists('price', $options)) {

			//if wholesale was applied, need to re-run the tag with new prices
			if ($wholesale) {
				$result = $Product->tag("variation", $options);
			}

		}

		return $result;

	}

	/**
	 * Cancel sales if user is in wholesale mode.
	 *
	 * @param $result
	 * @param $options
	 * @param $Product
	 */
	function filterProductOnSale($result, $options, $Product) {
		return Util::isUserWholesale() ? false : $result;
	}

	/**
	 * Tests if a single product (ie with no variations) has a wholesale price.
	 *
	 * TODO: test
	 *
	 * @param $result
	 * @param $options
	 * @param $Product
	 */
	function filterProductWholesale($result, $options, $Product) {
		return Util::isTrue($Product->prices[0]->wholesale);
	}

	/**
	 * Replace standard price entry with one that allows wholesale price entry.
	 *
	 * Use of relative path is slightly hacky, but it was expedient due
	 * to difficulties in replacing the priceline script in the queue.
	 */
	function replacePricelineMetabox() {

		//this might be run during plugin init
		if (!function_exists('shopp_deregister_script')) {
			return;
		}

		shopp_deregister_script('priceline');
		shopp_enqueue_script(
		  'priceline',
		  '../../shopp-wholesale/js/sws-priceline.js',
		  array('jquery','shopp'),
		  '1.0',
		  false
		);

	}

}

?>