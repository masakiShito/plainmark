<?php
/**
 * Template Name: Portfolio
 * Description: A page template for displaying portfolio items
 *
 * @package plainmark
 * @since 0.1.0
 */

get_header();
?>

<main id="primary" class="site-main">
    <header class="page-header">
        <?php the_title( '<h1 class="page-title">', '</h1>' ); ?>

        <?php if ( has_excerpt() ) : ?>
            <div class="page-description">
                <?php the_excerpt(); ?>
            </div>
        <?php endif; ?>
    </header>

    <?php
    $portfolio_args = array(
        'post_type'      => 'portfolio',
        'posts_per_page' => 12,
        'paged'          => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
    );

    $portfolio_query = new WP_Query( $portfolio_args );
    ?>

    <?php if ( $portfolio_query->have_posts() ) : ?>
        <div class="portfolio-grid">
            <?php
            while ( $portfolio_query->have_posts() ) :
                $portfolio_query->the_post();
                get_template_part( 'template-parts/content', 'portfolio' );
            endwhile;
            ?>
        </div>

        <?php
        $big = 999999999;
        echo '<nav class="pagination">';
        echo paginate_links( array(
            'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format'    => '?paged=%#%',
            'current'   => max( 1, get_query_var( 'paged' ) ),
            'total'     => $portfolio_query->max_num_pages,
            'prev_text' => __( '&laquo; Previous', 'plainmark' ),
            'next_text' => __( 'Next &raquo;', 'plainmark' ),
        ) );
        echo '</nav>';
        ?>

        <?php wp_reset_postdata(); ?>

    <?php else : ?>
        <p><?php esc_html_e( 'No portfolio items found.', 'plainmark' ); ?></p>
    <?php endif; ?>
</main>

<?php
get_footer();
