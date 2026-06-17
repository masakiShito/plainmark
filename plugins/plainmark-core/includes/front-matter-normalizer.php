<?php
/**
 * Front matter normalization helpers for Obsidian and Notion exports.
 *
 * @package plainmark
 * @since 0.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert a value to a trimmed list.
 *
 * @param mixed $value Raw value.
 * @return string[]
 */
function plainmark_front_matter_list( $value ) {
	if ( is_array( $value ) ) {
		$items = $value;
	} else {
		$items = preg_split( '/\s*,\s*/', (string) $value );
	}

	return array_values(
		array_filter(
			array_map(
				static function ( $item ) {
					$item = trim( (string) $item );
					$item = trim( $item, " \t\n\r\0\x0B\"'[]" );
					return ltrim( $item, '#' );
				},
				$items
			)
		)
	);
}

/**
 * Normalize front matter keys from Obsidian, Notion, and plainmark.
 *
 * @param array $front_matter Front matter.
 * @return array
 */
function plainmark_normalize_front_matter( array $front_matter ) {
	$normalized = array();

	$key_map = array(
		'created'          => 'date',
		'created time'     => 'date',
		'last edited time' => 'modified',
		'technology'       => 'technologies',
		'tech'             => 'technologies',
		'tech stack'       => 'technologies',
		'status'           => 'verified_status',
		'verification'     => 'verified_status',
		'aliases'          => 'slug',
	);

	foreach ( $front_matter as $key => $value ) {
		$canonical_key = strtolower( str_replace( array( '-', '_' ), ' ', trim( (string) $key ) ) );
		$target_key    = $key_map[ $canonical_key ] ?? sanitize_key( str_replace( ' ', '_', $canonical_key ) );

		if ( in_array( $target_key, array( 'categories', 'tags', 'technologies', 'related_works', 'related_posts' ), true ) ) {
			$normalized[ $target_key ] = plainmark_front_matter_list( $value );
			continue;
		}

		if ( 'slug' === $target_key && is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( in_array( $target_key, array( 'date', 'modified', 'verified_date', 'review_date' ), true ) ) {
			$value = substr( (string) $value, 0, 10 );
		}

		if ( 'verified_status' === $target_key ) {
			$status_map = array(
				'done'        => 'verified',
				'complete'    => 'verified',
				'completed'   => 'verified',
				'verified'    => 'verified',
				'unverified'  => 'unverified',
				'todo'        => 'unverified',
				'draft'       => 'unverified',
				'deprecated'  => 'deprecated',
				'outdated'    => 'deprecated',
			);
			$value = $status_map[ strtolower( (string) $value ) ] ?? sanitize_key( (string) $value );
		}

		if ( 'difficulty' === $target_key ) {
			$difficulty_map = array(
				'easy'         => 'beginner',
				'basic'        => 'beginner',
				'beginner'     => 'beginner',
				'medium'       => 'intermediate',
				'intermediate' => 'intermediate',
				'hard'         => 'advanced',
				'advanced'     => 'advanced',
			);
			$value = $difficulty_map[ strtolower( (string) $value ) ] ?? sanitize_key( (string) $value );
		}

		$normalized[ $target_key ] = $value;
	}

	return $normalized;
}

/**
 * Parse a wider set of YAML-ish front matter formats.
 *
 * @param string $markdown Raw Markdown.
 * @return array
 */
function plainmark_extract_normalized_front_matter( $markdown ) {
	if ( ! preg_match( '/^---\R([\s\S]*?)\R---\R?/', (string) $markdown, $matches ) ) {
		return array();
	}

	$front_matter = array();
	$current_key  = '';

	foreach ( preg_split( '/\R/', $matches[1] ) as $line ) {
		if ( '' === trim( $line ) ) {
			continue;
		}

		if ( preg_match( '/^\s+-\s+(.*)$/', $line, $item_match ) && $current_key ) {
			if ( ! isset( $front_matter[ $current_key ] ) || ! is_array( $front_matter[ $current_key ] ) ) {
				$front_matter[ $current_key ] = array();
			}
			$front_matter[ $current_key ][] = trim( $item_match[1], " \t\n\r\0\x0B\"'" );
			continue;
		}

		if ( preg_match( '/^([^:]+):\s*(.*)$/', $line, $key_match ) ) {
			$current_key = trim( $key_match[1] );
			$value       = trim( $key_match[2] );

			if ( '' === $value ) {
				$front_matter[ $current_key ] = array();
				continue;
			}

			if ( '[' === substr( $value, 0, 1 ) && ']' === substr( $value, -1 ) ) {
				$front_matter[ $current_key ] = plainmark_front_matter_list( $value );
				continue;
			}

			$front_matter[ $current_key ] = trim( $value, " \t\n\r\0\x0B\"'" );
		}
	}

	return plainmark_normalize_front_matter( $front_matter );
}
