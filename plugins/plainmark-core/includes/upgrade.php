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
		'0.3.0' => 'plainmark_core_upgrade_030',
		'0.3.1' => 'plainmark_core_upgrade_031',
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

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( sprintf( '[plainmark-core] upgraded %s -> %s', $installed, $current ) );
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

	$from = isset( $notice['from'] ) ? $notice['from'] : 'unknown';
	$to   = isset( $notice['to'] ) ? $notice['to'] : PLAINMARK_CORE_VERSION;

	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: 1: old version, 2: new version. */
				__( 'plainmark-core を %1$s から %2$s に更新しました。', 'plainmark' ),
				$from,
				$to
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

/**
 * Schedule a background freshness recompute.
 */
function plainmark_core_schedule_freshness_recompute() {
	update_option( 'plainmark_core_recompute_offset', 0, false );

	if ( ! wp_next_scheduled( 'plainmark_core_recompute_freshness_batch' ) ) {
		wp_schedule_single_event( time() + 30, 'plainmark_core_recompute_freshness_batch' );
	}
}

/**
 * Upgrade routine for 0.3.0: recompute cached freshness scores in batches.
 *
 * @param string $from Previously installed version.
 */
function plainmark_core_upgrade_030( $from ) {
	unset( $from );
	plainmark_core_schedule_freshness_recompute();
}

/**
 * Upgrade routine for 0.3.1: re-run the batched freshness recompute.
 *
 * @param string $from Previously installed version.
 */
function plainmark_core_upgrade_031( $from ) {
	unset( $from );
	plainmark_core_schedule_freshness_recompute();
}

/**
 * Process one batch of freshness recomputation, rescheduling until done.
 */
function plainmark_core_recompute_freshness_batch() {
	if ( ! function_exists( 'plainmark_cache_freshness_score' ) ) {
		return;
	}

	$batch  = (int) apply_filters( 'plainmark_core_recompute_batch_size', 50 );
	$offset = (int) get_option( 'plainmark_core_recompute_offset', 0 );

	$query = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, $batch ),
			'offset'         => $offset,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	if ( empty( $query->posts ) ) {
		delete_option( 'plainmark_core_recompute_offset' );
		return;
	}

	foreach ( $query->posts as $post_id ) {
		plainmark_cache_freshness_score( $post_id );
	}

	update_option( 'plainmark_core_recompute_offset', $offset + count( $query->posts ), false );
	wp_schedule_single_event( time() + 60, 'plainmark_core_recompute_freshness_batch' );
}
add_action( 'plainmark_core_recompute_freshness_batch', 'plainmark_core_recompute_freshness_batch' );
