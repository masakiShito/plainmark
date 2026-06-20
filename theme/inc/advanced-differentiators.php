<?php
/**
 * Advanced differentiators for technical publishing.
 *
 * Adds:
 * - Code Playground shortcode.
 * - Revision diff UI.
 * - Reader persona shortcode.
 * - Technology graph route.
 * - RSS metadata extensions.
 *
 * @package plainmark
 * @since 0.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue assets only on pages that need them.
 */
function plainmark_enqueue_advanced_differentiator_assets() {
	if ( ! is_singular( 'post' ) && ! get_query_var( 'plainmark_technology_map' ) ) {
		return;
	}

	$css = PLAINMARK_DIR . '/assets/css/advanced-differentiators.css';
	$js  = PLAINMARK_DIR . '/assets/js/advanced-differentiators.js';

	wp_enqueue_style(
		'plainmark-advanced-differentiators',
		PLAINMARK_URI . '/assets/css/advanced-differentiators.css',
		array( 'plainmark-style' ),
		file_exists( $css ) ? (string) filemtime( $css ) : PLAINMARK_VERSION
	);

	wp_enqueue_script(
		'plainmark-advanced-differentiators',
		PLAINMARK_URI . '/assets/js/advanced-differentiators.js',
		array(),
		file_exists( $js ) ? (string) filemtime( $js ) : PLAINMARK_VERSION,
		true
	);

	if ( is_singular( 'post' ) && is_user_logged_in() ) {
		wp_localize_script(
			'plainmark-advanced-differentiators',
			'plainmarkData',
			array(
				'postId'  => get_the_ID(),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'restUrl' => rest_url(),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'plainmark_enqueue_advanced_differentiator_assets', 30 );

/**
 * Code Playground shortcode.
 *
 * Usage:
 * [playground title="Counter demo" language="javascript"]console.log('Hi')[/playground]
 * [playground title="Array flat" language="javascript" verified="true" env="Node.js 24" result="[1,2,3,4,5,6]"]nested.flat()[/playground]
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Code.
 * @return string
 */
function plainmark_playground_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'title'    => __( 'Code Playground', 'plainmark' ),
			'language' => 'javascript',
			'height'   => '260',
			'verified' => '',
			'env'      => '',
			'result'   => '',
		),
		$atts,
		'playground'
	);

	$language = sanitize_key( $atts['language'] );
	$allowed  = array( 'javascript', 'js', 'html', 'css' );
	$language = in_array( $language, $allowed, true ) ? $language : 'javascript';
	$height   = max( 180, min( 720, absint( $atts['height'] ) ) );
	$code     = trim( html_entity_decode( shortcode_unautop( $content ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );

	$is_verified = 'true' === $atts['verified'];
	$env         = sanitize_text_field( $atts['env'] );
	$result      = sanitize_text_field( $atts['result'] );

	$badge_html = '';
	if ( $is_verified ) {
		if ( '' === $env && function_exists( 'plainmark_get_verification_data' ) ) {
			$data = plainmark_get_verification_data();
			$env  = $data['env'] ?? '';
		}

		$badge_html = '<div class="code-playground__verified">'
			. '<span class="code-playground__verified-badge">✓ ' . esc_html__( '検証済み', 'plainmark' ) . '</span>';
		if ( $env ) {
			$badge_html .= '<span class="code-playground__verified-env">' . esc_html( $env ) . '</span>';
		}
		$badge_html .= '</div>';
	}

	$result_html = '';
	if ( '' !== $result ) {
		$result_html = '<div class="code-playground__expected">'
			. '<span class="code-playground__expected-label">' . esc_html__( '期待される出力', 'plainmark' ) . '</span>'
			. '<code>' . esc_html( $result ) . '</code>'
			. '</div>';
	}

	$section_class = 'code-playground' . ( $is_verified ? ' code-playground--verified' : '' );
	$post_id_attr  = is_singular( 'post' ) && is_user_logged_in()
		? ' data-post-id="' . esc_attr( (string) get_the_ID() ) . '"'
		: '';

	$html  = '<section class="' . esc_attr( $section_class ) . '" data-code-playground data-language="' . esc_attr( $language ) . '"' . $post_id_attr . '>';
	$html .= $badge_html;
	$html .= '<div class="code-playground__header">';
	$html .= '<strong>' . esc_html( $atts['title'] ) . '</strong>';
	$html .= '<button type="button" data-playground-run>' . esc_html__( 'Run', 'plainmark' ) . '</button>';
	$html .= '</div>';
	$html .= '<textarea class="code-playground__editor" spellcheck="false">' . esc_textarea( $code ) . '</textarea>';
	$html .= '<div class="code-playground__result" style="min-height:' . esc_attr( (string) $height ) . 'px">';
	$html .= '<iframe sandbox="allow-scripts" title="' . esc_attr__( 'Code playground result', 'plainmark' ) . '"></iframe>';
	$html .= '<pre aria-live="polite"></pre>';
	$html .= '</div>';
	$html .= $result_html;
	$html .= '</section>';

	return $html;
}
add_shortcode( 'playground', 'plainmark_playground_shortcode' );

/**
 * Reader persona shortcode.
 *
 * Usage: [persona level="beginner" framework="react"]...[/persona]
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Inner content.
 * @return string
 */
function plainmark_persona_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'level'     => 'all',
			'framework' => 'all',
			'label'     => '',
		),
		$atts,
		'persona'
	);

	$level      = sanitize_key( $atts['level'] );
	$framework  = sanitize_key( $atts['framework'] );
	$label      = $atts['label'] ? sanitize_text_field( $atts['label'] ) : trim( $level . ' / ' . $framework, ' /' );
	$level      = '' !== $level ? $level : 'all';
	$framework  = '' !== $framework ? $framework : 'all';
	$label_text = '' !== $label ? $label : __( 'Personalized section', 'plainmark' );

	return sprintf(
		'<section class="reader-persona" data-reader-persona data-persona-level="%1$s" data-persona-framework="%2$s"><p class="reader-persona__label">%3$s</p><div>%4$s</div></section>',
		esc_attr( $level ),
		esc_attr( $framework ),
		esc_html( $label_text ),
		do_shortcode( shortcode_unautop( $content ) )
	);
}
add_shortcode( 'persona', 'plainmark_persona_shortcode' );

