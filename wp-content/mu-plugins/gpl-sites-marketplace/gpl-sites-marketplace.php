<?php
/**
 * Plugin Name: GPL Sites Marketplace
 * Description: Guest Post Links Marketplace
 * Version: 1.0.0
 * Author: GPL Sites
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GPL_SITES_VERSION', '19.0.0');
define('GPL_SITES_PATH', plugin_dir_path(__FILE__));
define('GPL_SITES_URL', plugin_dir_url(__FILE__));

require_once GPL_SITES_PATH . 'includes/class-database.php';
require_once GPL_SITES_PATH . 'includes/class-user-roles.php';
require_once GPL_SITES_PATH . 'includes/class-ajax.php';
require_once GPL_SITES_PATH . 'includes/class-shortcodes.php';
require_once GPL_SITES_PATH . 'includes/class-rewrite.php';
require_once GPL_SITES_PATH . 'includes/class-helpers.php';
require_once GPL_SITES_PATH . 'gplm-admin.php';

class GPL_Sites_Marketplace {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_classes();
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    private function init_classes() {
        GPL_Sites_Database::init();
        GPL_Sites_User_Roles::init();
        GPL_Sites_AJAX::instance();
        GPL_Sites_Shortcodes::get_instance();
        GPL_Sites_Rewrite::init();
    }
    
    public function enqueue_assets() {
        wp_enqueue_style(
            'gpl-sites-styles',
            GPL_SITES_URL . 'assets/css/styles.css',
            array(),
            GPL_SITES_VERSION
        );
        
        wp_enqueue_script(
            'gpl-sites-app',
            GPL_SITES_URL . 'assets/js/app.js',
            array(),
            GPL_SITES_VERSION,
            true
        );
        
        wp_localize_script('gpl-sites-app', 'GPL_SITES', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gpl_sites_nonce'),
            'home_url' => home_url('/'),
            'is_logged_in' => is_user_logged_in(),
            'is_admin' => current_user_can('administrator'),
            'user_id' => get_current_user_id()
        ));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'GPL Dashboard',
            'GPL Dashboard',
            'manage_options',
            'gpl-sites',
            array($this, 'render_admin_page'),
            'dashicons-admin-site-alt3',
            30
        );
    }
    
    public function render_admin_page() {
        include GPL_SITES_PATH . 'templates/admin/dashboard.php';
    }
}

GPL_Sites_Marketplace::get_instance();
