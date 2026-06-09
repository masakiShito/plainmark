<?php
/**
 * The main template file
 *
 * @package plainmark
 * @since 0.1.0
 */

get_header();
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
      <?php if ( have_posts() ) : ?>
        <div class="post-list">
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
