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
 * Validate the GitHub sync secret from request parameters.
 *
 * @param WP_REST_Request $request REST request.
 * @return bool|WP_Error
 */
function plainmark_authorize_github_sync_form( WP_REST_Request $request ) {
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

	return true;
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
			'permission_callback' => 'plainmark_authorize_github_sync_form',
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
	$content_base64 = preg_replace( '/\s+/', '', (string) $request->get_param( 'content_base64' ) );
	$markdown       = base64_decode( $content_base64, true );
	$path           = sanitize_text_field( (string) $request->get_param( 'path' ) );
	$sha            = sanitize_text_field( (string) $request->get_param( 'sha' ) );

	if ( false === $markdown ) {
		return new WP_Error(
			'plainmark_invalid_base64',
			__( 'Invalid Base64 content.', 'plainmark' ),
			array( 'status' => 400 )
		);
	}

	$result = plainmark_sync_markdown( $markdown, $path, $sha );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return rest_ensure_response(
		array(
			'success' => true,
			'post_id' => $result['id'],
			'action'  => $result['action'],
			'path'    => $result['path'],
		)
	);
}
