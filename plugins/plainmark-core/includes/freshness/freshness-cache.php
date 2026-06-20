<?php
/**
 * Freshness cache and review reminder cron.
 *
 * @package plainmark-core
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register cached freshness metadata.
 */
function plainmark_register_freshness_cache_meta() {
	register_post_meta(
		'post',
		'_plainmark_freshness_score',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_post_meta(
		'post',
		'_plainmark_freshness_rank',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_key',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'plainmark_register_freshness_cache_meta' );

/**
 * Cache the Freshness score when an article is saved.
 *
 * @param int $post_id Post ID.
 */
function plainmark_cache_freshness_score( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( 'post' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! function_exists( 'plainmark_get_freshness_score' ) ) {
		return;
	}

	$freshness = plainmark_get_freshness_score( $post_id );

	update_post_meta( $post_id, '_plainmark_freshness_score', (int) $freshness['score'] );
	update_post_meta( $post_id, '_plainmark_freshness_rank', sanitize_key( $freshness['rank'] ) );
}
add_action( 'save_post', 'plainmark_cache_freshness_score' );

/**
 * Schedule the daily freshness check cron event.
 */
function plainmark_schedule_freshness_cron() {
	if ( ! wp_next_scheduled( 'plainmark_daily_freshness_check' ) ) {
		wp_schedule_event( time(), 'daily', 'plainmark_daily_freshness_check' );
	}
}
add_action( 'init', 'plainmark_schedule_freshness_cron' );

/**
 * Run the daily freshness check.
 */
function plainmark_run_freshness_check() {
	$now      = current_datetime();
	$today    = $now->format( 'Y-m-d' );
	$week_out = $now->modify( '+7 days' )->format( 'Y-m-d' );

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => '_plainmark_review_date',
					'value'   => array( $today, $week_out ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		)
	);

	if ( empty( $posts ) || ! function_exists( 'plainmark_get_freshness_score' ) ) {
		if ( function_exists( 'plainmark_feedback_purge_expired_transients' ) ) {
			plainmark_feedback_purge_expired_transients();
		}
		return;
	}

	$last_sent = get_option( 'plainmark_freshness_last_notified', '' );
	if ( $last_sent === $today ) {
		if ( function_exists( 'plainmark_feedback_purge_expired_transients' ) ) {
			plainmark_feedback_purge_expired_transients();
		}
		return;
	}

	$lines = array();
	foreach ( $posts as $post ) {
		$review_date = get_post_meta( $post->ID, '_plainmark_review_date', true );
		$freshness   = plainmark_get_freshness_score( $post->ID );
		$lines[]     = sprintf(
			'- [%s] (Freshness: %d) - レビュー期限: %s - %s',
			$post->post_title,
			$freshness['score'],
			$review_date,
			get_edit_post_link( $post->ID, 'raw' )
		);
	}

	$subject = sprintf(
		/* translators: %d: number of articles */
		__( '[plainmark] %d件の記事がレビュー期限に近づいています', 'plainmark' ),
		count( $posts )
	);

	$body  = __( '以下の記事のレビュー期限が7日以内です。内容が最新か確認してください。', 'plainmark' ) . "\n\n";
	$body .= implode( "\n", $lines );
	$body .= "\n\n" . __( 'このメールは plainmark-core の Freshness System から自動送信されています。', 'plainmark' );

	wp_mail( get_option( 'admin_email' ), $subject, $body );
	update_option( 'plainmark_freshness_last_notified', $today, false );

	if ( function_exists( 'plainmark_feedback_purge_expired_transients' ) ) {
		plainmark_feedback_purge_expired_transients();
	}
}
add_action( 'plainmark_daily_freshness_check', 'plainmark_run_freshness_check' );

/**
 * Clean up cron on core plugin deactivation.
 */
function plainmark_deactivate_freshness_cron() {
	wp_clear_scheduled_hook( 'plainmark_daily_freshness_check' );
}
