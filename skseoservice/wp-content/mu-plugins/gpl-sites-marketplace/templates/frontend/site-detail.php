<?php

if (!defined('ABSPATH')) {
    exit;
}

// Site data is passed as $site from rewrite handler
if (!isset($site) || empty($site)) {
    $site = isset($gpl_site_data) ? $gpl_site_data : null;
}

if (!$site) {
?>
<div style="max-width:800px;margin:50px auto;text-align:center;padding:60px 20px;background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
    <div style="font-size:64px;margin-bottom:20px;">🔍</div>
    <h2 style="font-size:28px;color:#1f2937;margin:0 0 15px;">Site Not Found</h2>
    <p style="color:#6b7280;margin:0 0 30px;">The site you're looking for doesn't exist or has been removed.</p>
    <a href="<?php echo home_url('/marketplace/'); ?>" style="display:inline-block;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:600;">← Back to Marketplace</a>
</div>
<?php
    return;
}

// Helper function
if (!function_exists('gpl_format_number')) {
    function gpl_format_number($num) {
        if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
        if ($num >= 1000) return round($num / 1000, 1) . 'K';
        return number_format($num);
    }
}

// Get seller info
$seller = get_userdata($site['seller_id']);
$seller_name = $seller ? $seller->display_name : 'Unknown Seller';
$seller_email = $seller ? $seller->user_email : '';
$seller_registered = $seller ? date('M Y', strtotime($seller->user_registered)) : '';

// Prepare display values
$domain = esc_html($site['website_url']);
$site_name = esc_html($site['website_name'] ?: $site['website_url']);
$favicon = function_exists('gpl_get_local_favicon') ? gpl_get_local_favicon($site['website_url']) : '';
$niche = esc_html($site['niche'] ?: 'General');
$niche_slug = sanitize_title($niche);

// Metrics
$da = intval($site['domain_authority'] ?? 0);
$dr = intval($site['domain_rating'] ?? 0);
$as = intval($site['authority_score'] ?? 0);
$tf = intval($site['trust_flow'] ?? 0);
$cf = intval($site['citation_flow'] ?? 0);
$spam_score = intval($site['spam_score'] ?? 0);
$domain_age = esc_html($site['domain_age'] ?? '');

// Traffic
$ahrefs_traffic = intval($site['ahrefs_traffic'] ?? 0);
$ahrefs_keywords = intval($site['ahrefs_keywords'] ?? 0);
$semrush_traffic = intval($site['semrush_traffic'] ?? 0);
$semrush_keywords = intval($site['semrush_keywords'] ?? 0);
$similarweb_traffic = intval($site['similarweb_traffic'] ?? 0);

// Pricing
$price = floatval($site['price'] ?? 0);
$sale_price = floatval($site['sale_price'] ?? 0);
$currency = $site['currency'] ?? 'USD';
$currency_symbol = $currency === 'EUR' ? '€' : ($currency === 'GBP' ? '£' : ($currency === 'INR' ? '₹' : '$'));
$has_discount = $sale_price > 0 && $sale_price < $price;
$discount_percent = $has_discount ? round((($price - $sale_price) / $price) * 100) : 0;
$display_price = $has_discount ? $sale_price : $price;

// Details
$link_type = esc_html($site['link_type'] ?? 'DoFollow');
$link_validity = esc_html($site['link_validity'] ?? 'Permanent');
$backlinks = intval($site['backlinks_allowed'] ?? 1);
$tat = esc_html($site['tat'] ?? '3-5 days');
$word_count = intval($site['word_count'] ?? 500);
$content_by = esc_html($site['content_written_by'] ?? 'Both');
$sample_url = esc_html($site['sample_url'] ?? '');
$guidelines = esc_html($site['guidelines'] ?? '');
$tld = esc_html($site['tld'] ?? '');

// Location
$country = esc_html($site['country'] ?? 'United States');
$country_code = esc_html($site['country_code'] ?? 'US');
$language = esc_html($site['language'] ?? 'English');
$traffic_countries = $site['traffic_countries'] ?? '';

// Features
$google_news = !empty($site['google_news']);
$marked_sponsored = !empty($site['marked_sponsored']);
$sports_gaming = !empty($site['sports_gaming_allowed']);
$pharmacy = !empty($site['pharmacy_allowed']);
$crypto = !empty($site['crypto_allowed']);
$foreign_lang = !empty($site['foreign_lang_allowed']);

// Current user
$is_logged_in = is_user_logged_in();
$current_user_id = get_current_user_id();
$is_admin = current_user_can('manage_options');
$is_owner = $is_logged_in && ($current_user_id == $site['seller_id']);
$can_edit = $is_admin || $is_owner;

