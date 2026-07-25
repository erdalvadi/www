<?php

if (!defined('ABSPATH')) exit;

class GPL_Sites_Shortcodes {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::init();
        }
        return self::$instance;
    }
    
    public static function init() {
        // New shortcode names (gplm_)
        add_shortcode('gplm_marketplace', array(__CLASS__, 'render_sites'));
        add_shortcode('gplm_login', array(__CLASS__, 'render_login_register'));
        add_shortcode('gplm_register', array(__CLASS__, 'render_login_register'));
        add_shortcode('gplm_seller_dashboard', array(__CLASS__, 'render_seller_dashboard'));
        add_shortcode('gplm_buyer_dashboard', array(__CLASS__, 'render_buyer_dashboard'));
        add_shortcode('gplm_add_site', array(__CLASS__, 'render_add_site'));
        add_shortcode('gplm_edit_site', array(__CLASS__, 'render_add_site'));
        
        // Legacy shortcode names (gpl_) for backward compatibility
        add_shortcode('gpl_sites', array(__CLASS__, 'render_sites'));
        add_shortcode('gpl_login_register', array(__CLASS__, 'render_login_register'));
        add_shortcode('gpl_seller_dashboard', array(__CLASS__, 'render_seller_dashboard'));
        add_shortcode('gpl_buyer_dashboard', array(__CLASS__, 'render_buyer_dashboard'));
        add_shortcode('gpl_add_site', array(__CLASS__, 'render_add_site'));
        add_shortcode('gpl_edit_site', array(__CLASS__, 'render_add_site'));
    }
    
    public static function render_sites($atts) {
        ob_start();
        include GPL_SITES_PATH . 'templates/frontend/sites-grid.php';
        return ob_get_clean();
    }
    
    public static function render_login_register($atts) {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (in_array('gpl_seller', $user->roles)) {
                wp_redirect(home_url('/seller-dashboard/'));
            } else {
                wp_redirect(home_url('/buyer-dashboard/'));
            }
            exit;
        }
        ob_start();
        include GPL_SITES_PATH . 'templates/frontend/login-register.php';
        return ob_get_clean();
    }
    
    public static function render_seller_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<div class="gplm-login-required"><div class="gplm-login-required-content"><h2>Login Required</h2><p>Please login to access your seller dashboard.</p><a href="' . home_url('/login/') . '" class="gplm-btn-primary">Login Now</a></div></div>';
        }
        ob_start();
        include GPL_SITES_PATH . 'templates/frontend/seller-dashboard.php';
        return ob_get_clean();
    }
    
    public static function render_buyer_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<div class="gplm-login-required"><div class="gplm-login-required-content"><h2>Login Required</h2><p>Please login to access your buyer dashboard.</p><a href="' . home_url('/login/') . '" class="gplm-btn-primary">Login Now</a></div></div>';
        }
        ob_start();
        include GPL_SITES_PATH . 'templates/frontend/buyer-dashboard.php';
        return ob_get_clean();
    }
    
    public static function render_add_site($atts) {
        if (!is_user_logged_in()) {
            return '<div class="gplm-login-required"><div class="gplm-login-required-content"><h2>Login Required</h2><p>Please login to add or edit sites.</p><a href="' . home_url('/login/') . '" class="gplm-btn-primary">Login Now</a></div></div>';
        }
        ob_start();
        include GPL_SITES_PATH . 'templates/frontend/add-site.php';
        return ob_get_clean();
    }
}
