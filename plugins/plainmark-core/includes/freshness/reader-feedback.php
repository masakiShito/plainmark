<?php
/**
 * Freshness reader feedback data handling.
 *
 * @package plainmark-core
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return an anonymous actor hash for feedback de-duplication.
 *
 * Raw IP addresses are never stored. Reverse proxy deployments should add an
 * explicit trusted proxy setting before using X-Forwarded-For.
 *
 * @return string Actor hash.
 */
function plainmark_feedback_actor_hash() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return substr( wp_hash( $ip . '|plainmark_feedback', 'nonce' ), 0, 16 );
}

/**
 * Check and increment the hourly actor rate limit.
 *
 * @param string $actor Actor hash.
 * @return bool Whether the actor is allowed to submit feedback.
 */
function plainmark_feedback_rate_limit_allows( $actor ) {
	$limit = (int) apply_filters( 'plainmark_feedback_rate_limit', 10 );
	$key   = 'plainmark_fb_rate_' . $actor;
	$count = (int) get_transient( $key );

	if ( $count >= $limit ) {
		return false;
	}

	set_transient( $key, $count + 1, HOUR_IN_SECONDS );

	return true;
}

/**
 * Handle freshness report AJAX.
 */
function plainmark_handle_freshness_report() {
	/*
	 * Nonce remains a defense-in-depth check. For nopriv requests it should not
	 * be treated as an actor identity or abuse-prevention mechanism.
	 */
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'plainmark_freshness_report' ) ) {
		wp_send_json_error( 'Invalid nonce' );
	}

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$report  = isset( $_POST['report'] ) ? sanitize_key( wp_unslash( $_POST['report'] ) ) : '';

	if ( ! $post_id || ! in_array( $report, array( 'accurate', 'outdated' ), true ) ) {
		wp_send_json_error( 'Invalid data' );
	}

	$actor = plainmark_feedback_actor_hash();

	if ( ! plainmark_feedback_rate_limit_allows( $actor ) ) {
		wp_send_json_error( 'Rate limited' );
	}

	$dedupe_key = 'plainmark_fb_' . $post_id . '_' . $actor;
	$existing   = get_transient( $dedupe_key );

	if ( false !== $existing ) {
		wp_send_json_success(
			array(
				'message' => 'Report already recorded',
				'noop'    => true,
			)
		);
	}

	set_transient( $dedupe_key, $report, 30 * DAY_IN_SECONDS );

	$meta_key = '_plainmark_freshness_report_' . $report;
	$count    = (int) get_post_meta( $post_id, $meta_key, true );
	update_post_meta( $post_id, $meta_key, $count + 1 );

	if ( 'outdated' === $report ) {
		$outdated_count = (int) get_post_meta( $post_id, '_plainmark_freshness_report_outdated', true );
		$status         = get_post_meta( $post_id, '_plainmark_verified_status', true );
		$threshold      = (int) apply_filters( 'plainmark_freshness_report_flag_threshold', 3 );

		if ( $outdated_count >= $threshold && 'verified' === $status ) {
			update_post_meta( $post_id, '_plainmark_freshness_review_flagged', 1 );
			update_post_meta( $post_id, '_plainmark_freshness_review_flagged_at', current_time( 'mysql' ) );

			if ( function_exists( 'plainmark_cache_freshness_score' ) ) {
				plainmark_cache_freshness_score( $post_id );
			}
		}
	}

	wp_send_json_success( array( 'message' => 'Report recorded' ) );
}
add_action( 'wp_ajax_plainmark_freshness_report', 'plainmark_handle_freshness_report' );
add_action( 'wp_ajax_nopriv_plainmark_freshness_report', 'plainmark_handle_freshness_report' );

/**
 * Get freshness report counts.
 *
 * @param int $post_id Post ID.
 * @return array{accurate:int,outdated:int}
 */
function plainmark_get_freshness_reports( $post_id ) {
	return array(
		'accurate' => (int) get_post_meta( $post_id, '_plainmark_freshness_report_accurate', true ),
		'outdated' => (int) get_post_meta( $post_id, '_plainmark_freshness_report_outdated', true ),
	);
}

/**
 * Backfill review flags for articles that already crossed the old threshold.
 */
function plainmark_migrate_feedback_020() {
	$threshold = (int) apply_filters( 'plainmark_freshness_report_flag_threshold', 3 );
	$query     = new WP_Query(
		array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_plainmark_freshness_report_outdated',
					'value'   => $threshold,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	foreach ( $query->posts as $post_id ) {
		if ( 'verified' === get_post_meta( $post_id, '_plainmark_verified_status', true )
			&& ! get_post_meta( $post_id, '_plainmark_freshness_review_flagged', true ) ) {
			update_post_meta( $post_id, '_plainmark_freshness_review_flagged', 1 );
			update_post_meta( $post_id, '_plainmark_freshness_review_flagged_at', current_time( 'mysql' ) );
		}
	}
}

/**
 * Clear the reader-feedback review flag when an author re-verifies an article.
 *
 * Verified status/date are saved via the Block Editor REST meta path, not a
 * save_post POST handler, so this hooks the meta write directly.
 *
 * @param int    $meta_id    Meta ID.
 * @param int    $post_id    Post ID.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value Meta value.
 */
function plainmark_clear_review_flag_on_reverify( $meta_id, $post_id, $meta_key, $meta_value ) {
	unset( $meta_id );

	if ( ! in_array( $meta_key, array( '_plainmark_verified_status', '_plainmark_verified_date' ), true ) ) {
		return;
	}

	$status = ( '_plainmark_verified_status' === $meta_key )
		? $meta_value
		: get_post_meta( $post_id, '_plainmark_verified_status', true );

	if ( 'verified' !== $status ) {
		return;
	}

	if ( ! get_post_meta( $post_id, '_plainmark_freshness_review_flagged', true ) ) {
		return;
	}

	delete_post_meta( $post_id, '_plainmark_freshness_review_flagged' );
	delete_post_meta( $post_id, '_plainmark_freshness_review_flagged_at' );

	if ( function_exists( 'plainmark_cache_freshness_score' ) ) {
		plainmark_cache_freshness_score( $post_id );
	}
}
add_action( 'updated_post_meta', 'plainmark_clear_review_flag_on_reverify', 10, 4 );
add_action( 'added_post_meta', 'plainmark_clear_review_flag_on_reverify', 10, 4 );
