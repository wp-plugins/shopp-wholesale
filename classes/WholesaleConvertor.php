<?php

/**
 * Utility class for converting products to wholesale mode.
 *
 * @author Tyson
 */
class WholesaleConvertor {

	/**
	 * Convert the specified price or prices to wholesale values.
	 *
	 * @param $Product
	 * @param $Price (Optional) A specific price object to convert. If null (default), converts all prices.
	 */
	public function convert($Product, $Price = null) {

		$changed = false;

		//single price or all in product
		if (null != $Price) {
			//use supplied price
			$prices = array(&$Price);
		} else {
			//load data
			if (empty($Product->prices) && method_exixts($Product, 'load_data')) {
				$Product->load_data(array('prices'));
			}
			//use all prices
			$prices = &$Product->prices;
		}

		//convert each specified price, using for-loop to preserve internal array pointer
		for ($i=0; $i<count($prices); $i++) {
			$Price = &$prices[$i];

			//should this price be updated to wholesale?
			if (Util::isTrue($Price->wholesale)) {

				//replace price/tax fields
				$Price->price = $this->wholesalePrice($Product, $Price);
				$Price->tax = $Price->wholesaletax;

				//turn sale off
				$this->removeSale($Price);
				$changed = true;

			}

		}

		//update min/max using price field as it has already been updated
		if ($changed) {
			$this->updateMinMax($Product);
		}

	}

	/**
	 * Whether tax is applied to the current wholesale price.
	 *
	 * @param $Price
	 */
	public function isTaxable($Price) {
		global $Shopp;
		return (Util::isTrue($Price->wholesaletax) && Util::isTrue($Shopp->Settings->get('taxes')));
	}

	/**
	 * Returns the amount of tax that will be applied to this price.
	 *
	 * This function is not wholesale-aware. Only use this after updating
	 * the Price object to wholesale prices if you want wholesale tax amount.
	 *
	 * This is done because we may want to get the tax of a product that is
	 * on sale in retail mode, which currently Shopp does not calculate correctly.
	 *
	 * @param $Product
	 * @param $Price
	 */
	public function getTax($Product, $Price) {

		//get price
		if ($Price->onsale) {
			$pricetag = $Price->promoprice;
		} else {
			$pricetag = $Price->price;
		}

		//get taxrate
		$taxrate = shopp_taxrate(null, $Price->tax, $Product);
		return money($taxrate * $pricetag);

	}

	/**
	 * Return the wholesale price for this product and variation.
	 *
	 * If the product does not have a wholesale price, this function
	 * will return FALSE.
	 *
	 * The 'taxed' parameter controls whether tax will be
	 * included in the price or not. Pass false to get the
	 * raw price without tax.
	 *
	 * @param $taxed (Optional) (Default: false) Whether to include tax in the price.
	 */
	public function wholesalePrice($Product, $Price, $taxed = false) {

		$result = false;

		//skip non-wholesale prices (shouldn't be called with non-wholesale objects)
		if (Util::isTrue($Price->wholesale)) {

			//did caller ask for inc tax?
			if ($taxed) {

				//return raw price if tax requested
				$result = $Price->wholesaleprice;

			} else {

				//will return 0 if tax not enabled or product not taxed
				$taxrate = shopp_taxrate(null, $Price->wholesaletax, $Product);

				//remove tax
				$result = floatvalue($Price->wholesaleprice) / (1+$taxrate);

			}

		}

		return $result;

	}

	/**
	 * Recalculate min/max prices.
	 *
	 * This function assumes that wholesale prices have already been applied to
	 * the Price array. This function is NOT aware of the wholesale fields, and
	 * will check sale price in case this is ever called in a retail context.
	 *
	 * If this product has some variations with wholesale prices and some without, the
	 * non-wholesale prices will naturally become the max. If this is confusing, it might
	 * be best to make sure that all variations in a product have a wholesale price.
	 *
	 * @param $Product
	 */
	function updateMinMax($Product) {

		$range = array('min'=>null, 'max'=>null);
		$varranges = array('price'=>'price', 'saleprice'=>'promoprice');

		//find min and max, using for-loop to preserve internal pointer
		for($i=0; $i<count($Product->prices); $i++) {

			//get next active price
			$price = &$Product->prices[$i];
			if ("N/A" != $price->type) {

				//for each type of price (wholesale, sale, retail), find the lowest
				foreach ($varranges as $name => $property) {

					if (null == $range['min'] || floatvalue($price->$property) < floatvalue($range['min']->$name)) {
						$range['min'] = $price;
					}
					if (null == $range['max'] || floatvalue($price->$property) > floatvalue($range['max']->$name)) {
						$range['max'] = $price;
					}
				}

			}

		}

		//change settings
		foreach($range as $field=>$Price) {
			if (null != $Price) {
				//update fields
				$Product->{$field}['price'] = $Price->price;
				$Product->{$field}['price_tax'] = $Price->tax;
				$Product->{$field}['saleprice'] = $Price->saleprice;
				$Product->{$field}['saleprice_tax'] = $Price->saleprice_tax;
			}
		}

	}

	/**
	 * Set the saleprice field to 0 and unset all sale-related fields.
	 *
	 * @param $Price
	 */
	public function removeSale($Price) {

		$Price->saleprice = 0;
		$Price->percentoff = 0;
		$Price->amountoff = 0;
		$Price->promoprice = 0;
		$Price->sale = 'off';
		$Price->onsale = false;

		$fields = array('min', 'max');
		foreach($fields as $field) {
			$Price->{$field}['saleprice'] = 0;
			$Price->{$field}['saleprice_tax'] = 0;
			$Price->{$field}['saved'] = 0;
			$Price->{$field}['savings'] = 0;
		}

	}

}

?>