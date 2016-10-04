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
}


function gtm4wp_container_id_render(  ) { 
	$options = get_option( 'gtm4wp_settings' );
	?>
	<input type='text' name='gtm4wp_settings[gtm4wp_container_id]' value='<?php echo $options['gtm4wp_container_id']; ?>'>
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


/* WooCommerce DataLayer
 *
 * This function pushes WooCommerce data into the dataLayer object.
 */
add_action( 'wp_footer', 'woo_data_layer' );
function woo_data_layer() {
    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        global $woocommerce;
        $category_slug = get_query_var( 'product_cat' );

        echo '<pre>'; print_r( $category_slug ); echo '</pre>';

        $brand = 'Cabeau';
        // CART page
        if ( is_cart() ):
            $str = '<script>dataLayer.push({"cart" : [';
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                //print_r($cart_item);
                //print_r(WC()->cart->get_item_data($cart_item, true));
                $_product     = $cart_item['data'];
                $product_id   = $cart_item['product_id'];
                $str .= sprintf('{
                        "name" : "%s",
                        "price" : %f,
                        "quantity" : %d
                    },', $_product->post->post_title, WC()->cart->get_product_price( $_product ), $cart_item['quantity']);
            }
            $str .= ']});</script>';
        endif;
        // PRODUCT CATEGORY
        if ( is_product_category() ):
            $i = 1;
            $category                   = get_term_by( 'slug', $category_slug, 'product_cat' );
            var_dump($category);
            // Get all products in category
            $args = array(
                'post_type'             => 'product',
                'post_status'           => 'publish',
                'posts_per_page'        => -1,
                'product_cat'           => $category_slug
            );
            $products = new WP_Query($args);
            echo '<pre>'; print_r($products); echo '</pre>';
            $str = '<script>
                dataLayer.push({
                    \'event\':\'enhanceEcom Product Impression\',
                    \'ecommerce\': {
                        \'impressions\': [';
            while ( $products->have_posts() ) : $products->the_post(); 
                global $product;
                $str .= sprintf('{
                    \'name\': \'%s\',
                    \'id\': %d,
                    \'price\': %f,
                    \'brand\': \'%s\',
                    \'category\': \'%s\',
                    \'list\': \'%s\',
                    \'position\': %d
                    },', get_the_title(), get_the_ID(), $product->get_price(), $brand, $category, $category, $i);
                $i++;
            endwhile;
            wp_reset_query();
            $str .= ']}});</script>';
        endif;
        // PRODUCT CATEGORY - Travel Pillows
        if ( is_page(6608) ):
            $i = 1;
            $category                   = 'Travel Pillows';
            $category_slug              = 'travel-pillows';
            // Get all products in category
            $args = array(
                'post_type'             => 'product',
                'post_status'           => 'publish',
                'posts_per_page'        => -1,
                'product_cat'           => $category_slug
            );
            $products = new WP_Query($args);
            echo '<pre>'; print_r($products); echo '</pre>';
            $str = '<script>
                dataLayer.push({
                    \'event\':\'enhanceEcom Product Impression\',
                    \'ecommerce\': {
                        \'impressions\': [';
            while ( $products->have_posts() ) : $products->the_post(); 
                global $product;
                $str .= sprintf('{
                    \'name\': \'%s\',
                    \'id\': %d,
                    \'price\': %f,
                    \'brand\': \'%s\',
                    \'category\': \'%s\',
                    \'list\': \'%s\',
                    \'position\': %d
                    },', get_the_title(), get_the_ID(), $product->get_price(), $brand, $category, $category, $i);
                $i++;
            endwhile;
            wp_reset_query();
            $str .= ']}});</script>';
        endif;
        // PRODUCT CATEGORY - Comfort
        if ( is_page(6785) ):
            var_dump(WC());
        endif;
        // PRODUCT CATEGORY - Accessories
        if ( is_page(6788) ):
            var_dump(WC());
        endif;
        // PRODUCT pages
        if ( is_product() ):
            $product = wc_get_product( get_the_ID() );
            $price = $product->get_price();
            $str = sprintf('<script>dataLayer.push({"product" : {
            	"id" : "%s",
                "name" : "%s",
                "price" : %f
            }})</script>', get_the_ID(), get_the_title(), $price);
        endif;
        // ORDER RECEIVED page
        if( is_wc_endpoint_url( 'order-received' ) ):
            $order = woo_order_obj();
            //var_dump($order);
            $items = $order->get_items();
            $str = sprintf('<script>dataLayer.push({
                "order" : {
                    "id" : %d,
                    "email" : "%s",
                    "country" : "%s",
                    "currency" : "%s",
                    "total" : %f,
                    "discounts" : %f,
                    "shipping-total" : %f,
                    "tax-total" : %f,
                    "est-ship-date" : "%s",
                    "est-delivery-date" : "%s",
                    "has-preorder" : "",
                    "has-digital" : "",
                },', $order->get_order_number(), $order->billing_email, $order->billing_country, $order->order_currency, $order->order_total, $order->cart_discount, $order->order_shipping, $order->order_tax, date('Y-m-d', strtotime('+1 weekday')), date('Y-m-d', strtotime('+6 weekday')));
            $str .= '"items" : [';
            foreach ( $items as $item ) {
                $str .= sprintf('{
                        "name" : "%s",
                        "price" : %f,
                        "quantity" : %d
                    },', $item['name'], number_format((float)$item['line_total'], 2, '.', ''), $item['qty']);
            }
            $str .= ']';
            $str .= '});</script>';
        endif;
        // print script
        print($str);
    } else {
        return false;
    }
}
