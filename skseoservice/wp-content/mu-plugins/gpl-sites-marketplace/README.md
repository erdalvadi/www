# GPL Sites Marketplace

A complete WordPress marketplace plugin for buying and selling guest post opportunities on websites.

## Version: 1.0.0

## Features

- **Marketplace** - Browse, search, and filter sites by niche, price, authority, and more
- **Buyer Dashboard** - Save sites to wishlist, manage saved items
- **Seller Dashboard** - Add, edit, and manage your site listings
- **Admin Panel** - Moderate submissions, approve/reject sites, manage all listings
- **User Registration** - Custom registration for buyers and sellers
- **Advanced Filtering** - Filter by SEO metrics, price range, niche, country, etc.

## Installation

### As MU-Plugin (Recommended for Production)

1. Upload the `gpl-sites-marketplace` folder to `/wp-content/mu-plugins/`
2. Create a loader file at `/wp-content/mu-plugins/load-gplm.php`:

```php
<?php
require WPMU_PLUGIN_DIR . '/gpl-sites-marketplace/gpl-sites-marketplace.php';
```

3. The plugin will auto-activate on all sites

### As Regular Plugin

1. Upload the `gpl-sites-marketplace` folder to `/wp-content/plugins/`
2. Activate through the WordPress Plugins menu

## Required Pages

Create these WordPress pages with the corresponding shortcodes:

| Page | URL Slug | Shortcode |
|------|----------|-----------|
| Marketplace | `/marketplace/` | `[gplm_marketplace]` |
| Login/Register | `/login/` | `[gplm_login]` |
| Buyer Dashboard | `/buyer-dashboard/` | `[gplm_buyer_dashboard]` |
| Seller Dashboard | `/seller-dashboard/` | `[gplm_seller_dashboard]` |
| Add/Edit Site | `/add-site/` | `[gplm_add_site]` |

## Database Setup

The plugin automatically creates required tables on activation:

- `{prefix}_gplm_sites` - Site listings
- `{prefix}_gplm_wishlist` - User wishlists

### Import Test Data

A SQL file with 100 realistic test records is included:

1. Open phpMyAdmin or your MySQL client
2. Select your WordPress database
3. Import `test-data-100-sites.sql`
4. Adjust table prefix if not using `wp_`

## User Roles

The plugin creates these custom roles:

- **GPLM Buyer** (`gplm_buyer`) - Can browse and save sites to wishlist
- **GPLM Seller** (`gplm_seller`) - Can add and manage site listings

## Admin Menu

Access the admin panel at: `WP Admin → GPL Marketplace`

- **Dashboard** - Overview statistics
- **Moderation** - Approve/reject pending submissions
- **All Sites** - Manage all listings
- **Add New** - Add sites directly from admin
- **Settings** - Plugin configuration

## Shortcode Options

### Marketplace
```
[gplm_marketplace posts_per_page="12" show_filters="true"]
```

### Login/Register
```
[gplm_login default_tab="login"]
```
Options: `login` or `register`

## AJAX Actions

All AJAX endpoints use the `gpl_` prefix:

- `gpl_get_sites` - Fetch sites with filters
- `gpl_add_site` - Add new site
- `gpl_update_site` - Update existing site
- `gpl_delete_site` - Delete site
- `gpl_add_to_wishlist` - Add to wishlist
- `gpl_remove_from_wishlist` - Remove from wishlist
- `gpl_login` - User login
- `gpl_register` - User registration
- `gpl_validate_email` - Email validation
- `gpl_moderate_site` - Admin moderation

## File Structure

```
gpl-sites-marketplace/
├── gpl-sites-marketplace.php    # Main plugin file
├── gplm-admin.php               # Admin functionality
├── README.md                    # This file
├── assets/
│   ├── css/
│   │   └── styles.css           # Frontend styles
│   ├── js/
│   │   └── app.js               # Frontend JavaScript
│   ├── admin.css                # Admin styles
│   └── admin.js                 # Admin JavaScript
├── includes/
│   ├── class-ajax.php           # AJAX handlers
│   ├── class-database.php       # Database operations
│   ├── class-helpers.php        # Helper functions
│   ├── class-rewrite.php        # URL rewriting
│   ├── class-shortcodes.php     # Shortcode handlers
│   └── class-user-roles.php     # User role management
└── templates/
    ├── admin/
    │   └── dashboard.php        # Admin dashboard template
    └── frontend/
        ├── add-site.php         # Add/Edit site form
        ├── buyer-dashboard.php  # Buyer dashboard
        ├── login-register.php   # Login/Register forms
        ├── seller-dashboard.php # Seller dashboard
        ├── site-detail.php      # Single site view
        └── sites-grid.php       # Marketplace grid
```

## Changelog

### 1.0.0
- Initialization

## Support

For issues and feature requests, contact the development team.

## License

GPL v2 or later
