<?php


//exit if accessed directly
if(!defined('ABSPATH')) exit;

class GTM4WP {
	protected $options;
	protected $products;
	protected $action;
	public $list;

	public $event;
	public $ecommerce;

	public $formattedProducts;

	public function __construct() {
		$this->options = get_option( 'gtm4wp_settings' );
		$this->products = array();
		$this->action = new stdClass();
		$this->formattedProducts = array();
	}

	public function setProduct( $product, $quantity = 1, $variant = false ) {
		$product->quantity = $quantity;
		$product->variant = $variant;
		$this->products[] = $product;
	}

	public function getProduct( $id = false ) {
		if ( ! $id ) {
			$id = get_the_ID();
		}
		$product = get_product( $id );
		return $product;
	}
	public function getProductsByCategory( $category = false ) {
		if ( ! $category ) {
			$category = get_query_var( 'product_cat' );
		}
		// Get all products in category
		$args = array(
			'post_type'			=> 'product',
			'post_status'		=> 'publish',
			'posts_per_page'	=> -1,
			'product_cat'		=> $category,
			'order_by'          => 'menu_order',
			'order'             => 'asc'
		);
		$products = new WP_Query($args);
		while ( $products->have_posts() ) : $products->the_post();
			$this->setProduct( $this->getProduct( get_the_ID() ) );
		endwhile;
	}
	public function getProductsCart( $items = false ) {
		if ( ! $items ) {
			global $woocommerce;
			$items = $woocommerce->cart->get_cart();
		}
		foreach ($items as $item) {
			$this->setProduct( $this->getProduct( $item['product_id'] ), $item['quantity'], array_values($item['variation'])[0] );
		}
	}
	public function getProductsOrder( $order = false ) {
		if ( ! $order ) {
			global $woocommerce;
			$order = woo_order_obj();
		}
		$this->action->affiliation = sanitize_text_field( $this->options['gtm4wp_brand'] );
		$this->action->coupon = '';
		$this->action->id = $order->get_order_number();
		$this->action->revenue = $order->order_total;
		$this->action->shipping = $order->order_shipping;
		$this->action->tax = $order->order_tax;

		$items = $order->get_items();
		foreach ($items as $item) {
			$id = ( $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'] );
			$this->setProduct( $this->getProduct( $id ), $item['quantity'] );
		}
	}

	public function setFormatProducts() {
		foreach ($this->products as $key => $product) {
			if ( $product->product_type == 'variable' ) { // Get variation Skus
				$variations = $product->get_available_variations();
				$variation_attributes = array();
				foreach ($variations as $variation) {
					$attributes = wc_get_product_variation_attributes($variation['variation_id']);
					$variation_attributes[] = array_values($attributes)[0];
				}
			}
			$terms   = get_the_terms( $product->post->ID, 'product_cat' );
			// Update Product Object
			$formattedProduct = new stdClass();
			$formattedProduct->brand    = sanitize_text_field( $this->options['gtm4wp_brand'] );
			$formattedProduct->category = $terms[0]->name;
			$formattedProduct->list     = $this->list;
			$formattedProduct->id       = ( $product->get_sku() ? $product->get_sku() : $product->post->ID );
			$formattedProduct->name     = $product->post->post_title;
			$formattedProduct->price    = $product->get_price();
			$formattedProduct->variant  = $product->variant;
			$formattedProduct->quantity = $product->quantity;
			$formattedProduct->position = $key;
			$this->formattedProducts[] = $formattedProduct;
		}
	}
	public function getFormattedProducts() {
		$this->setFormatProducts();
		return $this->formattedProducts;
	}

	public function getProductString( $product ) {
		$str = sprintf( '{ \'name\': \'%1$s\', \'id\': \'%2$s\', \'price\': %3$f, \'brand\': \'%4$s\', \'category\': \'%5$s\', \'list\': \'%6$s\', \'position\': %7$d, \'variant\': \'%8$s\' }', $product->name, $product->id, $product->price, $product->brand, $product->category, $product->list, $product->position, $product->variant );
		return $str;
	}

	public function getActionString() {
		if ( $this->event === 'enhanceEcom Product Detail View' ) {
			$str = sprintf( '{ \'list\': \'%1$s\' }', $this->list );
		}
		elseif ( $this->event === 'enhanceEcom transactionSuccess' ) {
			$str = sprintf( '{ \'id\': \'%1$s\', \'affiliation\': \'%2$s\', \'revenue\': %3$f, \'tax\': %4$f, \'shipping\': %5$f, \'coupon\': \'%6$s\' }', $this->action->id, $this->action->affiliation, $this->action->revenue, $this->action->tax, $this->action->shipping, $this->action->coupon );
		}
		else {
			return NULL;
		}
		$actionString = sprintf( '\'actionField\': %1$s, ', $str );
		return $actionString;
	}

	public function getDataLayer() {
		$actionString = $this->getActionString();
		$products = $this->getFormattedProducts();
		$productStringArray = array();
		foreach ( $products as $product ) {
			$productStringArray[] = $this->getProductString( $product );
		}
		$productString = implode( ', ', $productStringArray );
		$str = sprintf( '{ \'event\': \'%1$s\', \'ecommerce\': { \'%2$s\': { %4$s \'products\': [ %3$s ] } } }', $this->event, $this->ecommerce, $productString, $actionString );
		return $str;
	}
}


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
		$gtm4wp = new GTM4WP();
		// PRODUCT CATEGORY (Product Impressions)
		if ( is_product_category() || is_shop() ):
			$gtm4wp->event = 'enhanceEcom Product Impression';
			$gtm4wp->ecommerce = 'impressions';
			$gtm4wp->list = single_term_title( '', false );
			$gtm4wp->getProductsByCategory();
		endif;
		// PRODUCT (Product Details)
		if ( is_product() ):
			$gtm4wp->event = 'enhanceEcom Product Detail View';
			$gtm4wp->ecommerce  = 'detail';
			$gtm4wp->list = single_term_title( '', false );
			$gtm4wp->getProduct();
		endif;
		// CART ()
		if ( is_cart() ):
			$gtm4wp->event = 'View Cart';
			$gtm4wp->ecommerce  = 'cart';
			$gtm4wp->getProductsCart();
		endif;
		// CHECKOUT ()
		if ( is_checkout() ):
			$gtm4wp->event = 'Product Checkout';
			$gtm4wp->ecommerce = 'checkout';
			$gtm4wp->getProductsCart();
		endif;
		// ORDER RECEIVED page
		if( is_wc_endpoint_url( 'order-received' ) ):
			$gtm4wp->event = 'enhanceEcom transactionSuccess';
			$gtm4wp->getProductsOrder();
		endif;
		// print script
		printf( '<script>dataLayer.push(%1$s);</script>', $gtm4wp->getDataLayer() );
	} else {
		return false;
	}
}



/* Return product details to AJAX call
 *
 * This function gets the appropriate product details and returns them as a JSON encoded object.
 *
 */
function gtm4wp_get_product_ajax() {
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		die('WooCommerce not activated');
	};
	$gtm4wp = new GTM4WP();
	if ( isset( $_REQUEST ) && $_REQUEST['product_id'] && $_REQUEST['product_qty'] ) {
		$product = $gtm4wp->getProduct( $_REQUEST['product_id'] );
		$product->quantity = $_REQUEST['product_qty'];
		if (! empty( $_REQUEST['product_variant'] ) ) { $product->variant = $_REQUEST['product_variant']; }

		$gtm4wp->setProduct( $product );
		$formatted = $gtm4wp->getFormattedProducts();
		echo wp_json_encode( $formatted[0] );
	}
	else {
		echo 'product data is empty';
	}
	die(); // stop executing script
}
add_action( 'wp_ajax_gtm4wp_get_product', 'gtm4wp_get_product_ajax' ); // ajax for logged in users
add_action( 'wp_ajax_nopriv_gtm4wp_get_product', 'gtm4wp_get_product_ajax' ); // ajax for not logged in users

