<?php

if (!defined('ABSPATH')) {
    exit;
}

class GPL_Sites_Database {
    
    private static $sites_table;
    private static $wishlist_table;
    
    public static function init() {
        global $wpdb;
        self::$sites_table = $wpdb->prefix . 'gpl_sites';
        self::$wishlist_table = $wpdb->prefix . 'gpl_wishlist';
        
        add_action('init', array(__CLASS__, 'maybe_create_tables'));
    }
    
    /**
     * Get sites table name
     */
    public static function get_sites_table() {
        global $wpdb;
        return $wpdb->prefix . 'gpl_sites';
    }
    
    /**
     * Get wishlist table name
     */
    public static function get_wishlist_table() {
        global $wpdb;
        return $wpdb->prefix . 'gpl_wishlist';
    }
    
    /**
     * Create tables if not exist
     */
    public static function maybe_create_tables() {
        if (get_option('gpl_sites_db_version') === GPL_SITES_VERSION) {
            return;
        }
        
        self::create_tables();
        update_option('gpl_sites_db_version', GPL_SITES_VERSION);
    }
    
    /**
     * Create database tables with EXACT structure
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sites_table = self::get_sites_table();
        $wishlist_table = self::get_wishlist_table();
        
        // Sites table - EXACT columns from user's database + v6.2 new fields
        $sql_sites = "CREATE TABLE IF NOT EXISTS {$sites_table} (
            id bigint UNSIGNED NOT NULL AUTO_INCREMENT,
            seller_id bigint UNSIGNED DEFAULT NULL,
            website_url varchar(255) NOT NULL,
            website_name varchar(255) DEFAULT NULL,
            authority_score int DEFAULT 0,
            domain_authority int DEFAULT 0,
            domain_rating int DEFAULT 0,
            trust_flow int DEFAULT 0,
            citation_flow int DEFAULT 0,
            spam_score int DEFAULT 0,
            domain_age varchar(50) DEFAULT NULL,
            ahrefs_traffic bigint DEFAULT 0,
            ahrefs_keywords bigint DEFAULT 0,
            semrush_traffic bigint DEFAULT 0,
            semrush_keywords int DEFAULT 0,
            similarweb_traffic bigint DEFAULT 0,
            niche varchar(255) DEFAULT NULL,
            language varchar(100) DEFAULT 'English',
            country varchar(100) DEFAULT 'United States',
            country_code varchar(10) DEFAULT 'US',
            traffic_countries text DEFAULT NULL,
            price decimal(10,2) DEFAULT 0.00,
            sale_price decimal(10,2) DEFAULT NULL,
            currency varchar(10) DEFAULT 'USD',
            backlinks_allowed int DEFAULT 1,
            link_type varchar(50) DEFAULT 'DoFollow',
            link_validity varchar(50) DEFAULT 'Permanent',
            google_news tinyint(1) DEFAULT 0,
            marked_sponsored tinyint(1) DEFAULT 0,
            sports_gaming_allowed tinyint(1) DEFAULT 1,
            pharmacy_allowed tinyint(1) DEFAULT 0,
            crypto_allowed tinyint(1) DEFAULT 1,
            foreign_lang_allowed tinyint(1) DEFAULT 0,
            tld varchar(20) DEFAULT '.com',
            tat varchar(50) DEFAULT '3-5 days',
            sample_url varchar(500) DEFAULT '',
            guidelines text DEFAULT NULL,
            word_count int DEFAULT 500,
            content_written_by varchar(50) DEFAULT 'Publisher',
            featured tinyint(1) DEFAULT 0,
            status varchar(20) DEFAULT 'active',
            approval_status varchar(20) DEFAULT 'approved',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY seller_id (seller_id),
            KEY authority_score (authority_score),
            KEY domain_authority (domain_authority),
            KEY domain_rating (domain_rating),
            KEY trust_flow (trust_flow),
            KEY niche (niche),
            KEY country (country),
            KEY price (price),
            KEY status (status)
        ) $charset_collate;";
        
        // Wishlist table
        $sql_wishlist = "CREATE TABLE IF NOT EXISTS {$wishlist_table} (
            id bigint UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint UNSIGNED NOT NULL,
            site_id bigint UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_site (user_id, site_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_sites);
        dbDelta($sql_wishlist);
        
        // Insert sample data if table is empty
        self::maybe_insert_sample_data();
    }
    
    /**
     * Insert sample data
     */
    public static function maybe_insert_sample_data() {
        global $wpdb;
        $table = self::get_sites_table();
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }
        
