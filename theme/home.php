<?php
/**
 * The home/blog template
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

// Get current category filter.
$current_cat = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0;
?>

<main class="site-main" id="main">
	<div class="container">

		<header class="blog-header">
			<div class="blog-header__top">
				<h1 class="blog-header__title"><?php esc_html_e( 'Blog', 'plainmark' ); ?></h1>
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

			<?php if ( ! empty( $categories ) ) : ?>
				<nav class="blog-header__filters" aria-label="<?php esc_attr_e( 'カテゴリーフィルター', 'plainmark' ); ?>">
					<a href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"
					   class="blog-filter <?php echo ! $current_cat ? 'blog-filter--active' : ''; ?>">
						<?php esc_html_e( 'すべて', 'plainmark' ); ?>
					</a>
					<?php foreach ( $categories as $category ) : ?>
						<a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"
						   class="blog-filter <?php echo $current_cat === $category->term_id ? 'blog-filter--active' : ''; ?>">
							<?php echo esc_html( $category->name ); ?>
							<span class="blog-filter__count"><?php echo esc_html( $category->count ); ?></span>
						</a>
					<?php endforeach; ?>
				</nav>
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
