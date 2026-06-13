<?php

if (!defined('ABSPATH')) exit;

// Register admin menu
add_action('admin_menu', function () {
    add_menu_page(
        'GPL Sites',
        'GPL Sites',
        'manage_options',
        'gplm-moderation',
        'gplm_render_moderation',
        'dashicons-networking',
        26
    );
    
    // Add submenu for All Sites (rename default)
    add_submenu_page(
        'gplm-moderation',
        'All Sites',
        'All Sites',
        'manage_options',
        'gplm-moderation'
    );
    
    // Add submenu for Buyer Wishlists
    add_submenu_page(
        'gplm-moderation',
        'Buyer Wishlists',
        'Buyer Wishlists',
        'manage_options',
        'gplm-wishlists',
        'gplm_render_wishlists'
    );
    
    // Add submenu for Sites by Seller
    add_submenu_page(
        'gplm-moderation',
        'Sites by Seller',
        'Sites by Seller',
        'manage_options',
        'gplm-sellers',
        'gplm_render_sellers'
    );
    
    // Add submenu for editing (hidden)
    add_submenu_page(
        'gplm-moderation',
        'Edit Site',
        'Edit Site',
        'manage_options',
        'gplm-edit-site',
        'gplm_render_edit_site'
    );
});

// Hide the edit submenu but keep it functional
add_action('admin_head', function() {
    echo '<style>#toplevel_page_gplm-moderation .wp-submenu a[href*="gplm-edit-site"] { display: none; }</style>';
});

/**
 * Render the main moderation page
 * FIX: Added pagination support
 */
