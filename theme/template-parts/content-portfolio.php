<?php
/**
 * Template part for displaying portfolio items
 *
 * @package plainmark
 * @since 0.1.0
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'portfolio-item' ); ?>>
    <?php if ( has_post_thumbnail() ) : ?>
        <div class="portfolio-thumbnail">
            <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail( 'medium_large' ); ?>
                <div class="portfolio-overlay">
                    <span class="portfolio-view"><?php esc_html_e( 'View Project', 'plainmark' ); ?></span>
                </div>
            </a>
        </div>
    <?php endif; ?>

    <div class="portfolio-content">
        <header class="entry-header">
            <?php the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' ); ?>
        </header>

        <div class="entry-summary">
            <?php the_excerpt(); ?>
        </div>

        <?php
        $portfolio_categories = get_the_terms( get_the_ID(), 'portfolio_category' );
        if ( $portfolio_categories && ! is_wp_error( $portfolio_categories ) ) :
            ?>
            <div class="portfolio-categories">
                <?php
                foreach ( $portfolio_categories as $category ) {
                    echo '<span class="portfolio-category">' . esc_html( $category->name ) . '</span>';
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
</article>
