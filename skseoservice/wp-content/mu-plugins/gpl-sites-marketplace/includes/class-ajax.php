<?php
/**
 * AJAX Handler Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class GPL_Sites_AJAX {
    
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->register_hooks();
    }

    private function register_hooks() {
        $public_actions = array(
            'gpl_toggle_wishlist',
            'gpl_filter_sites',
            'gpl_get_site_details',
            'gpl_validate_email',
            'gpl_login',
            'gpl_register',
        );
        
        $private_actions = array(
            'gpl_add_site',
            'gpl_update_site',
            'gpl_delete_site',
            'gpl_update_profile',
            'gpl_purchase_site',
            'gpl_admin_update_status',
            'gpl_admin_delete_site',
            'gpl_admin_bulk_update',
            'gpl_admin_bulk_delete',
        );

        foreach ($public_actions as $action) {
            add_action('wp_ajax_' . $action, array($this, $action));
            add_action('wp_ajax_nopriv_' . $action, array($this, $action));
        }

        foreach ($private_actions as $action) {
            add_action('wp_ajax_' . $action, array($this, $action));
        }
    }

    private function verify_nonce($nonce_name = 'nonce') {
        $nonce = isset($_POST[$nonce_name]) ? sanitize_text_field($_POST[$nonce_name]) : '';
        
        if (wp_verify_nonce($nonce, 'gpl_sites_nonce')) {
            return true;
        }
        if (wp_verify_nonce($nonce, 'gplm_admin')) {
            return true;
        }
        
        return false;
    }

    private function send_error($message, $code = 400) {
        wp_send_json_error(array('message' => $message), $code);
    }

    private function send_success($data = array()) {
        wp_send_json_success($data);
    }

    public function gpl_toggle_wishlist() {
        if (!$this->verify_nonce()) {
            $this->send_error('Security check failed', 403);
        }

        if (!is_user_logged_in()) {
            $this->send_error('Please login to add to wishlist', 401);
        }

        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        if (!$site_id) {
            $site_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        }
        
        if (!$site_id) {
            $this->send_error('Invalid site ID');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gpl_wishlist';
        $user_id = get_current_user_id();

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND site_id = %d",
            $user_id, $site_id
        ));

        if ($exists) {
            $wpdb->delete($table, array('user_id' => $user_id, 'site_id' => $site_id));
            $this->send_success(array(
                'in_wishlist' => false,
                'message' => 'Removed from wishlist'
            ));
        } else {
            $wpdb->insert($table, array(
                'user_id' => $user_id,
                'site_id' => $site_id,
                'created_at' => current_time('mysql')
            ));
            $this->send_success(array(
                'in_wishlist' => true,
                'message' => 'Added to wishlist'
            ));
        }
    }

    public function gpl_filter_sites() {
        global $wpdb;
        $table = $wpdb->prefix . 'gpl_sites';

        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 24;
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $offset = ($page - 1) * $per_page;

        $where = array("status = 'active'");
        $values = array();

        if (!empty($_POST['niche'])) {
            $where[] = "niche LIKE %s";
            $values[] = '%' . $wpdb->esc_like(sanitize_text_field($_POST['niche'])) . '%';
        }

        if (!empty($_POST['search'])) {
            $search = '%' . $wpdb->esc_like(sanitize_text_field($_POST['search'])) . '%';
            $where[] = "(website_name LIKE %s OR website_url LIKE %s)";
            $values[] = $search;
            $values[] = $search;
        }

        if (!empty($_POST['language'])) {
            $where[] = "language = %s";
            $values[] = sanitize_text_field($_POST['language']);
        }

        if (!empty($_POST['country'])) {
            $where[] = "country = %s";
            $values[] = sanitize_text_field($_POST['country']);
        }

        if (!empty($_POST['link_type'])) {
            $where[] = "link_type = %s";
            $values[] = sanitize_text_field($_POST['link_type']);
        }

        if (!empty($_POST['da_min'])) {
            $where[] = "domain_authority >= %d";
            $values[] = intval($_POST['da_min']);
        }

        if (!empty($_POST['da_max']) && intval($_POST['da_max']) < 100) {
            $where[] = "domain_authority <= %d";
            $values[] = intval($_POST['da_max']);
        }

        if (!empty($_POST['dr_min'])) {
            $where[] = "domain_rating >= %d";
            $values[] = intval($_POST['dr_min']);
        }

        if (!empty($_POST['dr_max']) && intval($_POST['dr_max']) < 100) {
            $where[] = "domain_rating <= %d";
            $values[] = intval($_POST['dr_max']);
        }

        if (!empty($_POST['price_min'])) {
            $where[] = "price >= %d";
            $values[] = intval($_POST['price_min']);
        }

        if (!empty($_POST['price_max']) && intval($_POST['price_max']) > 0) {
            $where[] = "price <= %d";
            $values[] = intval($_POST['price_max']);
        }

        $where_sql = "WHERE " . implode(" AND ", $where);

        $order_by = "ORDER BY featured DESC, created_at DESC";
        if (!empty($_POST['sort'])) {
            $sort = sanitize_text_field($_POST['sort']);
            $order = !empty($_POST['order']) && strtoupper($_POST['order']) === 'ASC' ? 'ASC' : 'DESC';
            $allowed_sort = array('domain_authority', 'domain_rating', 'price', 'ahrefs_traffic', 'created_at', 'updated_at');
            if (in_array($sort, $allowed_sort)) {
                $order_by = "ORDER BY {$sort} {$order}";
            }
        }

        $count_query = "SELECT COUNT(*) FROM {$table} {$where_sql}";
        $total = empty($values) ? $wpdb->get_var($count_query) : $wpdb->get_var($wpdb->prepare($count_query, $values));

        $query = "SELECT * FROM {$table} {$where_sql} {$order_by} LIMIT %d OFFSET %d";
        $query_values = array_merge($values, array($per_page, $offset));
        $sites = $wpdb->get_results($wpdb->prepare($query, $query_values), ARRAY_A);

        $wishlist_ids = array();
        if (is_user_logged_in()) {
            $wishlist_ids = GPL_Sites_Database::get_user_wishlist_ids(get_current_user_id());
        }

        ob_start();
        if (!empty($sites)) {
            foreach ($sites as $site) {
                $in_wishlist = in_array($site['id'], $wishlist_ids);
                $this->render_site_card($site, $in_wishlist);
            }
        } else {
            echo '<div class="gpl-no-results"><p>No sites found matching your criteria.</p></div>';
        }
        $html = ob_get_clean();

        $this->send_success(array(
            'html' => $html,
            'sites' => $sites,
            'total' => intval($total),
            'showing' => count($sites),
            'pages' => ceil($total / $per_page),
            'current_page' => $page
        ));
    }

    private function render_site_card($site, $in_wishlist = false) {
        $da = intval($site['domain_authority'] ?? 0);
        $dr = intval($site['domain_rating'] ?? 0);
        $traffic = intval($site['ahrefs_traffic'] ?? 0);
        $price = floatval($site['price'] ?? 0);
        $website_url = esc_attr($site['website_url'] ?? '');
        $website_name = esc_html($site['website_name'] ?? $website_url);
        $niche = esc_html($site['niche'] ?? 'General');
        $site_id = intval($site['id']);
        
        $wishlist_class = $in_wishlist ? 'gpl-wishlisted' : '';
        $wishlist_icon = $in_wishlist ? '♥' : '♡';
        
        $traffic_display = $traffic >= 1000000 ? number_format($traffic / 1000000, 1) . 'M' : 
                          ($traffic >= 1000 ? number_format($traffic / 1000, 1) . 'K' : number_format($traffic));
        ?>
        <div class="gpl-site-card" data-site-id="<?php echo $site_id; ?>">
            <div class="gpl-card-header">
                <div class="gpl-site-info">
                    <img src="https://www.google.com/s2/favicons?domain=<?php echo $website_url; ?>&sz=32" alt="" class="gpl-favicon">
                    <div class="gpl-site-details">
                        <h3 class="gpl-site-name"><?php echo $website_name; ?></h3>
                        <span class="gpl-site-url"><?php echo $website_url; ?></span>
                    </div>
                </div>
                <button type="button" class="gpl-wishlist-btn <?php echo $wishlist_class; ?>" data-site-id="<?php echo $site_id; ?>" title="<?php echo $in_wishlist ? 'Remove from wishlist' : 'Add to wishlist'; ?>">
                    <span class="gpl-heart"><?php echo $wishlist_icon; ?></span>
                </button>
            </div>
            
            <div class="gpl-card-metrics">
                <div class="gpl-metric">
                    <span class="gpl-metric-label">DA</span>
                    <span class="gpl-metric-value"><?php echo $da; ?></span>
                </div>
                <div class="gpl-metric">
                    <span class="gpl-metric-label">DR</span>
                    <span class="gpl-metric-value"><?php echo $dr; ?></span>
                </div>
                <div class="gpl-metric">
                    <span class="gpl-metric-label">Traffic</span>
                    <span class="gpl-metric-value"><?php echo $traffic_display; ?></span>
                </div>
            </div>
            
            <div class="gpl-card-footer">
                <span class="gpl-niche-tag"><?php echo $niche; ?></span>
                <span class="gpl-price">$<?php echo number_format($price, 0); ?></span>
            </div>
            
            <a href="<?php echo home_url('/site/' . $site_id . '/'); ?>" class="gpl-card-link"></a>
        </div>
        <?php
    }

    public function gpl_get_site_details() {
        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        if (!$site_id) {
            $site_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        }
        
        if (!$site_id) {
            $this->send_error('Invalid site ID');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gpl_sites';
        
        $site = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d AND status = 'active'",
            $site_id
        ), ARRAY_A);

        if (!$site) {
            $this->send_error('Site not found', 404);
        }

        $this->send_success(array('site' => $site));
    }

    public function gpl_validate_email() {
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (empty($email) || !is_email($email)) {
            $this->send_error('Invalid email format');
        }
        
        // Check if email already exists
        if (email_exists($email)) {
            $this->send_error('This email is already registered');
        }
        
        // Email is valid and available
        $this->send_success(array('message' => 'Email is available'));
    }

    public function gpl_login() {
        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if (empty($username) || empty($password)) {
            $this->send_error('Please enter username and password');
        }
        
        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true
        );
        
        $user = wp_signon($creds, false);
        
        if (is_wp_error($user)) {
            $this->send_error('Invalid username or password');
        }
        
        // Determine redirect URL based on role
        $redirect_url = home_url('/marketplace/');
        if (in_array('gpl_seller', (array) $user->roles)) {
            $redirect_url = home_url('/seller-dashboard/');
        } elseif (in_array('gpl_buyer', (array) $user->roles)) {
            $redirect_url = home_url('/buyer-dashboard/');
        } elseif (in_array('administrator', (array) $user->roles)) {
            $redirect_url = admin_url('admin.php?page=gplm-dashboard');
        }
        
        $this->send_success(array(
            'message' => 'Login successful!',
            'redirect' => $redirect_url
        ));
    }

    public function gpl_register() {
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : 'buyer';
        
        // Validate inputs
        if (empty($username) || empty($email) || empty($password)) {
            $this->send_error('All fields are required');
        }
        
        if (strlen($username) < 3) {
            $this->send_error('Username must be at least 3 characters');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $this->send_error('Username can only contain letters, numbers and underscores');
        }
        
        if (!is_email($email)) {
            $this->send_error('Please enter a valid email address');
        }
        
        if (strlen($password) < 6) {
            $this->send_error('Password must be at least 6 characters');
        }
        
        // Check if username exists
        if (username_exists($username)) {
            $this->send_error('Username already exists');
        }
        
        // Check if email exists
        if (email_exists($email)) {
            $this->send_error('Email already registered');
        }
        
        // Map role to WordPress role
        $wp_role = ($role === 'seller') ? 'gpl_seller' : 'gpl_buyer';
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            $this->send_error($user_id->get_error_message());
        }
        
        // Set the role
        $user = new WP_User($user_id);
        $user->set_role($wp_role);
        
        // Auto-login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        
        // Determine redirect URL
        $redirect_url = ($role === 'seller') ? home_url('/seller-dashboard/') : home_url('/buyer-dashboard/');
        
        $this->send_success(array(
            'message' => 'Registration successful! Redirecting...',
            'redirect' => $redirect_url
        ));
    }

    public function gpl_add_site() {
        if (!$this->verify_nonce()) {
            $this->send_error('Security check failed', 403);
        }

        if (!current_user_can('gpl_seller') && !current_user_can('administrator')) {
            $this->send_error('Seller access required', 403);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gpl_sites';

        $website_url = isset($_POST['website_url']) ? sanitize_text_field($_POST['website_url']) : '';
        $website_url = preg_replace('#^https?://#', '', $website_url);
        $website_url = rtrim($website_url, '/');

        if (empty($website_url)) {
            $this->send_error('Website URL is required');
        }

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE website_url = %s",
            $website_url
        ));

        if ($exists) {
            $this->send_error('This website is already listed');
        }

        $data = array(
            'website_url' => $website_url,
            'website_name' => sanitize_text_field($_POST['website_name'] ?? $website_url),
            'niche' => sanitize_text_field($_POST['niche'] ?? ''),
            'domain_authority' => intval($_POST['domain_authority'] ?? 0),
            'domain_rating' => intval($_POST['domain_rating'] ?? 0),
            'authority_score' => intval($_POST['authority_score'] ?? 0),
            'trust_flow' => intval($_POST['trust_flow'] ?? 0),
            'citation_flow' => intval($_POST['citation_flow'] ?? 0),
            'spam_score' => intval($_POST['spam_score'] ?? 0),
            'domain_age' => sanitize_text_field($_POST['domain_age'] ?? ''),
            'tld' => sanitize_text_field($_POST['tld'] ?? '.com'),
            'ahrefs_traffic' => intval($_POST['ahrefs_traffic'] ?? 0),
            'ahrefs_keywords' => intval($_POST['ahrefs_keywords'] ?? 0),
            'semrush_traffic' => intval($_POST['semrush_traffic'] ?? 0),
            'semrush_keywords' => intval($_POST['semrush_keywords'] ?? 0),
            'similarweb_traffic' => intval($_POST['similarweb_traffic'] ?? 0),
            'traffic_countries' => sanitize_text_field($_POST['traffic_countries'] ?? ''),
            'country' => sanitize_text_field($_POST['country'] ?? 'United States'),
            'country_code' => sanitize_text_field($_POST['country_code'] ?? 'US'),
            'language' => sanitize_text_field($_POST['language'] ?? 'English'),
            'price' => floatval($_POST['price'] ?? 0),
            'sale_price' => !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null,
            'currency' => sanitize_text_field($_POST['currency'] ?? 'USD'),
            'backlinks_allowed' => intval($_POST['backlinks_allowed'] ?? 1),
            'link_type' => sanitize_text_field($_POST['link_type'] ?? 'DoFollow'),
            'link_validity' => sanitize_text_field($_POST['link_validity'] ?? 'Permanent'),
            'google_news' => intval($_POST['google_news'] ?? 0),
            'marked_sponsored' => intval($_POST['marked_sponsored'] ?? 0),
            'sports_gaming_allowed' => intval($_POST['sports_gaming_allowed'] ?? 0),
            'pharmacy_allowed' => intval($_POST['pharmacy_allowed'] ?? 0),
            'crypto_allowed' => intval($_POST['crypto_allowed'] ?? 0),
            'foreign_lang_allowed' => intval($_POST['foreign_lang_allowed'] ?? 0),
            'tat' => sanitize_text_field($_POST['tat'] ?? '3-5 days'),
            'sample_url' => esc_url_raw($_POST['sample_url'] ?? ''),
            'guidelines' => sanitize_textarea_field($_POST['guidelines'] ?? ''),
            'word_count' => intval($_POST['word_count'] ?? 500),
            'content_written_by' => sanitize_text_field($_POST['content_written_by'] ?? 'Publisher'),
            'seller_id' => get_current_user_id(),
            'status' => 'active',
            'approval_status' => 'pending',
            'created_at' => current_time('mysql'),
        );

        $result = $wpdb->insert($table, $data);

        if ($result) {
            $this->send_success(array(
                'site_id' => $wpdb->insert_id,
                'message' => 'Site submitted for review'
            ));
        } else {
            $this->send_error('Failed to add site: ' . $wpdb->last_error);
        }
    }

    public function gpl_update_site() {
        if (!$this->verify_nonce()) {
            $this->send_error('Security check failed', 403);
        }

        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        if (!$site_id) {
            $site_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        }
        
        if (!$site_id) {
            $this->send_error('Invalid site ID');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gpl_sites';

        $site = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $site_id
        ));

        if (!$site) {
            $this->send_error('Site not found', 404);
        }

        if ($site->seller_id != get_current_user_id() && !current_user_can('administrator')) {
            $this->send_error('You do not have permission to edit this site', 403);
        }

        $data = array(
            'website_name' => sanitize_text_field($_POST['website_name'] ?? $site->website_name),
            'niche' => sanitize_text_field($_POST['niche'] ?? $site->niche),
            'domain_authority' => intval($_POST['domain_authority'] ?? $site->domain_authority),
            'domain_rating' => intval($_POST['domain_rating'] ?? $site->domain_rating),
            'authority_score' => intval($_POST['authority_score'] ?? $site->authority_score),
            'trust_flow' => intval($_POST['trust_flow'] ?? $site->trust_flow),
            'citation_flow' => intval($_POST['citation_flow'] ?? $site->citation_flow),
            'spam_score' => intval($_POST['spam_score'] ?? $site->spam_score),
            'domain_age' => sanitize_text_field($_POST['domain_age'] ?? $site->domain_age),
            'tld' => sanitize_text_field($_POST['tld'] ?? $site->tld),
            'ahrefs_traffic' => intval($_POST['ahrefs_traffic'] ?? $site->ahrefs_traffic),
            'ahrefs_keywords' => intval($_POST['ahrefs_keywords'] ?? $site->ahrefs_keywords),
            'semrush_traffic' => intval($_POST['semrush_traffic'] ?? $site->semrush_traffic),
            'semrush_keywords' => intval($_POST['semrush_keywords'] ?? $site->semrush_keywords),
            'similarweb_traffic' => intval($_POST['similarweb_traffic'] ?? $site->similarweb_traffic),
            'traffic_countries' => sanitize_text_field($_POST['traffic_countries'] ?? $site->traffic_countries),
            'country' => sanitize_text_field($_POST['country'] ?? $site->country),
            'country_code' => sanitize_text_field($_POST['country_code'] ?? $site->country_code),
            'language' => sanitize_text_field($_POST['language'] ?? $site->language),
            'price' => floatval($_POST['price'] ?? $site->price),
            'sale_price' => isset($_POST['sale_price']) && $_POST['sale_price'] !== '' ? floatval($_POST['sale_price']) : null,
            'currency' => sanitize_text_field($_POST['currency'] ?? $site->currency),
            'backlinks_allowed' => intval($_POST['backlinks_allowed'] ?? $site->backlinks_allowed),
            'link_type' => sanitize_text_field($_POST['link_type'] ?? $site->link_type),
            'link_validity' => sanitize_text_field($_POST['link_validity'] ?? $site->link_validity),
            'google_news' => intval($_POST['google_news'] ?? 0),
            'marked_sponsored' => intval($_POST['marked_sponsored'] ?? 0),
            'sports_gaming_allowed' => intval($_POST['sports_gaming_allowed'] ?? 0),
            'pharmacy_allowed' => intval($_POST['pharmacy_allowed'] ?? 0),
            'crypto_allowed' => intval($_POST['crypto_allowed'] ?? 0),
            'foreign_lang_allowed' => intval($_POST['foreign_lang_allowed'] ?? 0),
            'tat' => sanitize_text_field($_POST['tat'] ?? $site->tat),
            'sample_url' => esc_url_raw($_POST['sample_url'] ?? $site->sample_url),
            'guidelines' => sanitize_textarea_field($_POST['guidelines'] ?? $site->guidelines),
            'word_count' => intval($_POST['word_count'] ?? $site->word_count),
            'content_written_by' => sanitize_text_field($_POST['content_written_by'] ?? $site->content_written_by),
            'updated_at' => current_time('mysql'),
        );

        $result = $wpdb->update($table, $data, array('id' => $site_id));

        if ($result !== false) {
            $this->send_success(array('message' => 'Site updated successfully'));
        } else {
            $this->send_error('Failed to update site: ' . $wpdb->last_error);
        }
    }

    public function gpl_delete_site() {
        if (!$this->verify_nonce()) {
            $this->send_error('Security check failed', 403);
        }

        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        if (!$site_id) {
            $site_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        }
        
        if (!$site_id) {
            $this->send_error('Invalid site ID');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gpl_sites';

        $site = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $site_id
        ));

        if (!$site) {
            $this->send_error('Site not found', 404);
        }

        if ($site->seller_id != get_current_user_id() && !current_user_can('administrator')) {
            $this->send_error('You do not have permission to delete this site', 403);
        }

        $result = $wpdb->delete($table, array('id' => $site_id));

        if ($result) {
            $wpdb->delete($wpdb->prefix . 'gpl_wishlist', array('site_id' => $site_id));
            $this->send_success(array('message' => 'Site deleted successfully'));
        } else {
            $this->send_error('Failed to delete site');
        }
    }

    public function gpl_update_profile() {
        if (!$this->verify_nonce()) {
            $this->send_error('Security check failed', 403);
        }

        if (!is_user_logged_in()) {
            $this->send_error('Please login', 401);
        }

        $user_id = get_current_user_id();
        $display_name = isset($_POST['display_name']) ? sanitize_text_field($_POST['display_name']) : '';

        if (empty($display_name)) {
            $this->send_error('Display name is required');
        }

        $result = wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $display_name
        ));

        if (is_wp_error($result)) {
            $this->send_error($result->get_error_message());
        }

        $this->send_success(array('message' => 'Profile updated successfully'));
    }

    public function gpl_purchase_site() {
        if (!$this->verify_nonce()) {
            $this->send_error('Security check failed', 403);
        }

        if (!is_user_logged_in()) {
            $this->send_error('Please login', 401);
        }

        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        if (!$site_id) {
            $this->send_error('Invalid site ID');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gpl_sites';

        $site = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d AND status = 'active'",
            $site_id
        ));

        if (!$site) {
            $this->send_error('Site not found or not available', 404);
        }

        $user_id = get_current_user_id();
        $purchases = get_user_meta($user_id, 'gpl_purchases', true);
        if (!is_array($purchases)) {
            $purchases = array();
        }

        $purchases[] = array(
            'site_id' => $site_id,
            'price' => $site->price,
            'date' => current_time('mysql'),
            'status' => 'pending'
        );

        update_user_meta($user_id, 'gpl_purchases', $purchases);

        $this->send_success(array('message' => 'Purchase request submitted'));
    }

    public function gpl_admin_update_status() {
        if (!current_user_can('manage_options')) {
            $this->send_error('Admin access required', 403);
        }

        if (!$this->verify_nonce()) {
            $this->send_error('Security check failed', 403);
        }

        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        if (!$site_id) {
            $site_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        }
        
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (!$site_id || !in_array($status, array('active', 'pending', 'rejected'))) {
            $this->send_error('Invalid parameters');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gpl_sites';

        $result = $wpdb->update(
            $table,
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('id' => $site_id)
        );

        if ($result !== false) {
            $this->send_success(array(
                'message' => 'Status updated to ' . ucfirst($status),
                'status' => $status
            ));
        } else {
            $this->send_error('Failed to update status');
        }
    }

    public function gpl_admin_delete_site() {
        if (!current_user_can('manage_options')) {
            $this->send_error('Admin access required', 403);
        }

        if (!$this->verify_nonce()) {
            $this->send_error('Security check failed', 403);
        }

        $site_id = isset($_POST['site_id']) ? intval($_POST['site_id']) : 0;
        if (!$site_id) {
            $site_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        }
        
        if (!$site_id) {
            $this->send_error('Invalid site ID');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gpl_sites';

        $result = $wpdb->delete($table, array('id' => $site_id));

        if ($result) {
            $wpdb->delete($wpdb->prefix . 'gpl_wishlist', array('site_id' => $site_id));
            $this->send_success(array('message' => 'Site deleted successfully'));
        } else {
            $this->send_error('Failed to delete site');
        }
    }

    public function gpl_admin_bulk_update() {
        if (!current_user_can('manage_options')) {
            $this->send_error('Admin access required', 403);
        }

        if (!$this->verify_nonce()) {
            $this->send_error('Security check failed', 403);
        }

        $ids_raw = isset($_POST['site_ids']) ? $_POST['site_ids'] : (isset($_POST['ids']) ? $_POST['ids'] : '');
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        if (is_string($ids_raw)) {
            $ids = json_decode(stripslashes($ids_raw), true);
        } else {
            $ids = $ids_raw;
        }

        if (empty($ids) || !is_array($ids)) {
            $this->send_error('No sites selected');
        }

        if (!in_array($status, array('active', 'pending', 'rejected'))) {
            $this->send_error('Invalid status');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gpl_sites';
        $updated = 0;

        foreach ($ids as $id) {
            $result = $wpdb->update(
                $table,
                array('status' => $status, 'updated_at' => current_time('mysql')),
                array('id' => intval($id))
            );
            if ($result !== false) {
                $updated++;
            }
        }

        $this->send_success(array(
            'message' => $updated . ' site(s) updated to ' . ucfirst($status),
            'updated' => $updated
        ));
    }

    public function gpl_admin_bulk_delete() {
        if (!current_user_can('manage_options')) {
            $this->send_error('Admin access required', 403);
        }

        if (!$this->verify_nonce()) {
            $this->send_error('Security check failed', 403);
        }

        $ids_raw = isset($_POST['site_ids']) ? $_POST['site_ids'] : (isset($_POST['ids']) ? $_POST['ids'] : '');

        if (is_string($ids_raw)) {
            $ids = json_decode(stripslashes($ids_raw), true);
        } else {
            $ids = $ids_raw;
        }

        if (empty($ids) || !is_array($ids)) {
            $this->send_error('No sites selected');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gpl_sites';
        $wishlist_table = $wpdb->prefix . 'gpl_wishlist';
        $deleted = 0;

        foreach ($ids as $id) {
            $id = intval($id);
            $result = $wpdb->delete($table, array('id' => $id));
            if ($result) {
                $wpdb->delete($wishlist_table, array('site_id' => $id));
                $deleted++;
            }
        }

        $this->send_success(array(
            'message' => $deleted . ' site(s) deleted',
            'deleted' => $deleted
        ));
    }
}

GPL_Sites_AJAX::instance();
