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

/**
 * Define compatibility constants after the active theme has had a chance to set them.
 */
function plainmark_core_define_compat_constants() {
	if ( ! defined( 'PLAINMARK_VERSION' ) ) {
		define( 'PLAINMARK_VERSION', PLAINMARK_CORE_VERSION );
	}

	if ( ! defined( 'PLAINMARK_DIR' ) ) {
		define( 'PLAINMARK_DIR', PLAINMARK_CORE_DIR );
	}

	if ( ! defined( 'PLAINMARK_URI' ) ) {
		define( 'PLAINMARK_URI', PLAINMARK_CORE_URI );
	}
}
add_action( 'after_setup_theme', 'plainmark_core_define_compat_constants', 5 );

/**
 * Require a core module only when its sentinel function is not already loaded.
 *
 * This keeps the bundled plugin compatible with deployments where the theme and
 * plugin are not updated atomically.
 *
 * @param string $relative_path Relative include path inside the plugin.
 * @param string $sentinel      Function expected to be defined by the module.
 */
function plainmark_core_require_module( $relative_path, $sentinel ) {
	if ( function_exists( $sentinel ) ) {
		return;
	}

	require_once PLAINMARK_CORE_DIR . $relative_path;
}

/**
 * Load migrated modules after the active theme finishes declaring its helpers.
 */
function plainmark_core_load_theme_integrated_modules() {
	plainmark_core_require_module( 'includes/custom-post-types.php', 'plainmark_register_portfolio_post_type' );
	plainmark_core_require_module( 'includes/admin/work-settings.php', 'plainmark_register_work_meta' );
	plainmark_core_require_module( 'includes/admin/sample-works.php', 'plainmark_add_sample_works_page' );
	plainmark_core_require_module( 'includes/front-matter-normalizer.php', 'plainmark_front_matter_list' );
	plainmark_core_require_module( 'includes/markdown-import.php', 'plainmark_add_import_menu' );
	plainmark_core_require_module( 'includes/markdown-export.php', 'plainmark_md_export_row_action' );
	plainmark_core_require_module( 'includes/content-bridge.php', 'plainmark_register_content_bridge_meta' );
	plainmark_core_require_module( 'includes/snippet-library.php', 'plainmark_register_snippet_post_type' );
	plainmark_core_require_module( 'includes/admin/snippet-settings.php', 'plainmark_register_snippet_settings_meta_boxes' );
	plainmark_core_require_module( 'includes/github-sync-ajax.php', 'plainmark_handle_github_sync_ajax' );
	plainmark_core_require_module( 'includes/github-sync-rest.php', 'plainmark_register_github_sync_form_route' );
	plainmark_core_require_module( 'includes/github-pull-sync.php', 'plainmark_add_github_pull_sync_page' );
	plainmark_core_require_module( 'includes/admin/article-inventory.php', 'plainmark_add_article_inventory_page' );
	plainmark_core_require_module( 'includes/admin/article-settings.php', 'plainmark_add_article_settings_meta_box' );
	plainmark_core_require_module( 'includes/admin/github-works-sync.php', 'plainmark_register_github_works_sync_page' );
	plainmark_core_require_module( 'includes/freshness/freshness-score.php', 'plainmark_get_freshness_score' );
	plainmark_core_require_module( 'includes/freshness/freshness-cache.php', 'plainmark_cache_freshness_score' );
	plainmark_core_require_module( 'includes/freshness/freshness-dashboard-widget.php', 'plainmark_render_freshness_widget' );
	plainmark_core_require_module( 'includes/freshness/reader-feedback.php', 'plainmark_handle_freshness_report' );
	plainmark_core_require_module( 'includes/dependency-watcher.php', 'plainmark_check_dependencies' );
}
add_action( 'after_setup_theme', 'plainmark_core_load_theme_integrated_modules', 20 );

/**
 * Flush rewrite rules when the core plugin is activated.
 */
function plainmark_core_activate() {
	plainmark_core_require_module( 'includes/custom-post-types.php', 'plainmark_register_portfolio_post_type' );
	plainmark_core_require_module( 'includes/freshness/freshness-cache.php', 'plainmark_cache_freshness_score' );

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
 * Flush rewrite rules and clear governance cron when the core plugin is deactivated.
 */
function plainmark_core_deactivate() {
	plainmark_core_require_module( 'includes/freshness/freshness-cache.php', 'plainmark_cache_freshness_score' );

	if ( function_exists( 'plainmark_deactivate_freshness_cron' ) ) {
		plainmark_deactivate_freshness_cron();
	}

	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'plainmark_core_deactivate' );
register_deactivation_hook( __FILE__, 'plainmark_deactivate_freshness_cron' );
