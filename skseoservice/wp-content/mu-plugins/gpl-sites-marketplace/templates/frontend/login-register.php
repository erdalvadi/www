<?php
/**
 * Login/Register Template
 */

if (!defined('ABSPATH')) {
    exit;
}

if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $is_seller = GPL_Sites_User_Roles::is_seller($user->ID);
?>
<div class="gpl-auth-wrapper">
    <div class="gpl-auth-box gpl-logged-in-box">
        <div class="gpl-user-avatar">👤</div>
        <h2>Welcome, <?php echo esc_html($user->display_name); ?>!</h2>
        <p class="gpl-user-role">Role: <?php echo GPL_Sites_User_Roles::get_role_label($user->ID); ?></p>
        <p class="gpl-user-email"><?php echo esc_html($user->user_email); ?></p>
        <div class="gpl-auth-actions">
            <a href="<?php echo home_url('/marketplace/'); ?>" class="gpl-btn gpl-btn-primary">Browse Sites</a>
            <?php if ($is_seller): ?>
                <a href="<?php echo home_url('/seller-dashboard/'); ?>" class="gpl-btn gpl-btn-secondary">Seller Dashboard</a>
                <a href="<?php echo home_url('/add-site/'); ?>" class="gpl-btn gpl-btn-secondary">Add Site</a>
            <?php endif; ?>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="gpl-btn gpl-btn-outline">Logout</a>
        </div>
    </div>
</div>
<?php
    return;
}
?>

<div class="gpl-auth-wrapper">
    <div class="gpl-auth-container">
        
        <div class="gpl-auth-tabs">
            <button type="button" class="gpl-auth-tab active" data-tab="login">Login</button>
            <button type="button" class="gpl-auth-tab" data-tab="register">Register</button>
        </div>
        
        <div class="gpl-auth-panel active" id="gpl-login-panel">
            <form id="gpl-login-form" class="gpl-auth-form">
                <div class="gpl-form-group">
                    <label for="gpl-login-username">Email or Username</label>
                    <input type="text" id="gpl-login-username" name="username" required placeholder="Enter your username or email" />
                </div>
                <div class="gpl-form-group">
                    <label for="gpl-login-password">Password</label>
                    <input type="password" id="gpl-login-password" name="password" required placeholder="Enter your password" />
                </div>
                <div class="gpl-form-group gpl-checkbox-group">
                    <label class="gpl-checkbox">
                        <input type="checkbox" name="remember" id="gpl-login-remember" />
                        <span>Remember me</span>
                    </label>
                    <a href="<?php echo wp_lostpassword_url(); ?>" class="gpl-forgot-link">Forgot password?</a>
                </div>
                <div class="gpl-form-message" id="gpl-login-message"></div>
                <button type="submit" class="gpl-btn gpl-btn-primary gpl-btn-full">Login</button>
            </form>
        </div>
        
        <div class="gpl-auth-panel" id="gpl-register-panel">
            <form id="gpl-register-form" class="gpl-auth-form">
                <div class="gpl-form-group">
                    <label for="gpl-register-username">Username</label>
                    <input type="text" id="gpl-register-username" name="username" required placeholder="Choose a username" minlength="3" />
                </div>
                <div class="gpl-form-group">
                    <label for="gpl-register-email">Email</label>
                    <input type="email" id="gpl-register-email" name="email" required placeholder="Enter your email" />
                </div>
                <div class="gpl-form-group">
                    <label for="gpl-register-password">Password</label>
                    <input type="password" id="gpl-register-password" name="password" required placeholder="Choose a strong password" minlength="6" />
                </div>
                <div class="gpl-form-group">
                    <label>Register as</label>
                    <div class="gpl-role-selector">
                        <label class="gpl-role-option">
                            <input type="radio" name="role" value="seller" checked />
                            <span class="gpl-role-card">
                                <span class="gpl-role-icon">🏪</span>
                                <span class="gpl-role-name">Seller</span>
                                <span class="gpl-role-desc">List and sell guest posts</span>
                            </span>
                        </label>
                        <label class="gpl-role-option">
                            <input type="radio" name="role" value="buyer" />
                            <span class="gpl-role-card">
                                <span class="gpl-role-icon">🛒</span>
                                <span class="gpl-role-name">Buyer</span>
                                <span class="gpl-role-desc">Browse and purchase</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="gpl-form-message" id="gpl-register-message"></div>
                <button type="submit" class="gpl-btn gpl-btn-primary gpl-btn-full">Create Account</button>
            </form>
        </div>
        
    </div>
