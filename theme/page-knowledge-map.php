<?php
/**
 * Knowledge map page.
 *
 * @package plainmark
 * @since 0.3.0
 */

defined( 'ABSPATH' ) || exit;

$items = get_posts(
	array(
		'post_type'      => array( 'post', 'portfolio' ),
		'post_status'    => 'publish',
		'posts_per_page' => 120,
		'orderby'        => 'modified',
		'order'          => 'DESC',
	)
);

$nodes        = array();
$links        = array();
$term_buckets = array();

foreach ( $items as $item ) {
	$terms = wp_get_post_terms( $item->ID, array( 'technology', 'category', 'portfolio_category' ) );
	$nodes[] = array(
		'id'    => (string) $item->ID,
		'title' => get_the_title( $item ),
		'url'   => get_permalink( $item ),
		'type'  => $item->post_type,
		'terms' => is_wp_error( $terms ) ? array() : wp_list_pluck( $terms, 'name' ),
	);

	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$key = $term->taxonomy . ':' . $term->term_id;
			$term_buckets[ $key ][] = (string) $item->ID;
		}
	}

	$series = (string) get_post_meta( $item->ID, '_plainmark_series_name', true );
	if ( $series ) {
		$term_buckets[ 'series:' . sanitize_key( $series ) ][] = (string) $item->ID;
	}
}

foreach ( $term_buckets as $bucket ) {
	$bucket = array_values( array_unique( $bucket ) );
	$count  = count( $bucket );
	for ( $i = 0; $i < $count; $i++ ) {
		for ( $j = $i + 1; $j < $count && $j < $i + 5; $j++ ) {
			$links[ $bucket[ $i ] . ':' . $bucket[ $j ] ] = array(
				'source' => $bucket[ $i ],
				'target' => $bucket[ $j ],
			);
		}
	}
}

$graph = array(
	'nodes' => $nodes,
	'links' => array_values( $links ),
);

get_header();
?>
<main id="main" class="knowledge-map-page">
	<section class="feature-page-hero">
		<div class="container container--wide">
			<p class="feature-page-eyebrow">KNOWLEDGE MAP</p>
			<h1><?php esc_html_e( '知識のつながりを見る。', 'plainmark' ); ?></h1>
			<p><?php esc_html_e( '記事・制作物・技術タグ・シリーズの関係から、このサイト全体の知識構造を可視化しています。', 'plainmark' ); ?></p>
		</div>
	</section>

	<section class="knowledge-map-section">
		<div class="container container--wide">
			<div class="knowledge-map-toolbar">
				<label>
					<span><?php esc_html_e( '絞り込み', 'plainmark' ); ?></span>
					<input type="search" data-knowledge-search placeholder="<?php esc_attr_e( 'タイトル・技術名で検索', 'plainmark' ); ?>">
				</label>
				<div class="knowledge-map-legend">
					<span><i class="is-post"></i><?php esc_html_e( '記事', 'plainmark' ); ?></span>
					<span><i class="is-portfolio"></i>Portfolio</span>
				</div>
			</div>

			<div class="knowledge-map" data-knowledge-map data-graph="<?php echo esc_attr( wp_json_encode( $graph ) ); ?>">
				<svg class="knowledge-map__canvas" role="img" aria-label="<?php esc_attr_e( '記事と制作物の関連グラフ', 'plainmark' ); ?>"></svg>
				<div class="knowledge-map__empty" hidden><?php esc_html_e( '一致する項目がありません。', 'plainmark' ); ?></div>
			</div>

			<noscript>
				<ul class="knowledge-map-fallback">
					<?php foreach ( $nodes as $node ) : ?>
						<li><a href="<?php echo esc_url( $node['url'] ); ?>"><?php echo esc_html( $node['title'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</noscript>
		</div>
	</section>
</main>
<?php get_footer();
