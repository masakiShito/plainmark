<?php
/**
 * Refresh cached Freshness scores for all posts.
 *
 * Usage: wp eval-file bin/refresh-freshness-cache.php
 *
 * @package plainmark
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "This script must be run with WP-CLI.\n";
	exit( 1 );
}

if ( ! function_exists( 'plainmark_cache_freshness_score' ) ) {
	WP_CLI::error( 'plainmark_cache_freshness_score() is unavailable.' );
}

$posts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);

foreach ( $posts as $post_id ) {
	plainmark_cache_freshness_score( $post_id );
}

WP_CLI::success( count( $posts ) . ' posts updated.' );
