<?php
/**
 * Sites Grid Template (Marketplace)
 */

if (!defined('ABSPATH')) {
    exit;
}

$filter_options = GPL_Sites_Database::get_filter_options();
$is_logged_in = is_user_logged_in();
$user_id = get_current_user_id();
$is_admin = current_user_can('administrator');
$is_seller = gpl_is_seller();
$is_buyer = gpl_is_buyer();
$url_limit = gpl_get_url_limit();

$per_page = 20;
$current_page = isset($_GET['gplpage']) ? max(1, intval($_GET['gplpage'])) : 1;

$initial_result = GPL_Sites_Database::get_sites(array(
    'per_page' => $per_page,
    'page' => $current_page,
    'status' => 'active',
    'show_all' => true,
    'max_sites' => 0
));

$sites = $initial_result['sites'];
$total_sites = $initial_result['total'];
$total_pages = ceil($total_sites / $per_page);

$wishlist_ids = array();
if ($is_logged_in && ($is_buyer || $is_admin)) {
    $wishlist_ids = GPL_Sites_Database::get_user_wishlist_ids($user_id);
}

$can_wishlist = $is_logged_in && ($is_buyer || $is_admin);
?>

<div class="gpl-sites-wrapper gpl-sidebar-layout">
    
    <div class="gpl-top-bar">
        <div class="gpl-search-box">
            <input type="text" id="gpl-search" placeholder="Search sites by domain, name, or niche..." />
            <button type="button" id="gpl-search-btn">🔍</button>
        </div>
        <div class="gpl-top-actions">
            <?php if ($is_logged_in): ?>
                <span class="gpl-user-info">
                    Hello, <?php echo esc_html(wp_get_current_user()->display_name); ?>
                    <?php if ($is_admin): ?>
                        <span class="gpl-role-badge admin">Admin</span>
                    <?php elseif ($is_seller): ?>
                        <span class="gpl-role-badge seller">Seller</span>
                    <?php else: ?>
                        <span class="gpl-role-badge buyer">Buyer</span>
                    <?php endif; ?>
                </span>
                <?php if ($is_seller && !$is_buyer): ?>
                    <a href="<?php echo home_url('/seller-dashboard/'); ?>" class="gpl-btn gpl-btn-secondary">Dashboard</a>
                    <a href="<?php echo home_url('/add-site/'); ?>" class="gpl-btn gpl-btn-primary">+ Add Site</a>
                <?php elseif ($is_buyer && !$is_seller): ?>
                    <a href="<?php echo home_url('/buyer-dashboard/'); ?>" class="gpl-btn gpl-btn-secondary">My Dashboard</a>
                <?php elseif ($is_admin): ?>
                    <a href="<?php echo home_url('/seller-dashboard/'); ?>" class="gpl-btn gpl-btn-outline">Seller</a>
                    <a href="<?php echo home_url('/buyer-dashboard/'); ?>" class="gpl-btn gpl-btn-outline">Buyer</a>
                    <a href="<?php echo home_url('/add-site/'); ?>" class="gpl-btn gpl-btn-primary">+ Add</a>
                <?php endif; ?>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="gpl-btn gpl-btn-outline">Logout</a>
            <?php else: ?>
                <a href="<?php echo home_url('/login/'); ?>" class="gpl-btn gpl-btn-primary">Login / Register</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="gpl-main-layout">
        
        <aside class="gpl-sidebar">
            <div class="gpl-sidebar-header">
                <h3>🔍 Filter Sites</h3>
                <button type="button" id="gpl-reset-filters" class="gpl-btn-reset">Reset All</button>
            </div>
            
            <div class="gpl-sidebar-content">
                <div class="gpl-sidebar-filter">
                    <label>Niche / Category</label>
                    <select id="gpl-filter-niche">
                        <option value="">All Niches</option>
                        <?php foreach ($filter_options['niches'] as $niche): ?>
                            <option value="<?php echo esc_attr($niche); ?>"><?php echo esc_html($niche); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="gpl-sidebar-filter">
                    <label>Language</label>
                    <select id="gpl-filter-language">
                        <option value="">All Languages</option>
                        <?php foreach ($filter_options['languages'] as $language): ?>
                            <option value="<?php echo esc_attr($language); ?>"><?php echo esc_html($language); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="gpl-sidebar-filter">
                    <label>Country</label>
                    <select id="gpl-filter-country">
                        <option value="">All Countries</option>
                        <?php foreach ($filter_options['countries'] as $country): ?>
                            <option value="<?php echo esc_attr($country); ?>"><?php echo esc_html($country); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="gpl-sidebar-filter">
                    <label>Link Type</label>
                    <select id="gpl-filter-link-type">
                        <option value="">All Types</option>
                        <?php foreach ($filter_options['link_types'] as $type): ?>
                            <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="gpl-sidebar-divider"></div>
                
                <div class="gpl-sidebar-filter">
                    <label>DA (Domain Authority)</label>
                    <div class="gpl-range-row">
                        <input type="number" id="gpl-filter-da-min" placeholder="Min" min="0" max="100" />
                        <span class="gpl-range-sep">to</span>
                        <input type="number" id="gpl-filter-da-max" placeholder="Max" min="0" max="100" />
                    </div>
                </div>
                
                <div class="gpl-sidebar-filter">
                    <label>DR (Domain Rating)</label>
                    <div class="gpl-range-row">
                        <input type="number" id="gpl-filter-dr-min" placeholder="Min" min="0" max="100" />
                        <span class="gpl-range-sep">to</span>
                        <input type="number" id="gpl-filter-dr-max" placeholder="Max" min="0" max="100" />
                    </div>
                </div>
                
                <div class="gpl-sidebar-filter">
                    <label>Price Range ($)</label>
                    <div class="gpl-range-row">
                        <input type="number" id="gpl-filter-price-min" placeholder="Min" min="0" />
                        <span class="gpl-range-sep">to</span>
                        <input type="number" id="gpl-filter-price-max" placeholder="Max" min="0" />
                    </div>
                </div>
                
                <div class="gpl-sidebar-filter">
                    <label>Sort By</label>
                    <select id="gpl-sort">
                        <option value="domain_authority-DESC">DA: High to Low</option>
                        <option value="domain_authority-ASC">DA: Low to High</option>
                        <option value="domain_rating-DESC">DR: High to Low</option>
                        <option value="price-ASC">Price: Low to High</option>
                        <option value="price-DESC">Price: High to Low</option>
                        <option value="created_at-DESC">Newest First</option>
                    </select>
                </div>
                
                <div class="gpl-sidebar-actions">
                    <button type="button" id="gpl-apply-filters" class="gpl-btn gpl-btn-primary gpl-btn-full">Apply Filters</button>
                </div>
            </div>
        </aside>
        
        <main class="gpl-content">
            
            <?php if (!$is_admin): ?>
                <div class="gpl-info-banner">
                    <div class="gpl-info-icon">ℹ️</div>
                    <div class="gpl-info-text">
                        <strong>Note:</strong> Website URLs are visible for the first <?php echo $url_limit; ?> sites. 
                        <?php if (!$is_logged_in): ?>
                            <a href="<?php echo home_url('/login/'); ?>">Login</a> for more features.
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="gpl-results-header">
                <div class="gpl-results-info">
                    <span id="gpl-results-count"><?php echo $total_sites; ?> sites found</span>
                </div>
                <div class="gpl-view-controls">
                    <div class="gpl-view-toggle">
                        <button type="button" class="gpl-view-btn active" data-view="grid" title="Grid View">▦</button>
                        <button type="button" class="gpl-view-btn" data-view="list" title="List View">☰</button>
                    </div>
                    <button type="button" class="gpl-mobile-filter-btn" id="gpl-mobile-filter-toggle">🔍 Filters</button>
                </div>
            </div>
            
            <div class="gpl-sites-grid" id="gpl-sites-container">
                <?php if (empty($sites)): ?>
                    <div class="gpl-no-results">No sites found. Check back later!</div>
                <?php else: ?>
                    <?php 
                    $position = 0;
                    foreach ($sites as $site): 
                        $position++;
                        $show_url = ($is_admin || $position <= $url_limit || $url_limit === 0);
                        $in_wishlist = in_array($site['id'], $wishlist_ids);
                        $detail_url = gpl_get_site_detail_url($site);
                        $favicon = gpl_get_local_favicon($site['website_url']);
                        $price = floatval($site['price']);
                        $sale_price = $site['sale_price'] ? floatval($site['sale_price']) : null;
                    ?>
                        <div class="gpl-site-card <?php echo !$show_url ? 'gpl-url-hidden' : ''; ?>" data-id="<?php echo intval($site['id']); ?>" data-position="<?php echo $position; ?>">
                            <div class="gpl-card-header">
                                <div class="gpl-site-info">
                                    <img src="<?php echo esc_attr($favicon); ?>" alt="" class="gpl-site-favicon">
                                    <div class="gpl-site-details">
                                        <a href="<?php echo esc_url($detail_url); ?>" class="gpl-site-name">
                                            <?php echo esc_html($site['website_name'] ?: $site['website_url']); ?>
                                        </a>
                                        <?php if ($show_url): ?>
                                            <span class="gpl-site-domain">
                                                <a href="https://<?php echo esc_attr($site['website_url']); ?>" target="_blank" rel="noopener">
                                                    <?php echo esc_html($site['website_url']); ?>
                                                </a>
                                            </span>
                                        <?php else: ?>
                                            <span class="gpl-site-domain gpl-url-locked">
                                                🔒 URL Hidden - <a href="<?php echo esc_url($detail_url); ?>">View Details</a>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($can_wishlist): ?>
                                    <button class="gpl-wishlist-btn <?php echo $in_wishlist ? 'gpl-wishlisted' : ''; ?>" 
                                            data-site-id="<?php echo intval($site['id']); ?>">
                                        <?php echo $in_wishlist ? '❤️' : '🤍'; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="gpl-card-metrics">
                                <div class="gpl-metric">
                                    <span class="gpl-metric-label">DA</span>
                                    <span class="gpl-metric-value gpl-da"><?php echo intval($site['domain_authority']); ?></span>
                                </div>
                                <div class="gpl-metric">
                                    <span class="gpl-metric-label">DR</span>
                                    <span class="gpl-metric-value gpl-dr"><?php echo intval($site['domain_rating']); ?></span>
                                </div>
                                <div class="gpl-metric">
                                    <span class="gpl-metric-label">AS</span>
                                    <span class="gpl-metric-value gpl-as"><?php echo intval($site['authority_score']); ?></span>
                                </div>
                                <div class="gpl-metric">
                                    <span class="gpl-metric-label">Traffic</span>
                                    <span class="gpl-metric-value"><?php echo gpl_format_number($site['ahrefs_traffic']); ?></span>
                                </div>
                            </div>
                            
                            <div class="gpl-card-footer">
                                <div class="gpl-card-tags">
                                    <span class="gpl-tag gpl-tag-niche"><?php echo esc_html($site['niche']); ?></span>
                                    <span class="gpl-tag gpl-tag-link"><?php echo esc_html($site['link_type']); ?></span>
                                    <span class="gpl-tag gpl-tag-country"><?php echo esc_html($site['country_code']); ?></span>
                                </div>
                                <div class="gpl-card-price">
                                    <?php if ($sale_price && $sale_price < $price): ?>
                                        <span class="gpl-original-price">$<?php echo number_format($price, 0); ?></span>
                                        <span class="gpl-sale-price">$<?php echo number_format($sale_price, 0); ?></span>
                                    <?php else: ?>
                                        <span class="gpl-price">$<?php echo number_format($price, 0); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="gpl-card-actions">
                                <a href="<?php echo esc_url($detail_url); ?>" class="gpl-btn gpl-btn-small gpl-btn-primary">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="gpl-pagination" id="gpl-pagination">
                    <button type="button" class="gpl-page-btn" data-page="1" <?php echo $current_page <= 1 ? 'disabled' : ''; ?>>« First</button>
                    <button type="button" class="gpl-page-btn" data-page="prev" <?php echo $current_page <= 1 ? 'disabled' : ''; ?>>‹ Prev</button>
                    <span class="gpl-page-info">Page <span id="gpl-current-page"><?php echo $current_page; ?></span> of <span id="gpl-total-pages"><?php echo $total_pages; ?></span></span>
                    <button type="button" class="gpl-page-btn" data-page="next" <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>>Next ›</button>
                    <button type="button" class="gpl-page-btn" data-page="<?php echo $total_pages; ?>" <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>>Last »</button>
                </div>
            <?php endif; ?>
            
        </main>
    </div>
    
    <div class="gpl-mobile-overlay" id="gpl-mobile-overlay"></div>
    
    <div class="gpl-loading" id="gpl-loading">
        <div class="gpl-spinner"></div>
        <span>Loading...</span>
    </div>
</div>

<script>
var gplSites = {
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('gpl_sites_nonce'); ?>',
    homeUrl: '<?php echo home_url('/'); ?>',
    isLoggedIn: <?php echo $is_logged_in ? 'true' : 'false'; ?>,
    isAdmin: <?php echo $is_admin ? 'true' : 'false'; ?>,
    perPage: <?php echo $per_page; ?>,
    showAll: true,
    totalSites: <?php echo $total_sites; ?>,
    totalPages: <?php echo $total_pages; ?>,
    currentPage: <?php echo $current_page; ?>,
    showPagination: true,
    urlLimit: <?php echo $url_limit; ?>
};
</script>