        $niches = array('Technology', 'Business', 'Health', 'Finance', 'Travel', 'Fashion', 'Food', 'Sports', 'Education', 'Entertainment');
        $countries = array(
            array('United States', 'US'),
            array('United Kingdom', 'GB'),
            array('Canada', 'CA'),
            array('Australia', 'AU'),
            array('Germany', 'DE'),
            array('France', 'FR'),
            array('India', 'IN')
        );
        $link_types = array('DoFollow', 'NoFollow', 'Both');
        $languages = array('English', 'Spanish', 'French', 'German', 'Hindi');
        $tlds = array('.com', '.net', '.org', '.io', '.co');
        $tat_options = array('1-2 days', '3-5 days', '5-7 days', '1 week', '2 weeks');
        $content_by = array('Publisher', 'Buyer', 'Both');
        
        $domains = array(
            'techcrunch', 'mashable', 'wired', 'theverge', 'engadget',
            'forbes', 'entrepreneur', 'inc', 'businessinsider', 'fastcompany',
            'healthline', 'webmd', 'medicalnewstoday', 'everydayhealth', 'health',
            'bloomberg', 'marketwatch', 'investopedia', 'nerdwallet', 'bankrate',
            'lonelyplanet', 'tripadvisor', 'travelandleisure', 'cntraveler', 'afar',
            'vogue', 'elle', 'harpersbazaar', 'cosmopolitan', 'glamour',
            'foodnetwork', 'allrecipes', 'epicurious', 'bonappetit', 'delish',
            'espn', 'bleacherreport', 'sportingnews', 'cbssports', 'si',
            'edutopia', 'coursera', 'edx', 'khanacademy', 'udemy',
            'variety', 'hollywoodreporter', 'ew', 'vulture', 'avclub'
        );
        