</div>

<script>
var gplSites = {
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('gpl_sites_nonce'); ?>',
    homeUrl: '<?php echo home_url('/'); ?>',
    isLoggedIn: false
};

document.addEventListener('DOMContentLoaded', function() {
    var tabs = document.querySelectorAll('.gpl-auth-tab');
    var panels = document.querySelectorAll('.gpl-auth-panel');
    
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var target = this.getAttribute('data-tab');
            
            tabs.forEach(function(t) { 
                t.classList.remove('active'); 
            });
            panels.forEach(function(p) { 
                p.classList.remove('active'); 
            });
            
            this.classList.add('active');
            
            var targetPanel = document.getElementById('gpl-' + target + '-panel');
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });
});
</script>

<style>
.gpl-auth-wrapper {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gpl-auth-container {
    width: 100%;
    max-width: 440px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    overflow: hidden;
}

.gpl-auth-tabs {
    display: flex;
    background: #f5f5f5;
    position: relative;
    z-index: 10;
}

.gpl-auth-tab {
    flex: 1;
    padding: 18px 20px;
    border: none;
    background: transparent;
    font-size: 16px;
    font-weight: 600;
    color: #888;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    z-index: 11;
}

.gpl-auth-tab:hover {
    color: #555;
    background: #eee;
}

.gpl-auth-tab.active {
    color: #667eea;
    background: #fff;
}

.gpl-auth-tab.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gpl-auth-panel {
    display: none;
    padding: 35px;
}

.gpl-auth-panel.active {
    display: block;
}

.gpl-auth-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.gpl-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.gpl-form-group label {
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.gpl-form-group input[type="text"],
.gpl-form-group input[type="email"],
.gpl-form-group input[type="password"] {
    padding: 14px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.2s;
    outline: none;
}

.gpl-form-group input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.gpl-checkbox-group {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
}

.gpl-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: normal;
}

.gpl-checkbox input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.gpl-forgot-link {
    color: #667eea;
    text-decoration: none;
    font-size: 14px;
}

.gpl-forgot-link:hover {
    text-decoration: underline;
}

.gpl-role-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.gpl-role-option {
    cursor: pointer;
}

.gpl-role-option input {
    display: none;
}

.gpl-role-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    transition: all 0.2s;
    text-align: center;
}

.gpl-role-option input:checked + .gpl-role-card {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
}

.gpl-role-icon {
    font-size: 32px;
    margin-bottom: 8px;
}

.gpl-role-name {
    font-weight: 700;
    color: #333;
    font-size: 15px;
    margin-bottom: 4px;
}

.gpl-role-desc {
    font-size: 12px;
    color: #888;
}

.gpl-form-message {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    display: none;
}

.gpl-message-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.gpl-message-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.gpl-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 16px;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.gpl-btn-full {
    width: 100%;
}

.gpl-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.gpl-btn-primary:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.gpl-btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.gpl-logged-in-box {
    background: #fff;
    border-radius: 20px;
    padding: 50px 40px;
    text-align: center;
    max-width: 400px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.gpl-logged-in-box .gpl-user-avatar {
    font-size: 64px;
    margin-bottom: 20px;
}

.gpl-logged-in-box h2 {
    margin: 0 0 10px 0;
    color: #333;
}

.gpl-user-role {
    color: #667eea;
    font-weight: 600;
    margin-bottom: 5px;
}

.gpl-logged-in-box .gpl-user-email {
    color: #888;
    margin-bottom: 25px;
}

.gpl-auth-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.gpl-btn-secondary {
    background: #f0f0f0;
    color: #333;
}

.gpl-btn-secondary:hover {
    background: #e0e0e0;
}

.gpl-btn-outline {
    background: transparent;
    color: #667eea;
    border: 2px solid #667eea;
}

.gpl-btn-outline:hover {
    background: rgba(102, 126, 234, 0.1);
}

.gpl-email-status {
    font-size: 13px;
    margin-top: 5px;
}

.gpl-email-valid {
    color: #28a745;
}

.gpl-email-invalid {
    color: #dc3545;
}

.gpl-email-checking {
    color: #666;
}

.gpl-email-spinner {
    display: inline-block;
    width: 12px;
    height: 12px;
    border: 2px solid #ddd;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-right: 5px;
    vertical-align: middle;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 480px) {
    .gpl-auth-container {
        margin: 0 10px;
    }
    
    .gpl-auth-panel {
        padding: 25px 20px;
    }
    
    .gpl-role-selector {
        grid-template-columns: 1fr;
    }
}
</style>
