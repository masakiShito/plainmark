<?php
/**
 * Template for /blog/ custom route
 *
 * @package plainmark
 * @since 0.7.0
 */

get_header();

// Build custom query for blog posts.
$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

$args = array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => get_option( 'posts_per_page' ),
	'paged'          => $paged,
);

$blog_query = new WP_Query( $args );

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
        <?php if ( $blog_query->found_posts ) : ?>
          <span class="blog-hero__count">
            <?php
            printf(
              /* translators: %s: number of posts. */
              esc_html__( '%s POSTS', 'plainmark' ),
              esc_html( number_format_i18n( $blog_query->found_posts ) )
            );
            ?>
          </span>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="blog-index-section">
    <div class="container container--with-sidebar">

      <div class="blog-main">
        <?php if ( $blog_query->have_posts() ) : ?>
          <div class="post-list post-list--cards">
            <?php while ( $blog_query->have_posts() ) : $blog_query->the_post(); ?>
              <?php get_template_part( 'template-parts/content', get_post_type() ); ?>
            <?php endwhile; ?>
          </div>

          <?php
          // Custom pagination for WP_Query.
          $big = 999999999;
          echo '<nav class="navigation pagination" aria-label="' . esc_attr__( '投稿', 'plainmark' ) . '">';
          echo '<div class="nav-links">';
          echo paginate_links( array(
            'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format'    => '?paged=%#%',
            'current'   => max( 1, $paged ),
            'total'     => $blog_query->max_num_pages,
            'prev_text' => '&larr;',
            'next_text' => '&rarr;',
          ) );
          echo '</div>';
          echo '</nav>';
          ?>
        <?php else : ?>
          <?php get_template_part( 'template-parts/content', 'none' ); ?>
        <?php endif; ?>

        <?php wp_reset_postdata(); ?>
      </div><!-- .blog-main -->

      <aside class="blog-sidebar" aria-label="<?php esc_attr_e( '記事ナビゲーション', 'plainmark' ); ?>">

        <!-- カテゴリ -->
        <?php if ( ! empty( $categories ) ) : ?>
          <div class="blog-sidebar__section">
            <h2 class="blog-sidebar__heading"><?php esc_html_e( 'Category', 'plainmark' ); ?></h2>
            <ul class="blog-sidebar__list">
              <li>
                <a class="blog-sidebar__link blog-sidebar__link--active"
                   href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">
                  <?php esc_html_e( 'すべて', 'plainmark' ); ?>
                  <span class="blog-sidebar__count"><?php echo esc_html( number_format_i18n( $blog_query->found_posts ) ); ?></span>
                </a>
              </li>
              <?php foreach ( $categories as $category ) : ?>
                <li>
                  <a class="blog-sidebar__link"
                     href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>">
                    <?php echo esc_html( $category->name ); ?>
                    <span class="blog-sidebar__count"><?php echo esc_html( $category->count ); ?></span>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- Technology -->
        <?php if ( ! is_wp_error( $technologies ) && ! empty( $technologies ) ) : ?>
          <div class="blog-sidebar__section">
            <h2 class="blog-sidebar__heading"><?php esc_html_e( 'Technology', 'plainmark' ); ?></h2>
            <ul class="blog-sidebar__list">
              <?php foreach ( $technologies as $technology ) : ?>
                <li>
                  <a class="blog-sidebar__link"
                     href="<?php echo esc_url( get_term_link( $technology ) ); ?>">
                    <?php echo esc_html( $technology->name ); ?>
                    <span class="blog-sidebar__count"><?php echo esc_html( $technology->count ); ?></span>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- タグ（上位10件） -->
        <?php
        $tags = get_tags( array(
          'hide_empty' => true,
          'orderby'    => 'count',
          'order'      => 'DESC',
          'number'     => 10,
        ) );
        if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) : ?>
          <div class="blog-sidebar__section">
            <h2 class="blog-sidebar__heading"><?php esc_html_e( 'Tag', 'plainmark' ); ?></h2>
            <div class="blog-sidebar__tags">
              <?php foreach ( $tags as $tag ) : ?>
                <a class="blog-sidebar__tag"
                   href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>">
                  #<?php echo esc_html( $tag->name ); ?>
                  <span class="blog-sidebar__count"><?php echo esc_html( $tag->count ); ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

      </aside><!-- .blog-sidebar -->

    </div><!-- .container--with-sidebar -->
  </section>
</main>

<?php get_footer();
