<?php
/**
 * External-style Freshness dashboard widget renderer.
 *
 * @package plainmark
 * @since 0.8.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Freshness widget admin styles.
 */
function plainmark_enqueue_freshness_widget_styles() {
	$css = PLAINMARK_DIR . '/assets/css/admin-freshness-widget.css';

	wp_enqueue_style(
		'plainmark-freshness-widget',
		PLAINMARK_URI . '/assets/css/admin-freshness-widget.css',
		array(),
		file_exists( $css ) ? (string) filemtime( $css ) : PLAINMARK_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'plainmark_enqueue_freshness_widget_styles' );

/**
 * Replace the legacy widget callback with the external-style renderer.
 */
function plainmark_replace_freshness_widget_renderer() {
	remove_meta_box( 'plainmark_freshness_widget', 'dashboard', 'normal' );

	wp_add_dashboard_widget(
		'plainmark_freshness_widget',
		__( '記事の鮮度チェック', 'plainmark' ),
		'plainmark_render_freshness_widget_external_styles'
	);
}
add_action( 'wp_dashboard_setup', 'plainmark_replace_freshness_widget_renderer', 20 );

/**
 * Render the Freshness dashboard widget without inline style blocks.
 */
function plainmark_render_freshness_widget_external_styles() {
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

		<?php plainmark_render_freshness_widget_list_external_styles( __( '要対応（Freshness < 55）', 'plainmark' ), $stale, 'stale', 10 ); ?>
		<?php plainmark_render_freshness_widget_list_external_styles( __( '注意（Freshness 55-79）', 'plainmark' ), $watch, 'watch', 5 ); ?>

		<?php if ( empty( $stale ) && empty( $watch ) ) : ?>
			<p class="plainmark-fw-empty"><?php esc_html_e( 'すべての記事が良好な状態です。', 'plainmark' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render a freshness widget list without inline styles.
 *
 * @param string $title List title.
 * @param array  $items List items.
 * @param string $rank  Freshness rank.
 * @param int    $limit Max items.
 */
function plainmark_render_freshness_widget_list_external_styles( $title, $items, $rank, $limit ) {
	if ( empty( $items ) ) {
		return;
	}
	?>
	<h4 class="plainmark-fw-heading"><?php echo esc_html( $title ); ?></h4>
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
