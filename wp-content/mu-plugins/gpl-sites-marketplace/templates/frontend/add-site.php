<?php

if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in
if (!is_user_logged_in()) {
?>
<div class="gpl-auth-wrapper">
    <div class="gpl-auth-box">
        <h2>Please Login</h2>
        <p>You need to login to manage sites.</p>
        <a href="<?php echo esc_url(home_url('/login/')); ?>" class="gpl-btn gpl-btn-primary">Login</a>
    </div>
</div>
<?php
    return;
}

// Determine if this is edit mode
$edit_mode = false;
$site_data = array();
// Support both ?edit=ID and ?id=ID URL parameters
$site_id = isset($_GET['edit']) ? absint($_GET['edit']) : (isset($_GET['id']) ? absint($_GET['id']) : 0);
$current_user_id = get_current_user_id();
$is_admin = current_user_can('manage_options');
$is_seller = GPL_Sites_User_Roles::is_seller();

// If site ID is provided, we're in edit mode
if ($site_id > 0) {
    $site_data = GPL_Sites_Database::get_site_by_id($site_id);
    
    if ($site_data) {
        // Check permissions: must be admin or site owner
        $is_owner = (intval($site_data['seller_id']) === $current_user_id);
        
        if (!$is_admin && !$is_owner) {
            ?>
            <div class="gpl-auth-wrapper">
                <div class="gpl-auth-box">
                    <h2>Access Denied</h2>
                    <p>You can only edit your own sites.</p>
                    <a href="<?php echo esc_url(home_url('/seller-dashboard/')); ?>" class="gpl-btn gpl-btn-primary">Back to Dashboard</a>
                </div>
            </div>
            <?php
            return;
        }
        $edit_mode = true;
    } else {
        ?>
        <div class="gpl-auth-wrapper">
            <div class="gpl-auth-box">
                <h2>Site Not Found</h2>
                <p>The site you're trying to edit doesn't exist.</p>
                <a href="<?php echo esc_url(home_url('/seller-dashboard/')); ?>" class="gpl-btn gpl-btn-primary">Back to Dashboard</a>
            </div>
        </div>
        <?php
        return;
    }
}

// For adding new sites, must be a seller
if (!$edit_mode && !$is_seller && !$is_admin) {
?>
<div class="gpl-auth-wrapper">
    <div class="gpl-auth-box">
        <h2>Access Denied</h2>
        <p>Only sellers can add sites. You are registered as a Buyer.</p>
        <a href="<?php echo esc_url(home_url('/marketplace/')); ?>" class="gpl-btn gpl-btn-primary">Browse Sites</a>
    </div>
</div>
<?php
    return;
}

// Helper function to get value safely
function gpl_get_value($site_data, $key, $default = '') {
    return isset($site_data[$key]) ? $site_data[$key] : $default;
}

// Helper for checkbox values - returns true if value equals 1 (int or string)
function gpl_is_checked($site_data, $key) {
    if (!isset($site_data[$key])) {
        return false;
    }
    return intval($site_data[$key]) === 1;
}

// Dynamic options (can be extended via filters)
$niches = apply_filters('gpl_niches_list', array('Technology', 'Business', 'Health', 'Finance', 'Travel', 'Fashion', 'Food', 'Sports', 'Education', 'Entertainment', 'Lifestyle', 'Marketing', 'Real Estate', 'Legal', 'Automotive', 'Gaming', 'News', 'General'));
$languages = apply_filters('gpl_languages_list', array('English', 'Spanish', 'French', 'German', 'Portuguese', 'Italian', 'Dutch', 'Russian', 'Chinese', 'Japanese', 'Korean', 'Arabic', 'Hindi'));
$countries = apply_filters('gpl_countries_list', array(
    'US' => 'United States',
    'GB' => 'United Kingdom',
    'CA' => 'Canada',
    'AU' => 'Australia',
    'DE' => 'Germany',
    'FR' => 'France',
    'ES' => 'Spain',
    'IT' => 'Italy',
    'NL' => 'Netherlands',
    'IN' => 'India',
    'BR' => 'Brazil',
    'MX' => 'Mexico'
));
$link_types = apply_filters('gpl_link_types_list', array('DoFollow', 'NoFollow', 'Both'));
$tat_options = apply_filters('gpl_tat_options_list', array('1-2 days', '3-5 days', '5-7 days', '1 week', '2 weeks', '3+ weeks'));
$content_by = apply_filters('gpl_content_by_list', array('Publisher', 'Buyer', 'Both'));

