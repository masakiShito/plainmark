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

	if ( ! $post_id ) {
		return array();
	}

	$categories = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
	$tags       = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );

	$args = array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'orderby'             => 'rand',
	);

	// Build tax query.
	$tax_query = array( 'relation' => 'OR' );

	if ( ! empty( $categories ) ) {
		$tax_query[] = array(
			'taxonomy' => 'category',
			'field'    => 'term_id',
			'terms'    => $categories,
		);
	}

	if ( ! empty( $tags ) ) {
		$tax_query[] = array(
			'taxonomy' => 'post_tag',
			'field'    => 'term_id',
			'terms'    => $tags,
		);
	}

	if ( count( $tax_query ) > 1 ) {
		$args['tax_query'] = $tax_query;
	}

	$query = new WP_Query( $args );

	return $query->posts;
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
		'posts_per_page' => -1,
		'meta_query'     => array(
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

	wp_send_json_success( array( 'message' => 'Feedback recorded' ) );
}
add_action( 'wp_ajax_plainmark_article_feedback', 'plainmark_handle_article_feedback' );
add_action( 'wp_ajax_nopriv_plainmark_article_feedback', 'plainmark_handle_article_feedback' );

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
	$post_date = get_the_date( 'Y/m/d', $post_id );

	// Build the edit URL (assuming common blog structure).
	// This can be customized based on your content structure.
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

	// Compare dates (with 1 day tolerance).
	$published = strtotime( $post->post_date );
	$modified  = strtotime( $post->post_modified );

	return ( $modified - $published ) > DAY_IN_SECONDS;
}
