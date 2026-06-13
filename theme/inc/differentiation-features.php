<?php
/**
 * Differentiation features for technical publishing.
 *
 * Includes:
 * - Per-article changelog.
 * - Reader context switching shortcodes.
 * - Multi-file code tabs shortcodes.
 * - Knowledge map and skill sheet routes.
 *
 * @package plainmark
 * @since 0.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register article feature metadata.
 */
function plainmark_register_differentiation_meta() {
	register_post_meta(
		'post',
		'_plainmark_changelog',
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
add_action( 'init', 'plainmark_register_differentiation_meta' );

/**
 * Parse changelog lines.
 *
 * Input format: YYYY-MM-DD | description
 *
 * @param int $post_id Post ID.
 * @return array<int,array{date:string,text:string}>
 */
function plainmark_get_changelog_entries( $post_id = 0 ) {
	$post_id = $post_id ? absint( $post_id ) : get_the_ID();
	$raw     = (string) get_post_meta( $post_id, '_plainmark_changelog', true );

	if ( '' === trim( $raw ) ) {
		return array();
	}

	$entries = array();
	$lines   = preg_split( '/\r\n|\r|\n/', $raw );

	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}

		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		$date  = $parts[0] ?? '';
		$text  = $parts[1] ?? '';

		if ( '' === $text ) {
			$text = $date;
			$date = '';
		}

		$entries[] = array(
			'date' => $date,
			'text' => $text,
		);
	}

	return $entries;
}

/**
 * Prepend changelog UI to single post content.
 *
 * @param string $content Post content.
 * @return string
 */
function plainmark_prepend_changelog( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$entries = plainmark_get_changelog_entries();
	if ( empty( $entries ) ) {
		return $content;
	}

	$html  = '<details class="article-changelog">';
	$html .= '<summary><span class="article-changelog__badge">' . esc_html__( '更新あり', 'plainmark' ) . '</span>';
	$html .= '<span>' . esc_html__( '変更履歴を見る', 'plainmark' ) . '</span></summary>';
	$html .= '<ol class="article-changelog__list">';

	foreach ( $entries as $entry ) {
		$html .= '<li class="article-changelog__item">';
		if ( $entry['date'] ) {
			$html .= '<time datetime="' . esc_attr( $entry['date'] ) . '">' . esc_html( $entry['date'] ) . '</time>';
		}
		$html .= '<span>' . esc_html( $entry['text'] ) . '</span></li>';
	}

	$html .= '</ol></details>';

	return $html . $content;
}
add_filter( 'the_content', 'plainmark_prepend_changelog', 12 );

/**
 * Reader context shortcode (legacy alias for [persona]).
 *
 * Usage: [context level="beginner"]...[/context]
 * Internally delegates to [persona].
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Inner content.
 * @return string
 */
function plainmark_context_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'level' => 'beginner',
			'label' => '',
		),
		$atts,
		'context'
	);

	return plainmark_persona_shortcode(
		array(
			'level'     => $atts['level'],
			'framework' => 'all',
			'label'     => $atts['label'],
		),
		$content
	);
}
add_shortcode( 'context', 'plainmark_context_shortcode' );

/**
 * Manage the code tabs buffer using a static variable.
 *
 * @param string     $action 'get', 'add', or 'reset'.
 * @param array|null $tab    Tab data for 'add' action.
 * @return array Current buffer (for 'get') or empty array.
 */
function plainmark_code_tabs_buffer( $action = 'get', $tab = null ) {
	static $buffer = array();

	switch ( $action ) {
		case 'reset':
			$buffer = array();
			return $buffer;
		case 'add':
			if ( is_array( $tab ) ) {
				$buffer[] = $tab;
			}
			return $buffer;
		case 'get':
		default:
			return $buffer;
	}
}

/**
 * Individual code tab shortcode.
 *
 * @param array  $atts    Attributes.
 * @param string $content Code content.
 * @return string Empty placeholder.
 */
function plainmark_code_tab_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'file'     => __( 'file', 'plainmark' ),
			'language' => '',
		),
		$atts,
		'code_tab'
	);

	plainmark_code_tabs_buffer(
		'add',
		array(
			'file'     => sanitize_text_field( $atts['file'] ),
			'language' => sanitize_key( $atts['language'] ),
			'content'  => trim( html_entity_decode( $content, ENT_QUOTES, get_bloginfo( 'charset' ) ) ),
		)
	);

	return '';
}
add_shortcode( 'code_tab', 'plainmark_code_tab_shortcode' );

/**
 * Multi-file code tabs shortcode.
 *
 * @param array  $atts    Attributes.
 * @param string $content Inner shortcodes.
 * @return string
 */