function gplm_render_moderation() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'gpl_sites';
    
    // Get filter parameters
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // FIX: Add pagination parameters
    $per_page = 20; // Sites per page
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Build WHERE clause
    $where = "WHERE 1=1";
    $where_values = array();
    
    if ($status_filter) {
        $where .= " AND s.status = %s";
        $where_values[] = $status_filter;
    }
    if ($search) {
        $where .= " AND (s.website_name LIKE %s OR s.website_url LIKE %s)";
        $where_values[] = '%' . $wpdb->esc_like($search) . '%';
        $where_values[] = '%' . $wpdb->esc_like($search) . '%';
    }
    
    // Get total count for pagination
    $count_query = "SELECT COUNT(*) FROM {$table} s {$where}";
    if (!empty($where_values)) {
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, $where_values));
    } else {
        $total_items = $wpdb->get_var($count_query);
    }
    $total_items = intval($total_items); // Ensure integer even if null
    
    $total_pages = max(1, ceil($total_items / $per_page));
    
    // Get sites with seller info (paginated)
    $query = "SELECT s.*, u.user_login AS seller_username, u.display_name AS seller_name
              FROM {$table} s
              LEFT JOIN {$wpdb->users} u ON u.ID = s.seller_id
              {$where}
              ORDER BY s.id DESC
              LIMIT %d OFFSET %d";
    
    $query_values = array_merge($where_values, array($per_page, $offset));
    $sites = $wpdb->get_results($wpdb->prepare($query, $query_values));
    
    // Get counts
    $counts = $wpdb->get_row(
        "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
         FROM {$table}"
    );
    
    // Fallback if table empty or doesn't exist
    if (!$counts) {
        $counts = (object) array('total' => 0, 'active' => 0, 'pending' => 0, 'rejected' => 0);
    }
    
    $frontend_url = home_url('/marketplace/');
    ?>
    <div class="wrap gplm-admin-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-networking"></span>
            GPL Sites Moderation
        </h1>
        <a href="<?php echo esc_url($frontend_url); ?>" class="page-title-action" target="_blank">View Frontend</a>
        
        <!-- Stats Cards -->
        <div class="gplm-stats-row">
            <div class="gplm-stat-card gplm-stat-total">
                <span class="gplm-stat-number"><?php echo intval($counts->total); ?></span>
                <span class="gplm-stat-label">Total Sites</span>
            </div>
            <div class="gplm-stat-card gplm-stat-active">
                <span class="gplm-stat-number"><?php echo intval($counts->active); ?></span>
                <span class="gplm-stat-label">Active</span>
            </div>
            <div class="gplm-stat-card gplm-stat-pending">
                <span class="gplm-stat-number"><?php echo intval($counts->pending); ?></span>
                <span class="gplm-stat-label">Pending</span>
            </div>
            <div class="gplm-stat-card gplm-stat-rejected">
                <span class="gplm-stat-number"><?php echo intval($counts->rejected); ?></span>
                <span class="gplm-stat-label">Rejected</span>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="gplm-filters">
            <form method="get" class="gplm-filter-form">
                <input type="hidden" name="page" value="gplm-moderation">
                
                <div class="gplm-filter-group">
                    <label>Status:</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                        <option value="rejected" <?php selected($status_filter, 'rejected'); ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="gplm-filter-group gplm-search-group">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search sites...">
                    <button type="submit" class="button">Search</button>
                </div>
            </form>
            
            <div class="gplm-bulk-actions">
                <button id="gplm-bulk-approve" class="button button-primary" disabled>Bulk Approve</button>
                <button id="gplm-bulk-reject" class="button" disabled>Bulk Reject</button>
                <button id="gplm-bulk-delete" class="button button-link-delete" disabled>Bulk Delete</button>
            </div>
        </div>
        
        <!-- Sites Table -->
        <table class="wp-list-table widefat fixed striped gplm-sites-table">
            <thead>
                <tr>
                    <th class="check-column"><input type="checkbox" id="gplm-check-all"></th>
                    <th class="column-site">Website</th>
                    <th class="column-seller">Seller</th>
                    <th class="column-metrics">DA / DR / AS</th>
                    <th class="column-traffic">Traffic</th>
                    <th class="column-price">Price</th>
                    <th class="column-niche">Niche</th>
                    <th class="column-status">Status</th>
                    <th class="column-date">Added</th>
                    <th class="column-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($sites)): ?>
                <tr>
                    <td colspan="10" class="gplm-no-sites">No sites found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($sites as $site): 
                    $domain = esc_html($site->website_url);
                    $name = esc_html($site->website_name ?: $site->website_url);
                    // Clean domain for URL
                    $clean_domain = preg_replace('/^https?:\/\//', '', $site->website_url);
                    $clean_domain = preg_replace('/^www\./', '', $clean_domain);
                    $clean_domain = rtrim($clean_domain, '/');
                    $view_url = home_url("/site/" . $clean_domain . "/");
                    $edit_url = admin_url("admin.php?page=gplm-edit-site&id=" . intval($site->id));
                    $favicon = function_exists('gpl_get_local_favicon') ? gpl_get_local_favicon($site->website_url) : '';
                ?>
                <tr data-id="<?php echo intval($site->id); ?>">
                    <td class="check-column"><input type="checkbox" class="gplm-row-check"></td>
                    <td class="column-site">
                        <div class="gplm-site-info">
                            <img src="<?php echo esc_attr($favicon); ?>" 
                                 class="gplm-favicon" alt="" width="16" height="16">
                            <div class="gplm-site-details">
                                <strong class="gplm-site-name"><?php echo $name; ?></strong>
                                <span class="gplm-site-url"><?php echo $domain; ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="column-seller">
                        <?php if ($site->seller_name): ?>
                            <span class="gplm-seller"><?php echo esc_html($site->seller_name); ?></span>
                        <?php else: ?>
                            <span class="gplm-no-seller">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="column-metrics">
                        <span class="gplm-metric gplm-da"><?php echo intval($site->domain_authority); ?></span>
                        <span class="gplm-metric-sep">/</span>
                        <span class="gplm-metric gplm-dr"><?php echo intval($site->domain_rating); ?></span>
                        <span class="gplm-metric-sep">/</span>
                        <span class="gplm-metric gplm-as"><?php echo intval($site->authority_score); ?></span>
                    </td>
                    <td class="column-traffic">
                        <?php echo number_format(intval($site->ahrefs_traffic)); ?>
                    </td>
                    <td class="column-price">
                        <strong>$<?php echo number_format(floatval($site->price), 2); ?></strong>
                    </td>
                    <td class="column-niche">
                        <span class="gplm-niche-tag"><?php echo esc_html($site->niche ?: 'General'); ?></span>
                    </td>
                    <td class="column-status">
                        <span class="gplm-status gplm-status-<?php echo esc_attr($site->status); ?>">
                            <?php echo ucfirst(esc_html($site->status)); ?>
                        </span>
                    </td>
                    <td class="column-date">
                        <?php echo date('M j, Y', strtotime($site->created_at)); ?>
                    </td>
                    <td class="column-actions">
                        <div class="gplm-actions">
                            <a href="<?php echo esc_url($view_url); ?>" class="button button-small gplm-btn-view" target="_blank" title="View">
                                <span class="dashicons dashicons-visibility"></span>
                            </a>
                            <a href="<?php echo esc_url($edit_url); ?>" class="button button-small gplm-btn-edit" title="Edit">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                            <?php if ($site->status !== 'active'): ?>
                            <button class="button button-small button-primary gplm-btn-approve" title="Approve">
                                <span class="dashicons dashicons-yes"></span>
                            </button>
                            <?php endif; ?>
                            <?php if ($site->status !== 'rejected'): ?>
                            <button class="button button-small gplm-btn-reject" title="Reject">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                            <?php endif; ?>
                            <button class="button button-small button-link-delete gplm-btn-delete" title="Delete">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        
        <!-- FIX: Added Pagination -->
        <div class="gplm-table-footer">
            <span class="gplm-showing">
                <?php 
                $start = $offset + 1;
                $end = min($offset + $per_page, $total_items);
                if ($total_items > 0) {
                    printf('Showing %d-%d of %d site(s)', $start, $end, $total_items);
                } else {
                    echo 'No sites found';
                }
                ?>
            </span>
            
            <?php if ($total_pages > 1): ?>
            <div class="gplm-pagination">
                <?php
                $base_url = admin_url('admin.php?page=gplm-moderation');
                if ($status_filter) {
                    $base_url = add_query_arg('status', $status_filter, $base_url);
                }
                if ($search) {
                    $base_url = add_query_arg('s', $search, $base_url);
                }
                ?>
                
                <!-- First Page -->
                <?php if ($current_page > 1): ?>
                <a href="<?php echo esc_url(add_query_arg('paged', 1, $base_url)); ?>" class="button gplm-pagination-link" title="First Page">&laquo;</a>
                <?php else: ?>
                <span class="button disabled">&laquo;</span>
                <?php endif; ?>
                
                <!-- Previous Page -->
                <?php if ($current_page > 1): ?>
                <a href="<?php echo esc_url(add_query_arg('paged', $current_page - 1, $base_url)); ?>" class="button gplm-pagination-link" title="Previous Page">&lsaquo;</a>
                <?php else: ?>
                <span class="button disabled">&lsaquo;</span>
                <?php endif; ?>
                
                <!-- Page Info -->
                <span class="gplm-page-info">
                    Page <strong><?php echo $current_page; ?></strong> of <strong><?php echo $total_pages; ?></strong>
                </span>
                
                <!-- Next Page -->
                <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo esc_url(add_query_arg('paged', $current_page + 1, $base_url)); ?>" class="button gplm-pagination-link" title="Next Page">&rsaquo;</a>
                <?php else: ?>
                <span class="button disabled">&rsaquo;</span>
                <?php endif; ?>
                
                <!-- Last Page -->
                <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo esc_url(add_query_arg('paged', $total_pages, $base_url)); ?>" class="button gplm-pagination-link" title="Last Page">&raquo;</a>
                <?php else: ?>
                <span class="button disabled">&raquo;</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Render the edit site page
 */
