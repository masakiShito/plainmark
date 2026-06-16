<?php
/**
 * Admin settings for Snippet Library.
 *
 * @package plainmark
 * @since 0.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register snippet settings meta boxes.
 */
function plainmark_register_snippet_settings_meta_boxes() {
	add_meta_box(
		'plainmark_snippet_details',
		__( 'スニペット設定', 'plainmark' ),
		'plainmark_render_snippet_details_meta_box',
		'plainmark_snippet',
		'side',
		'default'
	);

	add_meta_box(
		'plainmark_article_snippets',
		__( '参照スニペット', 'plainmark' ),
		'plainmark_render_article_snippets_meta_box',
		'post',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'plainmark_register_snippet_settings_meta_boxes' );

/**
 * Render snippet metadata fields.
 *
 * @param WP_Post $post Post object.
 */
function plainmark_render_snippet_details_meta_box( $post ) {
	wp_nonce_field( 'plainmark_save_snippet_settings', 'plainmark_snippet_settings_nonce' );

	$language = get_post_meta( $post->ID, '_plainmark_snippet_language', true );
	$version  = get_post_meta( $post->ID, '_plainmark_snippet_version', true );
	$env      = get_post_meta( $post->ID, '_plainmark_snippet_env', true );
	?>
	<p>
		<label for="plainmark_snippet_language"><strong><?php esc_html_e( '言語', 'plainmark' ); ?></strong></label>
		<input type="text" id="plainmark_snippet_language" name="plainmark_snippet_language" value="<?php echo esc_attr( $language ); ?>" class="widefat" placeholder="javascript">
	</p>
	<p>
		<label for="plainmark_snippet_version"><strong><?php esc_html_e( '検証バージョン', 'plainmark' ); ?></strong></label>
		<input type="text" id="plainmark_snippet_version" name="plainmark_snippet_version" value="<?php echo esc_attr( $version ); ?>" class="widefat" placeholder="React 18.2.0">
	</p>
	<p>
		<label for="plainmark_snippet_env"><strong><?php esc_html_e( '検証環境', 'plainmark' ); ?></strong></label>
		<textarea id="plainmark_snippet_env" name="plainmark_snippet_env" class="widefat" rows="3" placeholder="Node.js 24 / Chrome 126"><?php echo esc_textarea( $env ); ?></textarea>
	</p>
	<?php
}

/**
 * Render article snippet reference field.
 *
 * @param WP_Post $post Post object.
 */
function plainmark_render_article_snippets_meta_box( $post ) {
	wp_nonce_field( 'plainmark_save_article_snippet_refs', 'plainmark_article_snippets_nonce' );

	$ids = get_post_meta( $post->ID, '_plainmark_snippet_ids', true );
	$ids = is_array( $ids ) ? $ids : array();
	?>
	<p>
		<label for="plainmark_snippet_ids"><strong><?php esc_html_e( 'スニペットID', 'plainmark' ); ?></strong></label>
		<input type="text" id="plainmark_snippet_ids" name="plainmark_snippet_ids" value="<?php echo esc_attr( implode( ', ', array_map( 'absint', $ids ) ) ); ?>" class="widefat" placeholder="42, 57">
	</p>
	<p class="description">
		<?php esc_html_e( '[snippet id="42"] で本文から参照できます。複数ある場合はカンマ区切りで管理用に記録します。', 'plainmark' ); ?>
	</p>
	<?php
}

/**
 * Save snippet metadata.
 *
 * @param int $post_id Post ID.
 */
function plainmark_save_snippet_settings( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( 'plainmark_snippet' === get_post_type( $post_id ) ) {
		if ( ! isset( $_POST['plainmark_snippet_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['plainmark_snippet_settings_nonce'] ) ), 'plainmark_save_snippet_settings' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_plainmark_snippet_language', isset( $_POST['plainmark_snippet_language'] ) ? sanitize_key( wp_unslash( $_POST['plainmark_snippet_language'] ) ) : '' );
		update_post_meta( $post_id, '_plainmark_snippet_version', isset( $_POST['plainmark_snippet_version'] ) ? sanitize_text_field( wp_unslash( $_POST['plainmark_snippet_version'] ) ) : '' );
		update_post_meta( $post_id, '_plainmark_snippet_env', isset( $_POST['plainmark_snippet_env'] ) ? sanitize_textarea_field( wp_unslash( $_POST['plainmark_snippet_env'] ) ) : '' );
	}

	if ( 'post' === get_post_type( $post_id ) ) {
		if ( ! isset( $_POST['plainmark_article_snippets_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['plainmark_article_snippets_nonce'] ) ), 'plainmark_save_article_snippet_refs' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$ids = isset( $_POST['plainmark_snippet_ids'] ) && function_exists( 'plainmark_sanitize_id_array' )
			? plainmark_sanitize_id_array( wp_unslash( $_POST['plainmark_snippet_ids'] ) )
			: array();

		update_post_meta( $post_id, '_plainmark_snippet_ids', $ids );
	}
}
add_action( 'save_post', 'plainmark_save_snippet_settings', 15 );
