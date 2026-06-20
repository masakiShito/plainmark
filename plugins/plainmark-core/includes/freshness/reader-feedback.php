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
 * Resolve the client IP, optionally trusting X-Forwarded-For.
 *
 * @return string
 */
function plainmark_feedback_client_ip() {
	$remote = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: '';

	if ( ! apply_filters( 'plainmark_feedback_trust_proxy', false ) ) {
		return $remote;
	}

	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$parts = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
		$first = trim( $parts[0] );

		if ( filter_var( $first, FILTER_VALIDATE_IP ) ) {
			return $first;
		}
	}

	return $remote;
}

/**
 * Return an anonymous actor hash for feedback de-duplication.
 *
 * Raw IP addresses are never stored. Reverse proxy deployments can opt in to
 * trusting X-Forwarded-For via the plainmark_feedback_trust_proxy filter.
 *
 * @return string Actor hash.
 */
function plainmark_feedback_actor_hash() {
	$ip = plainmark_feedback_client_ip();

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

	if ( 'post' !== get_post_type( $post_id ) ) {
		wp_send_json_error( 'Invalid post type' );
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

	$dedupe_days = (int) apply_filters( 'plainmark_feedback_dedupe_ttl_days', 14 );
	set_transient( $dedupe_key, $report, max( 1, $dedupe_days ) * DAY_IN_SECONDS );

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

	if ( 'post' !== get_post_type( $post_id ) ) {
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

/**
 * Purge expired feedback transients from the options table.
 *
 * No-op when an external object cache is active because transients are not
 * stored in the options table in that configuration.
 */
function plainmark_feedback_purge_expired_transients() {
	if ( wp_using_ext_object_cache() ) {
		return;
	}

	global $wpdb;

	$timeout_names = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
			$wpdb->esc_like( '_transient_timeout_plainmark_fb_' ) . '%',
			time()
		)
	);

	foreach ( $timeout_names as $timeout_name ) {
		$key = str_replace( '_transient_timeout_', '', $timeout_name );
		delete_transient( $key );
	}
}