function gplm_render_edit_site() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'gpl_sites';
    
    $site_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if (!$site_id) {
        echo '<div class="wrap"><div class="notice notice-error"><p>Invalid site ID.</p></div></div>';
        return;
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gplm_edit_nonce'])) {
        if (!wp_verify_nonce($_POST['gplm_edit_nonce'], 'gplm_edit_site_' . $site_id)) {
            wp_die('Security check failed');
        }
        
        // Sanitize and prepare data
        $update_data = array(
            'website_name' => sanitize_text_field($_POST['website_name'] ?? ''),
            'niche' => sanitize_text_field($_POST['niche'] ?? ''),
            'domain_authority' => intval($_POST['domain_authority'] ?? 0),
            'domain_rating' => intval($_POST['domain_rating'] ?? 0),
            'authority_score' => intval($_POST['authority_score'] ?? 0),
            'trust_flow' => intval($_POST['trust_flow'] ?? 0),
            'citation_flow' => intval($_POST['citation_flow'] ?? 0),
            'spam_score' => intval($_POST['spam_score'] ?? 0),
            'domain_age' => sanitize_text_field($_POST['domain_age'] ?? ''),
            'ahrefs_traffic' => intval($_POST['ahrefs_traffic'] ?? 0),
            'ahrefs_keywords' => intval($_POST['ahrefs_keywords'] ?? 0),
            'semrush_traffic' => intval($_POST['semrush_traffic'] ?? 0),
            'semrush_keywords' => intval($_POST['semrush_keywords'] ?? 0),
            'similarweb_traffic' => intval($_POST['similarweb_traffic'] ?? 0),
            'price' => floatval($_POST['price'] ?? 0),
            'sale_price' => floatval($_POST['sale_price'] ?? 0),
            'currency' => sanitize_text_field($_POST['currency'] ?? 'USD'),
            'link_type' => sanitize_text_field($_POST['link_type'] ?? 'DoFollow'),
            'link_validity' => sanitize_text_field($_POST['link_validity'] ?? 'Permanent'),
            'backlinks_allowed' => intval($_POST['backlinks_allowed'] ?? 1),
            'tat' => sanitize_text_field($_POST['tat'] ?? '3-5 days'),
            'word_count' => intval($_POST['word_count'] ?? 500),
            'content_written_by' => sanitize_text_field($_POST['content_written_by'] ?? 'Both'),
            'sample_url' => esc_url_raw($_POST['sample_url'] ?? ''),
            'guidelines' => sanitize_textarea_field($_POST['guidelines'] ?? ''),
            'language' => sanitize_text_field($_POST['language'] ?? 'English'),
            'country' => sanitize_text_field($_POST['country'] ?? 'United States'),
            'country_code' => sanitize_text_field($_POST['country_code'] ?? 'US'),
            'traffic_countries' => sanitize_text_field($_POST['traffic_countries'] ?? ''),
            'tld' => sanitize_text_field($_POST['tld'] ?? ''),
            'google_news' => isset($_POST['google_news']) ? 1 : 0,
            'marked_sponsored' => isset($_POST['marked_sponsored']) ? 1 : 0,
            'sports_gaming_allowed' => isset($_POST['sports_gaming_allowed']) ? 1 : 0,
            'pharmacy_allowed' => isset($_POST['pharmacy_allowed']) ? 1 : 0,
            'crypto_allowed' => isset($_POST['crypto_allowed']) ? 1 : 0,
            'foreign_lang_allowed' => isset($_POST['foreign_lang_allowed']) ? 1 : 0,
            'status' => sanitize_text_field($_POST['status'] ?? 'active'),
            'featured' => isset($_POST['featured']) ? 1 : 0,
            'updated_at' => current_time('mysql'),
        );
        
        $result = $wpdb->update($table, $update_data, array('id' => $site_id));
        
        if ($result !== false) {
            echo '<div class="notice notice-success is-dismissible"><p>Site updated successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error updating site.</p></div>';
        }
    }
    
    // Get site data
    $site = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $site_id), ARRAY_A);
    
    if (!$site) {
        echo '<div class="wrap"><div class="notice notice-error"><p>Site not found.</p></div></div>';
        return;
    }
    
    // Get seller info
    $seller = get_userdata($site['seller_id']);
    $seller_name = $seller ? $seller->display_name : 'Unknown';
    ?>
    <div class="wrap gplm-edit-wrap">
        <h1>
            <a href="<?php echo admin_url('admin.php?page=gplm-moderation'); ?>" class="gplm-back-link">← Back</a>
            Edit Site: <?php echo esc_html($site['website_name'] ?: $site['website_url']); ?>
        </h1>
        
        <form method="post" class="gplm-edit-form">
            <?php wp_nonce_field('gplm_edit_site_' . $site_id, 'gplm_edit_nonce'); ?>
            
            <div class="gplm-edit-grid">
                <!-- Main Column -->
                <div class="gplm-edit-main">
                    
                    <!-- Basic Info -->
                    <div class="gplm-edit-section">
                        <h2>Basic Information</h2>
                        <table class="form-table">
                            <tr>
                                <th><label>Website URL</label></th>
                                <td>
                                    <input type="text" value="<?php echo esc_attr($site['website_url']); ?>" class="regular-text" readonly disabled>
                                    <p class="description">URL cannot be changed</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="website_name">Website Name</label></th>
                                <td><input type="text" name="website_name" id="website_name" value="<?php echo esc_attr($site['website_name']); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="niche">Niche</label></th>
                                <td><input type="text" name="niche" id="niche" value="<?php echo esc_attr($site['niche']); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label>Seller</label></th>
                                <td><strong><?php echo esc_html($seller_name); ?></strong> (ID: <?php echo intval($site['seller_id']); ?>)</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- SEO Metrics -->
                    <div class="gplm-edit-section">
                        <h2>SEO Metrics</h2>
                        <table class="form-table">
                            <tr>
                                <th><label for="domain_authority">DA (Moz)</label></th>
                                <td><input type="number" name="domain_authority" id="domain_authority" value="<?php echo intval($site['domain_authority']); ?>" min="0" max="100" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="domain_rating">DR (Ahrefs)</label></th>
                                <td><input type="number" name="domain_rating" id="domain_rating" value="<?php echo intval($site['domain_rating']); ?>" min="0" max="100" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="authority_score">AS (SEMrush)</label></th>
                                <td><input type="number" name="authority_score" id="authority_score" value="<?php echo intval($site['authority_score']); ?>" min="0" max="100" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="trust_flow">Trust Flow (Majestic)</label></th>
                                <td><input type="number" name="trust_flow" id="trust_flow" value="<?php echo intval($site['trust_flow']); ?>" min="0" max="100" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="citation_flow">Citation Flow (Majestic)</label></th>
                                <td><input type="number" name="citation_flow" id="citation_flow" value="<?php echo intval($site['citation_flow']); ?>" min="0" max="100" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="spam_score">Spam Score</label></th>
                                <td><input type="number" name="spam_score" id="spam_score" value="<?php echo intval($site['spam_score']); ?>" min="0" max="100" class="small-text">%</td>
                            </tr>
                            <tr>
                                <th><label for="domain_age">Domain Age</label></th>
                                <td><input type="text" name="domain_age" id="domain_age" value="<?php echo esc_attr($site['domain_age']); ?>" class="regular-text" placeholder="e.g., 5 years"></td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Traffic -->
                    <div class="gplm-edit-section">
                        <h2>Traffic Data</h2>
                        <table class="form-table">
                            <tr>
                                <th><label for="ahrefs_traffic">Ahrefs Traffic</label></th>
                                <td><input type="number" name="ahrefs_traffic" id="ahrefs_traffic" value="<?php echo intval($site['ahrefs_traffic']); ?>" min="0" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="ahrefs_keywords">Ahrefs Keywords</label></th>
                                <td><input type="number" name="ahrefs_keywords" id="ahrefs_keywords" value="<?php echo intval($site['ahrefs_keywords']); ?>" min="0" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="semrush_traffic">SEMrush Traffic</label></th>
                                <td><input type="number" name="semrush_traffic" id="semrush_traffic" value="<?php echo intval($site['semrush_traffic']); ?>" min="0" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="semrush_keywords">SEMrush Keywords</label></th>
                                <td><input type="number" name="semrush_keywords" id="semrush_keywords" value="<?php echo intval($site['semrush_keywords']); ?>" min="0" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="similarweb_traffic">SimilarWeb Traffic</label></th>
                                <td><input type="number" name="similarweb_traffic" id="similarweb_traffic" value="<?php echo intval($site['similarweb_traffic']); ?>" min="0" class="regular-text"></td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Pricing -->
                    <div class="gplm-edit-section">
                        <h2>Pricing</h2>
                        <table class="form-table">
                            <tr>
                                <th><label for="price">Regular Price</label></th>
                                <td><input type="number" name="price" id="price" value="<?php echo floatval($site['price']); ?>" min="0" step="0.01" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="sale_price">Sale Price</label></th>
                                <td><input type="number" name="sale_price" id="sale_price" value="<?php echo floatval($site['sale_price']); ?>" min="0" step="0.01" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="currency">Currency</label></th>
                                <td>
                                    <select name="currency" id="currency">
                                        <option value="USD" <?php selected($site['currency'], 'USD'); ?>>USD ($)</option>
                                        <option value="EUR" <?php selected($site['currency'], 'EUR'); ?>>EUR (€)</option>
                                        <option value="GBP" <?php selected($site['currency'], 'GBP'); ?>>GBP (£)</option>
                                        <option value="INR" <?php selected($site['currency'], 'INR'); ?>>INR (₹)</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Link Details -->
                    <div class="gplm-edit-section">
                        <h2>Link & Posting Details</h2>
                        <table class="form-table">
                            <tr>
                                <th><label for="link_type">Link Type</label></th>
                                <td>
                                    <select name="link_type" id="link_type">
                                        <option value="DoFollow" <?php selected($site['link_type'], 'DoFollow'); ?>>DoFollow</option>
                                        <option value="NoFollow" <?php selected($site['link_type'], 'NoFollow'); ?>>NoFollow</option>
                                        <option value="Both" <?php selected($site['link_type'], 'Both'); ?>>Both</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="link_validity">Link Validity</label></th>
                                <td>
                                    <select name="link_validity" id="link_validity">
                                        <option value="Permanent" <?php selected($site['link_validity'], 'Permanent'); ?>>Permanent</option>
                                        <option value="1 Year" <?php selected($site['link_validity'], '1 Year'); ?>>1 Year</option>
                                        <option value="2 Years" <?php selected($site['link_validity'], '2 Years'); ?>>2 Years</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="backlinks_allowed">Backlinks Allowed</label></th>
                                <td><input type="number" name="backlinks_allowed" id="backlinks_allowed" value="<?php echo intval($site['backlinks_allowed']); ?>" min="1" max="10" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="tat">Turnaround Time</label></th>
                                <td><input type="text" name="tat" id="tat" value="<?php echo esc_attr($site['tat']); ?>" class="regular-text" placeholder="e.g., 3-5 days"></td>
                            </tr>
                            <tr>
                                <th><label for="word_count">Minimum Word Count</label></th>
                                <td><input type="number" name="word_count" id="word_count" value="<?php echo intval($site['word_count']); ?>" min="100" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="content_written_by">Content Written By</label></th>
                                <td>
                                    <select name="content_written_by" id="content_written_by">
                                        <option value="Buyer" <?php selected($site['content_written_by'], 'Buyer'); ?>>Buyer</option>
                                        <option value="Seller" <?php selected($site['content_written_by'], 'Seller'); ?>>Seller</option>
                                        <option value="Both" <?php selected($site['content_written_by'], 'Both'); ?>>Both</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="sample_url">Sample Post URL</label></th>
                                <td><input type="url" name="sample_url" id="sample_url" value="<?php echo esc_attr($site['sample_url']); ?>" class="large-text"></td>
                            </tr>
                            <tr>
                                <th><label for="guidelines">Guidelines</label></th>
                                <td><textarea name="guidelines" id="guidelines" rows="4" class="large-text"><?php echo esc_textarea($site['guidelines']); ?></textarea></td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Location -->
                    <div class="gplm-edit-section">
                        <h2>Location & Language</h2>
                        <table class="form-table">
                            <tr>
                                <th><label for="language">Language</label></th>
                                <td><input type="text" name="language" id="language" value="<?php echo esc_attr($site['language']); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="country">Country</label></th>
                                <td><input type="text" name="country" id="country" value="<?php echo esc_attr($site['country']); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="country_code">Country Code</label></th>
                                <td><input type="text" name="country_code" id="country_code" value="<?php echo esc_attr($site['country_code']); ?>" class="small-text" maxlength="2"></td>
                            </tr>
                            <tr>
                                <th><label for="tld">TLD</label></th>
                                <td><input type="text" name="tld" id="tld" value="<?php echo esc_attr($site['tld']); ?>" class="small-text" placeholder=".com"></td>
                            </tr>
                            <tr>
                                <th><label for="traffic_countries">Traffic Countries</label></th>
                                <td>
                                    <input type="text" name="traffic_countries" id="traffic_countries" value="<?php echo esc_attr($site['traffic_countries']); ?>" class="large-text">
                                    <p class="description">Format: US:50,UK:20,IN:15</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                </div>
                
                <!-- Sidebar Column -->
                <div class="gplm-edit-sidebar">
                    
                    <!-- Publish Box -->
                    <div class="gplm-edit-section gplm-publish-box">
                        <h2>Publish</h2>
                        <div class="gplm-publish-content">
                            <p>
                                <label for="status"><strong>Status:</strong></label>
                                <select name="status" id="status">
                                    <option value="active" <?php selected($site['status'], 'active'); ?>>Active</option>
                                    <option value="pending" <?php selected($site['status'], 'pending'); ?>>Pending</option>
                                    <option value="rejected" <?php selected($site['status'], 'rejected'); ?>>Rejected</option>
                                </select>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="featured" value="1" <?php checked($site['featured'], 1); ?>>
                                    Featured Site
                                </label>
                            </p>
                            <p class="gplm-dates">
                                <span>Created: <?php echo date('M j, Y g:i a', strtotime($site['created_at'])); ?></span><br>
                                <?php if ($site['updated_at']): ?>
                                <span>Updated: <?php echo date('M j, Y g:i a', strtotime($site['updated_at'])); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="gplm-publish-actions">
                            <a href="<?php echo admin_url('admin.php?page=gplm-moderation'); ?>" class="button">Cancel</a>
                            <button type="submit" class="button button-primary button-large">Update Site</button>
                        </div>
                    </div>
                    
                    <!-- Features Box -->
                    <div class="gplm-edit-section">
                        <h2>Features & Restrictions</h2>
                        <p><label><input type="checkbox" name="google_news" value="1" <?php checked($site['google_news'], 1); ?>> Google News Approved</label></p>
                        <p><label><input type="checkbox" name="marked_sponsored" value="1" <?php checked($site['marked_sponsored'], 1); ?>> Marked as Sponsored</label></p>
                        <hr>
                        <p><strong>Allowed Content:</strong></p>
                        <p><label><input type="checkbox" name="sports_gaming_allowed" value="1" <?php checked($site['sports_gaming_allowed'], 1); ?>> Sports/Gaming</label></p>
                        <p><label><input type="checkbox" name="pharmacy_allowed" value="1" <?php checked($site['pharmacy_allowed'], 1); ?>> Pharmacy/CBD</label></p>
                        <p><label><input type="checkbox" name="crypto_allowed" value="1" <?php checked($site['crypto_allowed'], 1); ?>> Crypto/Finance</label></p>
                        <p><label><input type="checkbox" name="foreign_lang_allowed" value="1" <?php checked($site['foreign_lang_allowed'], 1); ?>> Foreign Language</label></p>
                    </div>
                    
                </div>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Render Buyer Wishlists Page
 * FIX: Added pagination
 */
