<?php
/**
 * Plugin Name: Google Tag Manager 4 WordPress
 * Plugin URI: https://github.com/progothemes/gtm4wp
 * Description: Add Google Tag Manager to WordPress, with advanced eCommerce DataLayer support for WooCommerce
 * Version: 1.2.1
 * Author: ProGo
 * Author URI: http://www.progo.com
 * Text Domain: gtm4wp
 * License: GPL2
 */

//exit if accessed directly
if(!defined('ABSPATH')) exit;

add_action( 'admin_menu', 'gtm4wp_add_admin_menu' );
add_action( 'admin_init', 'gtm4wp_settings_init' );

// Plugin Scripts
add_action( 'wp_enqueue_scripts', 'gtm4wp_scripts', 1000 );

// GTM
add_action( 'wp_head', 'gtm4wp_datalayer_init', 1 );
add_action( 'wp_head', 'gtm4wp_container_output', 2 );
add_action( 'wp_footer', 'gtm4wp_woo_datalayer', 10 );

// Theme Hooks Alliance actions
add_action( 'tha_body_top', 'gtm4wp_noscript_output', 2 );

// And in case no THA...
add_action( 'gtm4wp_noscript', 'gtm4wp_noscript_output', 10 );

function gtm4wp_noscript() {
	do_action( 'gtm4wp_noscript' ); // use for hooking if tha_body_top not available
}

function gtm4wp_add_admin_menu(  ) {
	add_submenu_page( 'options-general.php', 'GTM 4 WP', 'Google Tag Manager', 'manage_options', 'gtmforwordpress', 'gtm4wp_options_page' );
}

function gtm4wp_settings_init(  ) {
	register_setting( 'pluginPage', 'gtm4wp_settings' );
	add_settings_section(
		'gtm4wp_pluginPage_section',
		__( 'Add Google Tag Manager to WordPress', 'gtm4wp' ),
		'gtm4wp_settings_section_callback',
		'pluginPage'
	);
	add_settings_field(
		'gtm4wp_container_id',
		__( 'Container ID', 'gtm4wp' ),
		'gtm4wp_container_id_render',
		'pluginPage',
		'gtm4wp_pluginPage_section'
	);
	add_settings_field(
		'gtm4wp_brand',
		__( 'Brand', 'gtm4wp' ),
		'gtm4wp_brand_render',
		'pluginPage',
		'gtm4wp_pluginPage_section'
	);
}

function gtm4wp_container_id_render(  ) {
	$options = get_option( 'gtm4wp_settings' );
	?>
	<input type='text' name='gtm4wp_settings[gtm4wp_container_id]' value='<?php echo $options['gtm4wp_container_id']; ?>'>
	<?php
}

function gtm4wp_brand_render(  ) {
	$options = get_option( 'gtm4wp_settings' );
	?>
	<input type='text' name='gtm4wp_settings[gtm4wp_brand]' value='<?php echo $options['gtm4wp_brand']; ?>'>
	<?php
}

function gtm4wp_settings_section_callback(  ) {
	echo '<p>'. __( 'This plugin adds Google Tag Manager script to the &lt;head&gt; of your theme.', 'gtm4wp' ) .'</p>';
	echo '<p>'. __( 'If your theme includes Theme Hook Alliance hooks, Google Tag Manager &lt;noscript&gt; will be added to the \'tha_body_top\' hook.', 'gtm4wp' ) .'</p>';
	echo '<p>'. __( 'If your theme does not include Theme Hook Alliance hooks, you will need to call the \'gtm4wp_noscript\' hook just after the opening &gt;body&lt; tag in your theme.', 'gtm4wp' ) .'</p>';
	echo '<p>'. __( 'For more information, see the plugin\'s README.md file.', 'gtm4wp' );
}

/**
 * Options Page
 *
 */
function gtm4wp_options_page(  ) {
	?>
	<form action='options.php' method='post'>
		<h2>GTMforWordPress</h2>
		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>
	</form>
	<?php
}

/**
 * GTM Code Output
 *
 */
