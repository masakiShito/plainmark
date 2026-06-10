<?php
/**
 * GitHub content synchronization through WordPress front controller.
 *
 * This endpoint accepts form-encoded Base64 content so shared hosting WAF
 * rules are less likely to reject the request.
 *
 * @package plainmark
 * @since 0.4.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the hosting-compatible REST route.
 */
function plainmark_register_github_sync_form_route() {
	register_rest_route(
		'plainmark/v1',
		'/github-sync-form',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'plainmark_handle_github_sync_form',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'plainmark_register_github_sync_form_route' );

/**
 * Handle a Base64 form-encoded Markdown synchronization request.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response|WP_Error
 */
function plainmark_handle_github_sync_form( WP_REST_Request $request ) {
	$configured_secret = function_exists( 'plainmark_get_github_sync_secret' )
		? plainmark_get_github_sync_secret()
		: (string) get_option( 'plainmark_github_sync_secret', '' );
	$provided_secret = sanitize_text_field( (string) $request->get_param( 'secret' ) );

	if ( '' === $configured_secret || '' === $provided_secret || ! hash_equals( $configured_secret, $provided_secret ) ) {
		return new WP_Error(
			'plainmark_invalid_sync_secret',
			__( 'Invalid synchronization secret.', 'plainmark' ),
			array( 'status' => 403 )
		);
	}

	$content_base64 = preg_replace( '/\s+/', '', (string) $request->get_param( 'content_base64' ) );
	$markdown       = base64_decode( $content_base64, true );
	$path           = sanitize_text_field( (string) $request->get_param( 'path' ) );
	$sha            = sanitize_text_field( (string) $request->get_param( 'sha' ) );

	if ( false === $markdown || '' === trim( $markdown ) ) {
		return new WP_Error(
			'plainmark_empty_sync_content',
			__( 'Markdown content is required.', 'plainmark' ),
			array( 'status' => 400 )
		);
	}

	if ( ! function_exists( 'plainmark_parse_md_content' ) || ! function_exists( 'plainmark_import_single_md' ) ) {
		return new WP_Error(
			'plainmark_importer_unavailable',
			__( 'Markdown importer is unavailable.', 'plainmark' ),
			array( 'status' => 500 )
		);
	}

	$parsed = plainmark_parse_md_content( $markdown );
	$result = plainmark_import_single_md( $markdown, true );

	if ( ! $result || empty( $result['id'] ) ) {
		return new WP_Error(
			'plainmark_sync_import_failed',
			__( 'Markdown import failed.', 'plainmark' ),
			array( 'status' => 422 )
		);
	}

	$post_id   = absint( $result['id'] );
	$post_type = get_post_type( $post_id );

	if ( $parsed && ! empty( $parsed['front_matter'] ) && function_exists( 'plainmark_apply_content_bridge_front_matter' ) ) {
		plainmark_apply_content_bridge_front_matter( $post_id, $parsed['front_matter'], $post_type );
	}

	update_post_meta( $post_id, '_plainmark_github_path', $path );
	update_post_meta( $post_id, '_plainmark_github_sha', $sha );
	update_post_meta( $post_id, '_plainmark_github_synced_at', current_time( 'mysql' ) );

	return rest_ensure_response(
		array(
			'success' => true,
			'post_id' => $post_id,
			'action'  => sanitize_key( $result['action'] ?? 'updated' ),
			'path'    => $path,
		)
	);
}
