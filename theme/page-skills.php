<?php
/**
 * Automatic skill sheet page.
 *
 * @package plainmark
 * @since 0.3.0
 */

defined( 'ABSPATH' ) || exit;

$technologies = get_terms(
	array(
		'taxonomy'   => 'technology',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);

$skills = array();

if ( ! is_wp_error( $technologies ) ) {
	foreach ( $technologies as $term ) {
		$post_count = count(
			get_posts(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'tax_query'      => array(
						array(
							'taxonomy' => 'technology',
							'field'    => 'term_id',
							'terms'    => $term->term_id,
						),
					),
				)
			)
		);

		$work_count = count(
			get_posts(
				array(
					'post_type'      => 'portfolio',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'tax_query'      => array(
						array(
							'taxonomy' => 'technology',
							'field'    => 'term_id',
							'terms'    => $term->term_id,
						),
					),
				)
			)
		);

		$skills[] = array(
			'term'       => $term,
			'post_count' => $post_count,
			'work_count' => $work_count,
			'total'      => $post_count + $work_count,
		);
	}
}

usort(
	$skills,
	static function ( $a, $b ) {
		return $b['total'] <=> $a['total'];
	}
);

$max_total = ! empty( $skills ) ? max( wp_list_pluck( $skills, 'total' ) ) : 1;

get_header();
?>
<main id="main" class="skills-page">
	<section class="feature-page-hero">
		<div class="container container--wide">
			<p class="feature-page-eyebrow">SKILL SHEET</p>
			<h1><?php esc_html_e( '書いたことと、作ったもので示す。', 'plainmark' ); ?></h1>
			<p><?php esc_html_e( '記事とPortfolioで使われている技術タグを集計し、実際のアウトプットに基づくスキルシートを自動生成しています。', 'plainmark' ); ?></p>
		</div>
	</section>

	<section class="skills-section">
		<div class="container container--wide">
			<?php if ( $skills ) : ?>
				<div class="skills-summary">
					<div><strong><?php echo esc_html( count( $skills ) ); ?></strong><span><?php esc_html_e( '技術', 'plainmark' ); ?></span></div>
					<div><strong><?php echo esc_html( array_sum( wp_list_pluck( $skills, 'post_count' ) ) ); ?></strong><span><?php esc_html_e( '関連記事', 'plainmark' ); ?></span></div>
					<div><strong><?php echo esc_html( array_sum( wp_list_pluck( $skills, 'work_count' ) ) ); ?></strong><span>Works</span></div>
				</div>

				<div class="skills-list">
					<?php foreach ( $skills as $skill ) : ?>
						<?php $percentage = max( 8, (int) round( ( $skill['total'] / $max_total ) * 100 ) ); ?>
						<article class="skill-row">
							<div class="skill-row__header">
								<h2><?php echo esc_html( $skill['term']->name ); ?></h2>
								<span><?php echo esc_html( $skill['total'] ); ?> outputs</span>
							</div>
							<div class="skill-row__meter" aria-hidden="true"><span style="width:<?php echo esc_attr( $percentage ); ?>%"></span></div>
							<div class="skill-row__meta">
								<a href="<?php echo esc_url( get_term_link( $skill['term'] ) ); ?>">
									<?php printf( esc_html__( '記事 %d件', 'plainmark' ), (int) $skill['post_count'] ); ?>
								</a>
								<span><?php printf( esc_html__( 'Works %d件', 'plainmark' ), (int) $skill['work_count'] ); ?></span>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="feature-empty"><?php esc_html_e( '技術スタックを記事またはPortfolioに設定すると、ここに自動表示されます。', 'plainmark' ); ?></div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer();
