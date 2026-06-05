<?php
/**
 * The 404 template
 *
 * @package plainmark
 * @since 0.1.0
 */

get_header();
?>

<main id="primary" class="site-main">
    <section class="error-404 not-found">
        <header class="page-header">
            <h1 class="page-title"><?php esc_html_e( 'Oops! That page can&rsquo;t be found.', 'plainmark' ); ?></h1>
        </header>

        <div class="page-content">
            <p><?php esc_html_e( 'It looks like nothing was found at this location. Maybe try a search?', 'plainmark' ); ?></p>

            <?php get_search_form(); ?>

            <div class="error-404__widgets">
                <?php
                the_widget( 'WP_Widget_Recent_Posts', array(
                    'title'  => esc_html__( 'Recent Posts', 'plainmark' ),
                    'number' => 5,
                ) );
                ?>

                <div class="widget widget_categories">
                    <h2 class="widget-title"><?php esc_html_e( 'Categories', 'plainmark' ); ?></h2>
                    <ul>
                        <?php
                        wp_list_categories( array(
                            'orderby'    => 'count',
                            'order'      => 'DESC',
                            'show_count' => 1,
                            'title_li'   => '',
                            'number'     => 10,
                        ) );
                        ?>
                    </ul>
                </div>

                <?php
                the_widget( 'WP_Widget_Archives', array(
                    'title'    => esc_html__( 'Archives', 'plainmark' ),
                    'count'    => 1,
                    'dropdown' => 0,
                ) );

                the_widget( 'WP_Widget_Tag_Cloud', array(
                    'title' => esc_html__( 'Tags', 'plainmark' ),
                ) );
                ?>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();
