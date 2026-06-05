<?php
/**
 * Theme Customizer settings
 *
 * @package plainmark
 * @since 0.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add postMessage support for site title and description
 */
function plainmark_customize_register( $wp_customize ) {
    // Site identity
    $wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
    $wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
    $wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

    // Theme Options Panel
    $wp_customize->add_panel( 'plainmark_options', array(
        'title'       => esc_html__( 'Theme Options', 'plainmark' ),
        'priority'    => 30,
        'description' => esc_html__( 'Configure plainmark theme options.', 'plainmark' ),
    ) );

    // Header Section
    $wp_customize->add_section( 'plainmark_header', array(
        'title'    => esc_html__( 'Header', 'plainmark' ),
        'panel'    => 'plainmark_options',
        'priority' => 10,
    ) );

    // Sticky Header
    $wp_customize->add_setting( 'plainmark_sticky_header', array(
        'default'           => false,
        'sanitize_callback' => 'plainmark_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'plainmark_sticky_header', array(
        'label'   => esc_html__( 'Enable Sticky Header', 'plainmark' ),
        'section' => 'plainmark_header',
        'type'    => 'checkbox',
    ) );

    // Footer Section
    $wp_customize->add_section( 'plainmark_footer', array(
        'title'    => esc_html__( 'Footer', 'plainmark' ),
        'panel'    => 'plainmark_options',
        'priority' => 20,
    ) );

    // Footer Copyright Text
    $wp_customize->add_setting( 'plainmark_footer_copyright', array(
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
    ) );

    $wp_customize->add_control( 'plainmark_footer_copyright', array(
        'label'       => esc_html__( 'Copyright Text', 'plainmark' ),
        'description' => esc_html__( 'Custom copyright text for the footer.', 'plainmark' ),
        'section'     => 'plainmark_footer',
        'type'        => 'textarea',
    ) );

    // Blog Section
    $wp_customize->add_section( 'plainmark_blog', array(
        'title'    => esc_html__( 'Blog', 'plainmark' ),
        'panel'    => 'plainmark_options',
        'priority' => 30,
    ) );

    // Show Author
    $wp_customize->add_setting( 'plainmark_show_author', array(
        'default'           => true,
        'sanitize_callback' => 'plainmark_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'plainmark_show_author', array(
        'label'   => esc_html__( 'Show Author', 'plainmark' ),
        'section' => 'plainmark_blog',
        'type'    => 'checkbox',
    ) );

    // Show Date
    $wp_customize->add_setting( 'plainmark_show_date', array(
        'default'           => true,
        'sanitize_callback' => 'plainmark_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'plainmark_show_date', array(
        'label'   => esc_html__( 'Show Date', 'plainmark' ),
        'section' => 'plainmark_blog',
        'type'    => 'checkbox',
    ) );

    // Show Categories
    $wp_customize->add_setting( 'plainmark_show_categories', array(
        'default'           => true,
        'sanitize_callback' => 'plainmark_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'plainmark_show_categories', array(
        'label'   => esc_html__( 'Show Categories', 'plainmark' ),
        'section' => 'plainmark_blog',
        'type'    => 'checkbox',
    ) );

    // Show Tags
    $wp_customize->add_setting( 'plainmark_show_tags', array(
        'default'           => true,
        'sanitize_callback' => 'plainmark_sanitize_checkbox',
    ) );

    $wp_customize->add_control( 'plainmark_show_tags', array(
        'label'   => esc_html__( 'Show Tags', 'plainmark' ),
        'section' => 'plainmark_blog',
        'type'    => 'checkbox',
    ) );

    // Selective refresh for site title and description
    if ( isset( $wp_customize->selective_refresh ) ) {
        $wp_customize->selective_refresh->add_partial( 'blogname', array(
            'selector'        => '.site-title a',
            'render_callback' => 'plainmark_customize_partial_blogname',
        ) );

        $wp_customize->selective_refresh->add_partial( 'blogdescription', array(
            'selector'        => '.site-description',
            'render_callback' => 'plainmark_customize_partial_blogdescription',
        ) );
    }
}
add_action( 'customize_register', 'plainmark_customize_register' );

/**
 * Render the site title for the selective refresh partial
 */
function plainmark_customize_partial_blogname() {
    bloginfo( 'name' );
}

/**
 * Render the site tagline for the selective refresh partial
 */
function plainmark_customize_partial_blogdescription() {
    bloginfo( 'description' );
}

/**
 * Sanitize checkbox
 */
function plainmark_sanitize_checkbox( $checked ) {
    return ( ( isset( $checked ) && true === $checked ) ? true : false );
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously
 */
function plainmark_customize_preview_js() {
    wp_enqueue_script(
        'plainmark-customizer',
        PLAINMARK_URI . '/assets/js/customizer.js',
        array( 'customize-preview' ),
        PLAINMARK_VERSION,
        true
    );
}
add_action( 'customize_preview_init', 'plainmark_customize_preview_js' );
