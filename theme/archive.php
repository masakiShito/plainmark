<?php
/**
 * The archive template
 *
 * @package plainmark
 * @since 0.1.0
 */

get_header();

// Get all categories for filter.
$categories = get_categories( array(
	'hide_empty' => true,
	'orderby'    => 'count',
	'order'      => 'DESC',
) );

// Get current category.
$current_cat = get_queried_object();
?>

<main id="primary" class="site-main">
	<div class="container">

		<header class="blog-header">
			<div class="blog-header__top">
				<h1 class="blog-header__title">
					<?php
					if ( is_category() ) {
						single_cat_title();
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
				<?php if ( $wp_query->found_posts ) : ?>
					<span class="blog-header__count">
						<?php
						printf(
							/* translators: %d: number of posts */
							esc_html( _n( '%d post', '%d posts', $wp_query->found_posts, 'plainmark' ) ),
							esc_html( number_format_i18n( $wp_query->found_posts ) )
						);
						?>
					</span>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $categories ) && ( is_category() || is_home() ) ) : ?>
				<nav class="blog-header__filters" aria-label="<?php esc_attr_e( 'カテゴリーフィルター', 'plainmark' ); ?>">
					<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"
					   class="blog-filter">
						<?php esc_html_e( 'すべて', 'plainmark' ); ?>
					</a>
					<?php foreach ( $categories as $category ) : ?>
						<?php
						$is_active = is_category( $category->term_id );
						?>
						<a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"
						   class="blog-filter <?php echo $is_active ? 'blog-filter--active' : ''; ?>">
							<?php echo esc_html( $category->name ); ?>
							<span class="blog-filter__count"><?php echo esc_html( $category->count ); ?></span>
						</a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<?php if ( is_category() && category_description() ) : ?>
				<p class="blog-header__description">
					<?php echo wp_kses_post( category_description() ); ?>
				</p>
			<?php endif; ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="post-list">
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
</main>

<?php get_footer(); ?>