// Check wishlist from database table
$in_wishlist = false;
if ($is_logged_in) {
    global $wpdb;
    $wishlist_table = $wpdb->prefix . 'gpl_wishlist';
    $in_wishlist = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wishlist_table} WHERE user_id = %d AND site_id = %d",
        $current_user_id,
        intval($site['id'])
    ));
    $in_wishlist = !empty($in_wishlist);
}

// Share URLs - NO category in URL
$share_url = urlencode(home_url("/site/" . sanitize_title($domain) . "/"));
$share_title = urlencode($site_name . " - Guest Post Opportunity");
?>

<style>
/* Site Detail Page Styles - Scoped */
.gpl-detail-page * { box-sizing: border-box; }
.gpl-detail-page { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; max-width: 1400px; margin: 0 auto; padding: 20px; color: #1f2937; line-height: 1.6; }
.gpl-detail-page a {
    text-decoration: auto;
    color: #3c2ca4;
}

/* Header */
.gpl-detail-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb; }
.gpl-back-link { display: inline-flex; align-items: center; gap: 8px; color: #6b7280; font-weight: 500; transition: color 0.2s; }
.gpl-back-link:hover { color: #4f46e5; }
.gpl-header-user { display: flex; align-items: center; gap: 15px; }
.gpl-user-greeting { color: #6b7280; }

/* Buttons */
.gpl-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; border: none; cursor: pointer; transition: all 0.2s; }
.gpl-btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; }
.gpl-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
.gpl-btn-outline { background: #fff; color: #4f46e5; border: 2px solid #e5e7eb; }
.gpl-btn-outline:hover { border-color: #4f46e5; background: #f8f7ff; }
.gpl-btn-sm { padding: 8px 16px; font-size: 13px; }
.gpl-btn-block { width: 100%; }
.gpl-btn-edit { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
.gpl-btn-edit:hover { background: #fcd34d; }

/* Main Layout */
.gpl-detail-layout { display: grid; grid-template-columns: 1fr 360px; gap: 30px; }

/* Hero Section */
.gpl-hero { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; padding: 40px; color: #fff; margin-bottom: 30px; }
.gpl-hero-inner { display: flex; align-items: flex-start; gap: 25px; }
.gpl-hero-avatar { width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.gpl-hero-avatar img { width: 64px; height: 64px; border-radius: 8px; }
.gpl-hero-info { flex: 1; min-width: 0; }
.gpl-hero-title { font-size: 28px; font-weight: 700; margin: 0 0 8px; }
.gpl-hero-domain { font-size: 16px; opacity: 0.9; margin-bottom: 15px; }
.gpl-hero-tags { display: flex; flex-wrap: wrap; gap: 8px; }
.gpl-hero-tag { background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500; }
.gpl-hero-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 15px; }
.gpl-hero-price { text-align: right; }
.gpl-price-amount { font-size: 36px; font-weight: 700; }
.gpl-price-original { font-size: 18px; text-decoration: line-through; opacity: 0.7; margin-left: 10px; }
.gpl-price-discount { background: #fbbf24; color: #000; padding: 4px 10px; border-radius: 6px; font-size: 13px; font-weight: 700; margin-right: 10px; }

/* Section Cards */
.gpl-section { background: #fff; border-radius: 16px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; }
.gpl-section-title { font-size: 18px; font-weight: 700; margin: 0 0 20px; padding-bottom: 15px; border-bottom: 2px solid #f3f4f6; display: flex; align-items: center; gap: 10px; }

/* Metrics Grid */
.gpl-metrics-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; margin-bottom: 25px; }
.gpl-metric-card { background: #f9fafb; border-radius: 12px; padding: 20px; text-align: center; border: 1px solid #e5e7eb; }
.gpl-metric-label { font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
.gpl-metric-value { font-size: 32px; font-weight: 700; margin-bottom: 8px; }
.gpl-metric-bar { height: 6px; background: #e5e7eb; border-radius: 3px; overflow: hidden; }
.gpl-metric-bar div { height: 100%; border-radius: 3px; }
.gpl-metric-source { font-size: 11px; color: #9ca3af; margin-top: 8px; }
.gpl-metric-da .gpl-metric-value { color: #2563eb; }
.gpl-metric-da .gpl-metric-bar div { background: #2563eb; }
.gpl-metric-dr .gpl-metric-value { color: #dc2626; }
.gpl-metric-dr .gpl-metric-bar div { background: #dc2626; }
.gpl-metric-as .gpl-metric-value { color: #059669; }
.gpl-metric-as .gpl-metric-bar div { background: #059669; }
.gpl-metric-tf .gpl-metric-value { color: #7c3aed; }
.gpl-metric-tf .gpl-metric-bar div { background: #7c3aed; }
.gpl-metric-cf .gpl-metric-value { color: #0891b2; }
.gpl-metric-cf .gpl-metric-bar div { background: #0891b2; }
.gpl-metric-spam .gpl-metric-value { color: #dc2626; }
.gpl-metric-spam .gpl-metric-bar div { background: #dc2626; }

/* Traffic Grid */
.gpl-traffic-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
.gpl-traffic-card { display: flex; align-items: center; gap: 15px; background: #f9fafb; border-radius: 12px; padding: 18px; border: 1px solid #e5e7eb; }
.gpl-traffic-icon { font-size: 28px; }
.gpl-traffic-value { font-size: 22px; font-weight: 700; color: #1f2937; }
.gpl-traffic-label { font-size: 13px; color: #6b7280; }

/* Info Grid */
.gpl-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.gpl-info-item { display: flex; flex-direction: column; gap: 5px; }
.gpl-info-label { font-size: 13px; color: #6b7280; font-weight: 500; }
.gpl-info-value { font-size: 15px; font-weight: 600; color: #1f2937; }
.gpl-link-dofollow { color: #059669; }
.gpl-link-nofollow { color: #dc2626; }

/* Features */
.gpl-features-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
.gpl-features-col h3 { font-size: 15px; font-weight: 600; margin: 0 0 15px; color: #374151; }
.gpl-feature-list { display: flex; flex-direction: column; gap: 10px; }
.gpl-feature-item { display: flex; align-items: center; gap: 10px; padding: 10px 15px; background: #f9fafb; border-radius: 8px; font-size: 14px; }
.gpl-feature-yes { background: #ecfdf5; color: #065f46; }
.gpl-feature-no { background: #fef2f2; color: #991b1b; }

/* Countries */
.gpl-country-bar { display: flex; align-items: center; gap: 15px; margin-bottom: 12px; }
.gpl-country-code { font-weight: 600; width: 35px; }
.gpl-country-progress { flex: 1; height: 10px; background: #e5e7eb; border-radius: 5px; overflow: hidden; }
.gpl-country-progress div { height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 5px; }
.gpl-country-percent { font-weight: 600; width: 50px; text-align: right; }

/* Sample URL - Text Only */
.gpl-sample-box { background: #f9fafb; border: 2px dashed #e5e7eb; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 15px; }
.gpl-sample-icon { font-size: 24px; }
.gpl-sample-url { font-family: monospace; font-size: 14px; color: #6b7280; word-break: break-all; }

/* Guidelines */
.gpl-guidelines-box { background: #fefce8; border-radius: 12px; padding: 20px; color: #713f12; line-height: 1.8; }

/* Seller Card */
.gpl-seller-card { display: flex; align-items: center; gap: 20px; }
.gpl-seller-avatar { width: 64px; height: 64px; border-radius: 50%; overflow: hidden; background: #e5e7eb; }
.gpl-seller-avatar img { width: 100%; height: 100%; object-fit: cover; }
.gpl-seller-name { font-size: 18px; font-weight: 600; margin-bottom: 4px; }
.gpl-seller-since { font-size: 14px; color: #6b7280; }

/* Sidebar */
.gpl-sidebar { display: flex; flex-direction: column; gap: 20px; }
.gpl-sidebar-card { background: #fff; border-radius: 16px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e5e7eb; }
.gpl-sidebar-card h3 { font-size: 16px; font-weight: 600; margin: 0 0 15px; }

/* Summary */
.gpl-summary-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
.gpl-summary-item:last-child { border-bottom: none; }
.gpl-summary-label { color: #6b7280; }
.gpl-summary-value { font-weight: 600; }

/* Price Card */
.gpl-price-card { background: linear-gradient(135deg, #f8f7ff 0%, #fff 100%); text-align: center; }
.gpl-sidebar-price { margin-bottom: 20px; }
.gpl-sidebar-amount { font-size: 42px; font-weight: 700; color: #4f46e5; }
.gpl-sidebar-original { font-size: 18px; color: #9ca3af; text-decoration: line-through; }
.gpl-sidebar-discount { display: inline-block; background: #fbbf24; color: #000; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 700; margin-bottom: 10px; }
.gpl-login-note { font-size: 13px; color: #6b7280; margin-top: 15px; }

/* Share */
.gpl-share-buttons { display: flex; gap: 10px; }
.gpl-share-btn { width: 44px; height: 44px; border-radius: 10px; border: none; cursor: pointer; font-size: 18px; font-weight: 700; transition: transform 0.2s; }
.gpl-share-btn:hover { transform: scale(1.1); }
.gpl-share-twitter { background: #000; color: #fff; }
.gpl-share-linkedin { background: #0077b5; color: #fff; }
.gpl-share-facebook { background: #1877f2; color: #fff; }
.gpl-share-copy { background: #e5e7eb; color: #374151; }

/* Wishlist */
.gpl-in-wishlist { background: #fef2f2 !important; border-color: #fecaca !important; color: #dc2626 !important; }

/* Modal */
.gpl-modal { display: none; position: fixed; inset: 0; z-index: 99999; align-items: center; justify-content: center; }
.gpl-modal.gpl-modal-open { display: flex; }
.gpl-modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.6); }
.gpl-modal-content { position: relative; background: #fff; border-radius: 20px; max-width: 500px; width: 90%; max-height: 90vh; overflow: auto; }
.gpl-modal-close { position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 28px; color: #9ca3af; cursor: pointer; }
.gpl-modal-header { padding: 25px 25px 0; }
.gpl-modal-header h2 { margin: 0; font-size: 24px; }
.gpl-modal-body { padding: 25px; }
.gpl-modal-footer { padding: 0 25px 25px; display: flex; gap: 15px; justify-content: flex-end; }
.gpl-purchase-site { display: flex; align-items: center; gap: 15px; background: #f9fafb; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
.gpl-purchase-icon { width: 48px; height: 48px; border-radius: 10px; }
.gpl-purchase-info { flex: 1; }
.gpl-purchase-name { font-weight: 600; }
.gpl-purchase-domain { font-size: 13px; color: #6b7280; }
.gpl-purchase-price { font-size: 24px; font-weight: 700; color: #4f46e5; }
.gpl-purchase-details { font-size: 14px; color: #6b7280; }
.gpl-purchase-details p { margin: 10px 0; }

/* Responsive */
@media (max-width: 1024px) {
    .gpl-detail-layout { grid-template-columns: 1fr; }
    .gpl-sidebar { order: -1; }
    .gpl-metrics-grid { grid-template-columns: repeat(3, 1fr); }
    .gpl-hero-inner { flex-direction: column; }
    .gpl-hero-actions { align-items: flex-start; width: 100%; flex-direction: row; justify-content: space-between; }
}
@media (max-width: 640px) {
    .gpl-detail-page { padding: 15px; }
    .gpl-hero { padding: 25px; }
    .gpl-hero-title { font-size: 22px; }
    .gpl-price-amount { font-size: 28px; }
    .gpl-metrics-grid { grid-template-columns: repeat(2, 1fr); }
    .gpl-info-grid { grid-template-columns: 1fr 1fr; }
    .gpl-features-grid { grid-template-columns: 1fr; }
    .gpl-traffic-grid { grid-template-columns: 1fr; }
    .gpl-detail-header { flex-direction: column; gap: 15px; align-items: flex-start; }
}
</style>

<div class="gpl-detail-page">
    
    <!-- Header -->
    <div class="gpl-detail-header">
        <a href="<?php echo home_url('/marketplace/'); ?>" class="gpl-back-link">← Back to Marketplace</a>
        <div class="gpl-header-user">
            <?php if ($is_logged_in): 
                $user = wp_get_current_user();
            ?>
                <span class="gpl-user-greeting">Hello, <?php echo esc_html($user->display_name); ?></span>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="gpl-btn gpl-btn-outline gpl-btn-sm">Logout</a>
            <?php else: ?>
                <a href="<?php echo home_url('/login/'); ?>" class="gpl-btn gpl-btn-primary gpl-btn-sm">Login</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Layout -->
    <div class="gpl-detail-layout">
        
        <!-- Main Content -->
        <div class="gpl-main">
            
            <!-- Hero Section -->
            <div class="gpl-hero">
                <div class="gpl-hero-inner">
                    <div class="gpl-hero-avatar">
                        <img src="<?php echo esc_attr($favicon); ?>" alt="<?php echo $site_name; ?>" style="width:64px;height:64px;">
                    </div>
                    <div class="gpl-hero-info">
                        <h1 class="gpl-hero-title"><?php echo $site_name; ?></h1>
                        <div class="gpl-hero-domain"><?php echo $domain; ?></div>
                        <div class="gpl-hero-tags">
                            <span class="gpl-hero-tag"><?php echo $niche; ?></span>
                            <span class="gpl-hero-tag"><?php echo $country; ?></span>
                            <span class="gpl-hero-tag"><?php echo $language; ?></span>
                            <?php if ($tld): ?><span class="gpl-hero-tag"><?php echo $tld; ?></span><?php endif; ?>
                            <?php if ($domain_age): ?><span class="gpl-hero-tag"><?php echo $domain_age; ?> old</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="gpl-hero-actions">
                        <div class="gpl-hero-price">
                            <?php if ($has_discount): ?>
                                <span class="gpl-price-discount">-<?php echo $discount_percent; ?>%</span>
                            <?php endif; ?>
                            <span class="gpl-price-amount"><?php echo $currency_symbol . number_format($display_price, 2); ?></span>
                            <?php if ($has_discount): ?>
                                <span class="gpl-price-original"><?php echo $currency_symbol . number_format($price, 2); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($can_edit): ?>
                        <a href="<?php echo esc_url(home_url('/add-site/?edit=' . $site['id'])); ?>" class="gpl-btn gpl-btn-edit gpl-btn-sm">
                            ✏️ Edit Site
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Authority Metrics -->
            <div class="gpl-section">
                <h2 class="gpl-section-title">📊 Authority & Traffic Metrics</h2>
                <div class="gpl-metrics-grid">
                    <div class="gpl-metric-card gpl-metric-da">
                        <div class="gpl-metric-label">DA</div>
                        <div class="gpl-metric-value"><?php echo $da; ?></div>
                        <div class="gpl-metric-bar"><div style="width: <?php echo $da; ?>%"></div></div>
                        <div class="gpl-metric-source">Moz</div>
                    </div>
                    <div class="gpl-metric-card gpl-metric-dr">
                        <div class="gpl-metric-label">DR</div>
                        <div class="gpl-metric-value"><?php echo $dr; ?></div>
                        <div class="gpl-metric-bar"><div style="width: <?php echo $dr; ?>%"></div></div>
                        <div class="gpl-metric-source">Ahrefs</div>
                    </div>
                    <div class="gpl-metric-card gpl-metric-as">
                        <div class="gpl-metric-label">AS</div>
                        <div class="gpl-metric-value"><?php echo $as; ?></div>
                        <div class="gpl-metric-bar"><div style="width: <?php echo $as; ?>%"></div></div>
                        <div class="gpl-metric-source">SEMrush</div>
                    </div>
                    <div class="gpl-metric-card gpl-metric-tf">
                        <div class="gpl-metric-label">TF</div>
                        <div class="gpl-metric-value"><?php echo $tf; ?></div>
                        <div class="gpl-metric-bar"><div style="width: <?php echo $tf; ?>%"></div></div>
                        <div class="gpl-metric-source">Majestic</div>
                    </div>
                    <div class="gpl-metric-card gpl-metric-cf">
                        <div class="gpl-metric-label">CF</div>
                        <div class="gpl-metric-value"><?php echo $cf; ?></div>
                        <div class="gpl-metric-bar"><div style="width: <?php echo $cf; ?>%"></div></div>
                        <div class="gpl-metric-source">Majestic</div>
                    </div>
                    <div class="gpl-metric-card gpl-metric-spam">
                        <div class="gpl-metric-label">Spam</div>
                        <div class="gpl-metric-value"><?php echo $spam_score; ?>%</div>
                        <div class="gpl-metric-bar"><div style="width: <?php echo $spam_score; ?>%"></div></div>
                        <div class="gpl-metric-source">Moz</div>
                    </div>
                </div>
                
                <div class="gpl-traffic-grid">
                    <div class="gpl-traffic-card">
                        <div class="gpl-traffic-icon">🔥</div>
                        <div>
                            <div class="gpl-traffic-value"><?php echo gpl_format_number($ahrefs_traffic); ?></div>
                            <div class="gpl-traffic-label">Ahrefs Traffic</div>
                        </div>
                    </div>
                    <div class="gpl-traffic-card">
                        <div class="gpl-traffic-icon">🔑</div>
                        <div>
                            <div class="gpl-traffic-value"><?php echo gpl_format_number($ahrefs_keywords); ?></div>
                            <div class="gpl-traffic-label">Ahrefs Keywords</div>
                        </div>
                    </div>
                    <div class="gpl-traffic-card">
                        <div class="gpl-traffic-icon">📈</div>
                        <div>
                            <div class="gpl-traffic-value"><?php echo gpl_format_number($semrush_traffic); ?></div>
                            <div class="gpl-traffic-label">SEMrush Traffic</div>
                        </div>
                    </div>
                    <div class="gpl-traffic-card">
                        <div class="gpl-traffic-icon">🎯</div>
                        <div>
                            <div class="gpl-traffic-value"><?php echo gpl_format_number($semrush_keywords); ?></div>
                            <div class="gpl-traffic-label">SEMrush Keywords</div>
                        </div>
                    </div>
                    <?php if ($similarweb_traffic > 0): ?>
                    <div class="gpl-traffic-card">
                        <div class="gpl-traffic-icon">🌐</div>
                        <div>
                            <div class="gpl-traffic-value"><?php echo gpl_format_number($similarweb_traffic); ?></div>
                            <div class="gpl-traffic-label">SimilarWeb</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Link Details -->
            <div class="gpl-section">
                <h2 class="gpl-section-title">🔗 Link & Posting Details</h2>
                <div class="gpl-info-grid">
                    <div class="gpl-info-item">
                        <span class="gpl-info-label">Link Type</span>
                        <span class="gpl-info-value gpl-link-<?php echo strtolower($link_type); ?>"><?php echo $link_type; ?></span>
                    </div>
                    <div class="gpl-info-item">
                        <span class="gpl-info-label">Link Validity</span>
                        <span class="gpl-info-value"><?php echo $link_validity; ?></span>
                    </div>
                    <div class="gpl-info-item">
                        <span class="gpl-info-label">Backlinks Allowed</span>
                        <span class="gpl-info-value"><?php echo $backlinks; ?></span>
                    </div>
                    <div class="gpl-info-item">
                        <span class="gpl-info-label">Turnaround Time</span>
                        <span class="gpl-info-value"><?php echo $tat; ?></span>
                    </div>
                    <div class="gpl-info-item">
                        <span class="gpl-info-label">Word Count</span>
                        <span class="gpl-info-value"><?php echo number_format($word_count); ?>+ words</span>
                    </div>
                    <div class="gpl-info-item">
                        <span class="gpl-info-label">Content Written By</span>
                        <span class="gpl-info-value"><?php echo $content_by; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Features & Restrictions -->
            <div class="gpl-section">
                <h2 class="gpl-section-title">✨ Features & Restrictions</h2>
                <div class="gpl-features-grid">
                    <div class="gpl-features-col">
                        <h3>Features</h3>
                        <div class="gpl-feature-list">
                            <div class="gpl-feature-item <?php echo $google_news ? 'gpl-feature-yes' : 'gpl-feature-no'; ?>">
                                <span><?php echo $google_news ? '✅' : '❌'; ?></span>
                                <span>Google News Approved</span>
                            </div>
                            <div class="gpl-feature-item <?php echo $marked_sponsored ? 'gpl-feature-yes' : 'gpl-feature-no'; ?>">
                                <span><?php echo $marked_sponsored ? '✅' : '❌'; ?></span>
                                <span>Marked as Sponsored</span>
                            </div>
                        </div>
                    </div>
                    <div class="gpl-features-col">
                        <h3>Content Restrictions</h3>
                        <div class="gpl-feature-list">
                            <div class="gpl-feature-item <?php echo $sports_gaming ? 'gpl-feature-yes' : 'gpl-feature-no'; ?>">
                                <span><?php echo $sports_gaming ? '✅' : '🚫'; ?></span>
                                <span>Sports/Gaming</span>
                            </div>
                            <div class="gpl-feature-item <?php echo $crypto ? 'gpl-feature-yes' : 'gpl-feature-no'; ?>">
                                <span><?php echo $crypto ? '✅' : '🚫'; ?></span>
                                <span>Crypto/Finance</span>
                            </div>
                            <div class="gpl-feature-item <?php echo $pharmacy ? 'gpl-feature-yes' : 'gpl-feature-no'; ?>">
                                <span><?php echo $pharmacy ? '✅' : '🚫'; ?></span>
                                <span>Pharmacy/CBD</span>
                            </div>
                            <div class="gpl-feature-item <?php echo $foreign_lang ? 'gpl-feature-yes' : 'gpl-feature-no'; ?>">
                                <span><?php echo $foreign_lang ? '✅' : '🚫'; ?></span>
                                <span>Foreign Language</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($traffic_countries): ?>
            <!-- Traffic Distribution -->
            <div class="gpl-section">
                <h2 class="gpl-section-title">🌍 Traffic Distribution</h2>
                <div class="gpl-countries-chart">
                    <?php
                    $countries_arr = explode(',', $traffic_countries);
                    foreach ($countries_arr as $c) {
                        $parts = explode(':', trim($c));
                        if (count($parts) === 2) {
                            $code = strtoupper(trim($parts[0]));
                            $percent = intval($parts[1]);
                            echo '<div class="gpl-country-bar">';
                            echo '<span class="gpl-country-code">' . esc_html($code) . '</span>';
                            echo '<div class="gpl-country-progress"><div style="width: ' . $percent . '%"></div></div>';
                            echo '<span class="gpl-country-percent">' . $percent . '%</span>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($sample_url): ?>
            <!-- Sample Post - Text Only -->
            <div class="gpl-section">
                <h2 class="gpl-section-title">📝 Sample Post</h2>
                <div class="gpl-sample-box">
                    <span class="gpl-sample-icon">🔗</span>
                    <span class="gpl-sample-url"><?php echo $sample_url; ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($guidelines): ?>
            <!-- Guidelines -->
            <div class="gpl-section">
                <h2 class="gpl-section-title">📖 Publisher Guidelines</h2>
                <div class="gpl-guidelines-box">
                    <?php echo nl2br($guidelines); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Seller Info -->
            <div class="gpl-section">
                <h2 class="gpl-section-title">👤 Seller Information</h2>
                <div class="gpl-seller-card">
                    <div class="gpl-seller-avatar">
                        <?php echo get_avatar($site['seller_id'], 64); ?>
                    </div>
                    <div>
                        <div class="gpl-seller-name"><?php echo $seller_name; ?></div>
                        <div class="gpl-seller-since">Member since <?php echo $seller_registered; ?></div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Sidebar -->
        <div class="gpl-sidebar">
            
            <!-- Quick Summary -->
            <div class="gpl-sidebar-card">
                <h3>Quick Summary</h3>
                <div class="gpl-summary-item">
                    <span class="gpl-summary-label">Domain</span>
                    <span class="gpl-summary-value"><?php echo $domain; ?></span>
                </div>
                <div class="gpl-summary-item">
                    <span class="gpl-summary-label">DA / DR / AS</span>
                    <span class="gpl-summary-value"><?php echo $da; ?> / <?php echo $dr; ?> / <?php echo $as; ?></span>
                </div>
                <div class="gpl-summary-item">
                    <span class="gpl-summary-label">TF / CF</span>
                    <span class="gpl-summary-value"><?php echo $tf; ?> / <?php echo $cf; ?></span>
                </div>
                <div class="gpl-summary-item">
                    <span class="gpl-summary-label">Spam Score</span>
                    <span class="gpl-summary-value"><?php echo $spam_score; ?>%</span>
                </div>
                <?php if ($domain_age): ?>
                <div class="gpl-summary-item">
                    <span class="gpl-summary-label">Domain Age</span>
                    <span class="gpl-summary-value"><?php echo $domain_age; ?></span>
                </div>
                <?php endif; ?>
                <div class="gpl-summary-item">
                    <span class="gpl-summary-label">Traffic</span>
                    <span class="gpl-summary-value"><?php echo gpl_format_number($ahrefs_traffic); ?></span>
                </div>
                <div class="gpl-summary-item">
                    <span class="gpl-summary-label">Niche</span>
                    <span class="gpl-summary-value"><?php echo $niche; ?></span>
                </div>
                <div class="gpl-summary-item">
                    <span class="gpl-summary-label">Link Type</span>
                    <span class="gpl-summary-value"><?php echo $link_type; ?></span>
                </div>
                <div class="gpl-summary-item">
                    <span class="gpl-summary-label">TAT</span>
                    <span class="gpl-summary-value"><?php echo $tat; ?></span>
                </div>
            </div>
            
            <!-- Price Card -->
            <div class="gpl-sidebar-card gpl-price-card">
                <div class="gpl-sidebar-price">
                    <?php if ($has_discount): ?>
                        <span class="gpl-sidebar-discount">Save <?php echo $discount_percent; ?>%</span><br>
                    <?php endif; ?>
                    <div class="gpl-sidebar-amount"><?php echo $currency_symbol . number_format($display_price, 2); ?></div>
                    <?php if ($has_discount): ?>
                        <div class="gpl-sidebar-original"><?php echo $currency_symbol . number_format($price, 2); ?></div>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_logged_in): ?>
                    <button class="gpl-btn gpl-btn-primary gpl-btn-block gpl-btn-buy" data-site-id="<?php echo $site['id']; ?>">
                        🛒 Buy Now
                    </button>
                    <button class="gpl-btn gpl-btn-outline gpl-btn-block gpl-btn-wishlist <?php echo $in_wishlist ? 'gpl-in-wishlist' : ''; ?>" 
                            data-site-id="<?php echo $site['id']; ?>" style="margin-top: 10px;">
                        <?php echo $in_wishlist ? '❤️ In Wishlist' : '🤍 Add to Wishlist'; ?>
                    </button>
                <?php else: ?>
                    <a href="<?php echo home_url('/login/'); ?>" class="gpl-btn gpl-btn-primary gpl-btn-block">
                        Login to Purchase
                    </a>
                    <p class="gpl-login-note">Login or register to buy this site or add to wishlist</p>
                <?php endif; ?>
            </div>
            
            <!-- Share Card -->
            <div class="gpl-sidebar-card">
                <h3>Share this site</h3>
                <div class="gpl-share-buttons">
                    <button class="gpl-share-btn gpl-share-twitter" onclick="window.open('https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>', '_blank')">𝕏</button>
                    <button class="gpl-share-btn gpl-share-linkedin" onclick="window.open('https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $share_url; ?>', '_blank')">in</button>
                    <button class="gpl-share-btn gpl-share-facebook" onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>', '_blank')">f</button>
                    <button class="gpl-share-btn gpl-share-copy" onclick="navigator.clipboard.writeText(decodeURIComponent('<?php echo $share_url; ?>')).then(function(){alert('Link copied!')})">📋</button>
                </div>
            </div>
            
        </div>
        
    </div>
    
</div>

<!-- Purchase Modal -->
<div class="gpl-modal" id="gpl-purchase-modal">
    <div class="gpl-modal-overlay"></div>
    <div class="gpl-modal-content">
        <button class="gpl-modal-close">&times;</button>
        <div class="gpl-modal-header">
            <h2>Confirm Purchase</h2>
        </div>
        <div class="gpl-modal-body">
            <div class="gpl-purchase-site">
                <img src="<?php echo esc_attr($favicon); ?>" alt="" class="gpl-purchase-icon" style="width:48px;height:48px;">
                <div class="gpl-purchase-info">
                    <div class="gpl-purchase-name"><?php echo $site_name; ?></div>
                    <div class="gpl-purchase-domain"><?php echo $domain; ?></div>
                </div>
                <div class="gpl-purchase-price"><?php echo $currency_symbol . number_format($display_price, 2); ?></div>
            </div>
            <div class="gpl-purchase-details">
                <p>By clicking "Confirm Purchase", you agree to proceed with this guest post opportunity.</p>
                <p>The seller will be notified and will contact you at your registered email address.</p>
            </div>
        </div>
        <div class="gpl-modal-footer">
            <button class="gpl-btn gpl-btn-outline gpl-modal-cancel">Cancel</button>
            <button class="gpl-btn gpl-btn-primary gpl-confirm-purchase" data-site-id="<?php echo $site['id']; ?>">Confirm Purchase</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Config
    var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var nonce = '<?php echo wp_create_nonce('gpl_sites_nonce'); ?>';
    var isLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
    
    // Purchase modal
    var modal = document.getElementById('gpl-purchase-modal');
    var buyBtn = document.querySelector('.gpl-btn-buy');
    var closeBtn = modal ? modal.querySelector('.gpl-modal-close') : null;
    var cancelBtn = modal ? modal.querySelector('.gpl-modal-cancel') : null;
    var overlay = modal ? modal.querySelector('.gpl-modal-overlay') : null;
    
    if (buyBtn && modal) {
        buyBtn.addEventListener('click', function() {
            if (!isLoggedIn) {
                alert('Please login to make a purchase.');
                window.location.href = '<?php echo home_url('/login/'); ?>';
                return;
            }
            modal.classList.add('gpl-modal-open');
        });
    }
    
    function closeModal() {
        if (modal) modal.classList.remove('gpl-modal-open');
    }
    
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', closeModal);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });
    
    // Confirm purchase
    var confirmBtn = document.querySelector('.gpl-confirm-purchase');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            var siteId = this.getAttribute('data-site-id');
            this.disabled = true;
            this.textContent = 'Processing...';
            
            var formData = new FormData();
            formData.append('action', 'gpl_purchase_site');
            formData.append('nonce', nonce);
            formData.append('site_id', siteId);
            
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    alert('Purchase request sent! Check your email for details.');
                    closeModal();
                } else {
                    alert(data.data && data.data.message ? data.data.message : 'Error processing purchase');
                }
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirm Purchase';
            })
            .catch(function() {
                alert('Network error. Please try again.');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirm Purchase';
            });
        });
    }
    
    // Wishlist toggle
    var wishlistBtn = document.querySelector('.gpl-btn-wishlist');
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', function() {
            if (!isLoggedIn) {
                alert('Please login to add to wishlist.');
                window.location.href = '<?php echo home_url('/login/'); ?>';
                return;
            }
            
            var siteId = this.getAttribute('data-site-id');
            var btn = this;
            btn.disabled = true;
            
            var formData = new FormData();
            formData.append('action', 'gpl_toggle_wishlist');
            formData.append('nonce', nonce);
            formData.append('site_id', siteId);
            
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    if (data.data.in_wishlist) {
                        btn.classList.add('gpl-in-wishlist');
                        btn.innerHTML = '❤️ In Wishlist';
                    } else {
                        btn.classList.remove('gpl-in-wishlist');
                        btn.innerHTML = '🤍 Add to Wishlist';
                    }
                } else {
                    alert(data.data && data.data.message ? data.data.message : 'Error updating wishlist');
                }
                btn.disabled = false;
            })
            .catch(function() {
                alert('Network error. Please try again.');
                btn.disabled = false;
            });
        });
    }
});
</script>
