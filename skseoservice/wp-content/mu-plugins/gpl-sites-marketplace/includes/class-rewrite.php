<?php

if (!defined('ABSPATH')) {
    exit;
}

class GPL_Sites_Rewrite {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'add_rewrite_rules'), 10);
        add_filter('query_vars', array(__CLASS__, 'add_query_vars'));
        add_action('template_redirect', array(__CLASS__, 'handle_site_template'));
        
        // Flush rules on version change
        add_action('init', array(__CLASS__, 'maybe_flush_rules'), 20);
    }
    
    /**
     * Add rewrite rules for site detail pages
     */
    public static function add_rewrite_rules() {
        // Match: /site/anything/ (including dots for domains)
        add_rewrite_rule(
            '^site/([^/]+)/?$',
            'index.php?gpl_site_domain=$matches[1]',
            'top'
        );
    }
    
    /**
     * Register query vars
     */
    public static function add_query_vars($vars) {
        $vars[] = 'gpl_site_domain';
        return $vars;
    }
    
    /**
     * Handle site detail template
     */
    public static function handle_site_template() {
        $domain = get_query_var('gpl_site_domain');
        
        if (empty($domain)) {
            return;
        }
        
        // Clean domain
        $domain = sanitize_text_field($domain);
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/^www\./', '', $domain);
        $domain = rtrim($domain, '/');
        
        // Try to find site by domain (exact match)
        $site = GPL_Sites_Database::get_site_by_domain($domain);
        
        // If not found, try with www prefix
        if (!$site) {
            $site = GPL_Sites_Database::get_site_by_domain('www.' . $domain);
        }
        
        // If still not found, try partial match
        if (!$site) {
            global $wpdb;
            $table = GPL_Sites_Database::get_sites_table();
            $site = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE website_url LIKE %s LIMIT 1",
                '%' . $wpdb->esc_like($domain) . '%'
            ), ARRAY_A);
        }
        
        if (!$site) {
            // Site not found - show 404
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        
        // Set global for template
        $GLOBALS['gpl_current_site'] = $site;
        
        // Load template
        $template = GPL_SITES_PATH . 'templates/frontend/site-detail.php';
        if (file_exists($template)) {
            include $template;
            exit;
        }
    }
    
    /**
     * Flush rewrite rules on version update
     */
    public static function maybe_flush_rules() {
        $version = get_option('gpl_sites_rewrite_version');
        if ($version !== GPL_SITES_VERSION) {
            flush_rewrite_rules();
            update_option('gpl_sites_rewrite_version', GPL_SITES_VERSION);
        }
    }
}
