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

/**
 * Add meta box.
 */
function plainmark_add_work_meta_box() {
    add_meta_box(
        'plainmark_work_case_study',
        __( 'ケーススタディ設定', 'plainmark' ),
        'plainmark_render_work_meta_box',
        'portfolio',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'plainmark_add_work_meta_box' );

/**
 * Render meta box.
 *
 * @param WP_Post $post Post object.
 */
function plainmark_render_work_meta_box( $post ) {
    wp_nonce_field( 'plainmark_save_work_meta', 'plainmark_work_meta_nonce' );

    $fields = array(
        'work_summary'      => array( 'label' => '概要', 'type' => 'textarea', 'rows' => 3 ),
        'work_problem'      => array( 'label' => '課題', 'type' => 'textarea', 'rows' => 4 ),
        'work_solution'     => array( 'label' => '解決方法', 'type' => 'textarea', 'rows' => 4 ),
        'work_architecture' => array( 'label' => '設計・構成', 'type' => 'textarea', 'rows' => 4 ),
        'work_features'     => array( 'label' => '主な機能', 'type' => 'textarea', 'rows' => 4 ),
        'work_learnings'    => array( 'label' => '学び・工夫', 'type' => 'textarea', 'rows' => 4 ),
        'work_next_steps'   => array( 'label' => '今後の改善', 'type' => 'textarea', 'rows' => 4 ),
        'work_role'         => array( 'label' => '担当・役割', 'type' => 'text' ),
        'work_period'       => array( 'label' => '制作時期', 'type' => 'text' ),
        'work_github_url'   => array( 'label' => 'GitHub URL', 'type' => 'url' ),
        'work_demo_url'     => array( 'label' => 'Demo URL（任意）', 'type' => 'url' ),
    );
    ?>
    <style>
        .plainmark-work-fields {
            display: grid;
            gap: 18px;
        }
        .plainmark-work-field label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
        }
        .plainmark-work-field input,
        .plainmark-work-field textarea {
            width: 100%;
        }
        .plainmark-work-field p {
            margin: 6px 0 0;
            color: #666;
            font-size: 12px;
        }
    </style>
    <div class="plainmark-work-fields">
        <?php foreach ( $fields as $key => $config ) : ?>
            <?php $value = get_post_meta( $post->ID, $key, true ); ?>
            <div class="plainmark-work-field">
                <label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $config['label'] ); ?></label>
                <?php if ( 'textarea' === $config['type'] ) : ?>
                    <textarea id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" rows="<?php echo esc_attr( $config['rows'] ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
                <?php else : ?>
                    <input id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" type="<?php echo esc_attr( $config['type'] ); ?>" value="<?php echo esc_attr( $value ); ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Save meta fields.
 *
 * @param int $post_id Post ID.
 */
function plainmark_save_work_meta( $post_id ) {
    if ( ! isset( $_POST['plainmark_work_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['plainmark_work_meta_nonce'] ) ), 'plainmark_save_work_meta' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

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
        if ( ! isset( $_POST[ $field ] ) ) {
            delete_post_meta( $post_id, $field );
            continue;
        }

        $raw_value = wp_unslash( $_POST[ $field ] );
        $value     = str_contains( $field, '_url' ) ? esc_url_raw( $raw_value ) : sanitize_textarea_field( $raw_value );

        if ( '' === $value ) {
            delete_post_meta( $post_id, $field );
        } else {
            update_post_meta( $post_id, $field, $value );
        }
    }
}
add_action( 'save_post_portfolio', 'plainmark_save_work_meta' );
