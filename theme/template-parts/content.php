<?php
/**
 * Template part for displaying posts in listing
 *
 * @package plainmark
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-item' ); ?>>
  <a href="<?php the_permalink(); ?>" class="post-item__link" aria-label="<?php the_title_attribute(); ?>">

    <?php if ( has_post_thumbnail() ) : ?>
      <div class="post-item__thumb">
        <?php the_post_thumbnail( 'thumbnail', array(
          'class'   => 'post-item__thumb-img',
          'loading' => 'lazy',
          'alt'     => get_the_title(),
        ) ); ?>
      </div>
    <?php endif; ?>

    <div class="post-item__body">
      <div class="post-item__meta">
        <time class="post-item__date" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
          <?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?>
        </time>
        <?php
        $categories = get_the_category();
        if ( $categories ) :
          $cat = $categories[0];
        ?>
          <span class="post-item__cat">
            <?php echo esc_html( $cat->name ); ?>
          </span>
        <?php endif; ?>
      </div>

      <h2 class="post-item__title"><?php the_title(); ?></h2>

      <p class="post-item__excerpt">
        <?php echo esc_html( wp_trim_words( get_the_excerpt(), 60, '...' ) ); ?>
      </p>

      <div class="post-item__footer">
        <div class="post-item__tags">
          <?php
          $tags = get_the_tags();
          if ( $tags ) :
            foreach ( array_slice( $tags, 0, 3 ) as $tag ) :
          ?>
            <span class="post-item__tag">#<?php echo esc_html( $tag->name ); ?></span>
          <?php
            endforeach;
          endif;
          ?>
        </div>
        <span class="post-item__readtime">
          <?php
          $content    = get_the_content();
          $word_count = mb_strlen( strip_tags( $content ) );
          $minutes    = max( 1, ceil( $word_count / 400 ) );
          echo esc_html( $minutes ) . ' min read';
          ?>
        </span>
      </div>
    </div>

  </a>
</article>
