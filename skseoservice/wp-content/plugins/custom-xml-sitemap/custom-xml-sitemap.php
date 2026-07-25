<?php
/*
Plugin Name: Custom XML Sitemap (RankMath Style)
Description: Creates sitemap.xml with post, page, and category sitemaps.
Version: 1.0
*/

if (!defined('ABSPATH')) exit;

/* Register rewrite rules */
add_action('init', function () {
    add_rewrite_rule('^sitemap\.xml$', 'index.php?custom_sitemap=index', 'top');
    add_rewrite_rule('^post-sitemap\.xml$', 'index.php?custom_sitemap=post', 'top');
    add_rewrite_rule('^page-sitemap\.xml$', 'index.php?custom_sitemap=page', 'top');
    add_rewrite_rule('^category-sitemap\.xml$', 'index.php?custom_sitemap=category', 'top');
});

/* Register query var */
add_filter('query_vars', function ($vars) {
    $vars[] = 'custom_sitemap';
    return $vars;
});

/* Sitemap output */
add_action('template_redirect', function () {

    $type = get_query_var('custom_sitemap');
    if (!$type) return;

    header('Content-Type: application/xml; charset=utf-8');

    /* MAIN SITEMAP INDEX */
    if ($type === 'index') {
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $maps = [
            'post-sitemap.xml',
            'page-sitemap.xml',
            'category-sitemap.xml'
        ];

        foreach ($maps as $map) {
            echo '<sitemap>';
            echo '<loc>' . esc_url(home_url('/' . $map)) . '</loc>';
            echo '<lastmod>' . date('c') . '</lastmod>';
            echo '</sitemap>';
        }

        echo '</sitemapindex>';
        exit;
    }

    /* CHILD SITEMAPS */
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    if ($type === 'post') {
        $posts = get_posts(['post_type' => 'post', 'posts_per_page' => -1]);
        foreach ($posts as $post) {
            echo '<url>';
            echo '<loc>' . get_permalink($post->ID) . '</loc>';
            echo '<lastmod>' . get_the_modified_time('c', $post->ID) . '</lastmod>';
            echo '</url>';
        }
    }

    if ($type === 'page') {
        $pages = get_posts(['post_type' => 'page', 'posts_per_page' => -1]);
        foreach ($pages as $page) {
            echo '<url>';
            echo '<loc>' . get_permalink($page->ID) . '</loc>';
            echo '<lastmod>' . get_the_modified_time('c', $page->ID) . '</lastmod>';
            echo '</url>';
        }
    }

    if ($type === 'category') {
        $cats = get_categories(['hide_empty' => true]);
        foreach ($cats as $cat) {
            echo '<url>';
            echo '<loc>' . get_category_link($cat->term_id) . '</loc>';
            echo '<lastmod>' . date('c') . '</lastmod>';
            echo '</url>';
        }
    }

    echo '</urlset>';
    exit;
});
