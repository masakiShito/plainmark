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
define( 'PLAINMARK_VERSION', '0.1.0' );
define( 'PLAINMARK_DIR', get_template_directory() );
define( 'PLAINMARK_URI', get_template_directory_uri() );

/**
 * Include theme files
 */
require_once PLAINMARK_DIR . '/inc/setup.php';
require_once PLAINMARK_DIR . '/inc/custom-post-types.php';
require_once PLAINMARK_DIR . '/inc/toc-functions.php';
require_once PLAINMARK_DIR . '/inc/admin/article-settings.php';
require_once PLAINMARK_DIR . '/inc/admin/work-settings.php';
require_once PLAINMARK_DIR . '/inc/admin/sample-works.php';
require_once PLAINMARK_DIR . '/inc/enqueue.php';
require_once PLAINMARK_DIR . '/inc/customizer.php';
require_once PLAINMARK_DIR . '/inc/walker-nav.php';
require_once PLAINMARK_DIR . '/inc/shortcodes.php';
require_once PLAINMARK_DIR . '/inc/json-ld.php';
require_once PLAINMARK_DIR . '/inc/ogp.php';
require_once PLAINMARK_DIR . '/inc/markdown-export.php';
require_once PLAINMARK_DIR . '/inc/markdown-import.php';
require_once PLAINMARK_DIR . '/inc/article-functions.php';
require_once PLAINMARK_DIR . '/inc/blocks.php';
require_once PLAINMARK_DIR . '/inc/differentiation-features.php';
require_once PLAINMARK_DIR . '/inc/content-bridge.php';
require_once PLAINMARK_DIR . '/inc/github-pull-sync.php';
require_once PLAINMARK_DIR . '/inc/advanced-differentiators.php';

/**
 * Register custom theme routes.
 */
function plainmark_register_custom_routes() {
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
 * Force /blog/ to query normal posts.
 *
 * @param WP_Query $query Main query.
 */
function plainmark_blog_archive_query( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( $query->get( 'plainmark_blog_archive' ) ) {
        $query->set( 'post_type', 'post' );
        $query->set( 'posts_per_page', get_option( 'posts_per_page' ) );
        $query->is_home    = true;
        $query->is_archive = false;
        $query->is_page    = false;
        $query->is_404     = false;
    }

    if ( $query->get( 'plainmark_about_page' ) ) {
        $query->is_page    = true;
        $query->is_home    = false;
        $query->is_archive = false;
        $query->is_404     = false;
    }
}
add_action( 'pre_get_posts', 'plainmark_blog_archive_query' );

/**
 * Use theme templates for custom routes.
 *
 * @param string $template Template path.
 * @return string
 */
function plainmark_custom_route_template( $template ) {
    if ( get_query_var( 'plainmark_blog_archive' ) ) {
        $index_template = locate_template( 'index.php' );

        if ( $index_template ) {
            return $index_template;
        }
    }

    if ( get_query_var( 'plainmark_about_page' ) ) {
        $about_template = locate_template( 'page-about.php' );

        if ( $about_template ) {
            return $about_template;
        }
    }

    return $template;
}
add_filter( 'template_include', 'plainmark_custom_route_template' );

/**
 * Flush rewrite rules once after route changes.
 */
function plainmark_maybe_flush_rewrite_rules() {
    $rewrite_version = '20260608_blog_about_routes';

    if ( get_option( 'plainmark_rewrite_version' ) !== $rewrite_version ) {
        flush_rewrite_rules();
        update_option( 'plainmark_rewrite_version', $rewrite_version );
    }
}
add_action( 'init', 'plainmark_maybe_flush_rewrite_rules', 20 );