/**
 * Freshness score is intentionally not attached to the_content here.
 * It is embedded into the verification card by content-bridge.php.
 */

/**
 * Append latest revision diff UI.
 *
 * @param string $content Post content.
 * @return string
 */
function plainmark_append_revision_diff_ui( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$revisions = wp_get_post_revisions(
		get_the_ID(),
		array(
			'posts_per_page' => 2,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	if ( count( $revisions ) < 2 || ! function_exists( 'wp_text_diff' ) ) {
		return $content;
	}

	$items = array_values( $revisions );
	$diff  = wp_text_diff(
		wp_strip_all_tags( $items[1]->post_content ),
		wp_strip_all_tags( $items[0]->post_content ),
		array( 'title' => __( '前回更新との差分', 'plainmark' ) )
	);

	if ( ! $diff ) {
		return $content;
	}

	$html  = '<details class="article-diff"><summary>' . esc_html__( 'この記事の最新差分を見る', 'plainmark' ) . '</summary>';
	$html .= '<div class="article-diff__body">' . wp_kses_post( $diff ) . '</div></details>';

	return $content . $html;
}
add_filter( 'the_content', 'plainmark_append_revision_diff_ui', 31 );

/**
 * Register technology graph route.
 */
function plainmark_register_technology_graph_route() {
	add_rewrite_rule( '^technology-map/?$', 'index.php?plainmark_technology_map=1', 'top' );
}
add_action( 'init', 'plainmark_register_technology_graph_route' );

/**
 * Add technology graph query var.
 *
 * @param array $vars Query vars.
 * @return array
 */
function plainmark_add_technology_graph_query_var( $vars ) {
	$vars[] = 'plainmark_technology_map';
	return $vars;
}
add_filter( 'query_vars', 'plainmark_add_technology_graph_query_var' );

/**
 * Resolve technology graph template.
 *
 * @param string $template Current template path.
 * @return string
 */
function plainmark_technology_graph_template_include( $template ) {
	if ( get_query_var( 'plainmark_technology_map' ) ) {
		$custom = locate_template( 'page-technology-map.php' );
		return $custom ? $custom : $template;
	}
	return $template;
}
add_filter( 'template_include', 'plainmark_technology_graph_template_include' );

/**
 * Make technology graph route valid.
 *
 * @param WP_Query $query Main query.
 */
function plainmark_prepare_technology_graph_route( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->get( 'plainmark_technology_map' ) ) {
		$query->is_404  = false;
		$query->is_page = true;
	}
}
add_action( 'pre_get_posts', 'plainmark_prepare_technology_graph_route' );

/**
 * Add RSS namespace.
 */
function plainmark_add_rss_tech_namespace() {
	echo ' xmlns:plainmark="https://github.com/masakiShito/plainmark/wiki/rss-ns/1.0"';
}
add_action( 'rss2_ns', 'plainmark_add_rss_tech_namespace' );

/**
 * Add RSS metadata.
 */
function plainmark_add_rss_tech_metadata() {
	if ( 'post' !== get_post_type() ) {
		return;
	}

	$freshness    = function_exists( 'plainmark_get_freshness_score' ) ? plainmark_get_freshness_score( get_the_ID() ) : array( 'score' => 0 );
	$verification = function_exists( 'plainmark_get_verification_data' ) ? plainmark_get_verification_data( get_the_ID() ) : array( 'status' => 'unverified' );
	$terms        = wp_get_post_terms( get_the_ID(), 'technology' );

	echo '<plainmark:difficulty>' . esc_html( (string) get_post_meta( get_the_ID(), '_plainmark_difficulty', true ) ) . '</plainmark:difficulty>' . "\n";
	echo '<plainmark:verifiedStatus>' . esc_html( $verification['status'] ?? 'unverified' ) . '</plainmark:verifiedStatus>' . "\n";
	echo '<plainmark:freshnessScore>' . esc_html( (string) $freshness['score'] ) . '</plainmark:freshnessScore>' . "\n";
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			echo '<plainmark:technology>' . esc_html( $term->name ) . '</plainmark:technology>' . "\n";
		}
	}
}
add_action( 'rss2_item', 'plainmark_add_rss_tech_metadata' );

