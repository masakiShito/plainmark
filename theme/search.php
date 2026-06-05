<?php
/**
 * The search results template
 *
 * @package plainmark
 * @since 0.1.0
 */

get_header();
?>

<main id="primary" class="site-main">
    <?php if ( have_posts() ) : ?>

        <header class="page-header">
            <h1 class="page-title">
                <?php
                printf(
                    esc_html__( 'Search Results for: %s', 'plainmark' ),
                    '<span>' . get_search_query() . '</span>'
                );
                ?>
            </h1>
        </header>

        <?php
        while ( have_posts() ) :
            the_post();
            get_template_part( 'template-parts/content', 'search' );
        endwhile;

        the_posts_pagination( array(
            'mid_size'  => 2,
            'prev_text' => __( '&laquo; Previous', 'plainmark' ),
            'next_text' => __( 'Next &raquo;', 'plainmark' ),
        ) );

    else :

        get_template_part( 'template-parts/content', 'none' );

    endif;
    ?>
</main>

<?php
get_sidebar();
get_footer();
