# Google Tag Manager 4 WordPress
Add Google Tag Manager to WordPress, with advanced eCommerce DataLayer support for WooCommerce.

## Installation
1. Download this plugin
2. Upload to your site /wp-content/plugins/gtm4wp
3. Activate the "Google Tag Manager 4 WordPress" plugin
4. Go to the "Google Tag Manager" Settings page to add your Container and optional Brand code.
5. If your theme uses Theme Hooks Alliance action hooks, then you are done!
6. If it doesn't, and if you do not see the `noscript` output below your opening `<body>` tag, then you will need to edit your theme's `header.php` file and add `<?php gtm4wp_noscript(); ?>` right below the opening `<body>` tag.

## Usage
Go to `Settings` -> `Google Tag Manager` and add your Google Tag Manager `container ID` and save.
Adds latest version of GTM container code and `dataLayer` JavaScript object using `wp_head` hook.

If your theme does not support Theme Hooks Alliance action hook `tha_body_top`, add `<?php gtm4wp_noscript(); ?>` to your theme, right after the opening `<body>` tag.

To disable GTM, disable the plugin.

## Enhanced Ecommerce
GTM4WP uses Google's enhanced ecommerce `dataLayer` object. For more information, see: [Enhanced Ecommerce (UA) Developer Guide](https://developers.google.com/tag-manager/enhanced-ecommerce)
Enhanced Ecommerce data only works if WooCommerce is installed and active.