<?php
/**
 * The home/blog template
 *
 * @package plainmark
 * @since 0.1.0
 */

get_header();
?>

<main class="site-main" id="main">
  <div class="container">

    <header class="archive-header">
      <h1 class="archive-header__title">Blog</h1>
      <?php if ( $wp_query->found_posts ) : ?>
        <span class="archive-header__count">
          <?php echo esc_html( $wp_query->found_posts ); ?> posts
        </span>
      <?php endif; ?>
    </header>

    <?php if ( have_posts() ) : ?>
      <div class="post-list">
        <?php while ( have_posts() ) : the_post(); ?>
          <?php get_template_part( 'template-parts/content', get_post_type() ); ?>
        <?php endwhile; ?>
      </div>

      <?php
      the_posts_pagination( array(
        'mid_size'  => 2,
        'prev_text' => '&larr;',
        'next_text' => '&rarr;',
        'class'     => 'pagination',
      ) );
      ?>

    <?php else : ?>
      <?php get_template_part( 'template-parts/content', 'none' ); ?>
    <?php endif; ?>

  </div>
</main>

<?php get_footer(); ?>