function gplm_render_wishlists() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }
    
    global $wpdb;
    $wishlist_table = $wpdb->prefix . 'gpl_wishlist';
    $sites_table = $wpdb->prefix . 'gpl_sites';
    
    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['wishlist_id'])) {
        $wishlist_id = intval($_GET['wishlist_id']);
        if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_wishlist_' . $wishlist_id)) {
            $wpdb->delete($wishlist_table, array('id' => $wishlist_id));
            echo '<div class="notice notice-success"><p>Wishlist item removed.</p></div>';
        }
    }
    
    // Get filter
    $buyer_filter = isset($_GET['buyer_id']) ? intval($_GET['buyer_id']) : 0;
    
    // Pagination
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Build query
    $where = "";
    $where_values = array();
    if ($buyer_filter) {
        $where = "WHERE w.user_id = %d";
        $where_values[] = $buyer_filter;
    }
    
    // Get total count
    $count_query = "SELECT COUNT(*) FROM {$wishlist_table} w {$where}";
    if (!empty($where_values)) {
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, $where_values));
    } else {
        $total_items = $wpdb->get_var($count_query);
    }
    $total_items = intval($total_items); // Ensure integer even if null
    $total_pages = max(1, ceil($total_items / $per_page));
    
    // Get wishlist items with site and user info
    $query = "SELECT w.*, s.website_url, s.website_name, s.price, s.niche, 
                     u.display_name as buyer_name, u.user_email as buyer_email
              FROM {$wishlist_table} w
              LEFT JOIN {$sites_table} s ON s.id = w.site_id
              LEFT JOIN {$wpdb->users} u ON u.ID = w.user_id
              {$where}
              ORDER BY w.created_at DESC
              LIMIT %d OFFSET %d";
    
    $query_values = array_merge($where_values, array($per_page, $offset));
    $items = $wpdb->get_results($wpdb->prepare($query, $query_values));
    
    // Get all buyers with wishlists for filter dropdown
    $buyers = $wpdb->get_results(
        "SELECT DISTINCT u.ID, u.display_name, u.user_email, COUNT(w.id) as wishlist_count
         FROM {$wishlist_table} w
         JOIN {$wpdb->users} u ON u.ID = w.user_id
         GROUP BY u.ID
         ORDER BY u.display_name"
    );
    
    ?>
    <div class="wrap gplm-admin-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-heart"></span>
            Buyer Wishlists
        </h1>
        
        <!-- Filter -->
        <div class="gplm-filters" style="margin: 20px 0;">
            <form method="get">
                <input type="hidden" name="page" value="gplm-wishlists">
                <select name="buyer_id" onchange="this.form.submit()">
                    <option value="">All Buyers</option>
                    <?php foreach ($buyers as $buyer): ?>
                    <option value="<?php echo $buyer->ID; ?>" <?php selected($buyer_filter, $buyer->ID); ?>>
                        <?php echo esc_html($buyer->display_name); ?> (<?php echo $buyer->wishlist_count; ?> items)
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        
        <!-- Stats -->
        <div class="gplm-stats-row">
            <div class="gplm-stat-card gplm-stat-total">
                <span class="gplm-stat-number"><?php echo $total_items; ?></span>
                <span class="gplm-stat-label">Total Wishlist Items</span>
            </div>
            <div class="gplm-stat-card gplm-stat-active">
                <span class="gplm-stat-number"><?php echo count($buyers); ?></span>
                <span class="gplm-stat-label">Buyers with Wishlists</span>
            </div>
        </div>
        
        <!-- Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Buyer</th>
                    <th>Site</th>
                    <th>Niche</th>
                    <th>Price</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr><td colspan="6">No wishlist items found.</td></tr>
                <?php else: ?>
                <?php foreach ($items as $item): 
                    $favicon = function_exists('gpl_get_local_favicon') ? gpl_get_local_favicon($item->website_url) : '';
                    $delete_url = wp_nonce_url(
                        admin_url('admin.php?page=gplm-wishlists&action=delete&wishlist_id=' . $item->id),
                        'delete_wishlist_' . $item->id
                    );
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($item->buyer_name); ?></strong><br>
                        <small><?php echo esc_html($item->buyer_email); ?></small>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <?php if ($favicon): ?>
                            <img src="<?php echo $favicon; ?>" width="16" height="16" alt="">
                            <?php endif; ?>
                            <div>
                                <strong><?php echo esc_html($item->website_name ?: $item->website_url); ?></strong><br>
                                <small><?php echo esc_html($item->website_url); ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?php echo esc_html($item->niche ?: 'General'); ?></td>
                    <td><strong>$<?php echo number_format(floatval($item->price), 2); ?></strong></td>
                    <td><?php echo date('M j, Y', strtotime($item->created_at)); ?></td>
                    <td>
                        <a href="<?php echo esc_url($delete_url); ?>" class="button button-small button-link-delete" 
                           onclick="return confirm('Remove this item from wishlist?');">
                            Remove
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="gplm-table-footer">
            <div class="gplm-pagination">
                <?php
                $base_url = admin_url('admin.php?page=gplm-wishlists');
                if ($buyer_filter) {
                    $base_url = add_query_arg('buyer_id', $buyer_filter, $base_url);
                }
                
                if ($current_page > 1): ?>
                <a href="<?php echo esc_url(add_query_arg('paged', $current_page - 1, $base_url)); ?>" class="button">&lsaquo; Previous</a>
                <?php endif; ?>
                
                <span class="gplm-page-info">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
                
                <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo esc_url(add_query_arg('paged', $current_page + 1, $base_url)); ?>" class="button">Next &rsaquo;</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render Sites by Seller Page
 * FIX: Added pagination
 */
