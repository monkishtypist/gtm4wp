<?php
/**
 * Plugin Name: Google Tag Manager 4 WordPress
 * Plugin URI: https://github.com/progothemes/gtm4wp
 * Description: Add Google Tag Manager to WordPress, with advanced eCommerce DataLayer support for WooCommerce
 * Version: 2.0.0
 * Author: ProGo
 * Author URI: http://www.progo.com
 * Text Domain: gtm4wp
 * License: GPL2
 */

//exit if accessed directly
if(!defined('ABSPATH')) exit;

require_once('includes/gtm4wp_settings.php');
require_once('includes/gtm4wp_container.php');
require_once('includes/gtm4wp_woo.php');

add_action( 'admin_menu', 'gtm4wp_add_admin_menu' );
add_action( 'admin_init', 'gtm4wp_settings_init' );

// Plugin Custom Scripts
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

/* Register Woo plugin scripts
 *
 * This function includes the plugin scripts and styles.
 *
 */
function gtm4wp_scripts() {
	// Register the script
	wp_register_script( 'gtm4wp_js', plugins_url( '/js/gtm4wp.js' , __FILE__ ), array( 'jquery' ) );
	// Localize the script with new data
	$_product_array = gtm4wp_woo_product_array();

	wp_localize_script( 'gtm4wp_js', 'product', $_product_array );

	// Enqueued script with localized data.
	wp_enqueue_script( 'gtm4wp_js' );
}
