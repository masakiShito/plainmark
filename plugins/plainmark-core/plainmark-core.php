<?php
/**
 * Plugin Name: Plainmark Core
 * Plugin URI: https://github.com/masakiShito/plainmark
 * Description: Core data model and editorial governance features for the Plainmark theme.
 * Version: 0.1.0
 * Author: plainmark
 * Text Domain: plainmark
 *
 * @package plainmark-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PLAINMARK_CORE_VERSION' ) ) {
	define( 'PLAINMARK_CORE_VERSION', '0.1.0' );
}

if ( ! defined( 'PLAINMARK_CORE_DIR' ) ) {
	define( 'PLAINMARK_CORE_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'PLAINMARK_CORE_URI' ) ) {
	define( 'PLAINMARK_CORE_URI', plugin_dir_url( __FILE__ ) );
}

require_once PLAINMARK_CORE_DIR . 'includes/custom-post-types.php';

/**
 * Load modules that depend on the active theme's display helpers.
 */
function plainmark_core_load_theme_integrated_modules() {
	require_once PLAINMARK_CORE_DIR . 'includes/admin/article-settings.php';
	require_once PLAINMARK_CORE_DIR . 'includes/admin/work-settings.php';
	require_once PLAINMARK_CORE_DIR . 'includes/admin/github-works-sync.php';
	require_once PLAINMARK_CORE_DIR . 'includes/admin/sample-works.php';
	require_once PLAINMARK_CORE_DIR . 'includes/front-matter-normalizer.php';
	require_once PLAINMARK_CORE_DIR . 'includes/markdown-import.php';
	require_once PLAINMARK_CORE_DIR . 'includes/markdown-export.php';
	require_once PLAINMARK_CORE_DIR . 'includes/content-bridge.php';
	require_once PLAINMARK_CORE_DIR . 'includes/snippet-library.php';
	require_once PLAINMARK_CORE_DIR . 'includes/admin/snippet-settings.php';
	require_once PLAINMARK_CORE_DIR . 'includes/github-sync-ajax.php';
	require_once PLAINMARK_CORE_DIR . 'includes/github-sync-rest.php';
	require_once PLAINMARK_CORE_DIR . 'includes/github-pull-sync.php';
	require_once PLAINMARK_CORE_DIR . 'includes/admin/article-inventory.php';
}
add_action( 'after_setup_theme', 'plainmark_core_load_theme_integrated_modules', 20 );

/**
 * Flush rewrite rules when the core plugin is activated.
 */
function plainmark_core_activate() {
	if ( function_exists( 'plainmark_register_portfolio_post_type' ) ) {
		plainmark_register_portfolio_post_type();
	}

	if ( function_exists( 'plainmark_register_portfolio_taxonomy' ) ) {
		plainmark_register_portfolio_taxonomy();
	}

	if ( function_exists( 'plainmark_register_technology_taxonomy' ) ) {
		plainmark_register_technology_taxonomy();
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'plainmark_core_activate' );

/**
 * Flush rewrite rules when the core plugin is deactivated.
 */
function plainmark_core_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'plainmark_core_deactivate' );
