<?php


//exit if accessed directly
if(!defined('ABSPATH')) exit;


/* WooCommerce Enhanced Ecommerce (WEE) DataLayer
 *
 * This function pushes WooCommerce data into the dataLayer object.
 *
 * https://developers.google.com/tag-manager/enhanced-ecommerce
 */
function gtm4wp_woo_datalayer() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		// Globals and default vars
		global $woocommerce;
		//echo '<pre>'; print_r($woocommerce); echo '</pre>';

		$str = '';
		$strArr = array();

		// PRODUCT CATEGORY (Product Impressions)
		if ( is_product_category() ):
			$_category_slug = get_query_var( 'product_cat' );
			$_products_str = gtm4wp_wee_category_items_part( $_category_slug );
			$_event = 'enhanceEcom Product Impression';
			$_ecommerce = 'impressions';
			$str = sprintf( '{ \'event\': \'%1$s\', \'ecommerce\': { \'%2$s\': [%3$s] } }', $_event, $_ecommerce, $_products_str );
		endif;

		// PRODUCT (Product Details)
		if ( is_product() ):
			$_product_id = get_the_ID();
			$_product_js = gtm4wp_wee_product_detail_part( $_product_id );
			$_action_field = gtm4wp_wee_action_field_part( $_product_id );
			$_event = 'enhanceEcom Product Detail View';
			$_ecommerce  = 'detail';
			$str = sprintf( '{\'event\': \'%1$s\', \'ecommerce\': { \'%2$s\': { \'actionField\': { %3$s }, \'products\': [ %4$s ] } } }', $_event, $_ecommerce, $_action_field, $_product_js );
		endif;

		// CART ()
		if ( is_cart() ):
			$_items = $woocommerce->cart->get_cart();
			$_products_str = gtm4wp_wee_cart_items_part( $_items );
			$_event      = 'View Cart';
			$_ecommerce  = 'cart';
			$str = sprintf( '{ \'event\': \'%1$s\', \'ecommerce\': { \'%2$s\': { \'products\': [ %3$s ] } } }', $_event, $_ecommerce, $_products_str );
		endif;

		// CHECKOUT ()
		if ( is_checkout() ):
			$_items = $woocommerce->cart->get_cart();
			$_products_str = gtm4wp_wee_cart_items_part( $_items );
			$_event      = 'Product Checkout';
			$_ecommerce  = 'checkout';
			$str = sprintf( '{ \'event\': \'%1$s\', \'ecommerce\': { \'%2$s\': { \'products\': [ %3$s ] } } }', $_event, $_ecommerce, $_products_str );
		endif;

		// ORDER RECEIVED page
		if( is_wc_endpoint_url( 'order-received' ) ):
			$order = woo_order_obj();
			$_items = $order->get_items();
			foreach ( $_items as $_item ) {
				$_product_id = $_item['variation_id'];
				if ( $_product_id ) {
					$_product = new WC_Product_Variation( $_item['variation_id'] );
				} else {
					$_product = new WC_Product( $_item['product_id'] );
				}
				$_terms = get_the_terms( $_product->post->ID, 'product_cat' );
				$strArr[] = sprintf('{ \'name\': \'%s\', \'id\': \'%s\', \'price\': %f, \'brand\': \'%s\', \'category\': \'%s\', \'variant\': \'%s\', \'quantity\': %d, \'coupon\': \'%s\' }', $_product->post->post_title, $_product->get_sku(), $_product->get_price(), $_brand, $_terms[0]->name, $_product->get_sku(), $_item['quantity'], 'CouponCode' );
			}
			$_event = 'enhanceEcom transactionSuccess';
			$str = sprintf('{ \'event\': \'%1$s\', \'ecommerce\': { \'purchase\': { \'actionField\': { \'id\': \'%2$s\', \'affiliation\': \'%3$s\', \'revenue\': %4$f, \'tax\': %5$f, \'shipping\': %6$f, \'coupon\': \'%7$s\' }, \'products\': [%8$s] } } }', $_event, $order->get_order_number(), $_brand, $order->order_total, $order->order_tax, $order->order_shipping, 'CouponCode', implode(',', $strArr) );
		endif;

		// print script
		printf('<script>dataLayer.push(%1$s);</script>', $str);
	} else {
		return false;
	}
}


/* Woo Enhanced Ecommerce (WEE) String Functions
 *
 * The following functions get Product details by group and return as JS string
 *
 * https://developers.google.com/tag-manager/enhanced-ecommerce
 */

// Return Products in Category
function gtm4wp_wee_category_items_part( $_category_slug = false ) {
	if ( ! $_category_slug ) { return false; }
	$_category = get_term_by( 'slug', $_category_slug, 'product_cat' );
	if ( ! $_category ) { return false; }

	$options = get_option( 'gtm4wp_settings' );

	// Get all products in category
	$args = array(
		'post_type'			=> 'product',
		'post_status'		=> 'publish',
		'posts_per_page'	=> -1,
		'product_cat'		=> $_category_slug,
		'order_by'          => 'menu_order',
		'order'             => 'asc'
	);
	$_products = new WP_Query($args);
	$strArr = array();
	$i = 1;
	while ( $_products->have_posts() ) : $_products->the_post();
		$_product = get_product( get_the_ID() );
		// the replacement vars
		$__brand = sanitize_text_field( $options['gtm4wp_brand'] );
		$__cat    = $_category->name;
		$__id     = ( $_product->get_sku() ? $_product->get_sku() : $_product->post->ID );
		$__name   = $_product->post->post_title;
		$__pos    = $i;
		$__price  = $_product->get_price();
		// the string
		$strArr[] = sprintf( '{ \'name\': \'%1$s\', \'id\': \'%2$s\', \'price\': %3$f, \'brand\': \'%4$s\', \'category\': \'%5$s\', \'list\': \'%6$s\', \'position\': %7$d }', $__name, $__id, $__price, $__brand, $__cat, $__cat, $__pos );
		$i++;
	endwhile;
	wp_reset_query();
	$str = implode(',', $strArr);
	return $str;
}

