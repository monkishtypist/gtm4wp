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
if ( function_exists('tha_head_bottom') )
	add_action( 'tha_head_bottom', 'gtm4wp_dataLayer_output' );
if ( function_exists('tha_body_top') )
	add_action( 'tha_body_top', 'gtm4wp_container_output' );


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

	echo __( 'This plugin requires you have access to edit your theme files OR your theme includes an \'after_body\' hook.', 'gtm4wp' );

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
	$container_id = $options['gtm4wp_container_id'];

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

/**
 * GTM dataLayer Output
 *
 */
function gtm4wp_dataLayer_output() {

	$options = get_option( 'gtm4wp_settings' );

	printf( "<script>
		dataLayer = [];
		</script>" );

}



?>