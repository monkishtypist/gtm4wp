<?php
/**
 * GTM Container Code Output
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
'//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','%s');</script>
<!-- End Google Tag Manager -->", $container_id );
	}
}
function gtm4wp_noscript_output() {
	$options = get_option( 'gtm4wp_settings' );
	$container_id = sanitize_text_field( $options['gtm4wp_container_id'] );
	if ( !empty($container_id) ) {
		printf( "<!-- Google Tag Manager (noscript) -->
<noscript><iframe src=\"//www.googletagmanager.com/ns.html?id=%s\"
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

