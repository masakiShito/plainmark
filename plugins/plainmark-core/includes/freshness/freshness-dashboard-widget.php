<?php
/**
 * Freshness dashboard widget.
 *
 * @package plainmark-core
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Freshness dashboard widget.
 */
function plainmark_register_freshness_widget() {
	wp_add_dashboard_widget(
		'plainmark_freshness_widget',
		__( '記事の鮮度チェック', 'plainmark' ),
		'plainmark_render_freshness_widget'
	);
}
add_action( 'wp_dashboard_setup', 'plainmark_register_freshness_widget' );

/**
 * Enqueue Freshness widget admin styles.
 */
function plainmark_enqueue_freshness_widget_styles() {
	$base_dir = defined( 'PLAINMARK_DIR' ) ? PLAINMARK_DIR : PLAINMARK_CORE_DIR;
	$base_uri = defined( 'PLAINMARK_URI' ) ? PLAINMARK_URI : PLAINMARK_CORE_URI;
	$version  = defined( 'PLAINMARK_VERSION' ) ? PLAINMARK_VERSION : PLAINMARK_CORE_VERSION;
	$css      = $base_dir . '/assets/css/admin-freshness-widget.css';

	wp_enqueue_style(
		'plainmark-freshness-widget',
		$base_uri . '/assets/css/admin-freshness-widget.css',
		array(),
		file_exists( $css ) ? (string) filemtime( $css ) : $version
	);
}
add_action( 'admin_enqueue_scripts', 'plainmark_enqueue_freshness_widget_styles' );

/**
 * Count published posts by cached freshness rank.
 *
 * @param string $rank Freshness rank.
 * @return int
 */
function plainmark_count_posts_by_freshness_rank( $rank ) {
	global $wpdb;

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(1)
			FROM {$wpdb->postmeta} pm
			JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			AND pm.meta_value = %s
			AND p.post_status = 'publish'
			AND p.post_type = 'post'",
			'_plainmark_freshness_rank',
			$rank
		)
	);
}

/**
 * Get the average cached freshness score for published posts.
 *
 * @return int
 */
function plainmark_get_average_freshness_score() {
	global $wpdb;

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ROUND(AVG(pm.meta_value))
			FROM {$wpdb->postmeta} pm
			JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			AND p.post_status = 'publish'
			AND p.post_type = 'post'",
			'_plainmark_freshness_score'
		)
	);
}

/**
 * Build dashboard list items from cached freshness post IDs.
 *
 * @param int[] $post_ids Post IDs.
 * @return array<int,array{id:int,score:int,reasons:array,reports:array}>
 */
function plainmark_build_freshness_widget_items( $post_ids ) {
	$items = array();

	foreach ( $post_ids as $post_id ) {
		$freshness = function_exists( 'plainmark_get_freshness_score' ) ? plainmark_get_freshness_score( $post_id ) : array( 'reasons' => array() );
		$score     = get_post_meta( $post_id, '_plainmark_freshness_score', true );
		$reports   = function_exists( 'plainmark_get_freshness_reports' ) ? plainmark_get_freshness_reports( $post_id ) : array( 'accurate' => 0, 'outdated' => 0 );

		$items[] = array(
			'id'      => $post_id,
			'score'   => '' === $score ? (int) ( $freshness['score'] ?? 0 ) : (int) $score,
			'reasons' => $freshness['reasons'] ?? array(),
			'reports' => $reports,
		);
	}

	return $items;
}

/**
 * Render the Freshness dashboard widget.
 */
function plainmark_render_freshness_widget() {
	$stale_posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => '_plainmark_freshness_rank',
					'value' => 'stale',
				),
			),
			'meta_key'       => '_plainmark_freshness_score',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		)
	);

	$watch_posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => '_plainmark_freshness_rank',
					'value' => 'watch',
				),
			),
			'meta_key'       => '_plainmark_freshness_score',
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
		)
	);

	$total       = (int) wp_count_posts( 'post' )->publish;
	$stale_count = plainmark_count_posts_by_freshness_rank( 'stale' );
	$watch_count = plainmark_count_posts_by_freshness_rank( 'watch' );
	$healthy     = max( 0, $total - $stale_count - $watch_count );
	$avg         = plainmark_get_average_freshness_score();
	$stale       = plainmark_build_freshness_widget_items( $stale_posts );
	$watch       = plainmark_build_freshness_widget_items( $watch_posts );
	?>
	<div class="plainmark-freshness-widget">
		<div class="plainmark-fw-summary">
			<div class="is-stale">
				<strong><?php echo esc_html( (string) $stale_count ); ?></strong>
				<span><?php esc_html_e( '要対応', 'plainmark' ); ?></span>
			</div>
			<div class="is-watch">
				<strong><?php echo esc_html( (string) $watch_count ); ?></strong>
				<span><?php esc_html_e( '注意', 'plainmark' ); ?></span>
			</div>
			<div class="is-healthy">
				<strong><?php echo esc_html( (string) $healthy ); ?></strong>
				<span><?php esc_html_e( '良好', 'plainmark' ); ?></span>
			</div>
		</div>

		<p><?php printf( esc_html__( 'サイト平均 Freshness: %d / 100', 'plainmark' ), esc_html( (string) $avg ) ); ?></p>

		<?php plainmark_render_freshness_widget_list( __( '要対応（Freshness < 55）', 'plainmark' ), $stale, 'stale', 10 ); ?>
		<?php plainmark_render_freshness_widget_list( __( '注意（Freshness 55-79）', 'plainmark' ), $watch, 'watch', 5 ); ?>

		<?php if ( empty( $stale ) && empty( $watch ) ) : ?>
			<p style="color: #1a6b2a;"><?php esc_html_e( 'すべての記事が良好な状態です。', 'plainmark' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render a freshness widget list.
 *
 * @param string $title List title.
 * @param array  $items List items.
 * @param string $rank Freshness rank.
 * @param int    $limit Max items.
 */
function plainmark_render_freshness_widget_list( $title, $items, $rank, $limit ) {
	if ( empty( $items ) ) {
		return;
	}
	?>
	<h4 style="margin: 12px 0 4px;"><?php echo esc_html( $title ); ?></h4>
	<ul class="plainmark-fw-list">
		<?php foreach ( array_slice( $items, 0, $limit ) as $item ) : ?>
			<li>
				<div>
					<a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>"><?php echo esc_html( get_the_title( $item['id'] ) ); ?></a>
					<?php if ( ! empty( $item['reasons'] ) ) : ?>
						<span class="plainmark-fw-reason"><?php echo esc_html( $item['reasons'][0] ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $item['reports']['outdated'] ) ) : ?>
						<span class="plainmark-fw-reports"><?php printf( esc_html__( '読者報告: 古い情報 %d 件', 'plainmark' ), esc_html( (string) $item['reports']['outdated'] ) ); ?></span>
					<?php endif; ?>
				</div>
				<span class="plainmark-fw-score is-<?php echo esc_attr( $rank ); ?>"><?php echo esc_html( (string) $item['score'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}
