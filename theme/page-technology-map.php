<?php
/**
 * Technology Map page.
 *
 * @package plainmark
 * @since 0.5.0
 */

get_header();

$query = new WP_Query(
	array(
		'post_type'              => array( 'post', 'portfolio' ),
		'post_status'            => 'publish',
		'posts_per_page'         => 200,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
	)
);

$posts = $query->posts;
$nodes = array();
$links = array();

foreach ( $posts as $post ) {
	$terms = wp_get_post_terms( $post->ID, 'technology' );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		continue;
	}

	$term_ids = array();
	foreach ( $terms as $term ) {
		$id = (string) $term->term_id;
		$term_ids[] = $id;
		if ( empty( $nodes[ $id ] ) ) {
			$nodes[ $id ] = array(
				'id'    => $id,
				'label' => $term->name,
				'count' => 0,
				'url'   => get_term_link( $term ),
			);
		}
		$nodes[ $id ]['count']++;
	}

	$term_ids = array_values( array_unique( $term_ids ) );
	$count    = count( $term_ids );
	for ( $i = 0; $i < $count; $i++ ) {
		for ( $j = $i + 1; $j < $count; $j++ ) {
			$key = $term_ids[ $i ] < $term_ids[ $j ]
				? $term_ids[ $i ] . ':' . $term_ids[ $j ]
				: $term_ids[ $j ] . ':' . $term_ids[ $i ];
			if ( empty( $links[ $key ] ) ) {
				$links[ $key ] = array(
					'source' => $term_ids[ $i ],
					'target' => $term_ids[ $j ],
					'weight' => 0,
				);
			}
			$links[ $key ]['weight']++;
		}
	}
}

wp_reset_postdata();

$graph = array(
	'nodes' => array_values( $nodes ),
	'links' => array_values( $links ),
);
?>

<main class="technology-map-page">
	<p class="technology-map-page__eyebrow">Technology Map</p>
	<h1><?php esc_html_e( '技術のつながりを見る。', 'plainmark' ); ?></h1>
	<p class="technology-map-page__lead">
		<?php esc_html_e( '記事とWorksで一緒に使われている技術タグをもとに、技術スタック同士の関係を可視化します。ノードをクリックすると、その技術に関連するコンテンツへ移動できます。', 'plainmark' ); ?>
	</p>

	<?php if ( empty( $graph['nodes'] ) ) : ?>
		<p><?php esc_html_e( '表示できる技術タグがまだありません。', 'plainmark' ); ?></p>
	<?php else : ?>
		<section class="technology-map" data-technology-map data-graph="<?php echo esc_attr( wp_json_encode( $graph ) ); ?>">
			<svg role="img" aria-label="<?php esc_attr_e( 'Technology relationship graph', 'plainmark' ); ?>"></svg>
		</section>
	<?php endif; ?>
</main>

<?php
get_footer();
