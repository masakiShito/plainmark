<?php
/**
 * The main template file
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
?>

<main class="site-main blog-page" id="main">
  <section class="blog-hero">
    <div class="container container--wide blog-hero__inner">
      <p class="blog-hero__eyebrow"><?php esc_html_e( 'BLOG', 'plainmark' ); ?></p>
      <h1 class="blog-hero__title"><?php esc_html_e( '学びを、残す。', 'plainmark' ); ?></h1>
      <div class="blog-hero__bottom">
        <p class="blog-hero__lead">
          <?php esc_html_e( '開発で試したことや、つまずいて分かったことを、あとから使える知識として記録しています。', 'plainmark' ); ?>
        </p>
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
              <a class="blog-filter-pill blog-filter-pill--active" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">
                <?php esc_html_e( 'すべて', 'plainmark' ); ?>
              </a>
              <?php foreach ( $categories as $category ) : ?>
                <a class="blog-filter-pill" href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>">
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
                  <a class="blog-filter-pill blog-filter-pill--tech" href="<?php echo esc_url( get_term_link( $technology ) ); ?>">
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
          <?php while ( have_posts() ) : the_post(); ?>
            <?php get_template_part( 'template-parts/content', get_post_type() ); ?>
          <?php endwhile; ?>
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
