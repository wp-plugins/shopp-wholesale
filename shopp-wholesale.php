<?php
/*
Plugin Name: Shopp Wholesale
Description: Comprehensive Wholesale System for Shopp.
Author: Tyson LT
Version: 0.7
*/

define('SWS_DEBUG', true);

/**
 * Full path to plugin file.
 * @var string
 */
define('SWS_PLUGIN_FILE', __FILE__);

/**
 * Full path to plugin directory.
 * @var string
 */
define('SWS_ABSPATH',	dirname(__FILE__).'/');

/**
 * The main plugin object.
 *
 * @var ShoppWholesale
 */
global $ShoppWholesale;

/**
 * Instantiate main class.
 */
require_once("classes/ShoppWholesale.php");
$ShoppWholesale = new ShoppWholesale();
$ShoppWholesale->init();

/*** HIGH:
TODO: may need to include shopp scripts explicitly
TODO: check variation prices in checkout page
TODO: update ws price when global tax setting changed (like for price)
TODO: f/e user reg form creates unverified wp_user with shopp fields as wp_usermeta. Backend verify creates customer/user and sends activation email.
TODO: backorder: option to allow overbuy even if not in stock (make stock check optional, differentiate in/out of stock in checkout) use filter shopp_cartitem_stock and shopp_ordering_items_outofstock
TODO: backorder: option to allow them, whether require payment upfront
TODO: backorder: keep in queue
TODO: check it works with coupons etc
TODO: bulk order form skips products with no category
TODO: support product add-ons in bulk order form
TODO: price range on product page has lowest addon price as low value (can't just buy the addon)
TODO: option to hide non-wholesale products in wholesale mode
TODO: filter receipts etc to show wholesale order
*/

/*** MEDIUM:
TODO: deactivate when Shopp deactivated (action shopp_deactivate)
TODO: option to reactivate if Shopp activated (action shopp_activate)
TODO: fix taborder on product priceline
TODO: link product variations in product editor
TODO: fix taborder on product priceline
TODO: validate ws !> rrp
TODO: optional min order qty/sum for wholesale order
TODO: attribute_escape() all echoed request data
TODO: nonces
TODO: option to intercept all overbuy requests and show a nice y/n page
*/

/*** LOW:
LATER: may need to clear session on log in/out of ws account
LATER: maybe require ALL variations to have ws price if any do (all or none)
LATER: store favourite order
LATER: ws price formula?
LATER: collect abn
LATER: add actions and filters
LATER: options backup
LATER: make sure Shopp scripts are actually available, may need explicit include
LATER: backorder priority to ship in lots
*/

/***
LATER: add instructions for adding tax column to cart
LATER: recommend User Role Editor plugin
*/

?>