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

    // ─────────────────────────────────────────────────────────────
    // About Page Settings
    // ─────────────────────────────────────────────────────────────

    // About Hero Section
    $wp_customize->add_section( 'plainmark_about_hero', array(
        'title'    => esc_html__( 'About: Hero', 'plainmark' ),
        'panel'    => 'plainmark_options',
        'priority' => 40,
    ) );

    $wp_customize->add_setting( 'plainmark_about_hero_title', array(
        'default'           => '業務を、使いやすく。',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'plainmark_about_hero_title', array(
        'label'   => esc_html__( 'Hero Title', 'plainmark' ),
        'section' => 'plainmark_about_hero',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'plainmark_about_hero_lead', array(
        'default'           => 'まーさんです。業務システム、EC、予約システムなどの開発で、要件整理から設計、実装、テストまで一貫して携わってきました。複雑な仕様を整理し、利用者にも運用者にも扱いやすいWebシステムに落とし込むことを大切にしています。',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'plainmark_about_hero_lead', array(
        'label'   => esc_html__( 'Hero Lead Text', 'plainmark' ),
        'section' => 'plainmark_about_hero',
        'type'    => 'textarea',
    ) );

    // About Profile Card Section
    $wp_customize->add_section( 'plainmark_about_profile', array(
        'title'    => esc_html__( 'About: Profile Card', 'plainmark' ),
        'panel'    => 'plainmark_options',
        'priority' => 41,
    ) );

    $wp_customize->add_setting( 'plainmark_about_profile_mark', array(
        'default'           => 'M',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'plainmark_about_profile_mark', array(
        'label'   => esc_html__( 'Profile Mark/Initial', 'plainmark' ),
        'section' => 'plainmark_about_profile',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'plainmark_about_profile_name', array(
        'default'           => 'まーさん',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'plainmark_about_profile_name', array(
        'label'   => esc_html__( 'Name', 'plainmark' ),
        'section' => 'plainmark_about_profile',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'plainmark_about_profile_role', array(
        'default'           => 'Web Engineer / Frontend & Backend',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'plainmark_about_profile_role', array(
        'label'   => esc_html__( 'Role/Title', 'plainmark' ),
        'section' => 'plainmark_about_profile',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'plainmark_about_profile_focus', array(
        'default'           => '業務理解と設計',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'plainmark_about_profile_focus', array(
        'label'   => esc_html__( 'Focus', 'plainmark' ),
        'section' => 'plainmark_about_profile',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'plainmark_about_profile_frontend', array(
        'default'           => 'React / Vue / Next.js',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'plainmark_about_profile_frontend', array(
        'label'   => esc_html__( 'Frontend Skills', 'plainmark' ),
        'section' => 'plainmark_about_profile',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'plainmark_about_profile_backend', array(
        'default'           => 'FastAPI / Java',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'plainmark_about_profile_backend', array(
        'label'   => esc_html__( 'Backend Skills', 'plainmark' ),
        'section' => 'plainmark_about_profile',
        'type'    => 'text',
    ) );

    // About Philosophy Section
    $wp_customize->add_section( 'plainmark_about_philosophy', array(
        'title'    => esc_html__( 'About: Philosophy', 'plainmark' ),
        'panel'    => 'plainmark_options',
        'priority' => 42,
    ) );

    $wp_customize->add_setting( 'plainmark_about_philosophy_title', array(
        'default'           => '伝わる形にする。',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'plainmark_about_philosophy_title', array(
        'label'   => esc_html__( 'Philosophy Title', 'plainmark' ),
        'section' => 'plainmark_about_philosophy',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'plainmark_about_philosophy_text1', array(
        'default'           => '開発で大切にしているのは、仕様をそのまま実装することではなく、背景にある業務や課題を理解したうえで、保守しやすく、使いやすい形に整理することです。',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'plainmark_about_philosophy_text1', array(
        'label'   => esc_html__( 'Philosophy Text 1', 'plainmark' ),
        'section' => 'plainmark_about_philosophy',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'plainmark_about_philosophy_text2', array(
        'default'           => '画面、API、DB、権限、運用フローはそれぞれ独立しているようで、実際には強くつながっています。だからこそ、フロントエンドとバックエンドを横断して全体像を見ながら設計することを意識しています。',
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'plainmark_about_philosophy_text2', array(
        'label'   => esc_html__( 'Philosophy Text 2', 'plainmark' ),
        'section' => 'plainmark_about_philosophy',
        'type'    => 'textarea',
    ) );

    // About Strengths Section
    $wp_customize->add_section( 'plainmark_about_strengths', array(
        'title'    => esc_html__( 'About: Strengths', 'plainmark' ),
        'panel'    => 'plainmark_options',
        'priority' => 43,
    ) );

    for ( $i = 1; $i <= 3; $i++ ) {
        $wp_customize->add_setting( "plainmark_about_strength_{$i}_title", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "plainmark_about_strength_{$i}_title", array(
            'label'   => sprintf( esc_html__( 'Strength %d: Title', 'plainmark' ), $i ),
            'section' => 'plainmark_about_strengths',
            'type'    => 'text',
        ) );

        $wp_customize->add_setting( "plainmark_about_strength_{$i}_text", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_textarea_field',
        ) );
        $wp_customize->add_control( "plainmark_about_strength_{$i}_text", array(
            'label'   => sprintf( esc_html__( 'Strength %d: Description', 'plainmark' ), $i ),
            'section' => 'plainmark_about_strengths',
            'type'    => 'textarea',
        ) );
    }

    // About Experience Section
    $wp_customize->add_section( 'plainmark_about_experience', array(
        'title'    => esc_html__( 'About: Experience', 'plainmark' ),
        'panel'    => 'plainmark_options',
        'priority' => 44,
    ) );

    for ( $i = 1; $i <= 4; $i++ ) {
        $wp_customize->add_setting( "plainmark_about_exp_{$i}_label", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "plainmark_about_exp_{$i}_label", array(
            'label'   => sprintf( esc_html__( 'Experience %d: Label', 'plainmark' ), $i ),
            'section' => 'plainmark_about_experience',
            'type'    => 'text',
        ) );

        $wp_customize->add_setting( "plainmark_about_exp_{$i}_title", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( "plainmark_about_exp_{$i}_title", array(
            'label'   => sprintf( esc_html__( 'Experience %d: Title', 'plainmark' ), $i ),
            'section' => 'plainmark_about_experience',
            'type'    => 'text',
        ) );

        $wp_customize->add_setting( "plainmark_about_exp_{$i}_text", array(
            'default'           => '',
            'sanitize_callback' => 'sanitize_textarea_field',
        ) );
        $wp_customize->add_control( "plainmark_about_exp_{$i}_text", array(
            'label'   => sprintf( esc_html__( 'Experience %d: Description', 'plainmark' ), $i ),
            'section' => 'plainmark_about_experience',
            'type'    => 'textarea',
        ) );
    }

    // About CTA Section
    $wp_customize->add_section( 'plainmark_about_cta', array(
        'title'    => esc_html__( 'About: CTA', 'plainmark' ),
        'panel'    => 'plainmark_options',
        'priority' => 45,
    ) );

    $wp_customize->add_setting( 'plainmark_about_cta_title', array(
        'default'           => '学びを残す。',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'plainmark_about_cta_title', array(
        'label'   => esc_html__( 'CTA Title', 'plainmark' ),
        'section' => 'plainmark_about_cta',
        'type'    => 'text',
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
