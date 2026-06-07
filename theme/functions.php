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
require_once PLAINMARK_DIR . '/inc/admin/article-settings.php';
require_once PLAINMARK_DIR . '/inc/enqueue.php';
require_once PLAINMARK_DIR . '/inc/customizer.php';
require_once PLAINMARK_DIR . '/inc/walker-nav.php';
require_once PLAINMARK_DIR . '/inc/shortcodes.php';
require_once PLAINMARK_DIR . '/inc/article-functions.php';

/**
 * Redirect the fallback blog path to the latest posts section.
 */
function plainmark_redirect_blog_path() {
    $request_path = trim( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );

    if ( 'blog' === $request_path || str_ends_with( $request_path, '/blog' ) ) {
        wp_safe_redirect( home_url( '/#latest-posts' ), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'plainmark_redirect_blog_path' );
