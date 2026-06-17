<?php
/**
 * Snippet Library — reusable code snippets across articles.
 *
 * @package plainmark
 * @since 0.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'plainmark_sanitize_id_array' ) ) {
	/**
	 * Sanitize an array of positive IDs.
	 *
	 * @param mixed $value Raw value.
	 * @return int[]
	 */
	function plainmark_sanitize_id_array( $value ) {
		if ( is_string( $value ) ) {
			$value = preg_split( '/[\s,]+/', $value );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$ids = array_map( 'absint', $value );
		$ids = array_filter( $ids );

		return array_values( array_unique( $ids ) );
	}
}

/**
 * Register the plainmark_snippet custom post type.
 */
function plainmark_register_snippet_post_type() {
	register_post_type(
		'plainmark_snippet',
		array(
			'labels'          => array(
				'name'          => __( 'スニペット', 'plainmark' ),
				'singular_name' => __( 'スニペット', 'plainmark' ),
				'add_new_item'  => __( '新しいスニペットを追加', 'plainmark' ),
				'edit_item'     => __( 'スニペットを編集', 'plainmark' ),
				'search_items'  => __( 'スニペットを検索', 'plainmark' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'tools.php',
			'show_in_rest'    => true,
			'supports'        => array( 'title', 'editor', 'custom-fields' ),
			'capability_type' => 'post',
		)
	);
}
add_action( 'init', 'plainmark_register_snippet_post_type' );

/**
 * Register snippet metadata.
 */
function plainmark_register_snippet_meta() {
	$meta_keys = array(
		'_plainmark_snippet_language' => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'description'       => 'Programming language of the snippet.',
		),
		'_plainmark_snippet_version'  => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'description'       => 'Version this snippet was verified against.',
		),
		'_plainmark_snippet_env'      => array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'description'       => 'Verified environment string.',
		),
	);

	foreach ( $meta_keys as $key => $args ) {
		register_post_meta(
			'plainmark_snippet',
			$key,
			array_merge(
				$args,
				array(
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => static function() {
						return current_user_can( 'edit_posts' );
					},
				)
			)
		);
	}

	register_post_meta(
		'post',
		'_plainmark_snippet_ids',
		array(
			'type'              => 'array',
			'single'            => true,
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
			),
			'sanitize_callback' => 'plainmark_sanitize_id_array',
			'auth_callback'     => static function() {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'plainmark_register_snippet_meta' );

/**
 * Render a snippet block HTML.
 *
 * @param int    $snippet_id Post ID of the snippet.
 * @param string $title      Override title.
 * @param bool   $show_meta  Whether to show language/version badges.
 * @return string HTML string.
 */
function plainmark_render_snippet_block( $snippet_id, $title = '', $show_meta = true ) {
	$snippet = get_post( $snippet_id );

	if ( ! $snippet || 'plainmark_snippet' !== $snippet->post_type || 'publish' !== $snippet->post_status ) {
		return '<div class="snippet-block snippet-block--not-found"><p>'
			. esc_html__( 'スニペットが見つかりません。', 'plainmark' )
			. '</p></div>';
	}

	$display_title = $title ?: $snippet->post_title;
	$language      = sanitize_key( get_post_meta( $snippet_id, '_plainmark_snippet_language', true ) );
	$version       = sanitize_text_field( get_post_meta( $snippet_id, '_plainmark_snippet_version', true ) );
	$env           = sanitize_textarea_field( get_post_meta( $snippet_id, '_plainmark_snippet_env', true ) );
	$code          = wp_strip_all_tags( $snippet->post_content );
	$edit_url      = current_user_can( 'edit_posts' ) ? get_edit_post_link( $snippet_id ) : '';

	$meta_html = '';
	if ( $show_meta ) {
		if ( $language ) {
			$meta_html .= '<span class="snippet-block__badge snippet-block__badge--lang">'
				. esc_html( strtoupper( $language ) ) . '</span>';
		}
		if ( $version ) {
			$meta_html .= '<span class="snippet-block__badge snippet-block__badge--version">'
				. esc_html( $version ) . '</span>';
		}
	}

	$edit_html = '';
	if ( $edit_url ) {
		$edit_html = '<a href="' . esc_url( $edit_url ) . '" class="snippet-block__edit-link" target="_blank" rel="noopener">'
			. esc_html__( '編集', 'plainmark' ) . '</a>';
	}

	$html  = '<div class="snippet-block" data-snippet-id="' . esc_attr( (string) $snippet_id ) . '">';
	$html .= '<div class="snippet-block__header">';
	$html .= '<span class="snippet-block__title">' . esc_html( $display_title ) . '</span>';
	$html .= '<div class="snippet-block__badges">' . $meta_html . $edit_html . '</div>';
	$html .= '</div>';
	$html .= '<pre class="snippet-block__code"><code class="language-' . esc_attr( $language ?: 'plaintext' ) . '">'
		. esc_html( $code ) . '</code></pre>';

	if ( $env ) {
		$html .= '<div class="snippet-block__env">'
			. esc_html__( '検証環境: ', 'plainmark' )
			. esc_html( $env )
			. '</div>';
	}

	$html .= '</div>';

	return $html;
}

/**
 * [snippet] shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML.
 */
function plainmark_snippet_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id'        => '0',
			'title'     => '',
			'show_meta' => 'true',
		),
		$atts,
		'snippet'
	);

	$snippet_id = absint( $atts['id'] );
	if ( ! $snippet_id ) {
		return '';
	}

	return plainmark_render_snippet_block(
		$snippet_id,
		sanitize_text_field( $atts['title'] ),
		'false' !== strtolower( (string) $atts['show_meta'] )
	);
}
add_shortcode( 'snippet', 'plainmark_snippet_shortcode' );

/**
 * Register REST endpoint for snippet search.
 */
function plainmark_register_snippet_rest_route() {
	register_rest_route(
		'plainmark/v1',
		'/snippets',
		array(
			'methods'             => 'GET',
			'callback'            => 'plainmark_rest_list_snippets',
			'permission_callback' => static function() {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'search' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'plainmark_register_snippet_rest_route' );

/**
 * REST callback: list snippets for autocomplete.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function plainmark_rest_list_snippets( WP_REST_Request $request ) {
	$search = sanitize_text_field( (string) $request->get_param( 'search' ) );

	$posts = get_posts(
		array(
			'post_type'      => 'plainmark_snippet',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'no_found_rows'  => true,
			's'              => $search,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$items = array_map(
		static function( $post ) {
			return array(
				'id'       => $post->ID,
				'title'    => $post->post_title,
				'language' => get_post_meta( $post->ID, '_plainmark_snippet_language', true ),
			);
		},
		$posts
	);

	return new WP_REST_Response( $items, 200 );
}
