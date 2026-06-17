<?php
/**
 * Work case study meta fields.
 *
 * @package plainmark
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register meta fields for portfolio posts.
 */
function plainmark_register_work_meta() {
    $fields = array(
        'work_summary',
        'work_problem',
        'work_solution',
        'work_architecture',
        'work_features',
        'work_learnings',
        'work_next_steps',
        'work_role',
        'work_period',
        'work_github_url',
        'work_demo_url',
    );

    foreach ( $fields as $field ) {
        register_post_meta(
            'portfolio',
            $field,
            array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => str_contains( $field, '_url' ) ? 'esc_url_raw' : 'sanitize_textarea_field',
                'auth_callback'     => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );
    }
}
add_action( 'init', 'plainmark_register_work_meta' );