// Return Product details
function gtm4wp_wee_product_detail_part( $_product_id = false, $stringify = true ) {
	if ( ! $_product_id ) { return false; }

	$options = get_option( 'gtm4wp_settings' );

	$_product = get_product( $_product_id );
	$_vars = '';
	if ( $_product->product_type == 'variable' ) { // Get variation Skus
		$_variations = $_product->get_available_variations();
		$_variation_attribute = array();
		foreach ($_variations as $_variation) {
			$_variation_attributes = wc_get_product_variation_attributes($_variation['variation_id']);
			$_variation_attribute[] = array_values($_variation_attributes)[0];
		}
		$_vars = implode( ', ', $_variation_attribute );
	}
	$_terms   = get_the_terms( $_product->post->ID, 'product_cat' );
	
	// New Product Object
	$p_obj = new stdClass;
	$p_obj->brand    = sanitize_text_field( $options['gtm4wp_brand'] );
	$p_obj->category = $_terms[0]->name;
	$p_obj->id       = ( $_product->get_sku() ? $_product->get_sku() : $_product->post->ID );
	$p_obj->name     = $_product->post->post_title;
	$p_obj->price    = $_product->get_price();
	$p_obj->variant  = sprintf( '%s', $_vars );
	
	// the string
	$str = sprintf( '{ \'id\': \'%1$s\', \'name\': \'%2$s\', \'price\': %3$f, \'brand\': \'%4$s\', \'category\': \'%5$s\', \'variant\': \'%6$s\' }', $p_obj->id, $p_obj->name, $p_obj->price, $p_obj->brand, $p_obj->category, $p_obj->variant );
	
	// the results
	if ( $stringify ) {
		return $str;
	}
	else {
		return $p_obj;
	}
}

// Return Products in Cart
function gtm4wp_wee_cart_items_part( $_items = false, $singular = false ) {
	if ( ! $_items ) { return false; }

	$options = get_option( 'gtm4wp_settings' );

	$strArr = array();
	foreach ( $_items as $_item ) {
		$_product_id = $_item['variation_id'];
		$_variation_attribute = '';
		if ( $_product_id ) {
			$_product = new WC_Product_Variation( $_item['variation_id'] );
			$_variation_attributes = wc_get_product_variation_attributes($_item['variation_id']);
			$_variation_attribute = array_values($_variation_attributes)[0];
		} else {
			$_product = get_product( $_item['product_id'] );
		}
		$_terms = get_the_terms( $_product->post->ID, 'product_cat' );
		// the replacement vars
		$__brand = sanitize_text_field( $options['gtm4wp_brand'] );
		$__cat    = $_terms[0]->name;
		$__id     = ( $_product->get_sku() ? $_product->get_sku() : $_product->post->ID );
		$__name   = $_product->post->post_title;
		$__price  = $_product->get_price();
		$__qty    = $_item['quantity'];
		$__vars   = $_variation_attribute;
		// the string
		$strArr[] = sprintf('{ \'name\': \'%1$s\', \'id\': \'%2$s\', \'price\': %3$f, \'brand\': \'%4$s\', \'category\': \'%5$s\', \'variant\': \'%6$s\', \'quantity\': %7$d }', $__name, $__id, $__price, $__brand, $__cat, $__vars, $__qty );
	}
	$str = implode(',', $strArr);
	return $str;
}

// Return actionField
function gtm4wp_wee_action_field_part( $_product_id = false ) {
	if ( ! $_product_id ) { return false; }

	$options = get_option( 'gtm4wp_settings' );

	$_terms = get_the_terms( $_product_id, 'product_cat' );
	$__list = $_terms[0]->name;
	$str = sprintf( '\'list\': \'%1$s\'', $__list );
	return $str;
}


/* Return product details to AJAX call
 *
 * This function gets the appropriate product details and returns them as a JSON encoded object.
 *
 */
function gtm4wp_add_to_cart_ajax() {
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		die('WooCommerce not activated');
	};
	if ( isset( $_REQUEST ) && $_REQUEST['product_id'] && $_REQUEST['product_qty'] ) {
		$product = gtm4wp_wee_product_detail_part( $_REQUEST['product_id'], false );
		$product->quantity = $_REQUEST['product_qty'];
		echo wp_json_encode( $product );
	}
	else {
		echo 'product data is empty';
	}
	die(); // stop executing script
}
add_action( 'wp_ajax_gtm4wp_add_to_cart', 'gtm4wp_add_to_cart_ajax' ); // ajax for logged in users
add_action( 'wp_ajax_nopriv_gtm4wp_add_to_cart', 'gtm4wp_add_to_cart_ajax' ); // ajax for not logged in users




$dump = array();
$dump[] = get_option('woocommerce_cart_redirect_after_add');
$dump[] = get_option('woocommerce_enable_ajax_add_to_cart');
// var_dump($dump);
