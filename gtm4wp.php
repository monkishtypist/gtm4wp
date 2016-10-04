<?php
/**
 * Plugin Name: Google Tag Manager 4 WordPress
 * Plugin URI: http://ninthlink.com
 * Description: Add Google Tag Manager to WordPress
 * Version: 0.1.0
 * Author: Ninthlink, Inc.
 * Author URI: http://ninthlink.com
 * Text Domain: gtm4wp
 * License: GPL2
 */


//exit if accessed directly
if(!defined('ABSPATH')) exit;


add_action( 'admin_menu', 'gtm4wp_add_admin_menu' );
add_action( 'admin_init', 'gtm4wp_settings_init' );

// Theme Hooks Alliance actions
add_action( 'wp_head', 'gtm4wp_datalayer_init', 100 );
add_action( 'wp_footer', 'gtm4wp_woo_datalayer', 10 );
add_action( 'wp_footer', 'gtm4wp_container_output', 100 );
// And in case no THA...
add_action( 'gtm4wp_render', 'gtm4wp_container_output', 10 );


function gtm4wp_render() {
	do_action( 'gtm4wp_render' ); // use for hooking if tha_body_top not available
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
	echo __( 'This plugin requires you have access to edit your theme files OR your theme includes the \'wp_footer\' hook. See plugin README.md for details.', 'gtm4wp' );
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
			<noscript><iframe src=\"//www.googletagmanager.com/ns.html?id=%s\"
			height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
			<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
			new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
			j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
			'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
			})(window,document,'script','dataLayer','%s');</script>
			<!-- End Google Tag Manager -->", $container_id, $container_id );
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
add_action( 'wp_footer', 'gtm4wp_woo_data_layer' );
function gtm4wp_woo_data_layer() {
	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		// Globals and default vars
		global $woocommerce;
		//echo '<pre>'; print_r($woocommerce); echo '</pre>';

		$options = get_option( 'gtm4wp_settings' );
		$brand = sanitize_text_field( $options['gtm4wp_brand'] );
		$category_slug = get_query_var( 'product_cat' );
		if ( $category_slug ) {
			$category = get_term_by( 'slug', $category_slug, 'product_cat' );
		} else {
			$category = false;
		}

		$str = '';
		$strArr = array();


		// Start Script
		$str = '<script>dataLayer.push(';
		// CART page
		if ( is_cart() ):
			$items = $woocommerce->cart->get_cart();
			$str .= '{\'event\': \'viewCart\',
				\'ecommerce\': {
					\'cart\': {
					\'actionField\': {\'list\': \'Cart\'},
					\'products\': [';
			foreach ( $items as $item ) {
				$product = wc_get_product( $item['product_id'] );
				echo '<pre>'; print_r($product); echo '</pre>';
				$strArr[] = '{
					\'name\': \'Triblend Android T-Shirt\',
					\'id\': \'12345\',
					\'price\': \'15.25\',
					\'brand\': \'Google\',
					\'category\': \'Apparel\',
					\'variant\': \'Gray\',
					\'quantity\': 1
					}]
					}
					}';
			}
			$str .= implode(',', $strArr);
			$str .= ']}';
		endif;
		// PRODUCT CATEGORY
		if ( is_product_category() && $category ):
			$i = 1;
			// Get all products in category
			$args = array(
				'post_type'				=> 'product',
				'post_status'			=> 'publish',
				'posts_per_page'		=> -1,
				'product_cat'			=> $category_slug
			);
			$products = new WP_Query($args);
			$str .= '{
				\'event\':\'enhanceEcom Product Impression\',
				\'ecommerce\': {
					\'impressions\': [';
			while ( $products->have_posts() ) : $products->the_post(); 
				global $product;
				$strArr[] = sprintf( '{
					\'name\': \'%s\',
					\'id\': %d,
					\'price\': %f,
					\'brand\': \'%s\',
					\'category\': \'%s\',
					\'list\': \'%s\',
					\'position\': %d
					}', get_the_title(), get_the_ID(), $product->get_price(), $brand, $category->name, $category->name, $i );
				$i++;
			endwhile;
			wp_reset_query();
			$str .= implode(',', $strArr);
			$str .= ']}}';
		endif;
		// PRODUCT pages
		if ( is_product() ):
			$product = wc_get_product( get_the_ID() );
			$terms = get_the_terms( get_the_ID(), 'product_cat' );
			var_dump($terms);
			$str .= sprintf( '{\'event\': \'enhanceEcom Product Detail View\',
				\'ecommerce\': {
					\'detail\': {
						\'actionField\': {\'list\': \'%s\'},
						\'products\': [{
							\'id\': %d,
							\'name\': \'%s\',
							\'price\': %f,
							\'brand\': \'%s\',
							\'category\': \'%s\',
							\'variant\': \'\'
						}]
					}
				}}', $terms->name, get_the_ID(), get_the_title(), $product->get_price(), $brand, $terms->name, 'Variant' );
		endif;
		// ORDER RECEIVED page
		if( is_wc_endpoint_url( 'order-received' ) ):
			$order = woo_order_obj();
			//var_dump($order);
			$items = $order->get_items();
			$str .= sprintf( '{\'order\' : {
					\'id\' : %d,
					\'email\' : \'%s\',
					\'country\' : \'%s\',
					\'currency\' : \'%s\',
					\'total\' : %f,
					\'discounts\' : %f,
					\'shipping-total\' : %f,
					\'tax-total\' : %f,
					\'est-ship-date\' : \'%s\',
					\'est-delivery-date\' : \'%s\',
					\'has-preorder\' : \'\',
					\'has-digital\' : \'\',
				},', $order->get_order_number(), $order->billing_email, $order->billing_country, $order->order_currency, $order->order_total, $order->cart_discount, $order->order_shipping, $order->order_tax, date( 'Y-m-d', strtotime( '+1 weekday' ) ), date( 'Y-m-d', strtotime('+6 weekday') ) );
			$str .= '\'items\' : [';
			foreach ( $items as $item ) {
				$str .= sprintf( '{
						\'name\' : \'%s\',
						\'price\' : %f,
						\'quantity\' : %d
					},', $item['name'], number_format( (float) $item['line_total'], 2, '.', '' ), $item['qty'] );
			}
			$str .= ']';
			$str .= '}';
		endif;

		$str .= ');</script>';
		// print script
		print($str);
	} else {
		return false;
	}
}
