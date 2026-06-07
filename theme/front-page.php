<?php
/**
 * Front page template
 *
 * @package plainmark
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

$site_description = get_bloginfo( 'description' );
$posts_page_id    = (int) get_option( 'page_for_posts' );
$posts_page_url   = $posts_page_id ? get_permalink( $posts_page_id ) : home_url( '/blog/' );
$categories       = get_categories(
    array(
        'orderby' => 'count',
        'order'   => 'DESC',
        'number'  => 6,
    )
);
$latest_posts = new WP_Query(
    array(
        'post_type'           => 'post',
        'posts_per_page'      => 6,
        'ignore_sticky_posts' => true,
    )
);
$portfolio_items = new WP_Query(
    array(
        'post_type'           => 'portfolio',
        'posts_per_page'      => 3,
        'ignore_sticky_posts' => true,
    )
);
?>

<main id="main" class="front-page">
    <section class="front-hero">
        <div class="container container--wide front-hero__inner">
            <div class="front-hero__content">
                <p class="front-hero__eyebrow"><?php esc_html_e( 'TECH BLOG & PORTFOLIO', 'plainmark' ); ?></p>
                <h1 class="front-hero__title">
                    <?php bloginfo( 'name' ); ?>
                </h1>
                <p class="front-hero__lead">
                    <?php
                    echo esc_html(
                        $site_description ?: __( '技術、学び、ものづくりをシンプルに記録する場所。', 'plainmark' )
                    );
                    ?>
                </p>
                <div class="front-hero__actions">
                    <a class="button button--primary" href="#latest-posts">
                        <?php esc_html_e( '最新の記事を見る', 'plainmark' ); ?>
                    </a>
                    <?php if ( post_type_exists( 'portfolio' ) ) : ?>
                        <a class="button button--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'portfolio' ) ); ?>">
                            <?php esc_html_e( '制作物を見る', 'plainmark' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="front-hero__visual" aria-hidden="true">
                <div class="front-hero__code">
                    <span>const</span> <strong>ideas</strong> = [
                    <br>
                    &nbsp;&nbsp;'code',
                    <br>
                    &nbsp;&nbsp;'design',
                    <br>
                    &nbsp;&nbsp;'learning'
                    <br>
                    ];
                </div>
            </div>
        </div>
    </section>

    <?php if ( $categories ) : ?>
        <section class="front-section front-categories" aria-labelledby="front-categories-title">
            <div class="container container--wide">
                <div class="front-section__heading">
                    <div>
                        <p class="front-section__eyebrow"><?php esc_html_e( 'EXPLORE', 'plainmark' ); ?></p>
                        <h2 id="front-categories-title" class="front-section__title"><?php esc_html_e( 'カテゴリー', 'plainmark' ); ?></h2>
                    </div>
                </div>
                <div class="category-chips">
                    <?php foreach ( $categories as $category ) : ?>
                        <a class="category-chip" href="<?php echo esc_url( get_category_link( $category ) ); ?>">
                            <span><?php echo esc_html( $category->name ); ?></span>
                            <span class="category-chip__count"><?php echo esc_html( $category->count ); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section id="latest-posts" class="front-section front-posts" aria-labelledby="front-posts-title">
        <div class="container container--wide">
            <div class="front-section__heading">
                <div>
                    <p class="front-section__eyebrow"><?php esc_html_e( 'LATEST', 'plainmark' ); ?></p>
                    <h2 id="front-posts-title" class="front-section__title"><?php esc_html_e( '最新の記事', 'plainmark' ); ?></h2>
                </div>
                <a class="front-section__link" href="<?php echo esc_url( $posts_page_url ); ?>">
                    <?php esc_html_e( 'すべての記事', 'plainmark' ); ?>
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            <?php if ( $latest_posts->have_posts() ) : ?>
                <div class="front-post-grid">
                    <?php while ( $latest_posts->have_posts() ) : $latest_posts->the_post(); ?>
                        <article <?php post_class( 'front-post-card' ); ?>>
                            <a class="front-post-card__link" href="<?php the_permalink(); ?>">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <div class="front-post-card__media">
                                        <?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="front-post-card__body">
                                    <div class="front-post-card__meta">
                                        <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                            <?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?>
                                        </time>
                                        <?php $post_categories = get_the_category(); ?>
                                        <?php if ( $post_categories ) : ?>
                                            <span><?php echo esc_html( $post_categories[0]->name ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <h3 class="front-post-card__title"><?php the_title(); ?></h3>
                                    <p class="front-post-card__excerpt">
                                        <?php echo esc_html( wp_trim_words( get_the_excerpt(), 42, '…' ) ); ?>
                                    </p>
                                    <span class="front-post-card__more"><?php esc_html_e( '記事を読む', 'plainmark' ); ?> →</span>
                                </div>
                            </a>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <div class="front-empty">
                    <p><?php esc_html_e( '記事を公開すると、ここに最新記事が表示されます。', 'plainmark' ); ?></p>
                </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>
    </section>

    <?php if ( $portfolio_items->have_posts() ) : ?>
        <section id="works" class="front-section front-portfolio" aria-labelledby="front-portfolio-title">
            <div class="container container--wide">
                <div class="front-section__heading">
                    <div>
                        <p class="front-section__eyebrow"><?php esc_html_e( 'WORKS', 'plainmark' ); ?></p>
                        <h2 id="front-portfolio-title" class="front-section__title"><?php esc_html_e( '制作物', 'plainmark' ); ?></h2>
                    </div>
                    <a class="front-section__link" href="<?php echo esc_url( get_post_type_archive_link( 'portfolio' ) ); ?>">
                        <?php esc_html_e( 'すべて見る', 'plainmark' ); ?>
                        <span aria-hidden="true">→</span>
                    </a>
                </div>

                <div class="front-work-grid">
                    <?php while ( $portfolio_items->have_posts() ) : $portfolio_items->the_post(); ?>
                        <article <?php post_class( 'front-work-card' ); ?>>
                            <a href="<?php the_permalink(); ?>" class="front-work-card__link">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <div class="front-work-card__media">
                                        <?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="front-work-card__body">
                                    <h3><?php the_title(); ?></h3>
                                    <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '…' ) ); ?></p>
                                </div>
                            </a>
                        </article>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
        <?php wp_reset_postdata(); ?>
    <?php endif; ?>

    <section id="about" class="front-section front-about">
        <div class="container container--wide front-about__inner">
            <div>
                <p class="front-section__eyebrow"><?php esc_html_e( 'ABOUT', 'plainmark' ); ?></p>
                <h2 class="front-about__title"><?php esc_html_e( '学びを、わかりやすく残す。', 'plainmark' ); ?></h2>
            </div>
            <p class="front-about__text">
                <?php esc_html_e( '日々の開発で得た知識や、試してわかったことを整理して発信しています。誰かの問題解決に少しでも役立つ、読みやすい技術ブログを目指しています。', 'plainmark' ); ?>
            </p>
        </div>
    </section>
</main>

<?php get_footer();
