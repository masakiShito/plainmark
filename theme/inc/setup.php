<?php
/**
 * Theme setup and configuration
 *
 * @package plainmark
 * @since 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function plainmark_setup() {
    // Make theme available for translation
    load_theme_textdomain( 'plainmark', PLAINMARK_DIR . '/languages' );

    // Add default posts and comments RSS feed links to head
    add_theme_support( 'automatic-feed-links' );

    // Let WordPress manage the document title
    add_theme_support( 'title-tag' );

    // Enable support for Post Thumbnails
    add_theme_support( 'post-thumbnails' );

    // Custom image sizes
    add_image_size( 'plainmark-featured', 1200, 630, true );
    add_image_size( 'plainmark-thumbnail', 400, 300, true );

    // Register navigation menus
    register_nav_menus( array(
        'primary' => 'メインナビゲーション',
        'footer'  => 'フッターナビゲーション',
    ) );

    // Switch default core markup to HTML5
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // Add support for wide and full-width blocks
    add_theme_support( 'align-wide' );

    // Add support for Block Styles
    add_theme_support( 'wp-block-styles' );

    // Add support for editor styles
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/editor-style.css' );

    // Add support for custom logo
    add_theme_support( 'custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-width'  => true,
        'flex-height' => true,
    ) );

    // Add support for post formats
    add_theme_support( 'post-formats', array(
        'aside',
        'gallery',
        'link',
        'quote',
        'video',
    ) );

    // Add support for responsive embedded content
    add_theme_support( 'responsive-embeds' );

    // Add support for custom line height in blocks
    add_theme_support( 'custom-line-height' );

    // Add support for custom spacing in blocks
    add_theme_support( 'custom-spacing' );

    // Add support for custom units
    add_theme_support( 'custom-units', 'px', 'rem', 'em', '%' );
}
add_action( 'after_setup_theme', 'plainmark_setup' );

/**
 * Set the content width in pixels
 */
function plainmark_content_width() {
    $GLOBALS['content_width'] = apply_filters( 'plainmark_content_width', 1200 );
}
add_action( 'after_setup_theme', 'plainmark_content_width', 0 );

/**
 * Register widget areas
 */
function plainmark_widgets_init() {
    register_sidebar( array(
        'name'          => esc_html__( 'Sidebar', 'plainmark' ),
        'id'            => 'sidebar-1',
        'description'   => esc_html__( 'Add widgets here to appear in your sidebar.', 'plainmark' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );

    register_sidebar( array(
        'name'          => esc_html__( 'Footer', 'plainmark' ),
        'id'            => 'footer-1',
        'description'   => esc_html__( 'Add widgets here to appear in your footer.', 'plainmark' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'plainmark_widgets_init' );
