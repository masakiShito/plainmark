<?php
/**
 * Environment block server-side render.
 *
 * @package plainmark
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$title = ! empty( $attributes['title'] )
	? sanitize_text_field( $attributes['title'] )
	: __( 'Environment', 'plainmark' );

$items = isset( $attributes['items'] ) && is_array( $attributes['items'] )
	? $attributes['items']
	: array();

// Sanitize items.
$sanitized_items = array();
foreach ( $items as $item ) {
	if ( ! empty( $item['label'] ) && ! empty( $item['value'] ) ) {
		$sanitized_items[] = array(
			'label' => sanitize_text_field( $item['label'] ),
			'value' => sanitize_text_field( $item['value'] ),
		);
	}
}

// Use shared rendering function from shortcodes.php.
echo plainmark_render_environment( $title, $sanitized_items );
