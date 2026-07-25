<?php
/**
 * User Roles Class
 * Handles seller and buyer roles
 */

if (!defined('ABSPATH')) {
    exit;
}

class GPL_Sites_User_Roles {
    
    public static function init() {
        add_action('init', array(__CLASS__, 'register_roles'));
        add_action('user_register', array(__CLASS__, 'assign_default_role'), 10, 1);
    }
    
    /**
     * Register custom roles
     */
    public static function register_roles() {
        // Seller role - can add sites
        if (!get_role('gpl_seller')) {
            add_role('gpl_seller', 'GPL Seller', array(
                'read' => true,
                'gpl_add_site' => true,
                'gpl_edit_own_sites' => true,
                'gpl_delete_own_sites' => true,
                'gpl_view_sites' => true
            ));
        }
        
        // Buyer role - cannot add sites
        if (!get_role('gpl_buyer')) {
            add_role('gpl_buyer', 'GPL Buyer', array(
                'read' => true,
                'gpl_view_sites' => true,
                'gpl_add_to_wishlist' => true
            ));
        }
        
        // Add capabilities to admin
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('gpl_add_site');
            $admin->add_cap('gpl_edit_own_sites');
            $admin->add_cap('gpl_delete_own_sites');
            $admin->add_cap('gpl_view_sites');
            $admin->add_cap('gpl_manage_all_sites');
        }
    }
    
    /**
     * Assign default role on registration
     */
    public static function assign_default_role($user_id) {
        if (isset($_POST['gpl_role']) && in_array($_POST['gpl_role'], array('seller', 'buyer'))) {
            $role = $_POST['gpl_role'] === 'seller' ? 'gpl_seller' : 'gpl_buyer';
            $user = new WP_User($user_id);
            $user->set_role($role);
        }
    }
    
    /**
     * Check if user is seller
     */
    public static function is_seller($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        return in_array('gpl_seller', (array) $user->roles) || 
               in_array('administrator', (array) $user->roles);
    }
    
    /**
     * Check if user is buyer
     */
    public static function is_buyer($user_id = 0) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        return in_array('gpl_buyer', (array) $user->roles);
    }
    
    /**
     * Get user role label
     */
    public static function get_role_label($user_id = 0) {
        if (self::is_seller($user_id)) {
            return 'Seller';
        }
        if (self::is_buyer($user_id)) {
            return 'Buyer';
        }
        return 'User';
    }
}
