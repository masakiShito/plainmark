<?php
/**
 * Article helper functions
 *
 * @package plainmark
 * @since 0.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get related posts based on categories and tags.
 *
 * @param int $post_id Current post ID.
 * @param int $limit   Number of posts to return.
 * @return WP_Post[] Array of related posts.
 */
function plainmark_get_related_posts( $post_id = null, $limit = 3 ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();
	$limit   = max( 1, absint( $limit ) );

	if ( ! $post_id ) {
		return array();
	}

	$categories = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
	$tags       = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );

	$base_args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit * 4,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'fields'              => 'ids',
		'no_found_rows'       => true,
	);

	$candidate_ids = array();

	if ( ! empty( $categories ) ) {
		$category_query = new WP_Query(
			array_merge(
				$base_args,
				array(
					'tax_query' => array(
						array(
							'taxonomy' => 'category',
							'field'    => 'term_id',
							'terms'    => $categories,
						),
					),
				)
			)
		);
		$candidate_ids = array_merge( $candidate_ids, $category_query->posts );
	}

	if ( ! empty( $tags ) ) {
		$tag_query = new WP_Query(
			array_merge(
				$base_args,
				array(
					'tax_query' => array(
						array(
							'taxonomy' => 'post_tag',
							'field'    => 'term_id',
							'terms'    => $tags,
						),
					),
				)
			)
		);
		$candidate_ids = array_merge( $candidate_ids, $tag_query->posts );
	}

	$candidate_ids = array_values( array_unique( array_map( 'absint', $candidate_ids ) ) );

	if ( empty( $candidate_ids ) ) {
		return array();
	}

	shuffle( $candidate_ids );
	$selected_ids = array_slice( $candidate_ids, 0, $limit );

	return get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'post__in'       => $selected_ids,
			'orderby'        => 'post__in',
			'posts_per_page' => $limit,
			'no_found_rows'  => true,
		)
	);
}

/**
 * Get posts in the same series.
 *
 * @param int $post_id Current post ID.
 * @return array Array with series info and posts.
 */
function plainmark_get_series_posts( $post_id = null ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();

	if ( ! $post_id ) {
		return array();
	}

	$series_name = get_post_meta( $post_id, '_plainmark_series_name', true );

	if ( empty( $series_name ) ) {
		return array();
	}

	$args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'no_found_rows'  => true,
		'meta_query'     => array(
			'relation'     => 'AND',
			'series_name'  => array(
				'key'   => '_plainmark_series_name',
				'value' => $series_name,
			),
			'series_order' => array(
				'key'     => '_plainmark_series_order',
				'type'    => 'NUMERIC',
				'compare' => 'EXISTS',
			),
		),
		'orderby'        => 'series_order',
		'order'          => 'ASC',
	);

	$query = new WP_Query( $args );
	$posts = $query->posts;

	// Find current position.
	$current_index = 0;
	foreach ( $posts as $index => $post ) {
		if ( $post->ID === $post_id ) {
			$current_index = $index;
			break;
		}
	}

	return array(
		'name'          => $series_name,
		'posts'         => $posts,
		'total'         => count( $posts ),
		'current_index' => $current_index,
		'current_part'  => $current_index + 1,
		'prev_post'     => $current_index > 0 ? $posts[ $current_index - 1 ] : null,
		'next_post'     => $current_index < count( $posts ) - 1 ? $posts[ $current_index + 1 ] : null,
	);
}

/**
 * Update lifecycle metadata from accumulated reader feedback.
 *
 * Reader feedback is intentionally treated as an editorial signal. When enough
 * readers mark an article as not helpful, the article is pulled into the normal
 * review lifecycle by setting the review date to today. The Freshness System
 * then lowers the score through the existing review-due calculation.
 *
 * @param int $post_id Current post ID.
 */
function plainmark_update_feedback_lifecycle_signal( $post_id ) {
	$post_id = absint( $post_id );

	if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
		return;
	}

	$helpful_count     = (int) get_post_meta( $post_id, '_plainmark_helpful_count', true );
	$not_helpful_count = (int) get_post_meta( $post_id, '_plainmark_not_helpful_count', true );
	$total_count       = $helpful_count + $not_helpful_count;

	if ( $total_count <= 0 ) {
		delete_post_meta( $post_id, '_plainmark_reader_feedback_signal' );
		return;
	}

	$negative_ratio = $not_helpful_count / $total_count;
	$signal         = array(
		'helpful'        => $helpful_count,
		'not_helpful'    => $not_helpful_count,
		'total'          => $total_count,
		'negative_ratio' => round( $negative_ratio, 4 ),
		'updated_at'     => current_time( 'mysql' ),
	);

	update_post_meta( $post_id, '_plainmark_reader_feedback_signal', wp_json_encode( $signal ) );

	$min_negative_count = (int) apply_filters( 'plainmark_feedback_review_min_negative_count', 3, $post_id );
	$min_negative_ratio = (float) apply_filters( 'plainmark_feedback_review_min_negative_ratio', 0.3, $post_id );

	if ( $not_helpful_count < $min_negative_count || $negative_ratio < $min_negative_ratio ) {
		return;
	}

	$today       = current_time( 'Y-m-d' );
	$review_date = (string) get_post_meta( $post_id, '_plainmark_review_date', true );

	if ( '' === $review_date || strtotime( $review_date ) > current_datetime()->getTimestamp() ) {
		update_post_meta( $post_id, '_plainmark_review_date', $today );
		update_post_meta( $post_id, '_plainmark_review_reason', 'reader_feedback' );
		update_post_meta( $post_id, '_plainmark_review_triggered_at', current_time( 'mysql' ) );
	}
}