function plainmark_code_tabs_shortcode( $atts, $content = '' ) {
	plainmark_code_tabs_buffer( 'reset' );
	do_shortcode( shortcode_unautop( $content ) );
	$tabs = plainmark_code_tabs_buffer( 'get' );
	plainmark_code_tabs_buffer( 'reset' );

	if ( empty( $tabs ) ) {
		return '';
	}

	$group_id = wp_unique_id( 'code-tabs-' );
	$html     = '<div class="code-tabs" data-code-tabs id="' . esc_attr( $group_id ) . '">';
	$html    .= '<div class="code-tabs__tablist" role="tablist">';

	foreach ( $tabs as $index => $tab ) {
		$tab_id   = $group_id . '-tab-' . $index;
		$panel_id = $group_id . '-panel-' . $index;
		$html    .= sprintf(
			'<button class="code-tabs__tab%1$s" type="button" role="tab" id="%2$s" aria-controls="%3$s" aria-selected="%4$s" data-code-tab="%5$d">%6$s</button>',
			0 === $index ? ' is-active' : '',
			esc_attr( $tab_id ),
			esc_attr( $panel_id ),
			0 === $index ? 'true' : 'false',
			$index,
			esc_html( $tab['file'] )
		);
	}

	$html .= '</div><div class="code-tabs__panels">';

	foreach ( $tabs as $index => $tab ) {
		$tab_id   = $group_id . '-tab-' . $index;
		$panel_id = $group_id . '-panel-' . $index;
		$class    = $tab['language'] ? 'language-' . $tab['language'] : '';
		$html    .= sprintf(
			'<div class="code-tabs__panel%1$s" role="tabpanel" id="%2$s" aria-labelledby="%3$s" data-code-panel="%4$d"%5$s><pre><code class="%6$s">%7$s</code></pre></div>',
			0 === $index ? ' is-active' : '',
			esc_attr( $panel_id ),
			esc_attr( $tab_id ),
			$index,
			0 === $index ? '' : ' hidden',
			esc_attr( $class ),
			esc_html( $tab['content'] )
		);
	}

	$html .= '</div></div>';

	return $html;
}
add_shortcode( 'code_tabs', 'plainmark_code_tabs_shortcode' );

/**
 * Register routes for knowledge map and skill sheet.
 */
function plainmark_register_differentiation_routes() {
	add_rewrite_rule( '^knowledge-map/?$', 'index.php?plainmark_knowledge_map=1', 'top' );
	add_rewrite_rule( '^skills/?$', 'index.php?plainmark_skill_sheet=1', 'top' );
}
add_action( 'init', 'plainmark_register_differentiation_routes' );

/**
 * Add route query vars.
 *
 * @param array $vars Query vars.
 * @return array
 */
function plainmark_add_differentiation_query_vars( $vars ) {
	$vars[] = 'plainmark_knowledge_map';
	$vars[] = 'plainmark_skill_sheet';
	return $vars;
}
add_filter( 'query_vars', 'plainmark_add_differentiation_query_vars' );

/**
 * Mark custom routes as valid pages.
 *
 * @param WP_Query $query Main query.
 */
function plainmark_prepare_differentiation_routes( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->get( 'plainmark_knowledge_map' ) || $query->get( 'plainmark_skill_sheet' ) ) {
		$query->is_404     = false;
		$query->is_page    = true;
		$query->is_home    = false;
		$query->is_archive = false;
	}
}
add_action( 'pre_get_posts', 'plainmark_prepare_differentiation_routes' );

/**
 * Resolve feature page templates.
 *
 * @param string $template Current template.
 * @return string
 */
function plainmark_differentiation_template_include( $template ) {
	if ( get_query_var( 'plainmark_knowledge_map' ) ) {
		$custom = locate_template( 'page-knowledge-map.php' );
		return $custom ?: $template;
	}

	if ( get_query_var( 'plainmark_skill_sheet' ) ) {
		$custom = locate_template( 'page-skills.php' );
		return $custom ?: $template;
	}

	return $template;
}
add_filter( 'template_include', 'plainmark_differentiation_template_include' );

/**
 * Flush rewrite rules once for feature routes.
 */
function plainmark_maybe_flush_differentiation_routes() {
	$version = '20260610_differentiation_routes_v2';
	if ( get_option( 'plainmark_differentiation_routes_version' ) !== $version ) {
		flush_rewrite_rules();
		update_option( 'plainmark_differentiation_routes_version', $version );
	}
}
add_action( 'init', 'plainmark_maybe_flush_differentiation_routes', 30 );
