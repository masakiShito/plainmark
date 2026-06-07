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

    // Front page stylesheet.
    if ( is_front_page() ) {
        $front_page_css = PLAINMARK_DIR . '/assets/css/front-page.css';

        wp_enqueue_style(
            'plainmark-front-page',
            PLAINMARK_URI . '/assets/css/front-page.css',
            array( 'plainmark-style' ),
            file_exists( $front_page_css ) ? (string) filemtime( $front_page_css ) : PLAINMARK_VERSION
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

    // Localize script with data
    wp_localize_script( 'plainmark-script', 'plainmarkData', array(
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'plainmark_nonce' ),
        'homeUrl'  => home_url(),
        'themeUrl' => PLAINMARK_URI,
        'i18n'     => array(
            'menu'   => esc_html__( 'Menu', 'plainmark' ),
            'search' => esc_html__( 'Search', 'plainmark' ),
            'close'  => esc_html__( 'Close', 'plainmark' ),
        ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'plainmark_scripts' );

/**
 * Enqueue block editor assets
 */
function plainmark_editor_assets() {
    wp_enqueue_style(
        'plainmark-editor-style',
        PLAINMARK_URI . '/assets/css/editor-style.css',
        array(),
        PLAINMARK_VERSION
    );

    wp_enqueue_script(
        'plainmark-code-language-editor',
        PLAINMARK_URI . '/assets/js/code-language-editor.js',
        array( 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-compose', 'wp-element', 'wp-hooks', 'wp-i18n' ),
        PLAINMARK_VERSION,
        true
    );

    // Series settings sidebar panel.
    wp_enqueue_script(
        'plainmark-series-sidebar',
        PLAINMARK_URI . '/assets/js/series-sidebar.js',
        array( 'wp-plugins', 'wp-edit-post', 'wp-components', 'wp-data', 'wp-element', 'wp-i18n' ),
        PLAINMARK_VERSION,
        true
    );
}
add_action( 'enqueue_block_editor_assets', 'plainmark_editor_assets' );

/**
 * Add preload for critical assets
 */
function plainmark_preload_assets() {
    ?>
    <link rel="preload" href="<?php echo esc_url( PLAINMARK_URI . '/assets/css/main.css' ); ?>" as="style">
    <link rel="preload" href="<?php echo esc_url( PLAINMARK_URI . '/assets/js/main.js' ); ?>" as="script">
    <?php
}
add_action( 'wp_head', 'plainmark_preload_assets', 1 );
