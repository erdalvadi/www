<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate local SVG favicon (no external dependencies)
 */
function gpl_get_local_favicon($domain) {
    // Extract first letter
    $letter = strtoupper(substr(preg_replace('/^www\./', '', $domain), 0, 1));
    if (empty($letter) || !ctype_alpha($letter)) {
        $letter = 'W';
    }
    
    // Generate color based on domain
    $hash = crc32($domain);
    $colors = array(
        '#4f46e5', '#7c3aed', '#ec4899', '#ef4444', '#f97316',
        '#eab308', '#22c55e', '#14b8a6', '#06b6d4', '#3b82f6',
        '#8b5cf6', '#d946ef', '#f43f5e', '#0ea5e9', '#10b981'
    );
    $bg_color = $colors[abs($hash) % count($colors)];
    
    // Return data URI SVG
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">';
    $svg .= '<rect width="64" height="64" rx="12" fill="' . $bg_color . '"/>';
    $svg .= '<text x="32" y="43" font-family="Arial,sans-serif" font-size="32" font-weight="bold" fill="white" text-anchor="middle">' . $letter . '</text>';
    $svg .= '</svg>';
    
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Check if current user is admin
 */
function gpl_is_admin() {
    return current_user_can('administrator');
}

/**
 * Check if current user is seller
 */
function gpl_is_seller() {
    if (!is_user_logged_in()) return false;
    $user = wp_get_current_user();
    return in_array('gpl_seller', (array) $user->roles) || current_user_can('administrator');
}

/**
 * Check if current user is buyer
 */
function gpl_is_buyer() {
    if (!is_user_logged_in()) return false;
    $user = wp_get_current_user();
    return in_array('gpl_buyer', (array) $user->roles) || current_user_can('administrator');
}

/**
 * Get maximum sites with URLs for current user
 * Admin = unlimited, others = 15
 */
function gpl_get_url_limit() {
    return gpl_is_admin() ? 0 : 15; // 0 = unlimited
}

/**
 * Check if user can see URL for a site at given position
 */
function gpl_can_see_url($position) {
    if (gpl_is_admin()) return true;
    return $position <= 15;
}

/**
 * Get site detail URL
 * Uses actual domain: /site/example.com/
 */
function gpl_get_site_detail_url($site) {
    $domain = is_array($site) ? $site['website_url'] : $site->website_url;
    // Clean the domain
    $domain = preg_replace('/^https?:\/\//', '', $domain);
    $domain = preg_replace('/^www\./', '', $domain);
    $domain = rtrim($domain, '/');
    return home_url('/site/' . $domain . '/');
}

/**
 * Format number for display
 * 1000 -> 1K, 1000000 -> 1M
 */
function gpl_format_number($num) {
    if (!is_numeric($num)) return '0';
    $num = (int) $num;
    
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return number_format($num);
}

/**
 * Format price for display
 */
function gpl_format_price($price) {
    if (!is_numeric($price)) return '$0';
    return '$' . number_format((float) $price, 0);
}

/**
 * Get time ago string
 */
function gpl_time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    if ($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
    return date('M j, Y', $time);
}