/**
 * Handle article feedback AJAX.
 */
function plainmark_handle_article_feedback() {
	// Verify nonce.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'plainmark_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce' );
	}

	$post_id  = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	$feedback = isset( $_POST['feedback'] ) ? sanitize_key( $_POST['feedback'] ) : '';

	if ( ! $post_id || ! in_array( $feedback, array( 'helpful', 'not_helpful' ), true ) ) {
		wp_send_json_error( 'Invalid data' );
	}

	// Get current counts.
	$helpful_count     = (int) get_post_meta( $post_id, '_plainmark_helpful_count', true );
	$not_helpful_count = (int) get_post_meta( $post_id, '_plainmark_not_helpful_count', true );

	// Update count.
	if ( 'helpful' === $feedback ) {
		update_post_meta( $post_id, '_plainmark_helpful_count', $helpful_count + 1 );
	} else {
		update_post_meta( $post_id, '_plainmark_not_helpful_count', $not_helpful_count + 1 );
	}

	plainmark_update_feedback_lifecycle_signal( $post_id );

	wp_send_json_success( array( 'message' => 'Feedback recorded' ) );
}
add_action( 'wp_ajax_plainmark_article_feedback', 'plainmark_handle_article_feedback' );
add_action( 'wp_ajax_nopriv_plainmark_article_feedback', 'plainmark_handle_article_feedback' );

/**
 * Handle live search AJAX.
 */
function plainmark_handle_live_search() {
	// Verify nonce. This must match plainmarkData.nonce generated with wp_create_nonce( 'plainmark_nonce' ).
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'plainmark_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid nonce', 'plainmark' ) ) );
	}

	$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

	if ( mb_strlen( $query ) < 3 ) {
		wp_send_json_success( array( 'results' => array() ) );
	}

	$search_query = new WP_Query(
		array(
			'post_type'           => array( 'post', 'portfolio' ),
			'post_status'         => 'publish',
			'posts_per_page'      => 8,
			's'                   => $query,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	$type_labels = array(
		'post'      => __( '記事', 'plainmark' ),
		'portfolio' => 'Portfolio',
	);

	$results = array();

	foreach ( $search_query->posts as $post ) {
		$post_type = get_post_type( $post );

		$results[] = array(
			'id'      => (int) $post->ID,
			'title'   => get_the_title( $post ),
			'url'     => get_permalink( $post ),
			'excerpt' => wp_trim_words( get_the_excerpt( $post ), 24, '…' ),
			'type'    => isset( $type_labels[ $post_type ] ) ? $type_labels[ $post_type ] : $post_type,
		);
	}

	wp_send_json_success( array( 'results' => $results ) );
}
add_action( 'wp_ajax_plainmark_live_search', 'plainmark_handle_live_search' );
add_action( 'wp_ajax_nopriv_plainmark_live_search', 'plainmark_handle_live_search' );

/**
 * Get GitHub edit URL for the current post.
 *
 * @param int $post_id Post ID.
 * @return string|null GitHub edit URL or null.
 */
function plainmark_get_github_edit_url( $post_id = null ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();

	if ( ! $post_id ) {
		return null;
	}

	// Get GitHub repo URL from customizer or options.
	$repo_url = get_theme_mod( 'plainmark_github_repo', '' );

	if ( empty( $repo_url ) ) {
		return null;
	}

	// Get the post slug for the file path.
	$post      = get_post( $post_id );
	$post_slug = $post->post_name;

	// Build the edit URL. This can be customized based on your content structure.
	$edit_url = trailingslashit( $repo_url ) . 'edit/main/content/posts/' . $post_slug . '.md';

	return apply_filters( 'plainmark_github_edit_url', $edit_url, $post_id );
}

/**
 * Check if post has been modified after publish.
 *
 * @param int $post_id Post ID.
 * @return bool True if modified.
 */
function plainmark_is_post_modified( $post_id = null ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();

	if ( ! $post_id ) {
		return false;
	}

	$post = get_post( $post_id );

	if ( ! $post ) {
		return false;
	}

	// Compare dates with 1 day tolerance.
	$published = strtotime( $post->post_date );
	$modified  = strtotime( $post->post_modified );

	return ( $modified - $published ) > DAY_IN_SECONDS;
}
