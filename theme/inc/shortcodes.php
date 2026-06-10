<?php
/**
 * Custom shortcodes for plainmark theme
 *
 * @package plainmark
 * @since 0.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get alert type configuration.
 *
 * @return array Alert types with labels and icons.
 */
function plainmark_get_alert_types() {
	return array(
		'note'    => array(
			'label' => __( 'Note', 'plainmark' ),
			'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
		),
		'info'    => array(
			'label' => __( 'Info', 'plainmark' ),
			'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
		),
		'tip'     => array(
			'label' => __( 'Tip', 'plainmark' ),
			'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><circle cx="12" cy="12" r="10"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
		),
		'warning' => array(
			'label' => __( 'Warning', 'plainmark' ),
			'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
		),
		'danger'  => array(
			'label' => __( 'Danger', 'plainmark' ),
			'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
		),
	);
}

/**
 * Render alert block HTML.
 *
 * Shared by shortcode and Gutenberg block.
 *
 * @param string $type    Alert type (note|info|tip|warning|danger).
 * @param string $title   Alert title.
 * @param string $content Alert content HTML.
 * @return string Alert block HTML.
 */
function plainmark_render_alert( $type, $title, $content ) {
	$types = plainmark_get_alert_types();

	// Validate type.
	if ( ! isset( $types[ $type ] ) ) {
		$type = 'note';
	}

	// Use custom title or default.
	$title = $title ? $title : $types[ $type ]['label'];
	$icon  = $types[ $type ]['icon'];

	return sprintf(
		'<div class="alert-block alert-block--%s" role="alert">
			<div class="alert-block__header">
				<span class="alert-block__icon">%s</span>
				<span class="alert-block__title">%s</span>
			</div>
			<div class="alert-block__content">%s</div>
		</div>',
		esc_attr( $type ),
		$icon,
		esc_html( $title ),
		wp_kses_post( $content )
	);
}

/**
 * Alert block shortcode.
 *
 * Usage:
 * [alert type="note"]Content here[/alert]
 * [alert type="warning" title="Custom Title"]Content here[/alert]
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 * @return string Alert block HTML.
 */
function plainmark_alert_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'type'  => 'note',
			'title' => '',
		),
		$atts,
		'alert'
	);

	return plainmark_render_alert(
		sanitize_key( $atts['type'] ),
		sanitize_text_field( $atts['title'] ),
		do_shortcode( $content )
	);
}
add_shortcode( 'alert', 'plainmark_alert_shortcode' );

/**
 * Render environment block HTML.
 *
 * Shared by shortcode and Gutenberg block.
 *
 * @param string $title Environment block title.
 * @param array  $items Array of {label, value} items.
 * @return string Environment block HTML.
 */
function plainmark_render_environment( $title, $items ) {
	if ( empty( $items ) ) {
		return '';
	}

	$items_html = '';
	foreach ( $items as $item ) {
		$items_html .= sprintf(
			'<div class="environment-block__item">
				<span class="environment-block__label">%s</span>
				<span class="environment-block__value">%s</span>
			</div>',
			esc_html( $item['label'] ),
			esc_html( $item['value'] )
		);
	}

	return sprintf(
		'<div class="environment-block">
			<div class="environment-block__header">
				<svg class="environment-block__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
					<line x1="8" y1="21" x2="16" y2="21"/>
					<line x1="12" y1="17" x2="12" y2="21"/>
				</svg>
				<span class="environment-block__title">%s</span>
			</div>
			<div class="environment-block__body">%s</div>
		</div>',
		esc_html( $title ),
		$items_html
	);
}

/**
 * Environment info shortcode.
 *
 * Usage:
 * [environment]
 * OS: macOS 14.0
 * Node.js: 20.0.0
 * [/environment]
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 * @return string Environment block HTML.
 */
function plainmark_environment_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'title' => __( 'Environment', 'plainmark' ),
		),
		$atts,
		'environment'
	);

	$lines = array_filter( array_map( 'trim', explode( "\n", trim( $content ) ) ) );
	$items = array();

	foreach ( $lines as $line ) {
		$parts = explode( ':', $line, 2 );
		if ( count( $parts ) === 2 ) {
			$items[] = array(
				'label' => trim( $parts[0] ),
				'value' => trim( $parts[1] ),
			);
		}
	}

	return plainmark_render_environment(
		sanitize_text_field( $atts['title'] ),
		$items
	);
}
add_shortcode( 'environment', 'plainmark_environment_shortcode' );

/**
 * Render version badge HTML.
 *
 * Shared by shortcode and Gutenberg block.
 *
 * @param string $label   Version label (optional).
 * @param string $version Version number.
 * @return string Version badge HTML.
 */
function plainmark_render_version( $label, $version ) {
	if ( empty( $version ) ) {
		return '';
	}

	$output = '<span class="version-badge">';
	if ( $label ) {
		$output .= '<span class="version-badge__label">' . esc_html( $label ) . '</span>';
	}
	$output .= '<span class="version-badge__value">' . esc_html( $version ) . '</span>';
	$output .= '</span>';

	return $output;
}

/**
 * Version badge shortcode.
 *
 * Usage:
 * [version]1.0.0[/version]
 * [version label="WordPress"]6.4[/version]
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Shortcode content.
 * @return string Version badge HTML.
 */
function plainmark_version_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'label' => '',
		),
		$atts,
		'version'
	);

	return plainmark_render_version(
		sanitize_text_field( $atts['label'] ),
		sanitize_text_field( $content )
	);
}
add_shortcode( 'version', 'plainmark_version_shortcode' );
