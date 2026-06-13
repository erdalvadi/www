<?php
/**
 * Seller Dashboard Template
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
$is_admin = current_user_can('administrator');

$per_page = 10;
$current_page = isset($_GET['spage']) ? max(1, intval($_GET['spage'])) : 1;
$offset = ($current_page - 1) * $per_page;

$all_sites = GPL_Sites_Database::get_seller_sites($user_id);
$total_sites = count($all_sites);
$sites = array_slice($all_sites, $offset, $per_page);
$total_pages = ceil($total_sites / $per_page);

// Calculate stats from sites
$active_count = 0;
$pending_count = 0;
$total_views = 0;
foreach ($all_sites as $s) {
    if ($s['status'] === 'active') $active_count++;
    if ($s['status'] === 'pending') $pending_count++;
    $total_views += isset($s['views']) ? intval($s['views']) : 0;
}
$stats = array(
    'total_sites' => $total_sites,
    'active_sites' => $active_count,
    'pending_sites' => $pending_count,
    'total_views' => $total_views
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
                <span class="gpl-role-badge seller">Seller</span>
            </div>
        </div>
        <div class="gpl-dashboard-actions">
            <a href="<?php echo home_url('/add-site/'); ?>" class="gpl-btn gpl-btn-primary">+ Add New Site</a>
            <a href="<?php echo home_url('/marketplace/'); ?>" class="gpl-btn gpl-btn-secondary">Browse Sites</a>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="gpl-btn gpl-btn-outline">Logout</a>
        </div>
    </div>

    <div class="gpl-stats-row">
        <div class="gpl-stat-card">
            <div class="gpl-stat-icon">📊</div>
            <div class="gpl-stat-content">
                <span class="gpl-stat-value"><?php echo intval($stats['total_sites']); ?></span>
                <span class="gpl-stat-label">Total Sites</span>
            </div>
        </div>
        <div class="gpl-stat-card">
            <div class="gpl-stat-icon">✅</div>
            <div class="gpl-stat-content">
                <span class="gpl-stat-value"><?php echo intval($stats['active_sites']); ?></span>
                <span class="gpl-stat-label">Active Sites</span>
            </div>
        </div>
        <div class="gpl-stat-card">
            <div class="gpl-stat-icon">⏳</div>
            <div class="gpl-stat-content">
                <span class="gpl-stat-value"><?php echo intval($stats['pending_sites']); ?></span>
                <span class="gpl-stat-label">Pending Review</span>
            </div>
        </div>
    </div>

    <div class="gpl-dashboard-content">
        <div class="gpl-section">
            <div class="gpl-section-header">
                <h2>📋 My Sites</h2>
                <span class="gpl-count">(<?php echo $total_sites; ?> sites)</span>
            </div>

            <?php if (empty($sites)): ?>
                <div class="gpl-empty-state">
                    <div class="gpl-empty-icon">🏪</div>
                    <h3>No sites yet</h3>
                    <p>Start selling by adding your first site!</p>
                    <a href="<?php echo home_url('/add-site/'); ?>" class="gpl-btn gpl-btn-primary">+ Add Site</a>
                </div>
            <?php else: ?>
                <div class="gpl-table-responsive">
                    <table class="gpl-sites-table">
                        <thead>
                            <tr>
                                <th>Site</th>
                                <th>DA</th>
                                <th>DR</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sites as $site): 
                                $status = $site['status'];
                                $status_class = $status === 'active' ? 'success' : ($status === 'pending' ? 'warning' : 'danger');
                            ?>
                                <tr data-site-id="<?php echo intval($site['id']); ?>">
                                    <td>
                                        <div class="gpl-site-cell">
                                            <img src="<?php echo esc_attr(gpl_get_local_favicon($site['website_url'])); ?>" alt="" class="gpl-favicon">
                                            <div>
                                                <strong><?php echo esc_html($site['website_name'] ?: $site['website_url']); ?></strong>
                                                <span class="gpl-url"><?php echo esc_html($site['website_url']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="gpl-metric-badge"><?php echo intval($site['domain_authority']); ?></span></td>
                                    <td><span class="gpl-metric-badge"><?php echo intval($site['domain_rating']); ?></span></td>
                                    <td><strong>$<?php echo number_format(floatval($site['price']), 0); ?></strong></td>
                                    <td><span class="gpl-status gpl-status-<?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span></td>
                                    <td>
                                        <div class="gpl-action-buttons">
                                            <a href="<?php echo esc_url(gpl_get_site_detail_url($site)); ?>" class="gpl-btn-icon" title="View" target="_blank">👁️</a>
                                            <a href="<?php echo home_url('/add-site/?edit=' . $site['id']); ?>" class="gpl-btn-icon" title="Edit">✏️</a>
                                            <button type="button" class="gpl-btn-icon gpl-btn-danger" onclick="deleteSite(<?php echo intval($site['id']); ?>, this)" title="Delete">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="gpl-pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="?spage=1" class="gpl-page-link">« First</a>
                            <a href="?spage=<?php echo $current_page - 1; ?>" class="gpl-page-link">‹ Prev</a>
                        <?php endif; ?>
                        
                        <span class="gpl-page-info">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?spage=<?php echo $current_page + 1; ?>" class="gpl-page-link">Next ›</a>
                            <a href="?spage=<?php echo $total_pages; ?>" class="gpl-page-link">Last »</a>
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
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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

.gpl-role-badge.seller {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

.gpl-dashboard-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.gpl-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

.gpl-table-responsive {
    overflow-x: auto;
}

.gpl-sites-table {
    width: 100%;
    border-collapse: collapse;
}

.gpl-sites-table th,
.gpl-sites-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.gpl-sites-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #555;
    font-size: 13px;
    text-transform: uppercase;
}

.gpl-site-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.gpl-favicon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
}

.gpl-site-cell strong {
    display: block;
    color: #1a1a2e;
}

.gpl-url {
    font-size: 12px;
    color: #888;
}

.gpl-metric-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #e8f4fd;
    color: #1976d2;
    border-radius: 4px;
    font-weight: 600;
    font-size: 13px;
}

.gpl-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.gpl-status-success {
    background: #d4edda;
    color: #155724;
}

.gpl-status-warning {
    background: #fff3cd;
    color: #856404;
}

.gpl-status-danger {
    background: #f8d7da;
    color: #721c24;
}

.gpl-action-buttons {
    display: flex;
    gap: 8px;
}

.gpl-btn-icon {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.2s;
    text-decoration: none;
}

.gpl-btn-icon:hover {
    background: #f0f0f0;
}

.gpl-btn-danger:hover {
    background: #ffe0e0;
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

.gpl-btn-primary {
    background: #fff;
    color: #11998e;
}

.gpl-btn-primary:hover {
    background: rgba(255,255,255,0.9);
}

.gpl-btn-secondary {
    background: rgba(255,255,255,0.2);
    color: #fff;
}

.gpl-btn-secondary:hover {
    background: rgba(255,255,255,0.3);
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
    background: #11998e;
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
    
    .gpl-sites-table th:nth-child(2),
    .gpl-sites-table td:nth-child(2),
    .gpl-sites-table th:nth-child(3),
    .gpl-sites-table td:nth-child(3) {
        display: none;
    }
}
</style>