// Parse existing niches for multi-select
$selected_niches = array();
if ($edit_mode && !empty($site_data['niche'])) {
    $selected_niches = array_map('trim', explode(',', $site_data['niche']));
}

// Page title based on mode
$page_title = $edit_mode ? 'Edit Site' : 'Add New Site';
$page_subtitle = $edit_mode ? 'Update your site details' : 'List your guest posting opportunity';
$submit_text = $edit_mode ? 'Update Site' : 'Submit Site';
$form_action = $edit_mode ? 'gpl_update_site' : 'gpl_add_site';
?>

<div class="gpl-add-site-wrapper">
    
    <!-- Header -->
    <div class="gpl-add-site-header">
        <div>
            <h1><?php echo esc_html($page_title); ?></h1>
            <p><?php echo esc_html($page_subtitle); ?></p>
        </div>
        <div class="gpl-header-actions">
            <a href="<?php echo esc_url(home_url('/seller-dashboard/')); ?>" class="gpl-btn gpl-btn-secondary">← Back to Dashboard</a>
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="gpl-btn gpl-btn-outline">Logout</a>
        </div>
    </div>
    
    <!-- Form -->
    <form id="gpl-add-site-form" class="gpl-add-site-form" data-mode="<?php echo $edit_mode ? 'edit' : 'add'; ?>" data-site-id="<?php echo esc_attr($site_id); ?>">
        
        <?php if ($edit_mode): ?>
            <input type="hidden" name="site_id" value="<?php echo esc_attr($site_id); ?>" />
        <?php endif; ?>
        
        <!-- Basic Info Section -->
        <div class="gpl-form-section">
            <h2>Basic Information</h2>
            <div class="gpl-form-grid">
                <div class="gpl-form-group gpl-form-full">
                    <label for="website_url">Website URL <span class="gpl-required">*</span></label>
                    <input type="text" id="website_url" name="website_url" required placeholder="example.com (without http://)" value="<?php echo esc_attr(gpl_get_value($site_data, 'website_url')); ?>" <?php echo $edit_mode ? 'readonly' : ''; ?> />
                    <small><?php echo $edit_mode ? 'Domain cannot be changed after submission' : 'Enter domain without http:// or https://'; ?></small>
                </div>
                <div class="gpl-form-group">
                    <label for="website_name">Website Name <span class="gpl-required">*</span></label>
                    <input type="text" id="website_name" name="website_name" required placeholder="My Awesome Blog" value="<?php echo esc_attr(gpl_get_value($site_data, 'website_name')); ?>" />
                </div>
                <div class="gpl-form-group">
                    <label for="niche">Categories <span class="gpl-required">*</span></label>
                    <div class="gpl-multi-select-wrapper">
                        <div class="gpl-multi-select-display" id="niche-display">
                            <?php if (!empty($selected_niches)): ?>
                                <?php foreach ($selected_niches as $sn): ?>
                                    <span class="gpl-selected-tag"><?php echo esc_html($sn); ?><button type="button" class="gpl-tag-remove" data-value="<?php echo esc_attr($sn); ?>">×</button></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="gpl-placeholder">Select categories...</span>
                            <?php endif; ?>
                        </div>
                        <div class="gpl-multi-select-dropdown" id="niche-dropdown">
                            <div class="gpl-multi-select-search">
                                <input type="text" placeholder="Search categories..." id="niche-search" />
                            </div>
                            <div class="gpl-multi-select-options">
                                <?php foreach ($niches as $n): ?>
                                    <label class="gpl-multi-option">
                                        <input type="checkbox" name="niche[]" value="<?php echo esc_attr($n); ?>" <?php echo in_array($n, $selected_niches) ? 'checked' : ''; ?> />
                                        <span><?php echo esc_html($n); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="niche" name="niche" value="<?php echo esc_attr(gpl_get_value($site_data, 'niche')); ?>" />
                    <small>Select one or more categories</small>
                </div>
            </div>
        </div>
        
        <!-- SEO Metrics Section -->
        <div class="gpl-form-section">
            <h2>SEO Metrics</h2>
            <div class="gpl-form-grid gpl-form-grid-4">
                <div class="gpl-form-group">
                    <label for="domain_authority">Domain Authority (DA)</label>
                    <input type="number" id="domain_authority" name="domain_authority" min="0" max="100" placeholder="0-100" value="<?php echo esc_attr(gpl_get_value($site_data, 'domain_authority')); ?>" />
                    <small>Moz DA score</small>
                </div>
                <div class="gpl-form-group">
                    <label for="domain_rating">Domain Rating (DR)</label>
                    <input type="number" id="domain_rating" name="domain_rating" min="0" max="100" placeholder="0-100" value="<?php echo esc_attr(gpl_get_value($site_data, 'domain_rating')); ?>" />
                    <small>Ahrefs DR score</small>
                </div>
                <div class="gpl-form-group">
                    <label for="authority_score">Authority Score (AS)</label>
                    <input type="number" id="authority_score" name="authority_score" min="0" max="100" placeholder="0-100" value="<?php echo esc_attr(gpl_get_value($site_data, 'authority_score')); ?>" />
                    <small>SEMrush AS score</small>
                </div>
                <div class="gpl-form-group">
                    <label for="trust_flow">Trust Flow (TF)</label>
                    <input type="number" id="trust_flow" name="trust_flow" min="0" max="100" placeholder="0-100" value="<?php echo esc_attr(gpl_get_value($site_data, 'trust_flow')); ?>" />
                    <small>Majestic TF score</small>
                </div>
            </div>
            <div class="gpl-form-grid gpl-form-grid-4">
                <div class="gpl-form-group">
                    <label for="citation_flow">Citation Flow (CF)</label>
                    <input type="number" id="citation_flow" name="citation_flow" min="0" max="100" placeholder="0-100" value="<?php echo esc_attr(gpl_get_value($site_data, 'citation_flow')); ?>" />
                    <small>Majestic CF score</small>
                </div>
                <div class="gpl-form-group">
                    <label for="spam_score">Spam Score</label>
                    <input type="number" id="spam_score" name="spam_score" min="0" max="100" placeholder="0-100" value="<?php echo esc_attr(gpl_get_value($site_data, 'spam_score')); ?>" />
                    <small>Moz Spam Score</small>
                </div>
                <div class="gpl-form-group">
                    <label for="domain_age">Domain Age</label>
                    <input type="text" id="domain_age" name="domain_age" placeholder="e.g., 5 years" value="<?php echo esc_attr(gpl_get_value($site_data, 'domain_age')); ?>" />
                    <small>How old is the domain</small>
                </div>
                <div class="gpl-form-group">
                    <label for="tld">TLD</label>
                    <input type="text" id="tld" name="tld" placeholder=".com, .net, .org" value="<?php echo esc_attr(gpl_get_value($site_data, 'tld')); ?>" />
                    <small>Top-level domain</small>
                </div>
            </div>
            <div class="gpl-form-grid gpl-form-grid-3">
                <div class="gpl-form-group">
                    <label for="ahrefs_traffic">Ahrefs Traffic</label>
                    <input type="number" id="ahrefs_traffic" name="ahrefs_traffic" min="0" placeholder="Monthly organic traffic" value="<?php echo esc_attr(gpl_get_value($site_data, 'ahrefs_traffic')); ?>" />
                </div>
                <div class="gpl-form-group">
                    <label for="ahrefs_keywords">Ahrefs Keywords</label>
                    <input type="number" id="ahrefs_keywords" name="ahrefs_keywords" min="0" placeholder="Ranking keywords" value="<?php echo esc_attr(gpl_get_value($site_data, 'ahrefs_keywords')); ?>" />
                </div>
                <div class="gpl-form-group">
                    <label for="semrush_traffic">SEMrush Traffic</label>
                    <input type="number" id="semrush_traffic" name="semrush_traffic" min="0" placeholder="Monthly organic traffic" value="<?php echo esc_attr(gpl_get_value($site_data, 'semrush_traffic')); ?>" />
                </div>
            </div>
            <div class="gpl-form-grid gpl-form-grid-3">
                <div class="gpl-form-group">
                    <label for="semrush_keywords">SEMrush Keywords</label>
                    <input type="number" id="semrush_keywords" name="semrush_keywords" min="0" placeholder="Ranking keywords" value="<?php echo esc_attr(gpl_get_value($site_data, 'semrush_keywords')); ?>" />
                </div>
                <div class="gpl-form-group">
                    <label for="similarweb_traffic">SimilarWeb Traffic</label>
                    <input type="number" id="similarweb_traffic" name="similarweb_traffic" min="0" placeholder="Monthly visits" value="<?php echo esc_attr(gpl_get_value($site_data, 'similarweb_traffic')); ?>" />
                </div>
                <div class="gpl-form-group">
                    <label for="traffic_countries">Top Traffic Countries</label>
                    <input type="text" id="traffic_countries" name="traffic_countries" placeholder="US:40,UK:20,IN:15" value="<?php echo esc_attr(gpl_get_value($site_data, 'traffic_countries')); ?>" />
                    <small>Format: CODE:%, separated by commas</small>
                </div>
            </div>
        </div>
        
        <!-- Location & Language Section -->
        <div class="gpl-form-section">
            <h2>Location & Language</h2>
            <div class="gpl-form-grid gpl-form-grid-3">
                <div class="gpl-form-group">
                    <label for="country">Country</label>
                    <select id="country" name="country">
                        <?php 
                        $current_country = gpl_get_value($site_data, 'country', 'United States');
                        foreach ($countries as $code => $name): ?>
                            <option value="<?php echo esc_attr($name); ?>" data-code="<?php echo esc_attr($code); ?>" <?php selected($current_country, $name); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="gpl-form-group">
                    <label for="country_code">Country Code</label>
                    <input type="text" id="country_code" name="country_code" value="<?php echo esc_attr(gpl_get_value($site_data, 'country_code', 'US')); ?>" maxlength="5" placeholder="US" />
                </div>
                <div class="gpl-form-group">
                    <label for="language">Language</label>
                    <select id="language" name="language">
                        <?php 
                        $current_lang = gpl_get_value($site_data, 'language', 'English');
                        foreach ($languages as $lang): ?>
                            <option value="<?php echo esc_attr($lang); ?>" <?php selected($current_lang, $lang); ?>>
                                <?php echo esc_html($lang); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Pricing Section -->
        <div class="gpl-form-section">
            <h2>Pricing</h2>
            <div class="gpl-form-grid gpl-form-grid-3">
                <div class="gpl-form-group">
                    <label for="price">Price <span class="gpl-required">*</span></label>
                    <input type="number" id="price" name="price" required min="1" step="0.01" placeholder="99.00" value="<?php echo esc_attr(gpl_get_value($site_data, 'price')); ?>" />
                </div>
                <div class="gpl-form-group">
                    <label for="sale_price">Sale Price</label>
                    <input type="number" id="sale_price" name="sale_price" min="0" step="0.01" placeholder="Optional" value="<?php echo esc_attr(gpl_get_value($site_data, 'sale_price')); ?>" />
                    <small>Leave empty if no discount</small>
                </div>
                <div class="gpl-form-group">
                    <label for="currency">Currency</label>
                    <select id="currency" name="currency">
                        <?php $current_currency = gpl_get_value($site_data, 'currency', 'USD'); ?>
                        <option value="INR" <?php selected($current_currency, 'INR'); ?>>INR (₹)</option>
                        <option value="USD" <?php selected($current_currency, 'USD'); ?>>USD ($)</option>
                        <option value="EUR" <?php selected($current_currency, 'EUR'); ?>>EUR (€)</option>
                        <option value="GBP" <?php selected($current_currency, 'GBP'); ?>>GBP (£)</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Link Details Section -->
        <div class="gpl-form-section">
            <h2>Link Details</h2>
            <div class="gpl-form-grid gpl-form-grid-4">
                <div class="gpl-form-group">
                    <label for="link_type">Link Type</label>
                    <select id="link_type" name="link_type">
                        <?php 
                        $current_link_type = gpl_get_value($site_data, 'link_type', 'DoFollow');
                        foreach ($link_types as $type): ?>
                            <option value="<?php echo esc_attr($type); ?>" <?php selected($current_link_type, $type); ?>><?php echo esc_html($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="gpl-form-group">
                    <label for="link_validity">Link Validity</label>
                    <select id="link_validity" name="link_validity">
                        <?php $current_validity = gpl_get_value($site_data, 'link_validity', 'Permanent'); ?>
                        <option value="Permanent" <?php selected($current_validity, 'Permanent'); ?>>Permanent</option>
                        <option value="1 Year" <?php selected($current_validity, '1 Year'); ?>>1 Year</option>
                        <option value="6 Months" <?php selected($current_validity, '6 Months'); ?>>6 Months</option>
                        <option value="3 Months" <?php selected($current_validity, '3 Months'); ?>>3 Months</option>
                    </select>
                </div>
                <div class="gpl-form-group">
                    <label for="backlinks_allowed">Backlinks Allowed</label>
                    <input type="number" id="backlinks_allowed" name="backlinks_allowed" min="1" max="10" value="<?php echo esc_attr(gpl_get_value($site_data, 'backlinks_allowed', 1)); ?>" />
                </div>
                <div class="gpl-form-group">
                    <label for="tat">Turnaround Time</label>
                    <select id="tat" name="tat">
                        <?php 
                        $current_tat = gpl_get_value($site_data, 'tat', '3-5 days');
                        foreach ($tat_options as $tat_opt): ?>
                            <option value="<?php echo esc_attr($tat_opt); ?>" <?php selected($current_tat, $tat_opt); ?>>
                                <?php echo esc_html($tat_opt); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Content Requirements Section -->
        <div class="gpl-form-section">
            <h2>Content Requirements</h2>
            <div class="gpl-form-grid gpl-form-grid-3">
                <div class="gpl-form-group">
                    <label for="word_count">Minimum Word Count</label>
                    <input type="number" id="word_count" name="word_count" min="100" value="<?php echo esc_attr(gpl_get_value($site_data, 'word_count', 500)); ?>" placeholder="500" />
                </div>
                <div class="gpl-form-group">
                    <label for="content_written_by">Content Written By</label>
                    <select id="content_written_by" name="content_written_by">
                        <?php 
                        $current_content_by = gpl_get_value($site_data, 'content_written_by', 'Publisher');
                        foreach ($content_by as $cb): ?>
                            <option value="<?php echo esc_attr($cb); ?>" <?php selected($current_content_by, $cb); ?>><?php echo esc_html($cb); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="gpl-form-group">
                    <label for="sample_url">Sample Post URL</label>
                    <input type="url" id="sample_url" name="sample_url" placeholder="https://example.com/sample-post" value="<?php echo esc_attr(gpl_get_value($site_data, 'sample_url')); ?>" />
                </div>
            </div>
            <div class="gpl-form-group gpl-form-full">
                <label for="guidelines">Guidelines</label>
                <textarea id="guidelines" name="guidelines" rows="4" placeholder="Enter any specific guidelines for content submission..."><?php echo esc_textarea(gpl_get_value($site_data, 'guidelines')); ?></textarea>
            </div>
        </div>
        
        <!-- Features Section -->
        <div class="gpl-form-section">
            <h2>Features</h2>
            <div class="gpl-toggle-grid">
                <div class="gpl-toggle-row">
                    <div class="gpl-toggle-info">
                        <span class="gpl-toggle-icon">📰</span>
                        <span class="gpl-toggle-label">Google News Approved</span>
                    </div>
                    <label class="gpl-toggle-switch">
                        <input type="checkbox" name="google_news" value="1" <?php echo $edit_mode && gpl_is_checked($site_data, 'google_news') ? 'checked' : ''; ?>>
                        <span class="gpl-toggle-slider"></span>
                    </label>
                </div>
                <div class="gpl-toggle-row">
                    <div class="gpl-toggle-info">
                        <span class="gpl-toggle-icon">🏷️</span>
                        <span class="gpl-toggle-label">Marked as Sponsored</span>
                    </div>
                    <label class="gpl-toggle-switch">
                        <input type="checkbox" name="marked_sponsored" value="1" <?php echo $edit_mode && gpl_is_checked($site_data, 'marked_sponsored') ? 'checked' : ''; ?>>
                        <span class="gpl-toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Allowed Content Section -->
        <div class="gpl-form-section">
            <h2>Allowed Content</h2>
            <div class="gpl-toggle-grid">
                <div class="gpl-toggle-row">
                    <div class="gpl-toggle-info">
                        <span class="gpl-toggle-icon">🎮</span>
                        <span class="gpl-toggle-label">Sports Betting / iGaming</span>
                    </div>
                    <label class="gpl-toggle-switch">
                        <input type="checkbox" name="sports_gaming_allowed" value="1" <?php 
                            if ($edit_mode) {
                                echo gpl_is_checked($site_data, 'sports_gaming_allowed') ? 'checked' : '';
                            } else {
                                echo 'checked';
                            }
                        ?>>
                        <span class="gpl-toggle-slider"></span>
                    </label>
                </div>
                <div class="gpl-toggle-row">
                    <div class="gpl-toggle-info">
                        <span class="gpl-toggle-icon">💰</span>
                        <span class="gpl-toggle-label">Casino / Crypto</span>
                    </div>
                    <label class="gpl-toggle-switch">
                        <input type="checkbox" name="crypto_allowed" value="1" <?php 
                            if ($edit_mode) {
                                echo gpl_is_checked($site_data, 'crypto_allowed') ? 'checked' : '';
                            } else {
                                echo 'checked';
                            }
                        ?>>
                        <span class="gpl-toggle-slider"></span>
                    </label>
                </div>
                <div class="gpl-toggle-row">
                    <div class="gpl-toggle-info">
                        <span class="gpl-toggle-icon">💊</span>
                        <span class="gpl-toggle-label">CBD / Cannabis / Pharma</span>
                    </div>
                    <label class="gpl-toggle-switch">
                        <input type="checkbox" name="pharmacy_allowed" value="1" <?php 
                            if ($edit_mode) {
                                echo gpl_is_checked($site_data, 'pharmacy_allowed') ? 'checked' : '';
                            } else {
                                echo 'checked';
                            }
                        ?>>
                        <span class="gpl-toggle-slider"></span>
                    </label>
                </div>
                <div class="gpl-toggle-row">
                    <div class="gpl-toggle-info">
                        <span class="gpl-toggle-icon">🌍</span>
                        <span class="gpl-toggle-label">Foreign Language Content</span>
                    </div>
                    <label class="gpl-toggle-switch">
                        <input type="checkbox" name="foreign_lang_allowed" value="1" <?php 
                            if ($edit_mode) {
                                echo gpl_is_checked($site_data, 'foreign_lang_allowed') ? 'checked' : '';
                            } else {
                                echo 'checked';
                            }
                        ?>>
                        <span class="gpl-toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Submit -->
        <div class="gpl-form-actions">
            <div class="gpl-form-message" id="gpl-add-site-message"></div>
            <button type="submit" class="gpl-btn gpl-btn-primary gpl-btn-large"><?php echo esc_html($submit_text); ?></button>
        </div>
        
    </form>
</div>

<script>
var gplSites = {
    ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
    nonce: '<?php echo esc_attr(wp_create_nonce('gpl_sites_nonce')); ?>',
    homeUrl: '<?php echo esc_url(home_url('/')); ?>',
    isLoggedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>,
    userId: <?php echo get_current_user_id(); ?>
};

document.addEventListener('DOMContentLoaded', function() {
    // Auto-update country code when country changes
    var countrySelect = document.getElementById('country');
    var countryCodeInput = document.getElementById('country_code');
    
    if (countrySelect && countryCodeInput) {
        countrySelect.addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            var code = selected.getAttribute('data-code');
            if (code) {
                countryCodeInput.value = code;
            }
        });
    }
    
    // Multi-select category functionality
    var display = document.getElementById('niche-display');
    var dropdown = document.getElementById('niche-dropdown');
    var searchInput = document.getElementById('niche-search');
    var checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');
    var hiddenInput = document.getElementById('niche');
    
    // Toggle dropdown
    display.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('gpl-dropdown-open');
        if (dropdown.classList.contains('gpl-dropdown-open')) {
            searchInput.focus();
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.gpl-multi-select-wrapper')) {
            dropdown.classList.remove('gpl-dropdown-open');
        }
    });
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        var query = this.value.toLowerCase();
        var options = dropdown.querySelectorAll('.gpl-multi-option');
        options.forEach(function(opt) {
            var text = opt.textContent.toLowerCase();
            opt.style.display = text.includes(query) ? '' : 'none';
        });
    });
    
    // Update display when checkboxes change
    function updateDisplay() {
        var selected = [];
        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                selected.push(cb.value);
            }
        });
        
        if (selected.length === 0) {
            display.innerHTML = '<span class="gpl-placeholder">Select categories...</span>';
        } else {
            display.innerHTML = selected.map(function(s) {
                return '<span class="gpl-tag">' + s + '<button type="button" data-value="' + s + '">&times;</button></span>';
            }).join('');
        }
        
        // Update hidden input with combined value
        hiddenInput.value = selected.join(', ');
    }
    
    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', updateDisplay);
    });
    
    // Remove tag on click
    display.addEventListener('click', function(e) {
        if (e.target.tagName === 'BUTTON') {
            var value = e.target.getAttribute('data-value');
            checkboxes.forEach(function(cb) {
                if (cb.value === value) {
                    cb.checked = false;
                }
            });
            updateDisplay();
            e.stopPropagation();
        }
    });
    
    // Auto-detect TLD from URL
    var urlInput = document.getElementById('website_url');
    var tldInput = document.getElementById('tld');
    
    if (urlInput && tldInput) {
        urlInput.addEventListener('blur', function() {
            var url = this.value.trim();
            if (url) {
                var parts = url.split('.');
                if (parts.length > 1) {
                    var tld = '.' + parts[parts.length - 1].replace(/[^a-z]/gi, '');
                    if (tld.length > 1) {
                        tldInput.value = tld;
                    }
                }
            }
        });
    }
});
</script>
