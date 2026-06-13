<?php
/**
 * Admin Dashboard Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$stats = GPL_Sites_Database::get_stats();
?>

<div class="wrap">
    <h1>GPL Dashboard Marketplace</h1>
    
    <div class="gpl-admin-dashboard">
        
        <!-- Stats -->
        <div class="gpl-admin-stats">
            <div class="gpl-admin-stat-box">
                <h3><?php echo $stats['total_sites']; ?></h3>
                <p>Total Sites</p>
            </div>
            <div class="gpl-admin-stat-box">
                <h3><?php echo $stats['active_sites']; ?></h3>
                <p>Active Sites</p>
            </div>
            <div class="gpl-admin-stat-box">
                <h3><?php echo $stats['pending_sites']; ?></h3>
                <p>Pending Approval</p>
            </div>
            <div class="gpl-admin-stat-box">
                <h3><?php echo $stats['total_sellers']; ?></h3>
                <p>Total Sellers</p>
            </div>
        </div>
        
        <!-- Shortcodes Info -->
        <div class="gpl-admin-section">
            <h2>Available Shortcodes</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Shortcode</th>
                        <th>Description</th>
                        <th>Suggested Page</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[gpl_sites]</code></td>
                        <td>Main sites marketplace listing with filters</td>
                        <td>Sites</td>
                    </tr>
                    <tr>
                        <td><code>[gpl_login_register]</code></td>
                        <td>Login and registration form with role selection</td>
                        <td>Login</td>
                    </tr>
                    <tr>
                        <td><code>[gpl_seller_dashboard]</code></td>
                        <td>Seller dashboard with site management</td>
                        <td>Seller Dashboard</td>
                    </tr>
                    <tr>
                        <td><code>[gpl_add_site]</code></td>
                        <td>Form for sellers to add new sites</td>
                        <td>Add Site</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- URL Structure -->
        <div class="gpl-admin-section">
            <h2>Custom URL Structure</h2>
            <p>Site detail pages use the following URL format:</p>
            <code><?php echo home_url('/site/{domain}/{niche}/'); ?></code>
            <p style="margin-top: 10px;"><strong>Important:</strong> After installation, go to <a href="<?php echo admin_url('options-permalink.php'); ?>">Settings → Permalinks</a> and click "Save Changes" to activate the custom URL rules.</p>
        </div>
        
        <!-- Database Info -->
        <div class="gpl-admin-section">
            <h2>Database Tables</h2>
            <p>The plugin uses isolated database tables:</p>
            <ul>
                <li><code><?php echo GPL_Sites_Database::get_sites_table(); ?></code> - Sites data</li>
                <li><code><?php echo GPL_Sites_Database::get_wishlist_table(); ?></code> - User wishlists</li>
            </ul>
        </div>
        
        <!-- User Roles -->
        <div class="gpl-admin-section">
            <h2>User Roles</h2>
            <ul>
                <li><strong>GPL Seller:</strong> Can add, edit, and delete their own sites</li>
                <li><strong>GPL Buyer:</strong> Can browse sites and manage wishlist</li>
            </ul>
        </div>
        
    </div>
</div>

<style>
.gpl-admin-dashboard {
    max-width: 1200px;
}
.gpl-admin-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin: 20px 0;
}
.gpl-admin-stat-box {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    text-align: center;
}
.gpl-admin-stat-box h3 {
    font-size: 36px;
    margin: 0 0 5px;
    color: #2271b1;
}
.gpl-admin-stat-box p {
    margin: 0;
    color: #666;
}
.gpl-admin-section {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin: 20px 0;
}
.gpl-admin-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}
.gpl-admin-section code {
    background: #f0f0f1;
    padding: 5px 10px;
    border-radius: 3px;
}
.gpl-admin-section ul {
    list-style: disc;
    margin-left: 20px;
}
</style>
