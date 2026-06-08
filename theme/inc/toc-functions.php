<?php
/**
 * Table of contents and heading ID helpers.
 *
 * @package plainmark
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extract an existing ID attribute from heading attributes.
 *
 * @param string $attrs Heading attributes.
 * @return string Existing ID or empty string.
 */
function plainmark_extract_heading_id( $attrs ) {
	if ( preg_match( '/\sid\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attrs, $matches ) ) {
		$id = $matches[2] ?? $matches[3] ?? $matches[4] ?? '';
		return trim( html_entity_decode( $id, ENT_QUOTES, get_bloginfo( 'charset' ) ) );
	}

	return '';
}

/**
 * Generate a unique heading ID.
 *
 * @param string              $text    Heading text.
 * @param array<string, bool> $used    Already used IDs.
 * @param int                 $index   Heading index.
 * @return string Unique ID.
 */
function plainmark_generate_unique_heading_id( $text, &$used, $index ) {
	$base = sanitize_title( $text );

	if ( '' === $base ) {
		$base = 'section-' . max( 1, (int) $index );
	}

	$candidate = $base;
	$suffix    = 2;

	while ( isset( $used[ $candidate ] ) ) {
		$candidate = $base . '-' . $suffix;
		$suffix++;
	}

	$used[ $candidate ] = true;

	return $candidate;
}

/**
 * Build a heading index from HTML content.
 *
 * Each heading receives the same deterministic ID that will be used by the
 * content filter. Existing IDs are respected and registered as used so that
 * generated IDs do not collide with them.
 *
 * @param string $content HTML content.
 * @return array<int, array{level:int,text:string,id:string,has_id:bool}>
 */
function plainmark_build_heading_index( $content ) {
	static $cache = array();

	if ( ! is_string( $content ) || '' === $content ) {
		return array();
	}

	$cache_key = md5( $content );
	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$pattern = '/<h([23])([^>]*)>(.*?)<\/h\1>/is';
	if ( ! preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
		$cache[ $cache_key ] = array();
		return array();
	}

	$headings = array();
	$used     = array();
	$index    = 0;

	foreach ( $matches as $match ) {
		$index++;

		$level       = (int) $match[1];
		$attrs       = $match[2];
		$text        = trim( wp_strip_all_tags( $match[3] ) );
		$existing_id = plainmark_extract_heading_id( $attrs );
		$has_id      = '' !== $existing_id;
		$id          = $has_id ? $existing_id : plainmark_generate_unique_heading_id( $text, $used, $index );

		if ( $has_id ) {
			$used[ $id ] = true;
		}

		$headings[] = array(
			'level'  => $level,
			'text'   => $text,
			'id'     => $id,
			'has_id' => $has_id,
		);
	}

	$cache[ $cache_key ] = $headings;

	return $headings;
}

/**
 * Add IDs to h2/h3 headings in post content.
 *
 * @param string $content Post content.
 * @return string Content with heading IDs.
 */
function plainmark_add_heading_ids( $content ) {
	if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$headings = plainmark_build_heading_index( $content );

	if ( empty( $headings ) ) {
		return $content;
	}

	$position = 0;
	$pattern  = '/<h([23])([^>]*)>(.*?)<\/h\1>/is';

	return preg_replace_callback(
		$pattern,
		function ( $match ) use ( $headings, &$position ) {
			$heading = $headings[ $position ] ?? null;
			$position++;

			if ( ! $heading ) {
				return $match[0];
			}

			$level = $match[1];
			$attrs = $match[2];
			$inner = $match[3];

			if ( $heading['has_id'] ) {
				return $match[0];
			}

			return sprintf(
				'<h%s id="%s"%s>%s</h%s>',
				$level,
				esc_attr( $heading['id'] ),
				$attrs,
				$inner,
				$level
			);
		},
		$content
	);
}

/**
 * Generate table of contents from headings.
 *
 * @param string $content Post content.
 * @return string TOC HTML list or empty string if no headings.
 */
function plainmark_get_toc( $content ) {
	if ( ! $content ) {
		return '';
	}

	// Use the same filtered HTML source that will be rendered by the_content.
	// This keeps block-rendered or shortcode-generated headings aligned with the output.
	$processed_content = apply_filters( 'the_content', $content );
	$headings          = plainmark_build_heading_index( $processed_content );

	if ( empty( $headings ) ) {
		return '';
	}

	$toc    = '<ol class="article-toc__list">';
	$in_sub = false;

	foreach ( $headings as $heading ) {
		$level = (int) $heading['level'];
		$text  = $heading['text'];
		$id    = $heading['id'];

		if ( '' === $text || '' === $id ) {
			continue;
		}

		if ( 2 === $level ) {
			if ( $in_sub ) {
				$toc   .= '</ol></li>';
				$in_sub = false;
			}

			$toc .= sprintf(
				'<li class="article-toc__item article-toc__item--h2"><a class="article-toc__link" href="#%s">%s</a>',
				esc_attr( $id ),
				esc_html( $text )
			);
		} elseif ( 3 === $level ) {
			if ( ! $in_sub ) {
				$toc   .= '<ol class="article-toc__sublist">';
				$in_sub = true;
			}

			$toc .= sprintf(
				'<li class="article-toc__item article-toc__item--h3"><a class="article-toc__link" href="#%s">%s</a></li>',
				esc_attr( $id ),
				esc_html( $text )
			);
		}
	}

	if ( $in_sub ) {
		$toc .= '</ol></li>';
	}

	$toc .= '</ol>';

	return $toc;
}