/**
 * Register execution snapshot meta.
 */
function plainmark_register_execution_snapshot_meta() {
	register_post_meta(
		'post',
		'_plainmark_execution_snapshots',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'plainmark_register_execution_snapshot_meta' );

/**
 * REST endpoint: save execution snapshot.
 */
function plainmark_register_execution_snapshot_rest() {
	register_rest_route(
		'plainmark/v1',
		'/execution-snapshot',
		array(
			'methods'             => 'POST',
			'callback'            => 'plainmark_save_execution_snapshot',
			'permission_callback' => static function () {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'post_id'          => array(
					'type'     => 'integer',
					'required' => true,
					'minimum'  => 1,
				),
				'playground_title' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'code'             => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'output'           => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'language'         => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'plainmark_register_execution_snapshot_rest' );

/**
 * Save an execution snapshot for a post.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response|WP_Error
 */
function plainmark_save_execution_snapshot( WP_REST_Request $request ) {
	$post_id = (int) $request->get_param( 'post_id' );
	$post    = get_post( $post_id );

	if ( ! $post || 'post' !== $post->post_type ) {
		return new WP_Error( 'invalid_post', __( '投稿が見つかりません。', 'plainmark' ), array( 'status' => 404 ) );
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return new WP_Error( 'forbidden', __( 'この投稿を編集する権限がありません。', 'plainmark' ), array( 'status' => 403 ) );
	}

	$snapshots_json = get_post_meta( $post_id, '_plainmark_execution_snapshots', true );
	$snapshots      = $snapshots_json ? json_decode( $snapshots_json, true ) : array();
	if ( ! is_array( $snapshots ) ) {
		$snapshots = array();
	}

	$title    = $request->get_param( 'playground_title' );
	$title    = $title ? $title : 'untitled';
	$key      = md5( $title );
	$language = $request->get_param( 'language' );
	$language = $language ? $language : 'javascript';

	$snapshots[ $key ] = array(
		'title'    => $title,
		'code'     => $request->get_param( 'code' ),
		'output'   => $request->get_param( 'output' ),
		'language' => $language,
		'saved_at' => current_time( 'Y-m-d H:i:s' ),
	);

	if ( count( $snapshots ) > 10 ) {
		$snapshots = array_slice( $snapshots, -10, null, true );
	}

	update_post_meta( $post_id, '_plainmark_execution_snapshots', wp_json_encode( $snapshots ) );

	return new WP_REST_Response(
		array(
			'success'  => true,
			'snapshot' => $snapshots[ $key ],
			'total'    => count( $snapshots ),
		),
		200
	);
}