        foreach ($domains as $i => $domain) {
            $country_data = $countries[array_rand($countries)];
            $niche = $niches[$i % count($niches)];
            
            $wpdb->insert($table, array(
                'seller_id' => 1,
                'website_url' => $domain . '.com',
                'website_name' => ucfirst($domain),
                'authority_score' => rand(30, 95),
                'domain_authority' => rand(40, 95),
                'domain_rating' => rand(35, 90),
                'trust_flow' => rand(10, 60),
                'citation_flow' => rand(15, 70),
                'spam_score' => rand(1, 25),
                'domain_age' => rand(2, 15) . ' years',
                'ahrefs_traffic' => rand(50000, 5000000),
                'ahrefs_keywords' => rand(10000, 500000),
                'semrush_traffic' => rand(40000, 4000000),
                'semrush_keywords' => rand(8000, 400000),
                'similarweb_traffic' => rand(100000, 10000000),
                'niche' => $niche,
                'language' => $languages[array_rand($languages)],
                'country' => $country_data[0],
                'country_code' => $country_data[1],
                'price' => rand(50, 500) + (rand(0, 99) / 100),
                'sale_price' => (rand(0, 1) == 1) ? rand(30, 400) + (rand(0, 99) / 100) : null,
                'currency' => 'USD',
                'backlinks_allowed' => rand(1, 3),
                'link_type' => $link_types[array_rand($link_types)],
                'link_validity' => 'Permanent',
                'google_news' => rand(0, 1),
                'marked_sponsored' => rand(0, 1),
                'sports_gaming_allowed' => rand(0, 1),
                'pharmacy_allowed' => rand(0, 1),
                'crypto_allowed' => rand(0, 1),
                'foreign_lang_allowed' => rand(0, 1),
                'tld' => $tlds[array_rand($tlds)],
                'tat' => $tat_options[array_rand($tat_options)],
                'sample_url' => 'https://' . $domain . '.com/sample-post',
                'guidelines' => 'High-quality content required. No spam links.',
                'word_count' => rand(500, 2000),
                'content_written_by' => $content_by[array_rand($content_by)],
                'featured' => ($i < 5) ? 1 : 0,
                'status' => 'active',
                'approval_status' => 'approved'
            ));
        }
    }
    
    /**
     * Get filter options from database
     */
    public static function get_filter_options() {
        global $wpdb;
        $table = self::get_sites_table();
        
        $options = array(
            'niches' => array(),
            'languages' => array(),
            'countries' => array(),
            'link_types' => array()
        );
        
        // Get unique niches
        $niches = $wpdb->get_col("SELECT DISTINCT niche FROM {$table} WHERE niche IS NOT NULL AND niche != '' ORDER BY niche");
        if ($niches) {
            $options['niches'] = $niches;
        }
        
        // Get unique languages
        $languages = $wpdb->get_col("SELECT DISTINCT language FROM {$table} WHERE language IS NOT NULL AND language != '' ORDER BY language");
        if ($languages) {
            $options['languages'] = $languages;
        }
        
        // Get unique countries
        $countries = $wpdb->get_col("SELECT DISTINCT country FROM {$table} WHERE country IS NOT NULL AND country != '' ORDER BY country");
        if ($countries) {
            $options['countries'] = $countries;
        }
        
        // Get unique link types
        $link_types = $wpdb->get_col("SELECT DISTINCT link_type FROM {$table} WHERE link_type IS NOT NULL AND link_type != '' ORDER BY link_type");
        if ($link_types) {
            $options['link_types'] = $link_types;
        }
        
        return $options;
    }
    
    /**
     * Get sites with filters
     * v6.2 - Added max_sites support and TF/CF/Spam filters
     */
    public static function get_sites($args = array()) {
        global $wpdb;
        $table = self::get_sites_table();
        
        $defaults = array(
            'page' => 1,
            'per_page' => 20,
            'search' => '',
            'niche' => '',
            'language' => '',
            'country' => '',
            'link_type' => '',
            'da_min' => 0,
            'da_max' => 100,
            'dr_min' => 0,
            'dr_max' => 100,
            'as_min' => 0,
            'as_max' => 100,
            'tf_min' => 0,
            'tf_max' => 100,
            'cf_min' => 0,
            'cf_max' => 100,
            'spam_max' => 100,
            'traffic_min' => 0,
            'traffic_max' => 0,
            'keywords_min' => 0,
            'keywords_max' => 0,
            'price_min' => 0,
            'price_max' => 0,
            'sort' => 'updated_at',
            'order' => 'DESC',
            'seller_id' => 0,
            'featured' => false,
            'max_sites' => 0,
            'show_all' => false
        );
        
        $args = wp_parse_args($args, $defaults);
        
        //$where = array("status = 'active'", "approval_status = 'approved'");
        $where = array("approval_status = 'approved'");
        $values = array();
        
        // Search
        if (!empty($args['search'])) {
            $where[] = "(website_url LIKE %s OR website_name LIKE %s OR niche LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        // Niche filter
        if (!empty($args['niche'])) {
            $where[] = "niche = %s";
            $values[] = $args['niche'];
        }
        
        // Language filter
        if (!empty($args['language'])) {
            $where[] = "language = %s";
            $values[] = $args['language'];
        }
        
        // Country filter
        if (!empty($args['country'])) {
            $where[] = "country = %s";
            $values[] = $args['country'];
        }
        
        // Link type filter
        if (!empty($args['link_type'])) {
            $where[] = "link_type = %s";
            $values[] = $args['link_type'];
        }
        
        // DA range
        if ($args['da_min'] > 0) {
            $where[] = "domain_authority >= %d";
            $values[] = intval($args['da_min']);
        }
        if ($args['da_max'] > 0 && $args['da_max'] < 100) {
            $where[] = "domain_authority <= %d";
            $values[] = intval($args['da_max']);
        }
        
        // DR range
        if ($args['dr_min'] > 0) {
            $where[] = "domain_rating >= %d";
            $values[] = intval($args['dr_min']);
        }
        if ($args['dr_max'] > 0 && $args['dr_max'] < 100) {
            $where[] = "domain_rating <= %d";
            $values[] = intval($args['dr_max']);
        }
        
        // AS range
        if ($args['as_min'] > 0) {
            $where[] = "authority_score >= %d";
            $values[] = intval($args['as_min']);
        }
        if ($args['as_max'] > 0 && $args['as_max'] < 100) {
            $where[] = "authority_score <= %d";
            $values[] = intval($args['as_max']);
        }
        
        // TF range (Trust Flow)
        if ($args['tf_min'] > 0) {
            $where[] = "trust_flow >= %d";
            $values[] = intval($args['tf_min']);
        }
        if ($args['tf_max'] > 0 && $args['tf_max'] < 100) {
            $where[] = "trust_flow <= %d";
            $values[] = intval($args['tf_max']);
        }
        
        // CF range (Citation Flow)
        if ($args['cf_min'] > 0) {
            $where[] = "citation_flow >= %d";
            $values[] = intval($args['cf_min']);
        }
        if ($args['cf_max'] > 0 && $args['cf_max'] < 100) {
            $where[] = "citation_flow <= %d";
            $values[] = intval($args['cf_max']);
        }
        
        // Spam Score max
        if ($args['spam_max'] < 100) {
            $where[] = "spam_score <= %d";
            $values[] = intval($args['spam_max']);
        }
        
        // Traffic range (using ahrefs_traffic)
        if ($args['traffic_min'] > 0) {
            $where[] = "ahrefs_traffic >= %d";
            $values[] = intval($args['traffic_min']);
        }
        if ($args['traffic_max'] > 0) {
            $where[] = "ahrefs_traffic <= %d";
            $values[] = intval($args['traffic_max']);
        }
        
        // Keywords range (using ahrefs_keywords)
        if ($args['keywords_min'] > 0) {
            $where[] = "ahrefs_keywords >= %d";
            $values[] = intval($args['keywords_min']);
        }
        if ($args['keywords_max'] > 0) {
            $where[] = "ahrefs_keywords <= %d";
            $values[] = intval($args['keywords_max']);
        }
        
        // Price range
        if ($args['price_min'] > 0) {
            $where[] = "price >= %f";
            $values[] = floatval($args['price_min']);
        }
        if ($args['price_max'] > 0) {
            $where[] = "price <= %f";
            $values[] = floatval($args['price_max']);
        }
        
        // Seller filter
        if ($args['seller_id'] > 0) {
            $where[] = "seller_id = %d";
            $values[] = intval($args['seller_id']);
        }
        
        // Featured filter
        if ($args['featured']) {
            $where[] = "featured = 1";
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Sorting - map column names
        $sort_columns = array(
            'updated_at' => 'updated_at',
            'da' => 'domain_authority',
            'domain_rating' => 'domain_rating',
            'dr' => 'domain_rating',
            'authority_score' => 'authority_score',
            'as' => 'authority_score',
            'traffic' => 'ahrefs_traffic',
            'ahrefs_traffic' => 'ahrefs_traffic',
            'price' => 'price',
            'created_at' => 'created_at',
            'domain_authority' => 'domain_authority'
        );
        
        $sort = isset($sort_columns[$args['sort']]) ? $sort_columns[$args['sort']] : 'updated_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Count total
        $count_query = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
        if (!empty($values)) {
            $total = $wpdb->get_var($wpdb->prepare($count_query, $values));
        } else {
            $total = $wpdb->get_var($count_query);
        }
        
        // Apply max_sites limit for tiered access
        $max_sites = intval($args['max_sites']);
        $effective_total = $total;
        
        if ($max_sites > 0 && !$args['show_all']) {
            $effective_total = min($total, $max_sites);
        }
        
        // Get sites
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        // Ensure offset doesn't exceed max_sites
        if ($max_sites > 0 && !$args['show_all'] && $offset >= $max_sites) {
            return array(
                'sites' => array(),
                'total' => intval($total),
                'effective_total' => $effective_total,
                'pages' => ceil($effective_total / $args['per_page']),
                'current_page' => $args['page']
            );
        }
        
        // Adjust limit if near max_sites boundary
        $limit = intval($args['per_page']);
        if ($max_sites > 0 && !$args['show_all']) {
            $remaining = $max_sites - $offset;
            $limit = min($limit, $remaining);
        }
        
        $query = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$sort} {$order} LIMIT %d OFFSET %d";
        $values[] = $limit;
        $values[] = intval($offset);
        
        $sites = $wpdb->get_results($wpdb->prepare($query, $values), ARRAY_A);
        return array(
            'sites' => $sites ? $sites : array(),
            'total' => intval($total),
            'effective_total' => $effective_total,
            'pages' => ceil($effective_total / $args['per_page']),
            'current_page' => $args['page']
        );
    }
    
    /**
     * Get site by ID
     */
    public static function get_site_by_id($id) {
        global $wpdb;
        $table = self::get_sites_table();
        
        $site = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            intval($id)
        ), ARRAY_A);
        
        return $site ? $site : null;
    }
    
    /**
     * Get site by domain (website_url)
     */
    public static function get_site_by_domain($domain) {
        global $wpdb;
        $table = self::get_sites_table();
        
        // First try exact match with approval_status
        $site = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE website_url = %s AND approval_status = 'approved'",
            $domain
        ), ARRAY_A);
        
        // If not found, try without status filter
        if (!$site) {
            $site = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE website_url = %s",
                $domain
            ), ARRAY_A);
        }
        
        return $site ? $site : null;
    }
    
    /**
     * Get similar sites
     */
    public static function get_similar_sites($niche, $exclude_id, $limit = 4) {
        global $wpdb;
        $table = self::get_sites_table();
        
        $sites = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} 
             WHERE niche = %s AND id != %d AND status = 'active' AND approval_status = 'approved'
             ORDER BY domain_authority DESC 
             LIMIT %d",
            $niche,
            intval($exclude_id),
            intval($limit)
        ), ARRAY_A);
        
        return $sites ? $sites : array();
    }
    
    /**
     * Add new site
     */
    public static function add_site($data) {
        global $wpdb;
        $table = self::get_sites_table();
        
        $insert_data = array(
            'seller_id' => intval($data['seller_id']),
            'website_url' => sanitize_text_field($data['website_url']),
            'website_name' => sanitize_text_field($data['website_name']),
            'authority_score' => intval($data['authority_score']),
            'domain_authority' => intval($data['domain_authority']),
            'domain_rating' => intval($data['domain_rating']),
            'trust_flow' => isset($data['trust_flow']) ? intval($data['trust_flow']) : 0,
            'citation_flow' => isset($data['citation_flow']) ? intval($data['citation_flow']) : 0,
            'spam_score' => isset($data['spam_score']) ? intval($data['spam_score']) : 0,
            'domain_age' => isset($data['domain_age']) ? sanitize_text_field($data['domain_age']) : '',
            'ahrefs_traffic' => intval($data['ahrefs_traffic']),
            'ahrefs_keywords' => intval($data['ahrefs_keywords']),
            'semrush_traffic' => isset($data['semrush_traffic']) ? intval($data['semrush_traffic']) : 0,
            'semrush_keywords' => isset($data['semrush_keywords']) ? intval($data['semrush_keywords']) : 0,
            'similarweb_traffic' => isset($data['similarweb_traffic']) ? intval($data['similarweb_traffic']) : 0,
            'niche' => sanitize_text_field($data['niche']),
            'language' => sanitize_text_field($data['language']),
            'country' => sanitize_text_field($data['country']),
            'country_code' => isset($data['country_code']) ? sanitize_text_field($data['country_code']) : 'US',
            'price' => floatval($data['price']),
            'sale_price' => isset($data['sale_price']) && $data['sale_price'] > 0 ? floatval($data['sale_price']) : null,
            'currency' => isset($data['currency']) ? sanitize_text_field($data['currency']) : 'USD',
            'backlinks_allowed' => isset($data['backlinks_allowed']) ? intval($data['backlinks_allowed']) : 1,
            'link_type' => sanitize_text_field($data['link_type']),
            'link_validity' => isset($data['link_validity']) ? sanitize_text_field($data['link_validity']) : 'Permanent',
            'google_news' => isset($data['google_news']) ? intval($data['google_news']) : 0,
            'marked_sponsored' => isset($data['marked_sponsored']) ? intval($data['marked_sponsored']) : 0,
            'sports_gaming_allowed' => isset($data['sports_gaming_allowed']) ? intval($data['sports_gaming_allowed']) : 1,
            'pharmacy_allowed' => isset($data['pharmacy_allowed']) ? intval($data['pharmacy_allowed']) : 0,
            'crypto_allowed' => isset($data['crypto_allowed']) ? intval($data['crypto_allowed']) : 1,
            'foreign_lang_allowed' => isset($data['foreign_lang_allowed']) ? intval($data['foreign_lang_allowed']) : 0,
            'tld' => isset($data['tld']) ? sanitize_text_field($data['tld']) : '.com',
            'tat' => isset($data['tat']) ? sanitize_text_field($data['tat']) : '3-5 days',
            'sample_url' => isset($data['sample_url']) ? esc_url_raw($data['sample_url']) : '',
            'guidelines' => isset($data['guidelines']) ? sanitize_textarea_field($data['guidelines']) : '',
            'word_count' => isset($data['word_count']) ? intval($data['word_count']) : 500,
            'content_written_by' => isset($data['content_written_by']) ? sanitize_text_field($data['content_written_by']) : 'Publisher',
            'status' => 'active',
            'approval_status' => 'approved'
        );
        
        $result = $wpdb->insert($table, $insert_data);
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update site
     */
    public static function update_site($id, $data) {
        global $wpdb;
        $table = self::get_sites_table();
        
        $update_data = array();
        
        $allowed_fields = array(
            'website_url', 'website_name', 'authority_score', 'domain_authority', 'domain_rating',
            'trust_flow', 'citation_flow', 'spam_score', 'domain_age',
            'ahrefs_traffic', 'ahrefs_keywords', 'semrush_traffic', 'semrush_keywords', 'similarweb_traffic',
            'niche', 'language', 'country', 'country_code', 'price', 'sale_price', 'currency',
            'backlinks_allowed', 'link_type', 'link_validity', 'google_news', 'marked_sponsored',
            'sports_gaming_allowed', 'pharmacy_allowed', 'crypto_allowed', 'foreign_lang_allowed',
            'tld', 'tat', 'sample_url', 'guidelines', 'word_count', 'content_written_by',
            'featured', 'status', 'approval_status'
        );
        
        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        return $wpdb->update($table, $update_data, array('id' => intval($id)));
    }
    
    /**
     * Delete site
     */
    public static function delete_site($id, $seller_id = 0) {
        global $wpdb;
        $table = self::get_sites_table();
        
        $where = array('id' => intval($id));
        
        if ($seller_id > 0) {
            $where['seller_id'] = intval($seller_id);
        }
        
        return $wpdb->delete($table, $where);
    }
    
    /**
     * Get user's wishlist
     */
    public static function get_user_wishlist($user_id) {
        global $wpdb;
        $wishlist_table = self::get_wishlist_table();
        $sites_table = self::get_sites_table();
        
        $sites = $wpdb->get_results($wpdb->prepare(
            "SELECT s.* FROM {$sites_table} s
             INNER JOIN {$wishlist_table} w ON s.id = w.site_id
             WHERE w.user_id = %d AND s.status = 'active'
             ORDER BY w.created_at DESC",
            intval($user_id)
        ), ARRAY_A);
        
        return $sites ? $sites : array();
    }
    
    /**
     * Get wishlist IDs for a user
     */
    public static function get_user_wishlist_ids($user_id) {
        global $wpdb;
        $table = self::get_wishlist_table();
        
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT site_id FROM {$table} WHERE user_id = %d",
            intval($user_id)
        ));
        
        return $ids ? array_map('intval', $ids) : array();
    }
    
    /**
     * Toggle wishlist item
     */
    public static function toggle_wishlist($user_id, $site_id) {
        global $wpdb;
        $table = self::get_wishlist_table();
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND site_id = %d",
            intval($user_id),
            intval($site_id)
        ));
        
        if ($exists) {
            $wpdb->delete($table, array(
                'user_id' => intval($user_id),
                'site_id' => intval($site_id)
            ));
            return array('action' => 'removed', 'in_wishlist' => false);
        } else {
            $wpdb->insert($table, array(
                'user_id' => intval($user_id),
                'site_id' => intval($site_id)
            ));
            return array('action' => 'added', 'in_wishlist' => true);
        }
    }
    
    /**
     * Get seller's sites
     */
    public static function get_seller_sites($seller_id) {
        global $wpdb;
        $table = self::get_sites_table();
        
        $sites = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE seller_id = %d ORDER BY created_at DESC",
            intval($seller_id)
        ), ARRAY_A);
        
        return $sites ? $sites : array();
    }
    
    /**
     * Get stats
     */
    public static function get_stats() {
        global $wpdb;
        $table = self::get_sites_table();
        
        return array(
            'total_sites' => intval($wpdb->get_var("SELECT COUNT(*) FROM {$table}")),
            'active_sites' => intval($wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'")),
            'pending_sites' => intval($wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE approval_status = 'pending'")),
            'total_sellers' => intval($wpdb->get_var("SELECT COUNT(DISTINCT seller_id) FROM {$table}"))
        );
    }
}
