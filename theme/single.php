<?php
/**
 * The single post template
 *
 * @package plainmark
 * @since 0.1.0
 */

get_header();
?>

<main class="site-main" id="main">
  <div class="container">

    <?php if ( have_posts() ) : ?>
      <?php while ( have_posts() ) : the_post(); ?>
        <?php get_template_part( 'template-parts/content', 'single' ); ?>
      <?php endwhile; ?>
    <?php else : ?>
      <?php get_template_part( 'template-parts/content', 'none' ); ?>
    <?php endif; ?>

  </div>
</main>

<?php get_footer(); ?>
