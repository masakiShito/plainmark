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
 * Handle freshness report AJAX.
 */
function plainmark_handle_freshness_report() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'plainmark_freshness_report' ) ) {
		wp_send_json_error( 'Invalid nonce' );
	}

	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$report  = isset( $_POST['report'] ) ? sanitize_key( wp_unslash( $_POST['report'] ) ) : '';

	if ( ! $post_id || ! in_array( $report, array( 'accurate', 'outdated' ), true ) ) {
		wp_send_json_error( 'Invalid data' );
	}

	$meta_key = '_plainmark_freshness_report_' . $report;
	$count    = (int) get_post_meta( $post_id, $meta_key, true );
	update_post_meta( $post_id, $meta_key, $count + 1 );

	if ( 'outdated' === $report ) {
		$outdated_count = (int) get_post_meta( $post_id, '_plainmark_freshness_report_outdated', true );
		$status         = get_post_meta( $post_id, '_plainmark_verified_status', true );

		if ( $outdated_count >= 3 && 'verified' === $status ) {
			update_post_meta( $post_id, '_plainmark_verified_status', 'unverified' );
			update_post_meta( $post_id, '_plainmark_review_date', current_time( 'Y-m-d' ) );
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
