<?php
/**
 * The archive template
 *
 * @package plainmark
 * @since 0.1.0
 */

get_header();

$categories = get_categories(
	array(
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 8,
	)
);

$technologies = get_terms(
	array(
		'taxonomy'   => 'technology',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 10,
	)
);

$current_term = get_queried_object();
?>

<main id="primary" class="site-main blog-page">
	<section class="blog-hero blog-hero--compact">
		<div class="container container--wide blog-hero__inner">
			<p class="blog-hero__eyebrow">
				<?php
				if ( is_category() ) {
					esc_html_e( 'CATEGORY', 'plainmark' );
				} elseif ( is_tax( 'technology' ) ) {
					esc_html_e( 'TECHNOLOGY', 'plainmark' );
				} elseif ( is_tag() ) {
					esc_html_e( 'TAG', 'plainmark' );
				} else {
					esc_html_e( 'ARCHIVE', 'plainmark' );
				}
				?>
			</p>
			<h1 class="blog-hero__title">
				<?php
				if ( is_category() ) {
					single_cat_title();
				} elseif ( is_tax( 'technology' ) ) {
					single_term_title();
				} elseif ( is_tag() ) {
					printf( '#%s', single_tag_title( '', false ) );
				} elseif ( is_author() ) {
					the_author();
				} elseif ( is_date() ) {
					if ( is_year() ) {
						echo esc_html( get_the_date( 'Y' ) );
					} elseif ( is_month() ) {
						echo esc_html( get_the_date( 'Y.m' ) );
					} else {
						echo esc_html( get_the_date( 'Y.m.d' ) );
					}
				} else {
					esc_html_e( 'Archives', 'plainmark' );
				}
				?>
			</h1>
			<div class="blog-hero__bottom">
				<?php if ( is_category() && category_description() ) : ?>
					<p class="blog-hero__lead"><?php echo wp_kses_post( category_description() ); ?></p>
				<?php elseif ( is_tax( 'technology' ) && term_description() ) : ?>
					<p class="blog-hero__lead"><?php echo wp_kses_post( term_description() ); ?></p>
				<?php else : ?>
					<p class="blog-hero__lead"><?php esc_html_e( '選択した分類に関連する記事を表示しています。', 'plainmark' ); ?></p>
				<?php endif; ?>

				<?php if ( $wp_query->found_posts ) : ?>
					<span class="blog-hero__count">
						<?php
						printf(
							/* translators: %s: number of posts. */
							esc_html__( '%s POSTS', 'plainmark' ),
							esc_html( number_format_i18n( $wp_query->found_posts ) )
						);
						?>
					</span>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="blog-index-section">
		<div class="container">
			<?php if ( ! empty( $categories ) || ( ! is_wp_error( $technologies ) && ! empty( $technologies ) ) ) : ?>
				<aside class="blog-filter-panel" aria-label="<?php esc_attr_e( '記事フィルター', 'plainmark' ); ?>">
					<div class="blog-filter-panel__row">
						<span class="blog-filter-panel__label"><?php esc_html_e( 'Category', 'plainmark' ); ?></span>
						<div class="blog-filter-panel__items">
							<a class="blog-filter-pill" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">
								<?php esc_html_e( 'すべて', 'plainmark' ); ?>
							</a>
							<?php foreach ( $categories as $category ) : ?>
								<a class="blog-filter-pill <?php echo is_category( $category->term_id ) ? 'blog-filter-pill--active' : ''; ?>" href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>">
									<?php echo esc_html( $category->name ); ?>
									<span><?php echo esc_html( $category->count ); ?></span>
								</a>
							<?php endforeach; ?>
						</div>
					</div>

					<?php if ( ! is_wp_error( $technologies ) && ! empty( $technologies ) ) : ?>
						<div class="blog-filter-panel__row">
							<span class="blog-filter-panel__label"><?php esc_html_e( 'Technology', 'plainmark' ); ?></span>
							<div class="blog-filter-panel__items">
								<?php foreach ( $technologies as $technology ) : ?>
									<a class="blog-filter-pill blog-filter-pill--tech <?php echo ( is_tax( 'technology' ) && isset( $current_term->term_id ) && (int) $current_term->term_id === (int) $technology->term_id ) ? 'blog-filter-pill--active' : ''; ?>" href="<?php echo esc_url( get_term_link( $technology ) ); ?>">
										<?php echo esc_html( $technology->name ); ?>
										<span><?php echo esc_html( $technology->count ); ?></span>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				</aside>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>
				<div class="post-list post-list--cards">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'template-parts/content', get_post_type() );
					endwhile;
					?>
				</div>

				<?php
				the_posts_pagination(
					array(
						'mid_size'  => 2,
						'prev_text' => '&larr;',
						'next_text' => '&rarr;',
						'class'     => 'pagination',
					)
				);
				?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/content', 'none' ); ?>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php get_footer();
