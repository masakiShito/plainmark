<?php
/**
 * Core version upgrade detection and migration runner.
 *
 * @package plainmark-core
 * @since 0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PLAINMARK_CORE_VERSION_OPTION' ) ) {
	define( 'PLAINMARK_CORE_VERSION_OPTION', 'plainmark_core_version' );
}

/**
 * Return registered upgrade routines.
 *
 * @return array<string,string>
 */
function plainmark_core_upgrade_routines() {
	return array(
		'0.2.0' => 'plainmark_core_upgrade_020',
	);
}

/**
 * Compare installed and code versions, then run pending upgrades.
 */
function plainmark_core_maybe_upgrade() {
	$installed = get_option( PLAINMARK_CORE_VERSION_OPTION, '0.0.0' );
	$current   = PLAINMARK_CORE_VERSION;

	if ( version_compare( $installed, $current, '>=' ) ) {
		return;
	}

	foreach ( plainmark_core_upgrade_routines() as $version => $callback ) {
		if ( version_compare( $installed, $version, '<' )
			&& version_compare( $version, $current, '<=' )
			&& is_callable( $callback ) ) {
			call_user_func( $callback, $installed );
		}
	}

	update_option( PLAINMARK_CORE_VERSION_OPTION, $current );
	set_transient(
		'plainmark_core_upgraded_notice',
		array(
			'from' => $installed,
			'to'   => $current,
		),
		DAY_IN_SECONDS
	);
	error_log( sprintf( '[plainmark-core] upgraded %s -> %s', $installed, $current ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}
add_action( 'admin_init', 'plainmark_core_maybe_upgrade' );

/**
 * Render the one-time upgrade notice.
 */
function plainmark_core_render_upgrade_notice() {
	$notice = get_transient( 'plainmark_core_upgraded_notice' );

	if ( ! $notice || ! is_array( $notice ) ) {
		return;
	}

	delete_transient( 'plainmark_core_upgraded_notice' );

	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: 1: old version, 2: new version. */
				__( 'plainmark-core を %1$s から %2$s に更新しました。', 'plainmark' ),
				$notice['from'] ?? 'unknown',
				$notice['to'] ?? PLAINMARK_CORE_VERSION
			)
		)
	);
}
add_action( 'admin_notices', 'plainmark_core_render_upgrade_notice' );

/**
 * Upgrade routine for 0.2.0.
 *
 * @param string $from Previously installed version.
 */
function plainmark_core_upgrade_020( $from ) {
	unset( $from );

	if ( function_exists( 'plainmark_migrate_feedback_020' ) ) {
		plainmark_migrate_feedback_020();
	}
}
