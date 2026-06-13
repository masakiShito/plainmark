<?php
/**
 * Advanced differentiators for technical publishing.
 *
 * Adds:
 * - Code Playground shortcode.
 * - Freshness score utilities.
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

/** Register advanced metadata. */
function plainmark_register_advanced_differentiator_meta() {
	register_post_meta(
		'post',
		'_plainmark_dependencies',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => static function() {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'plainmark_register_advanced_differentiator_meta' );

/** Enqueue assets only on pages that need them. */
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

	$html  = '<section class="' . esc_attr( $section_class ) . '" data-code-playground data-language="' . esc_attr( $language ) . '">';
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

	$level     = sanitize_key( $atts['level'] );
	$framework = sanitize_key( $atts['framework'] );
	$label     = $atts['label'] ? sanitize_text_field( $atts['label'] ) : trim( $level . ' / ' . $framework, ' /' );

	return sprintf(
		'<section class="reader-persona" data-reader-persona data-persona-level="%1$s" data-persona-framework="%2$s"><p class="reader-persona__label">%3$s</p><div>%4$s</div></section>',
		esc_attr( $level ?: 'all' ),
		esc_attr( $framework ?: 'all' ),
		esc_html( $label ?: __( 'Personalized section', 'plainmark' ) ),
		do_shortcode( shortcode_unautop( $content ) )
	);
}
add_shortcode( 'persona', 'plainmark_persona_shortcode' );

/** Calculate article freshness score. */
function plainmark_get_freshness_score( $post_id = 0 ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();
	$data    = function_exists( 'plainmark_get_verification_data' ) ? plainmark_get_verification_data( $post_id ) : array();
	$score   = 100;
	$reasons = array();
	$status  = $data['status'] ?? 'unverified';
	$now     = current_datetime()->getTimestamp();

	if ( 'verified' !== $status ) {
		$score    -= 'deprecated' === $status ? 55 : 25;
		$reasons[] = 'deprecated' === $status ? __( '非推奨の記事です。', 'plainmark' ) : __( '動作確認が未完了です。', 'plainmark' );
	}

	$verified_date = ! empty( $data['date'] ) ? strtotime( $data['date'] ) : false;
	if ( $verified_date ) {
		$days = floor( ( $now - $verified_date ) / DAY_IN_SECONDS );
		if ( $days > 365 ) {
			$score    -= 35;
			$reasons[] = __( '最終確認から1年以上経過しています。', 'plainmark' );
		} elseif ( $days > 180 ) {
			$score    -= 18;
			$reasons[] = __( '最終確認から半年以上経過しています。', 'plainmark' );
		} elseif ( $days > 90 ) {
			$score    -= 8;
			$reasons[] = __( '最終確認から3か月以上経過しています。', 'plainmark' );
		}
	} else {
		$score    -= 15;
		$reasons[] = __( '最終確認日が未設定です。', 'plainmark' );
	}

	if ( ! empty( $data['review'] ) && strtotime( $data['review'] ) < $now ) {
		$score    -= 25;
		$reasons[] = __( 'レビュー期限を過ぎています。', 'plainmark' );
	}

	$dependencies = trim( (string) get_post_meta( $post_id, '_plainmark_dependencies', true ) );
	if ( '' === $dependencies ) {
		$score    -= 5;
		$reasons[] = __( '依存ライブラリ情報が未設定です。', 'plainmark' );
	}

	$score = max( 0, min( 100, $score ) );
	$rank  = $score >= 80 ? 'fresh' : ( $score >= 55 ? 'watch' : 'stale' );

	return array(
		'score'   => $score,
		'rank'    => $rank,
		'reasons' => $reasons,
	);
}

/**
 * Freshness score is intentionally not attached to the_content here.
 * It is embedded into the verification card by content-bridge.php.
 */

/** Append latest revision diff UI. */
function plainmark_append_revision_diff_ui( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$revisions = wp_get_post_revisions( get_the_ID(), array( 'posts_per_page' => 2, 'orderby' => 'date', 'order' => 'DESC' ) );
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

/** Register technology graph route. */
function plainmark_register_technology_graph_route() {
	add_rewrite_rule( '^technology-map/?$', 'index.php?plainmark_technology_map=1', 'top' );
}
add_action( 'init', 'plainmark_register_technology_graph_route' );

/** Add technology graph query var. */
function plainmark_add_technology_graph_query_var( $vars ) {
	$vars[] = 'plainmark_technology_map';
	return $vars;
}
add_filter( 'query_vars', 'plainmark_add_technology_graph_query_var' );

/** Resolve technology graph template. */
function plainmark_technology_graph_template_include( $template ) {
	if ( get_query_var( 'plainmark_technology_map' ) ) {
		$custom = locate_template( 'page-technology-map.php' );
		return $custom ?: $template;
	}
	return $template;
}
add_filter( 'template_include', 'plainmark_technology_graph_template_include' );

/** Make technology graph route valid. */
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

/** Add RSS namespace. */
function plainmark_add_rss_tech_namespace() {
	echo ' xmlns:plainmark="https://github.com/masakiShito/plainmark/wiki/rss-ns/1.0"';
}
add_action( 'rss2_ns', 'plainmark_add_rss_tech_namespace' );

/** Add RSS metadata. */
function plainmark_add_rss_tech_metadata() {
	if ( 'post' !== get_post_type() ) {
		return;
	}
	$freshness    = plainmark_get_freshness_score( get_the_ID() );
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
