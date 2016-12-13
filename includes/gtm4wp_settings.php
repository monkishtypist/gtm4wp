<?php
/**
 * Main Settings for plugin
 */

// admin menu
function gtm4wp_add_admin_menu(  ) {
	add_submenu_page( 'options-general.php', 'GTM 4 WP', 'Google Tag Manager', 'manage_options', 'gtmforwordpress', 'gtm4wp_options_page' );
}

//modify the link by unshifting the array
function gtm4wp_add_settings_link( $links, $file ) {
    $gtm4wp_settings_link = '<a href="' . admin_url( 'options-general.php?page=gtmforwordpress' ) . '">' . __( 'Settings', 'gtmforwordpress' ) . '</a>';
    array_unshift( $links, $gtm4wp_settings_link );

    return $links;
}

// settings
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

// input fields render
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

// description render
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
