# Google Tag Manager 4 WordPress
Add Google Tag Manager to WordPress, with advanced eCommerce DataLayer support for WooCommerce.

## Installation
1. Download this plugin
2. Upload to your site /wp-content/plugins/gtm4wp
3. Activate the "Google Tag Manager 4 WordPress" plugin
4. Go to the "Google Tag Manager" Settings page to add your Container and optional Brand code.
5. If your theme has the `tha_body_top` tag, then you are done!
6. If it doesn't - if you do not see the `noscript` output below your opening `<body` tag, then you will need to edit your theme's `header.php` file and add that right below the `<body` opening tag.

## Usage
Go to `Settings` -> `Google Tag Manager` and add your Google Tag Manager `container ID` and save.
Adds GTM container code using `wp_footer` hook for the container, and `wp_head` hook for the data layer.

If your theme does not support `wp_footer` hook, add `<?php gtm4wp_render(); ?>` to your theme before the closing `</body>` tag.

If your theme does not support `tha_body_top` hook, add `<?php do_action( 'that_body_top' ); ?>` to your theme, right after the opening `<body>` tag.

To disable GTM, disable the plugin.

## To Do
- Add warning/alert messages to plugin activation...
- Build out more custom dataLayer functionality...
