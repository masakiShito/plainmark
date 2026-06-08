<?php
/**
 * Single portfolio template.
 *
 * @package plainmark
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="work-single">
    <?php while ( have_posts() ) : the_post(); ?>
        <?php
        $summary      = get_post_meta( get_the_ID(), 'work_summary', true );
        $problem      = get_post_meta( get_the_ID(), 'work_problem', true );
        $solution     = get_post_meta( get_the_ID(), 'work_solution', true );
        $architecture = get_post_meta( get_the_ID(), 'work_architecture', true );
        $features     = get_post_meta( get_the_ID(), 'work_features', true );
        $learnings    = get_post_meta( get_the_ID(), 'work_learnings', true );
        $next_steps   = get_post_meta( get_the_ID(), 'work_next_steps', true );
        $role         = get_post_meta( get_the_ID(), 'work_role', true );
        $period       = get_post_meta( get_the_ID(), 'work_period', true );
        $github_url   = get_post_meta( get_the_ID(), 'work_github_url', true );
        $demo_url     = get_post_meta( get_the_ID(), 'work_demo_url', true );
        $tech_terms   = get_the_terms( get_the_ID(), 'technology' );
        ?>

        <article <?php post_class( 'work-case' ); ?>>
            <section class="work-case-hero">
                <div class="container container--wide work-case-hero__inner">
                    <div class="work-case-hero__content">
                        <p class="works-eyebrow"><?php esc_html_e( 'CASE STUDY', 'plainmark' ); ?></p>
                        <?php the_title( '<h1 class="work-case-hero__title">', '</h1>' ); ?>
                        <p class="work-case-hero__lead">
                            <?php echo esc_html( $summary ?: wp_trim_words( get_the_excerpt(), 52, '…' ) ); ?>
                        </p>

                        <div class="work-case-hero__actions">
                            <?php if ( $github_url ) : ?>
                                <a class="work-button work-button--primary" href="<?php echo esc_url( $github_url ); ?>" target="_blank" rel="noopener noreferrer">GitHub</a>
                            <?php endif; ?>
                            <?php if ( $demo_url ) : ?>
                                <a class="work-button" href="<?php echo esc_url( $demo_url ); ?>" target="_blank" rel="noopener noreferrer">Demo</a>
                            <?php endif; ?>
                            <a class="work-button" href="<?php echo esc_url( get_post_type_archive_link( 'portfolio' ) ); ?>"><?php esc_html_e( 'Works一覧', 'plainmark' ); ?></a>
                        </div>
                    </div>

                    <aside class="work-case-panel" aria-label="<?php esc_attr_e( 'プロジェクト概要', 'plainmark' ); ?>">
                        <dl>
                            <?php if ( $role ) : ?>
                                <div>
                                    <dt><?php esc_html_e( 'Role', 'plainmark' ); ?></dt>
                                    <dd><?php echo esc_html( $role ); ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ( $period ) : ?>
                                <div>
                                    <dt><?php esc_html_e( 'Period', 'plainmark' ); ?></dt>
                                    <dd><?php echo esc_html( $period ); ?></dd>
                                </div>
                            <?php endif; ?>
                            <?php if ( $tech_terms && ! is_wp_error( $tech_terms ) ) : ?>
                                <div>
                                    <dt><?php esc_html_e( 'Tech', 'plainmark' ); ?></dt>
                                    <dd class="work-case-panel__techs">
                                        <?php foreach ( $tech_terms as $term ) : ?>
                                            <span><?php echo esc_html( $term->name ); ?></span>
                                        <?php endforeach; ?>
                                    </dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                    </aside>
                </div>
            </section>

            <?php if ( has_post_thumbnail() ) : ?>
                <section class="work-case-visual">
                    <div class="container container--wide">
                        <div class="work-case-visual__frame">
                            <?php the_post_thumbnail( 'large' ); ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <section class="work-case-section work-case-overview">
                <div class="container container--wide work-case-layout">
                    <div class="work-case-section__heading">
                        <p class="works-eyebrow"><?php esc_html_e( 'OVERVIEW', 'plainmark' ); ?></p>
                        <h2><?php esc_html_e( '何を作ったか', 'plainmark' ); ?></h2>
                    </div>
                    <div class="work-case-body">
                        <?php the_content(); ?>
                    </div>
                </div>
            </section>

            <?php if ( $problem || $solution ) : ?>
                <section class="work-case-section work-case-problem-solution">
                    <div class="container container--wide work-case-split">
                        <?php if ( $problem ) : ?>
                            <div class="work-case-block">
                                <span><?php esc_html_e( 'Problem', 'plainmark' ); ?></span>
                                <h2><?php esc_html_e( '課題', 'plainmark' ); ?></h2>
                                <p><?php echo nl2br( esc_html( $problem ) ); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ( $solution ) : ?>
                            <div class="work-case-block work-case-block--dark">
                                <span><?php esc_html_e( 'Solution', 'plainmark' ); ?></span>
                                <h2><?php esc_html_e( '解決', 'plainmark' ); ?></h2>
                                <p><?php echo nl2br( esc_html( $solution ) ); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ( $architecture || $features ) : ?>
                <section class="work-case-section">
                    <div class="container container--wide work-case-layout">
                        <div class="work-case-section__heading">
                            <p class="works-eyebrow"><?php esc_html_e( 'DESIGN', 'plainmark' ); ?></p>
                            <h2><?php esc_html_e( 'どう設計したか', 'plainmark' ); ?></h2>
                        </div>
                        <div class="work-case-notes">
                            <?php if ( $architecture ) : ?>
                                <div>
                                    <h3><?php esc_html_e( 'Architecture', 'plainmark' ); ?></h3>
                                    <p><?php echo nl2br( esc_html( $architecture ) ); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ( $features ) : ?>
                                <div>
                                    <h3><?php esc_html_e( 'Features', 'plainmark' ); ?></h3>
                                    <p><?php echo nl2br( esc_html( $features ) ); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ( $learnings || $next_steps ) : ?>
                <section class="work-case-section work-case-learning">
                    <div class="container container--wide work-case-layout">
                        <div class="work-case-section__heading">
                            <p class="works-eyebrow"><?php esc_html_e( 'LEARNING', 'plainmark' ); ?></p>
                            <h2><?php esc_html_e( '作って学んだこと', 'plainmark' ); ?></h2>
                        </div>
                        <div class="work-case-notes">
                            <?php if ( $learnings ) : ?>
                                <div>
                                    <h3><?php esc_html_e( 'Learnings', 'plainmark' ); ?></h3>
                                    <p><?php echo nl2br( esc_html( $learnings ) ); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ( $next_steps ) : ?>
                                <div>
                                    <h3><?php esc_html_e( 'Next', 'plainmark' ); ?></h3>
                                    <p><?php echo nl2br( esc_html( $next_steps ) ); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </article>
    <?php endwhile; ?>
</main>

<?php get_footer();
