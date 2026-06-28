<?php
/**
 * Article choice meta validation.
 *
 * Keeps article_type and difficulty meta values aligned with their admin
 * allowlists regardless of whether they are saved from the admin screen,
 * Markdown Import, or GitHub synchronization.
 *
 * @package plainmark-core
 * @since 0.7.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate a choice against an allowlist while preserving the empty default.
 *
 * @param mixed $value   Raw value.
 * @param array $options Allowed options keyed by stored value.
 * @return string
 */
function plainmark_validate_choice_meta_value( $value, $options ) {
	$value = sanitize_text_field( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	return is_array( $options ) && array_key_exists( $value, $options ) ? $value : '';
}

/**
 * Validate imported/synced article choice meta after it is written.
 *
 * This also covers Markdown Import and GitHub Pull Sync because both paths call
 * update_post_meta() after parsing front matter.
 *
 * @param int    $meta_id    Metadata ID.
 * @param int    $post_id    Post ID.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value Raw meta value.
 */
function plainmark_validate_article_choice_meta_after_update( $meta_id, $post_id, $meta_key, $meta_value ) {
	unset( $meta_id );

	if ( '_plainmark_article_type' === $meta_key ) {
		if ( ! function_exists( 'plainmark_get_article_type_options' ) ) {
			return;
		}

		$validated = plainmark_validate_choice_meta_value( $meta_value, plainmark_get_article_type_options() );
	} elseif ( '_plainmark_difficulty' === $meta_key ) {
		if ( ! function_exists( 'plainmark_get_difficulty_options' ) ) {
			return;
		}

		$validated = plainmark_validate_choice_meta_value( $meta_value, plainmark_get_difficulty_options() );
	} else {
		return;
	}

	if ( (string) $meta_value !== $validated ) {
		update_post_meta( $post_id, $meta_key, $validated );
	}
}
add_action( 'added_post_meta', 'plainmark_validate_article_choice_meta_after_update', 10, 4 );
add_action( 'updated_post_meta', 'plainmark_validate_article_choice_meta_after_update', 10, 4 );
