<?php
/*
Plugin Name: Server Security Policy - Strict Block WP File Manager
Description: Strictly blocks download, extraction, and activation of WP File Manager.
Version: 3.0
Author: Server Administrator
*/

add_filter('upgrader_pre_download', function($reply, $package, $upgrader) {
    if (is_string($package) && strpos($package, 'wp-file-manager') !== false) {
        return new WP_Error('blocked', 'Server Security Policy: Downloading WP File Manager is strictly prohibited on this server.');
    }
    return $reply;
}, 10, 3);

add_filter('upgrader_source_selection', function($source, $remote_source, $upgrader) {
    if (strpos($source, 'wp-file-manager') !== false) {
        return new WP_Error('blocked', 'Server Security Policy: Extracting WP File Manager is strictly prohibited on this server.');
    }
    return $source;
}, 10, 3);

add_action('activate_plugin', function($plugin) {
    if ($plugin === 'wp-file-manager/file_folder_manager.php') {
        wp_die('<b>Server Security Policy:</b> The WP File Manager plugin is prohibited on this server due to security risks. <br><br><a href="' . admin_url('plugins.php') . '">&laquo; Return to Plugins</a>', 'Plugin Blocked', array('response' => 403));
    }
});
?>