function gplm_render_sellers() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }
    
    global $wpdb;
    $sites_table = $wpdb->prefix . 'gpl_sites';
    
    // Get filter
    $seller_filter = isset($_GET['seller_id']) ? intval($_GET['seller_id']) : 0;
    
    // Pagination
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Build query
    $where = "";
    $where_values = array();
    if ($seller_filter) {
        $where = "WHERE s.seller_id = %d";
        $where_values[] = $seller_filter;
    }
    
    // Get all sellers for filter dropdown
    $sellers = $wpdb->get_results(
        "SELECT DISTINCT u.ID, u.display_name, u.user_email, COUNT(s.id) as site_count,
                SUM(CASE WHEN s.status = 'active' THEN 1 ELSE 0 END) as active_count
         FROM {$sites_table} s
         JOIN {$wpdb->users} u ON u.ID = s.seller_id
         GROUP BY u.ID
         ORDER BY site_count DESC"
    );
    
    ?>
    <div class="wrap gplm-admin-wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-store"></span>
            Sites by Seller
        </h1>
        
        <!-- Filter -->
        <div class="gplm-filters" style="margin: 20px 0;">
            <form method="get">
                <input type="hidden" name="page" value="gplm-sellers">
                <select name="seller_id" onchange="this.form.submit()">
                    <option value="">All Sellers</option>
                    <?php foreach ($sellers as $seller): ?>
                    <option value="<?php echo $seller->ID; ?>" <?php selected($seller_filter, $seller->ID); ?>>
                        <?php echo esc_html($seller->display_name); ?> (<?php echo $seller->site_count; ?> sites)
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        
        <!-- Seller Stats Cards -->
        <?php if (!$seller_filter): ?>
        <div class="gplm-stats-row">
            <div class="gplm-stat-card gplm-stat-total">
                <span class="gplm-stat-number"><?php echo count($sellers); ?></span>
                <span class="gplm-stat-label">Total Sellers</span>
            </div>
            <div class="gplm-stat-card gplm-stat-active">
                <span class="gplm-stat-number"><?php echo array_sum(array_column($sellers, 'site_count')); ?></span>
                <span class="gplm-stat-label">Total Sites</span>
            </div>
        </div>
        
        <!-- Sellers Overview -->
        <h2>Sellers Overview</h2>
        <table class="wp-list-table widefat fixed striped" style="margin-bottom: 30px;">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Email</th>
                    <th>Total Sites</th>
                    <th>Active Sites</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sellers as $seller): ?>
                <tr>
                    <td><strong><?php echo esc_html($seller->display_name); ?></strong></td>
                    <td><?php echo esc_html($seller->user_email); ?></td>
                    <td><?php echo $seller->site_count; ?></td>
                    <td><?php echo $seller->active_count; ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=gplm-sellers&seller_id=' . $seller->ID); ?>" class="button button-small">
                            View Sites
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: 
            $current_seller = null;
            foreach ($sellers as $s) {
                if ($s->ID == $seller_filter) {
                    $current_seller = $s;
                    break;
                }
            }
            
            // Get total count for this seller
            $total_items = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$sites_table} WHERE seller_id = %d",
                $seller_filter
            ));
            $total_pages = ceil($total_items / $per_page);
            
            // Get paginated sites
            $sites = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, u.display_name as seller_name, u.user_email as seller_email
                 FROM {$sites_table} s
                 LEFT JOIN {$wpdb->users} u ON u.ID = s.seller_id
                 WHERE s.seller_id = %d
                 ORDER BY s.created_at DESC
                 LIMIT %d OFFSET %d",
                $seller_filter, $per_page, $offset
            ));
        ?>
        
        <!-- Single Seller View -->
        <div class="gplm-stats-row">
            <div class="gplm-stat-card gplm-stat-total">
                <span class="gplm-stat-number"><?php echo $current_seller ? $current_seller->site_count : 0; ?></span>
                <span class="gplm-stat-label">Total Sites</span>
            </div>
            <div class="gplm-stat-card gplm-stat-active">
                <span class="gplm-stat-number"><?php echo $current_seller ? $current_seller->active_count : 0; ?></span>
                <span class="gplm-stat-label">Active Sites</span>
            </div>
        </div>
        
        <h2>Sites by <?php echo esc_html($current_seller ? $current_seller->display_name : 'Seller'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Website</th>
                    <th>Niche</th>
                    <th>DA/DR/AS</th>
                    <th>Traffic</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sites as $site): 
                    $favicon = function_exists('gpl_get_local_favicon') ? gpl_get_local_favicon($site->website_url) : '';
                    $edit_url = admin_url('admin.php?page=gplm-edit-site&id=' . $site->id);
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <?php if ($favicon): ?>
                            <img src="<?php echo $favicon; ?>" width="16" height="16" alt="">
                            <?php endif; ?>
                            <div>
                                <strong><?php echo esc_html($site->website_name ?: $site->website_url); ?></strong><br>
                                <small><?php echo esc_html($site->website_url); ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?php echo esc_html($site->niche ?: 'General'); ?></td>
                    <td>
                        <?php echo intval($site->domain_authority); ?> / 
                        <?php echo intval($site->domain_rating); ?> / 
                        <?php echo intval($site->authority_score); ?>
                    </td>
                    <td><?php echo number_format(intval($site->ahrefs_traffic)); ?></td>
                    <td><strong>$<?php echo number_format(floatval($site->price), 2); ?></strong></td>
                    <td>
                        <span class="gplm-status gplm-status-<?php echo esc_attr($site->status); ?>">
                            <?php echo ucfirst($site->status); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="gplm-table-footer">
            <div class="gplm-pagination">
                <?php
                $base_url = admin_url('admin.php?page=gplm-sellers&seller_id=' . $seller_filter);
                
                if ($current_page > 1): ?>
                <a href="<?php echo esc_url(add_query_arg('paged', $current_page - 1, $base_url)); ?>" class="button">&lsaquo; Previous</a>
                <?php endif; ?>
                
                <span class="gplm-page-info">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
                
                <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo esc_url(add_query_arg('paged', $current_page + 1, $base_url)); ?>" class="button">Next &rsaquo;</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
    <?php
}

// Enqueue admin styles and scripts
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'gplm-') === false && strpos($hook, 'gpl-sites') === false) return;
    
    wp_enqueue_style('gplm-admin-css', GPL_SITES_URL . '/assets/admin.css', array(), '8.3.3');
    wp_enqueue_script('gplm-admin-js', GPL_SITES_URL . '/assets/admin.js', array(), '8.3.3', true);
    wp_localize_script('gplm-admin-js', 'GPLM', array(
        'ajax' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gplm_admin')
    ));
});
