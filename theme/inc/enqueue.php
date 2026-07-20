<?php
/**
 * Enqueue scripts and styles
 *
 * @package plainmark
 * @since 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue frontend scripts and styles
 */
function plainmark_scripts() {
    // Google Fonts: Noto Sans JP
    wp_enqueue_style(
        'plainmark-google-fonts',
        'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap',
        array(),
        null
    );

    // Main stylesheet
    wp_enqueue_style(
        'plainmark-style',
        PLAINMARK_URI . '/assets/css/main.css',
        array(),
        PLAINMARK_VERSION
    );

    $freshness_badge_css = PLAINMARK_DIR . '/assets/css/freshness-badge.css';
    wp_enqueue_style(
        'plainmark-freshness-badge',
        PLAINMARK_URI . '/assets/css/freshness-badge.css',
        array( 'plainmark-style' ),
        file_exists( $freshness_badge_css ) ? (string) filemtime( $freshness_badge_css ) : PLAINMARK_VERSION
    );

    $live_search_css = PLAINMARK_DIR . '/assets/css/live-search.css';
    wp_enqueue_style(
        'plainmark-live-search',
        PLAINMARK_URI . '/assets/css/live-search.css',
        array( 'plainmark-style' ),
        file_exists( $live_search_css ) ? (string) filemtime( $live_search_css ) : PLAINMARK_VERSION
    );

    $differentiation_css = PLAINMARK_DIR . '/assets/css/differentiation-features.css';
    wp_enqueue_style(
        'plainmark-differentiation-features',
        PLAINMARK_URI . '/assets/css/differentiation-features.css',
        array( 'plainmark-style' ),
        file_exists( $differentiation_css ) ? (string) filemtime( $differentiation_css ) : PLAINMARK_VERSION
    );

    $snippet_library_css = PLAINMARK_DIR . '/assets/css/snippet-library.css';
    wp_enqueue_style(
        'plainmark-snippet-library',
        PLAINMARK_URI . '/assets/css/snippet-library.css',
        array( 'plainmark-style' ),
        file_exists( $snippet_library_css ) ? (string) filemtime( $snippet_library_css ) : PLAINMARK_VERSION
    );

    $feature_navigation_css = PLAINMARK_DIR . '/assets/css/feature-navigation.css';
    wp_enqueue_style(
        'plainmark-feature-navigation',
        PLAINMARK_URI . '/assets/css/feature-navigation.css',
        array( 'plainmark-style' ),
        file_exists( $feature_navigation_css ) ? (string) filemtime( $feature_navigation_css ) : PLAINMARK_VERSION
    );

    if ( get_query_var( 'plainmark_knowledge_map' ) ) {
        $knowledge_map_fixes_css = PLAINMARK_DIR . '/assets/css/knowledge-map-fixes.css';

        wp_enqueue_style(
            'plainmark-knowledge-map-fixes',
            PLAINMARK_URI . '/assets/css/knowledge-map-fixes.css',
            array( 'plainmark-differentiation-features' ),
            file_exists( $knowledge_map_fixes_css ) ? (string) filemtime( $knowledge_map_fixes_css ) : PLAINMARK_VERSION
        );
    }

    // Front page stylesheet.
    if ( is_front_page() ) {
        $front_page_css = PLAINMARK_DIR . '/assets/css/front-page.css';

        wp_enqueue_style(
            'plainmark-front-page',
            PLAINMARK_URI . '/assets/css/front-page.css',
            array( 'plainmark-style' ),
            file_exists( $front_page_css ) ? (string) filemtime( $front_page_css ) : PLAINMARK_VERSION
        );

        $front_hero_js = PLAINMARK_DIR . '/assets/js/front-hero.js';
        wp_enqueue_script(
            'plainmark-front-hero',
            PLAINMARK_URI . '/assets/js/front-hero.js',
            array(),
            file_exists( $front_hero_js ) ? (string) filemtime( $front_hero_js ) : PLAINMARK_VERSION,
            true
        );
    }

    // Shared Home / Blog hero stylesheet. Load after front-page.css so the editorial hero wins.
    if ( is_front_page() || is_home() || get_query_var( 'plainmark_blog_archive' ) ) {
        $page_heroes_css = PLAINMARK_DIR . '/assets/css/page-heroes.css';
        $dependencies    = is_front_page()
            ? array( 'plainmark-front-page' )
            : array( 'plainmark-style' );

        wp_enqueue_style(
            'plainmark-page-heroes',
            PLAINMARK_URI . '/assets/css/page-heroes.css',
            $dependencies,
            file_exists( $page_heroes_css ) ? (string) filemtime( $page_heroes_css ) : PLAINMARK_VERSION
        );
    }

    // Blog index / archive card stylesheet.
    if ( is_home() || is_archive() || get_query_var( 'plainmark_blog_archive' ) ) {
        $blog_index_css = PLAINMARK_DIR . '/assets/css/blog-index.css';
        $blog_index_ver = file_exists( $blog_index_css ) ? (string) filemtime( $blog_index_css ) : PLAINMARK_VERSION;
        $blog_index_ver .= '-darkmode-fix-20260615';

        wp_enqueue_style(
            'plainmark-blog-index',
            PLAINMARK_URI . '/assets/css/blog-index.css',
            array( 'plainmark-style' ),
            $blog_index_ver
        );
    }

    // About page stylesheet.
    if ( is_page( 'about' ) || get_query_var( 'plainmark_about_page' ) ) {
        $about_css        = PLAINMARK_DIR . '/assets/css/about.css';
        $about_polish_css = PLAINMARK_DIR . '/assets/css/about-polish.css';

        wp_enqueue_style(
            'plainmark-about',
            PLAINMARK_URI . '/assets/css/about.css',
            array( 'plainmark-style' ),
            file_exists( $about_css ) ? (string) filemtime( $about_css ) : PLAINMARK_VERSION
        );

        wp_enqueue_style(
            'plainmark-about-polish',
            PLAINMARK_URI . '/assets/css/about-polish.css',
            array( 'plainmark-about' ),
            file_exists( $about_polish_css ) ? (string) filemtime( $about_polish_css ) : PLAINMARK_VERSION
        );
    }

    // Works stylesheet.
    if ( is_post_type_archive( 'portfolio' ) || is_singular( 'portfolio' ) ) {
        $works_css = PLAINMARK_DIR . '/assets/css/works.css';

        wp_enqueue_style(
            'plainmark-works',
            PLAINMARK_URI . '/assets/css/works.css',
            array( 'plainmark-style' ),
            file_exists( $works_css ) ? (string) filemtime( $works_css ) : PLAINMARK_VERSION
        );
    }

    // Main script
    wp_enqueue_script(
        'plainmark-script',
        PLAINMARK_URI . '/assets/js/main.js',
        array(),
        PLAINMARK_VERSION,
        true
    );

    // Navigation script
    wp_enqueue_script(
        'plainmark-navigation',
        PLAINMARK_URI . '/assets/js/navigation.js',
        array(),
        PLAINMARK_VERSION,
        true
    );

    // Search script
    wp_enqueue_script(
        'plainmark-search',
        PLAINMARK_URI . '/assets/js/search.js',
        array(),
        PLAINMARK_VERSION,
        true
    );

    $differentiation_js = PLAINMARK_DIR . '/assets/js/differentiation-features.js';
    wp_enqueue_script(
        'plainmark-differentiation-features',
        PLAINMARK_URI . '/assets/js/differentiation-features.js',
        array(),
        file_exists( $differentiation_js ) ? (string) filemtime( $differentiation_js ) : PLAINMARK_VERSION,
        true
    );

    $feature_navigation_js = PLAINMARK_DIR . '/assets/js/feature-navigation.js';
    wp_enqueue_script(
        'plainmark-feature-navigation',
        PLAINMARK_URI . '/assets/js/feature-navigation.js',
        array(),
        file_exists( $feature_navigation_js ) ? (string) filemtime( $feature_navigation_js ) : PLAINMARK_VERSION,
        true
    );

    // Dark mode script (load early to prevent flash).
    wp_enqueue_script(
        'plainmark-dark-mode',
        PLAINMARK_URI . '/assets/js/dark-mode.js',
        array(),
        PLAINMARK_VERSION,
        false // Load in head to prevent flash.
    );

    // Single post specific scripts.
    if ( is_single() ) {
        $show_code_copy = true;
        if ( function_exists( 'plainmark_get_article_meta' ) ) {
            $article_meta   = plainmark_get_article_meta( get_the_ID() );
            $show_code_copy = $article_meta['show_code_copy'] ?? true;
        }

        if ( $show_code_copy ) {
            wp_enqueue_script(
                'plainmark-code-copy',
                PLAINMARK_URI . '/assets/js/code-copy.js',
                array(),
                PLAINMARK_VERSION,
                true
            );
        }

        // Reading progress script.
        wp_enqueue_script(
            'plainmark-reading-progress',
            PLAINMARK_URI . '/assets/js/reading-progress.js',
            array(),
            PLAINMARK_VERSION,
            true
        );

        // Article enhancements script (anchors, share, feedback).
        wp_enqueue_script(
            'plainmark-article-enhancements',
            PLAINMARK_URI . '/assets/js/article-enhancements.js',
            array(),
            PLAINMARK_VERSION,
            true
        );
    }

    // Comment reply script
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'plainmark_scripts' );

/**
 * Add preload for critical fonts
 */
function plainmark_preload_fonts() {
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php
}
add_action( 'wp_head', 'plainmark_preload_fonts', 1 );

/**
 * Add inline script for dark mode initialization
 */
function plainmark_dark_mode_init() {
    ?>
    <script>
        (function() {
            const saved = localStorage.getItem('plainmark-color-scheme');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const effective = (saved === 'dark' || saved === 'light') ? saved : (systemPrefersDark ? 'dark' : 'light');
            if (effective === 'dark') {
                document.documentElement.classList.add('is-dark-mode');
            }
            document.documentElement.setAttribute('data-color-scheme', effective);
        })();
    </script>
    <?php
}
add_action( 'wp_head', 'plainmark_dark_mode_init', 0 );