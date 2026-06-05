<?php
/**
 * Template part for displaying single posts
 *
 * @package plainmark
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

$categories = get_the_category();
$tags       = get_the_tags();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-post' ); ?>>
  <header class="single-post__header">
    <div class="single-post__meta">
      <time class="single-post__date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
        <?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?>
      </time>

      <?php if ( $categories ) : ?>
        <?php foreach ( $categories as $category ) : ?>
          <?php
          $category_url = get_category_link( $category->term_id );
          if ( is_wp_error( $category_url ) ) {
            continue;
          }
          ?>
          <a class="single-post__cat" href="<?php echo esc_url( $category_url ); ?>">
            <?php echo esc_html( $category->name ); ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php the_title( '<h1 class="single-post__title">', '</h1>' ); ?>
  </header>

  <?php if ( has_post_thumbnail() ) : ?>
    <figure class="single-post__thumbnail">
      <?php
      the_post_thumbnail( 'plainmark-featured', array(
        'class' => 'single-post__thumbnail-img',
      ) );
      ?>
    </figure>
  <?php endif; ?>

  <div class="single-post__content">
    <?php
    the_content();

    wp_link_pages( array(
      'before' => '<nav class="page-links" aria-label="' . esc_attr__( '投稿ページ', 'plainmark' ) . '"><span class="page-links-title">' . esc_html__( 'ページ:', 'plainmark' ) . '</span>',
      'after'  => '</nav>',
    ) );
    ?>
  </div>

  <footer class="single-post__footer">
    <?php if ( $tags ) : ?>
      <div class="single-post__tags" aria-label="<?php esc_attr_e( 'タグ', 'plainmark' ); ?>">
        <?php foreach ( $tags as $tag ) : ?>
          <?php
          $tag_url = get_tag_link( $tag->term_id );
          if ( is_wp_error( $tag_url ) ) {
            continue;
          }
          ?>
          <a class="single-post__tag" href="<?php echo esc_url( $tag_url ); ?>">
            #<?php echo esc_html( $tag->name ); ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ( get_previous_post() || get_next_post() ) : ?>
      <nav class="single-post__nav" aria-label="<?php esc_attr_e( '前後の記事', 'plainmark' ); ?>">
        <div class="single-post__nav-item single-post__nav-item--prev">
          <?php previous_post_link( '%link', '&larr; ' . esc_html__( '前の記事:', 'plainmark' ) . ' %title' ); ?>
        </div>
        <div class="single-post__nav-item single-post__nav-item--next">
          <?php next_post_link( '%link', esc_html__( '次の記事:', 'plainmark' ) . ' %title &rarr;' ); ?>
        </div>
      </nav>
    <?php endif; ?>

    <a class="single-post__back" href="<?php echo esc_url( home_url( '/' ) ); ?>">
      &larr; <?php esc_html_e( '記事一覧へ戻る', 'plainmark' ); ?>
    </a>
  </footer>
</article>
