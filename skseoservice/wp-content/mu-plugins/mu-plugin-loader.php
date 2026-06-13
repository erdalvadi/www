<?php
/**
 * GPL Sites Marketplace MU-Plugin Loader
 * 
 * Installation:
 * 1. Upload the 'gpl-sites-marketplace' folder to /wp-content/mu-plugins/
 * 2. Copy this file to /wp-content/mu-plugins/load-gplm.php
 * 3. The plugin will auto-activate
 * 
 * Note: MU-Plugins cannot be deactivated from the admin panel.
 * To disable, simply remove or rename this loader file.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load the main plugin file
require WPMU_PLUGIN_DIR . '/gpl-sites-marketplace/gpl-sites-marketplace.php';