function gtm4wp_container_output() {
	$options = get_option( 'gtm4wp_settings' );
	$container_id = sanitize_text_field( $options['gtm4wp_container_id'] );
	if ( !empty($container_id) ) {
		printf( "<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','%s');</script>
<!-- End Google Tag Manager -->", $container_id );
	}
}
function gtm4wp_noscript_output() {
	$options = get_option( 'gtm4wp_settings' );
	$container_id = sanitize_text_field( $options['gtm4wp_container_id'] );
	if ( !empty($container_id) ) {
		printf( "<!-- Google Tag Manager (noscript) -->
<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=%s\"
height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->", $container_id );
	}
}

/**
 * GTM dataLayer Init
 *
 */
function gtm4wp_datalayer_init() {
	$options = get_option( 'gtm4wp_settings' );
	printf( "<script>window.dataLayer = window.dataLayer || [];</script>" );
}

/* Enhanced Ecommerce DataLayer
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

		$options = get_option( 'gtm4wp_settings' );
		$brand = sanitize_text_field( $options['gtm4wp_brand'] );

		$str = '';
		$strArr = array();

		// PRODUCT CATEGORY
		if ( is_product_category() ):
			$category = '';
			$category_slug = get_query_var( 'product_cat' );
			if ( $category_slug ) { $category = get_term_by( 'slug', $category_slug, 'product_cat' ); }
			$i = 1;
			// Get all products in category
			$args = array(
				'post_type'				=> 'product',
				'post_status'			=> 'publish',
				'posts_per_page'		=> -1,
				'product_cat'			=> $category_slug
			);
			$products = new WP_Query($args);
			$str .= '{ \'event\':\'enhanceEcom Product Impression\', \'ecommerce\': { \'impressions\': [';
			while ( $products->have_posts() ) : $products->the_post();
				global $product;
				$strArr[] = sprintf( '{ \'name\': \'%s\', \'id\': \'%s\', \'price\': %f, \'brand\': \'%s\', \'category\': \'%s\', \'list\': \'%s\', \'position\': %d }', get_the_title(), $product->get_sku(), $product->get_price(), $brand, $category->name, $category->name, $i );
				$i++;
			endwhile;
			wp_reset_query();
			$str .= implode(',', $strArr);
			$str .= ']}}';
		endif;

		// PRODUCT pages
		if ( is_product() ):
			$product = new WC_Product( get_the_ID() );
			$variation_skus = '';
			if ( $product->product_type == 'variable' ) { // Get variation Skus
				$variations = $product->get_available_variations();
				$variation_skus = '[{';
				$skusArr = array();
				foreach ($variations as $variation) {
					$skusArr = $variation['sku'];
				}
				$variation_skus .= implode(',', $skusArr);
				$variation_skus .= '}]';
			}
			$terms = get_the_terms( $product->post->ID, 'product_cat' );
			$str .= sprintf( '{\'event\': \'enhanceEcom Product Detail View\', \'ecommerce\': { \'detail\': { \'actionField\': {\'list\': \'%s\'}, \'products\': [{ \'id\': \'%s\', \'name\': \'%s\', \'price\': %f, \'brand\': \'%s\', \'category\': \'%s\', \'variant\': \'%s\' }]}}}', $terms[0]->name, $product->get_sku(), get_the_title(), $product->get_price(), $brand, $terms[0]->name, $variation_skus );
		endif;

		// CART page
		if ( is_cart() ):
			$items = $woocommerce->cart->get_cart();
			$str .= '{ \'event\': \'view Cart\', \'ecommerce\': { \'cart\': { \'products\': [';
			foreach ( $items as $item ) {
				$product_id = $item['variation_id'];
				if ( $product_id ) {
					$product = new WC_Product_Variation( $item['variation_id'] );
				} else {
					$product = new WC_Product( $item['product_id'] );
				}
				$terms = get_the_terms( $product->post->ID, 'product_cat' );
				$strArr[] = sprintf('{ \'name\': \'%s\', \'id\': \'%s\', \'price\': %f, \'brand\': \'%s\', \'category\': \'%s\', \'variant\': \'%s\', \'quantity\': %d }', $product->post->post_title, $product->get_sku(), $product->get_price(), $brand, $terms[0]->name, $product->get_sku(), $item['quantity'] );
			}
			$str .= implode(',', $strArr);
			$str .= ']}}}';
		endif;

		// CHECKOUT page
		if ( is_checkout() ):
			$items = $woocommerce->cart->get_cart();
			$str .= '{ \'event\': \'Product Checkout\', \'ecommerce\': { \'checkout\': { \'products\': [';
			foreach ( $items as $item ) {
				$product_id = $item['variation_id'];
				if ( $product_id ) {
					$product = new WC_Product_Variation( $item['variation_id'] );
				} else {
					$product = new WC_Product( $item['product_id'] );
				}
				$terms = get_the_terms( $product->post->ID, 'product_cat' );
				$strArr[] = sprintf('{ \'name\': \'%s\', \'id\': \'%s\', \'price\': %f, \'brand\': \'%s\', \'category\': \'%s\', \'variant\': \'%s\', \'quantity\': %d }', $product->post->post_title, $product->get_sku(), $product->get_price(), $brand, $terms[0]->name, $product->get_sku(), $item['quantity'] );
			}
			$str .= implode(',', $strArr);
			$str .= ']}}}';
		endif;

		// ORDER RECEIVED page
		if( is_wc_endpoint_url( 'order-received' ) ):
			$order = woo_order_obj();
			$items = $order->get_items();
			$str .= sprintf('{ \'event\': \'enhanceEcom transactionSuccess\', \'ecommerce\': { \'purchase\': { \'actionField\': { \'id\': \'%s\', \'affiliation\': \'%s\', \'revenue\': %f, \'tax\': %f, \'shipping\': %f, \'coupon\': \'%s\' },', $order->get_order_number(), $brand, $order->order_total, $order->order_tax, $order->order_shipping, 'CouponCode' );
			$str .= '\'products\': [';
			foreach ( $items as $item ) {
				$product_id = $item['variation_id'];
				if ( $product_id ) {
					$product = new WC_Product_Variation( $item['variation_id'] );
				} else {
					$product = new WC_Product( $item['product_id'] );
				}
				$terms = get_the_terms( $product->post->ID, 'product_cat' );
				$strArr[] = sprintf('{ \'name\': \'%s\', \'id\': \'%s\', \'price\': %f, \'brand\': \'%s\', \'category\': \'%s\', \'variant\': \'%s\', \'quantity\': %d, \'coupon\': \'%s\' }', $product->post->post_title, $product->get_sku(), $product->get_price(), $brand, $terms[0]->name, $product->get_sku(), $item['quantity'], 'CouponCode' );
			}
			$str .= implode(',', $strArr);
			$str .= ']}}}';
		endif;

		// print script
		printf('<script>dataLayer.push(%1$s);</script>', $str);
	} else {
		return false;
	}
}

/* Register plugin scripts
 *
 * This function includes the plugin scripts and styles.
 *
 */
function gtm4wp_scripts() {
	// Register the script
	wp_register_script( 'gtm4wp_js', plugins_url( '/js/gtm4wp.js' , __FILE__ ), array( 'jquery' ) );
	// Localize the script with new data
	$product_array = gtm4wp_woo_product_array();

	wp_localize_script( 'gtm4wp_js', 'product', $product_array );

	// Enqueued script with localized data.
	wp_enqueue_script( 'gtm4wp_js' );
}

/* Return product details as array
 *
 * This function gets the appropriate product details and returns them as an associative array.
 *
 */
function gtm4wp_woo_product_array( $product_id = false ) {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		// Globals and default vars
		global $woocommerce;

		$options = get_option( 'gtm4wp_settings' );
		$brand = sanitize_text_field( $options['gtm4wp_brand'] );

		$product_array = array();

		if ( $product_id || is_product() ):
			$product = $product_id ? new WC_Product( $product_id ) : new WC_Product( get_the_ID() );
			$variation_skus = '';
			if ( $product->product_type == 'variable' ) { // Get variation Skus
				$variations = $product->get_available_variations();
				$variation_skus = '[{';
				$skusArr = array();
				foreach ($variations as $variation) {
					$skusArr = $variation['sku'];
				}
				$variation_skus .= implode(',', $skusArr);
				$variation_skus .= '}]';
			}
			
			$terms = get_the_terms( $product->post->ID, 'product_cat' );
			
			$product_array['brand'] 	= $brand;
			$product_array['category'] 	= $terms[0]->name;
			$product_array['id'] 		= $product->get_sku();
			$product_array['list'] 		= $terms[0]->name;
			$product_array['name'] 		= get_the_title();
			$product_array['price'] 	= $product->get_price();
			$product_array['variant'] 	= $variation_skus;

		endif;

		return $product_array;
	} else {
		return false;
	}
}
