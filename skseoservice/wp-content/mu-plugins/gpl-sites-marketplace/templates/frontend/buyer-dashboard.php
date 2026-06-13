<?php
/**
 * Buyer Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

$user = wp_get_current_user();
$user_id = $user->ID;

$per_page = 12;
$current_page = isset($_GET['wpage']) ? max(1, intval($_GET['wpage'])) : 1;
$offset = ($current_page - 1) * $per_page;

$all_wishlist = GPL_Sites_Database::get_user_wishlist($user_id);
$total_wishlist = count($all_wishlist);
$wishlist = array_slice($all_wishlist, $offset, $per_page);
$total_pages = ceil($total_wishlist / $per_page);

$stats = array(
    'wishlist_count' => $total_wishlist,
    'member_since' => date('M Y', strtotime($user->user_registered))
);
?>

<div class="gpl-dashboard-wrapper">
    <div class="gpl-dashboard-header">
        <div class="gpl-user-profile">
            <div class="gpl-user-avatar">
                <?php echo get_avatar($user_id, 80); ?>
            </div>
            <div class="gpl-user-info">
                <h1><?php echo esc_html($user->display_name); ?></h1>
                <p class="gpl-user-email"><?php echo esc_html($user->user_email); ?></p>
                <span class="gpl-role-badge buyer">Buyer</span>
            </div>
        </div>
        <div class="gpl-dashboard-actions">
            <a href="<?php echo home_url('/marketplace/'); ?>" class="gpl-btn gpl-btn-primary">Browse Sites</a>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="gpl-btn gpl-btn-outline">Logout</a>
        </div>
    </div>

    <div class="gpl-stats-row">
        <div class="gpl-stat-card">
            <div class="gpl-stat-icon">❤️</div>
            <div class="gpl-stat-content">
                <span class="gpl-stat-value"><?php echo $stats['wishlist_count']; ?></span>
                <span class="gpl-stat-label">Wishlist Items</span>
            </div>
        </div>
        <div class="gpl-stat-card">
            <div class="gpl-stat-icon">📅</div>
            <div class="gpl-stat-content">
                <span class="gpl-stat-value"><?php echo $stats['member_since']; ?></span>
                <span class="gpl-stat-label">Member Since</span>
            </div>
        </div>
    </div>

    <div class="gpl-dashboard-content">
        <div class="gpl-section">
            <div class="gpl-section-header">
                <h2>❤️ My Wishlist</h2>
                <span class="gpl-count">(<?php echo $total_wishlist; ?> sites)</span>
            </div>

            <?php if (empty($wishlist)): ?>
                <div class="gpl-empty-state">
                    <div class="gpl-empty-icon">🤍</div>
                    <h3>Your wishlist is empty</h3>
                    <p>Browse our marketplace and add sites you're interested in!</p>
                    <a href="<?php echo home_url('/marketplace/'); ?>" class="gpl-btn gpl-btn-primary">Browse Sites</a>
                </div>
            <?php else: ?>
                <div class="gpl-wishlist-grid">
                    <?php foreach ($wishlist as $site): 
                        $detail_url = gpl_get_site_detail_url($site);
                        $favicon = gpl_get_local_favicon($site['website_url']);
                        $price = floatval($site['price']);
                        $sale_price = $site['sale_price'] ? floatval($site['sale_price']) : null;
                    ?>
                        <div class="gpl-wish-card" data-site-id="<?php echo intval($site['id']); ?>">
                            <div class="gpl-wish-header">
                                <img src="<?php echo esc_attr($favicon); ?>" alt="" class="gpl-favicon">
                                <div class="gpl-wish-info">
                                    <a href="<?php echo esc_url($detail_url); ?>" class="gpl-wish-name">
                                        <?php echo esc_html($site['website_name'] ?: $site['website_url']); ?>
                                    </a>
                                    <span class="gpl-wish-url"><?php echo esc_html($site['website_url']); ?></span>
                                </div>
                                <button type="button" class="gpl-remove-btn" onclick="removeFromWishlist(<?php echo intval($site['id']); ?>, this)" title="Remove from wishlist">
                                    ✕
                                </button>
                            </div>
                            <div class="gpl-wish-metrics">
                                <span class="gpl-metric"><strong>DA:</strong> <?php echo intval($site['domain_authority']); ?></span>
                                <span class="gpl-metric"><strong>DR:</strong> <?php echo intval($site['domain_rating']); ?></span>
                                <span class="gpl-metric"><strong>Traffic:</strong> <?php echo gpl_format_number($site['ahrefs_traffic']); ?></span>
                            </div>
                            <div class="gpl-wish-footer">
                                <div class="gpl-wish-tags">
                                    <span class="gpl-tag"><?php echo esc_html($site['niche']); ?></span>
                                    <span class="gpl-tag"><?php echo esc_html($site['link_type']); ?></span>
                                </div>
                                <div class="gpl-wish-price">
                                    <?php if ($sale_price && $sale_price < $price): ?>
                                        <span class="gpl-original-price">$<?php echo number_format($price, 0); ?></span>
                                        <span class="gpl-sale-price">$<?php echo number_format($sale_price, 0); ?></span>
                                    <?php else: ?>
                                        <span class="gpl-price">$<?php echo number_format($price, 0); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="gpl-wish-actions">
                                <a href="<?php echo esc_url($detail_url); ?>" class="gpl-btn gpl-btn-small gpl-btn-primary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="gpl-pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?wpage=1" class="gpl-page-link">« First</a>
                            <a href="?wpage=<?php echo $current_page - 1; ?>" class="gpl-page-link">‹ Prev</a>
                        <?php endif; ?>
                        
                        <span class="gpl-page-info">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?wpage=<?php echo $current_page + 1; ?>" class="gpl-page-link">Next ›</a>
                            <a href="?wpage=<?php echo $total_pages; ?>" class="gpl-page-link">Last »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
var gplSites = {
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('gpl_sites_nonce'); ?>',
    homeUrl: '<?php echo home_url('/'); ?>',
    isLoggedIn: true
};
</script>

<style>
.gpl-dashboard-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.gpl-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
    padding: 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    color: #fff;
}

.gpl-user-profile {
    display: flex;
    align-items: center;
    gap: 20px;
}

.gpl-user-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.3);
}

.gpl-user-info h1 {
    margin: 0 0 5px 0;
    font-size: 24px;
    color: #fff;
}

.gpl-user-email {
    margin: 0 0 8px 0;
    opacity: 0.9;
    font-size: 14px;
}

.gpl-role-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.gpl-role-badge.buyer {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

.gpl-dashboard-actions {
    display: flex;
    gap: 10px;
}

.gpl-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.gpl-stat-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.gpl-stat-icon {
    font-size: 32px;
}

.gpl-stat-content {
    display: flex;
    flex-direction: column;
}

.gpl-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a2e;
}

.gpl-stat-label {
    font-size: 13px;
    color: #666;
}

.gpl-section {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.gpl-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.gpl-section-header h2 {
    margin: 0;
    font-size: 20px;
    color: #1a1a2e;
}

.gpl-count {
    color: #666;
    font-size: 14px;
}

.gpl-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.gpl-empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.gpl-empty-state h3 {
    margin: 0 0 10px 0;
    color: #1a1a2e;
}

.gpl-empty-state p {
    color: #666;
    margin-bottom: 20px;
}

.gpl-wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
}

.gpl-wish-card {
    background: #fff;
    border-radius: 16px;
    padding: 0;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.gpl-wish-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.gpl-wish-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 20px 16px 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e5e7eb;
}

.gpl-favicon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    object-fit: cover;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.gpl-wish-info {
    flex: 1;
    min-width: 0;
}

.gpl-wish-name {
    display: block;
    font-weight: 700;
    font-size: 16px;
    color: #1a1a2e;
    text-decoration: none;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.gpl-wish-name:hover {
    color: #667eea;
}

.gpl-wish-url {
    font-size: 13px;
    color: #64748b;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.gpl-remove-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    opacity: 0;
}

.gpl-wish-card:hover .gpl-remove-btn {
    opacity: 1;
}

.gpl-remove-btn:hover {
    background: #ef4444;
    color: #fff;
}

.gpl-wish-metrics {
    display: flex;
    gap: 8px;
    padding: 16px 20px;
    background: #fff;
    flex-wrap: wrap;
}

.gpl-metric {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: #f1f5f9;
    border-radius: 8px;
    font-size: 13px;
    color: #475569;
}

.gpl-metric strong {
    color: #1e293b;
    font-weight: 600;
}

.gpl-wish-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 20px 16px 20px;
}

.gpl-wish-tags {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.gpl-tag {
    padding: 4px 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 6px;
    font-size: 11px;
    font-weight: 500;
    color: #fff;
}

.gpl-wish-price {
    display: flex;
    align-items: center;
    gap: 8px;
}

.gpl-wish-price .gpl-price,
.gpl-wish-price .gpl-sale-price {
    font-size: 20px;
    font-weight: 700;
    color: #059669;
}

.gpl-wish-price .gpl-original-price {
    font-size: 14px;
    color: #94a3b8;
    text-decoration: line-through;
}

.gpl-wish-actions {
    display: flex;
    gap: 10px;
    padding: 16px 20px;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
}

.gpl-wish-actions .gpl-btn {
    flex: 1;
    justify-content: center;
}

.gpl-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    font-size: 14px;
}

.gpl-btn-small {
    padding: 8px 16px;
    font-size: 13px;
}

.gpl-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.gpl-btn-primary:hover {
    opacity: 0.9;
    color: #fff;
}

.gpl-btn-outline {
    background: transparent;
    color: #fff;
    border: 2px solid rgba(255,255,255,0.5);
}

.gpl-btn-outline:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}

.gpl-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.gpl-page-link {
    padding: 8px 16px;
    background: #f0f0f0;
    color: #333;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.gpl-page-link:hover {
    background: #667eea;
    color: #fff;
}

.gpl-page-info {
    padding: 8px 16px;
    color: #666;
    font-size: 14px;
}

@media (max-width: 768px) {
    .gpl-dashboard-header {
        flex-direction: column;
        text-align: center;
    }
    
    .gpl-user-profile {
        flex-direction: column;
    }
    
    .gpl-wishlist-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .gpl-wish-card:hover .gpl-remove-btn {
        opacity: 1;
    }
    
    .gpl-remove-btn {
        opacity: 1;
    }
    
    .gpl-wish-metrics {
        gap: 6px;
    }
    
    .gpl-metric {
        padding: 4px 8px;
        font-size: 12px;
    }
    
    .gpl-pagination {
        flex-wrap: wrap;
    }
}
</style>
