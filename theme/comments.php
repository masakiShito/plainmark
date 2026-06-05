<?php
/**
 * The comments template
 *
 * @package plainmark
 * @since 0.1.0
 */

// Prevent direct access
if ( post_password_required() ) {
    return;
}
?>

<div id="comments" class="comments-area">
    <?php if ( have_comments() ) : ?>
        <h2 class="comments-title">
            <?php
            $plainmark_comment_count = get_comments_number();
            if ( '1' === $plainmark_comment_count ) {
                printf(
                    esc_html__( 'One comment on &ldquo;%1$s&rdquo;', 'plainmark' ),
                    '<span>' . wp_kses_post( get_the_title() ) . '</span>'
                );
            } else {
                printf(
                    esc_html( _nx( '%1$s comment on &ldquo;%2$s&rdquo;', '%1$s comments on &ldquo;%2$s&rdquo;', $plainmark_comment_count, 'comments title', 'plainmark' ) ),
                    number_format_i18n( $plainmark_comment_count ),
                    '<span>' . wp_kses_post( get_the_title() ) . '</span>'
                );
            }
            ?>
        </h2>

        <?php the_comments_navigation(); ?>

        <ol class="comment-list">
            <?php
            wp_list_comments( array(
                'style'      => 'ol',
                'short_ping' => true,
                'avatar_size'=> 60,
            ) );
            ?>
        </ol>

        <?php
        the_comments_navigation();

        if ( ! comments_open() ) :
            ?>
            <p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'plainmark' ); ?></p>
            <?php
        endif;

    endif;

    comment_form( array(
        'title_reply'        => esc_html__( 'Leave a Comment', 'plainmark' ),
        'title_reply_to'     => esc_html__( 'Leave a Reply to %s', 'plainmark' ),
        'cancel_reply_link'  => esc_html__( 'Cancel reply', 'plainmark' ),
        'label_submit'       => esc_html__( 'Post Comment', 'plainmark' ),
        'comment_notes_before' => '',
    ) );
    ?>
</div>
