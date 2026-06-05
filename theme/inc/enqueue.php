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
