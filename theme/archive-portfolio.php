<?php
/**
 * Portfolio archive template.
 *
 * @package plainmark
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="works-archive">
    <section class="works-hero">
        <div class="container container--wide works-hero__inner">
            <p class="works-eyebrow"><?php esc_html_e( 'WORKS', 'plainmark' ); ?></p>
            <h1 class="works-hero__title"><?php esc_html_e( '作ったものを、考えたことまで。', 'plainmark' ); ?></h1>
            <p class="works-hero__lead">
                <?php esc_html_e( '単なる成果物一覧ではなく、何を解決したのか、どう設計したのか、どこを工夫したのかをまとめたケーススタディです。', 'plainmark' ); ?>
            </p>
        </div>
    </section>

    <section class="works-list-section">
        <div class="container container--wide">
            <?php if ( have_posts() ) : ?>
                <div class="works-list">
                    <?php while ( have_posts() ) : the_post(); ?>
                        <?php
                        $summary    = get_post_meta( get_the_ID(), 'work_summary', true );
                        $problem    = get_post_meta( get_the_ID(), 'work_problem', true );
                        $role       = get_post_meta( get_the_ID(), 'work_role', true );
                        $period     = get_post_meta( get_the_ID(), 'work_period', true );
                        $github_url = get_post_meta( get_the_ID(), 'work_github_url', true );
                        $demo_url   = get_post_meta( get_the_ID(), 'work_demo_url', true );
                        $tech_terms = get_the_terms( get_the_ID(), 'technology' );
                        ?>
                        <article <?php post_class( 'work-card' ); ?>>
                            <a class="work-card__main" href="<?php the_permalink(); ?>">
                                <div class="work-card__meta">
                                    <span><?php echo esc_html( $period ?: get_the_date( 'Y' ) ); ?></span>
                                    <?php if ( $role ) : ?>
                                        <span><?php echo esc_html( $role ); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="work-card__body">
                                    <h2 class="work-card__title"><?php the_title(); ?></h2>
                                    <p class="work-card__summary">
                                        <?php echo esc_html( $summary ?: wp_trim_words( get_the_excerpt(), 42, '…' ) ); ?>
                                    </p>

                                    <?php if ( $problem ) : ?>
                                        <div class="work-card__problem">
                                            <span><?php esc_html_e( 'Problem', 'plainmark' ); ?></span>
                                            <p><?php echo esc_html( wp_trim_words( $problem, 34, '…' ) ); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="work-card__footer">
                                    <?php if ( $tech_terms && ! is_wp_error( $tech_terms ) ) : ?>
                                        <div class="work-card__techs">
                                            <?php foreach ( array_slice( $tech_terms, 0, 5 ) as $term ) : ?>
                                                <span><?php echo esc_html( $term->name ); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <span class="work-card__arrow" aria-hidden="true">→</span>
                                </div>
                            </a>

                            <?php if ( has_post_thumbnail() ) : ?>
                                <a class="work-card__visual" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                                    <?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
                                </a>
                            <?php endif; ?>

                            <?php if ( $github_url || $demo_url ) : ?>
                                <div class="work-card__links">
                                    <?php if ( $github_url ) : ?>
                                        <a href="<?php echo esc_url( $github_url ); ?>" target="_blank" rel="noopener noreferrer">GitHub</a>
                                    <?php endif; ?>
                                    <?php if ( $demo_url ) : ?>
                                        <a href="<?php echo esc_url( $demo_url ); ?>" target="_blank" rel="noopener noreferrer">Demo</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </article>
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
                <div class="works-empty">
                    <p><?php esc_html_e( 'まだ制作物がありません。Portfolio投稿を追加すると、ここにケーススタディとして表示されます。', 'plainmark' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php get_footer();
