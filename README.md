# Google Tag Manager 4 WordPress
Add Google Tag Manager to WordPress

## Installation
Please update me!

## Usage
Go to `Settings` -> `Google Tag Manager` and add your Google Tag Manager `container ID` and save.
Adds GTM container code using `wp_footer` hook for the container, and `wp_head` hook for the data layer.

If your theme does not support `wp_footer` hook, add `<?php gtm4wp_render(); ?>` to your theme before the closing `<body>` tag.

To disable GTM, disable the plugin.

## To Do
- Add warning/alert messages to plugin activation
- Build out custom dataLayer functionality
