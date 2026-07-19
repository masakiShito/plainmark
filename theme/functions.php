<?php
/**
 * plainmark functions and definitions
 *
 * @package plainmark
 * @since 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define theme constants
if ( ! defined( 'PLAINMARK_VERSION' ) ) {
    define( 'PLAINMARK_VERSION', '0.1.0' );
}
if ( ! defined( 'PLAINMARK_DIR' ) ) {
    define( 'PLAINMARK_DIR', get_template_directory() );
}
if ( ! defined( 'PLAINMARK_URI' ) ) {
    define( 'PLAINMARK_URI', get_template_directory_uri() );
}

/**
 * Include theme files
 */
require_once PLAINMARK_DIR . '/inc/setup.php';
require_once PLAINMARK_DIR . '/inc/toc-functions.php';
require_once PLAINMARK_DIR . '/inc/enqueue.php';
require_once PLAINMARK_DIR . '/inc/customizer.php';
require_once PLAINMARK_DIR . '/inc/walker-nav.php';
require_once PLAINMARK_DIR . '/inc/shortcodes.php';
require_once PLAINMARK_DIR . '/inc/json-ld.php';
require_once PLAINMARK_DIR . '/inc/ogp.php';
require_once PLAINMARK_DIR . '/inc/article-functions.php';
require_once PLAINMARK_DIR . '/inc/blocks.php';
require_once PLAINMARK_DIR . '/inc/differentiation-features.php';
require_once PLAINMARK_DIR . '/inc/advanced-differentiators.php';
require_once PLAINMARK_DIR . '/inc/freshness-dashboard.php';
require_once PLAINMARK_DIR . '/inc/freshness-badge.php';
require_once PLAINMARK_DIR . '/inc/freshness-badge-single.php';
require_once PLAINMARK_DIR . '/inc/learning-paths.php';
require_once PLAINMARK_DIR . '/inc/skills-export.php';

/**
 * Show an admin notice when the bundled core plugin is inactive.
 */
function plainmark_core_recommendation_notice() {
    if ( defined( 'PLAINMARK_CORE_VERSION' ) || ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    echo '<div class="notice notice-warning"><p>';
    echo esc_html__( 'plainmark の Portfolio・Snippet・GitHub 連携などのデータ機能には、同梱プラグイン plainmark-core の有効化が必要です。', 'plainmark' );
    echo '</p></div>';
}
add_action( 'admin_notices', 'plainmark_core_recommendation_notice' );

/**
 * Register custom theme routes.
 */
function plainmark_register_custom_routes() {
    add_rewrite_rule( '^blog/page/([0-9]+)/?$', 'index.php?plainmark_blog_archive=1&paged=$matches[1]', 'top' );
    add_rewrite_rule( '^blog/?$', 'index.php?plainmark_blog_archive=1', 'top' );
    add_rewrite_rule( '^about/?$', 'index.php?plainmark_about_page=1', 'top' );
}
add_action( 'init', 'plainmark_register_custom_routes' );

/**
 * Add custom query vars.
 *
 * @param array $vars Query vars.
 * @return array
 */
function plainmark_add_query_vars( $vars ) {
    $vars[] = 'plainmark_blog_archive';
    $vars[] = 'plainmark_about_page';
    return $vars;
}
add_filter( 'query_vars', 'plainmark_add_query_vars' );

/**
 * Force WordPress to treat custom routes as valid pages.
 *
 * @param WP_Query $query Main query.
 */
function plainmark_prepare_custom_routes( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( $query->get( 'plainmark_blog_archive' ) ) {
        $query->is_404  = false;
        $query->is_home = true;
        $query->is_page = false;
        return;
    }

    if ( $query->get( 'plainmark_about_page' ) ) {
        $query->is_404  = false;
        $query->is_page = true;
        $query->is_home = false;
    }
}
add_action( 'pre_get_posts', 'plainmark_prepare_custom_routes' );

/**
 * Route template selection.
 *
 * @param string $template Current template path.
 * @return string
 */
function plainmark_template_include( $template ) {
    if ( get_query_var( 'plainmark_blog_archive' ) ) {
        $custom = locate_template( array( 'page-blog.php', 'index.php' ) );
        return $custom ?: $template;
    }

    if ( get_query_var( 'plainmark_about_page' ) ) {
        $custom = locate_template( 'page-about.php' );
        return $custom ?: $template;
    }

    return $template;
}
add_filter( 'template_include', 'plainmark_template_include' );

/**
 * Flush rewrite rules when custom route version changes.
 */
function plainmark_maybe_flush_rewrite_rules() {
    $version = '2025-06-02-plainmark-routes';
    if ( get_option( 'plainmark_rewrite_rules_version' ) === $version ) {
        return;
    }

    plainmark_register_custom_routes();
    flush_rewrite_rules();
    update_option( 'plainmark_rewrite_rules_version', $version, false );
}
add_action( 'init', 'plainmark_maybe_flush_rewrite_rules', 20 );
