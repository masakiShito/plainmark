<?php
/**
 * Dependency Watcher — checks npm / PyPI versions and updates Freshness.
 *
 * @package plainmark
 * @since 0.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse structured dependency lines from _plainmark_dependencies meta.
 *
 * @param int $post_id Post ID.
 * @return array<int,array{ecosystem:string,package:string,used:string}> Parsed dependencies.
 */
function plainmark_parse_dependencies( $post_id ) {
	$raw  = trim( (string) get_post_meta( $post_id, '_plainmark_dependencies', true ) );
	$deps = array();

	if ( '' === $raw ) {
		return $deps;
	}

	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line || '#' === substr( $line, 0, 1 ) ) {
			continue;
		}

		$parts = explode( ':', $line, 3 );
		if ( 3 !== count( $parts ) ) {
			continue;
		}

		$ecosystem = strtolower( trim( $parts[0] ) );
		if ( ! in_array( $ecosystem, array( 'npm', 'pypi' ), true ) ) {
			continue;
		}

		$package = trim( $parts[1] );
		$used    = trim( $parts[2] );
		if ( '' === $package || '' === $used ) {
			continue;
		}

		$deps[] = array(
			'ecosystem' => $ecosystem,
			'package'   => $package,
			'used'      => $used,
		);
	}

	return $deps;
}

/**
 * Fetch the latest version of a package from npm registry.
 *
 * @param string $package Package name.
 * @return string|null Latest version or null on failure.
 */
function plainmark_fetch_npm_latest( $package ) {
	$transient_key = 'plainmark_npm_' . md5( $package );
	$cached        = get_transient( $transient_key );

	if ( false !== $cached ) {
		return $cached ?: null;
	}

	$response = wp_remote_get(
		'https://registry.npmjs.org/' . rawurlencode( $package ) . '/latest',
		array(
			'timeout'    => 5,
			'user-agent' => 'plainmark-dependency-watcher/1.0',
		)
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		set_transient( $transient_key, '', HOUR_IN_SECONDS );
		return null;
	}

	$body    = json_decode( wp_remote_retrieve_body( $response ), true );
	$version = is_array( $body ) ? ( $body['version'] ?? null ) : null;

	set_transient( $transient_key, $version ?? '', 12 * HOUR_IN_SECONDS );
	return $version;
}

/**
 * Fetch the latest version of a package from PyPI.
 *
 * @param string $package Package name.
 * @return string|null Latest version or null on failure.
 */
function plainmark_fetch_pypi_latest( $package ) {
	$transient_key = 'plainmark_pypi_' . md5( $package );
	$cached        = get_transient( $transient_key );

	if ( false !== $cached ) {
		return $cached ?: null;
	}

	$response = wp_remote_get(
		'https://pypi.org/pypi/' . rawurlencode( $package ) . '/json',
		array(
			'timeout'    => 5,
			'user-agent' => 'plainmark-dependency-watcher/1.0',
		)
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		set_transient( $transient_key, '', HOUR_IN_SECONDS );
		return null;
	}

	$body    = json_decode( wp_remote_retrieve_body( $response ), true );
	$version = is_array( $body ) ? ( $body['info']['version'] ?? null ) : null;

	set_transient( $transient_key, $version ?? '', 12 * HOUR_IN_SECONDS );
	return $version;
}

/**
 * Get major version number from a version string.
 *
 * @param string $version Version string.
 * @return int
 */
function plainmark_dependency_major_version( $version ) {
	$version = ltrim( trim( $version ), 'v^~<>= ' );
	$parts   = explode( '.', $version );

	return isset( $parts[0] ) ? absint( $parts[0] ) : 0;
}

/**
 * Check dependencies for a post and return outdated ones.
 *
 * @param int $post_id Post ID.
 * @return array<int,array{ecosystem:string,package:string,used:string,latest:string,outdated:bool}>
 */
function plainmark_check_dependencies( $post_id ) {
	$deps    = plainmark_parse_dependencies( $post_id );
	$results = array();

	foreach ( $deps as $dep ) {
		$latest = null;

		if ( 'npm' === $dep['ecosystem'] ) {
			$latest = plainmark_fetch_npm_latest( $dep['package'] );
		} elseif ( 'pypi' === $dep['ecosystem'] ) {
			$latest = plainmark_fetch_pypi_latest( $dep['package'] );
		}

		$outdated = false;
		if ( $latest && $dep['used'] ) {
			$used_major   = plainmark_dependency_major_version( $dep['used'] );
			$latest_major = plainmark_dependency_major_version( $latest );
			$outdated     = $latest_major > $used_major;
		}

		$results[] = array_merge(
			$dep,
			array(
				'latest'   => $latest ?? '',
				'outdated' => $outdated,
			)
		);
	}

	return $results;
}

/**
 * Cache dependency check results to post meta.
 *
 * @param int $post_id Post ID.
 */
function plainmark_cache_dependency_check( $post_id ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( 'post' !== get_post_type( $post_id ) ) {
		return;
	}

	$results  = plainmark_check_dependencies( $post_id );
	$outdated = array_filter(
		$results,
		static function( $result ) {
			return ! empty( $result['outdated'] );
		}
	);

	update_post_meta( $post_id, '_plainmark_dep_outdated_count', count( $outdated ) );
	update_post_meta( $post_id, '_plainmark_dep_checked_at', current_time( 'Y-m-d' ) );
	update_post_meta( $post_id, '_plainmark_dep_results', wp_json_encode( $results ) );
}
add_action( 'save_post', 'plainmark_cache_dependency_check', 20 );

/**
 * Register dependency check meta keys.
 */
function plainmark_register_dependency_meta() {
	$keys = array(
		'_plainmark_dep_outdated_count' => 'integer',
		'_plainmark_dep_checked_at'     => 'string',
		'_plainmark_dep_results'        => 'string',
	);

	foreach ( $keys as $key => $type ) {
		register_post_meta(
			'post',
			$key,
			array(
				'type'              => $type,
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'integer' === $type ? 'absint' : 'sanitize_text_field',
				'auth_callback'     => static function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'plainmark_register_dependency_meta' );

/**
 * Add dependency status to the post list table.
 *
 * @param array $columns Columns array.
 * @return array Modified columns.
 */
function plainmark_add_dep_column( $columns ) {
	$columns['plainmark_deps'] = __( '依存', 'plainmark' );
	return $columns;
}
add_filter( 'manage_post_posts_columns', 'plainmark_add_dep_column' );

/**
 * Render dependency column cell.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function plainmark_render_dep_column( $column, $post_id ) {
	if ( 'plainmark_deps' !== $column ) {
		return;
	}

	$outdated   = (int) get_post_meta( $post_id, '_plainmark_dep_outdated_count', true );
	$checked_at = (string) get_post_meta( $post_id, '_plainmark_dep_checked_at', true );

	if ( '' === $checked_at ) {
		echo '<span style="color:var(--wp-components-color-gray-600,#757575);font-size:11px">'
			. esc_html__( '未確認', 'plainmark' )
			. '</span>';
		return;
	}

	if ( $outdated > 0 ) {
		printf(
			'<span style="color:#8b1a1a;font-weight:600;font-size:12px">%s</span>',
			esc_html(
				sprintf(
					/* translators: %d: number of outdated packages */
					_n( '%d 件古い', '%d 件古い', $outdated, 'plainmark' ),
					$outdated
				)
			)
		);
		return;
	}

	echo '<span style="color:#1a6b2a;font-size:12px">OK</span>';
}
add_action( 'manage_post_posts_custom_column', 'plainmark_render_dep_column', 10, 2 );
