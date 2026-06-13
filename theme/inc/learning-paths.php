<?php
/**
 * Learning Path auto-generation.
 *
 * Analyses series, difficulty, and technology tags to build
 * recommended reading sequences for each technology.
 *
 * @package plainmark
 * @since 0.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate learning paths grouped by technology.
 *
 * A "path" is a technology tag with 2+ published posts.
 * Posts are sorted: beginner → intermediate → advanced,
 * with series posts grouped by series_order.
 *
 * @return array<int, array{term: WP_Term, posts: array, series: array, count: int}>
 */
function plainmark_generate_learning_paths() {
	$technologies = get_terms(
		array(
			'taxonomy'   => 'technology',
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);

	if ( is_wp_error( $technologies ) || empty( $technologies ) ) {
		return array();
	}

	$difficulty_order = array(
		'beginner'     => 0,
		'intermediate' => 1,
		'advanced'     => 2,
		''             => 3,
	);
	$paths            = array();

	foreach ( $technologies as $term ) {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 30,
				'no_found_rows'  => true,
				'tax_query'      => array(
					array(
						'taxonomy' => 'technology',
						'field'    => 'term_id',
						'terms'    => $term->term_id,
					),
				),
			)
		);

		if ( count( $posts ) < 2 ) {
			continue;
		}

		$post_ids = wp_list_pluck( $posts, 'ID' );
		update_postmeta_cache( $post_ids );

		$enriched   = array();
		$series_map = array();

		foreach ( $posts as $post ) {
			$difficulty   = (string) get_post_meta( $post->ID, '_plainmark_difficulty', true );
			$series_name  = (string) get_post_meta( $post->ID, '_plainmark_series_name', true );
			$series_order = (int) get_post_meta( $post->ID, '_plainmark_series_order', true );
			$freshness    = function_exists( 'plainmark_get_freshness_score' )
				? plainmark_get_freshness_score( $post->ID )
				: array(
					'score'   => 100,
					'rank'    => 'fresh',
					'reasons' => array(),
				);

			$entry = array(
				'post'         => $post,
				'difficulty'   => $difficulty,
				'diff_order'   => $difficulty_order[ $difficulty ] ?? 3,
				'series_name'  => $series_name,
				'series_order' => $series_order,
				'freshness'    => $freshness,
			);

			$enriched[] = $entry;

			if ( '' !== $series_name ) {
				$series_map[ $series_name ][] = $entry;
			}
		}

		usort(
			$enriched,
			static function ( $a, $b ) {
				if ( $a['diff_order'] !== $b['diff_order'] ) {
					return $a['diff_order'] <=> $b['diff_order'];
				}
				if ( $a['series_name'] && $a['series_name'] === $b['series_name'] ) {
					return $a['series_order'] <=> $b['series_order'];
				}
				return strtotime( $a['post']->post_date ) <=> strtotime( $b['post']->post_date );
			}
		);

		foreach ( $series_map as &$series_posts ) {
			usort(
				$series_posts,
				static function ( $a, $b ) {
					return $a['series_order'] <=> $b['series_order'];
				}
			);
		}
		unset( $series_posts );

		$paths[] = array(
			'term'   => $term,
			'posts'  => $enriched,
			'series' => $series_map,
			'count'  => count( $enriched ),
		);
	}

	usort(
		$paths,
		static function ( $a, $b ) {
			return $b['count'] <=> $a['count'];
		}
	);

	return $paths;
}

/**
 * Register the learning paths route.
 */
function plainmark_register_learning_paths_route() {
	add_rewrite_rule( '^learning-paths/?$', 'index.php?plainmark_learning_paths=1', 'top' );
}
add_action( 'init', 'plainmark_register_learning_paths_route' );

/**
 * Add the learning paths query var.
 *
 * @param array $vars Query vars.
 * @return array
 */
function plainmark_add_learning_paths_query_var( $vars ) {
	$vars[] = 'plainmark_learning_paths';
	return $vars;
}
add_filter( 'query_vars', 'plainmark_add_learning_paths_query_var' );

/**
 * Mark the learning paths route as a valid page.
 *
 * @param WP_Query $query Main query.
 */
function plainmark_prepare_learning_paths_route( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->get( 'plainmark_learning_paths' ) ) {
		$query->is_404     = false;
		$query->is_page    = true;
		$query->is_home    = false;
		$query->is_archive = false;
	}
}
add_action( 'pre_get_posts', 'plainmark_prepare_learning_paths_route' );

/**
 * Resolve the learning paths template.
 *
 * @param string $template Current template.
 * @return string
 */
function plainmark_learning_paths_template_include( $template ) {
	if ( get_query_var( 'plainmark_learning_paths' ) ) {
		$custom = locate_template( 'page-learning-paths.php' );
		return $custom ?: $template;
	}
	return $template;
}
add_filter( 'template_include', 'plainmark_learning_paths_template_include' );

/**
 * Flush rewrite rules once for learning paths.
 */
function plainmark_maybe_flush_learning_paths_routes() {
	$version = '20260613_learning_paths_v1';
	if ( get_option( 'plainmark_learning_paths_routes_version' ) !== $version ) {
		flush_rewrite_rules();
		update_option( 'plainmark_learning_paths_routes_version', $version );
	}
}
add_action( 'init', 'plainmark_maybe_flush_learning_paths_routes', 30 );

/**
 * Enqueue learning paths assets.
 */
function plainmark_enqueue_learning_paths_assets() {
	if ( ! get_query_var( 'plainmark_learning_paths' ) ) {
		return;
	}

	$css = PLAINMARK_DIR . '/assets/css/learning-paths.css';
	wp_enqueue_style(
		'plainmark-learning-paths',
		PLAINMARK_URI . '/assets/css/learning-paths.css',
		array( 'plainmark-style' ),
		file_exists( $css ) ? (string) filemtime( $css ) : PLAINMARK_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'plainmark_enqueue_learning_paths_assets', 20 );
