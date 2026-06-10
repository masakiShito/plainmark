<?php
/**
 * Alert block server-side render.
 *
 * @package plainmark
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

// Use shared rendering function from shortcodes.php.
echo plainmark_render_alert(
	sanitize_key( $attributes['type'] ?? 'note' ),
	sanitize_text_field( $attributes['title'] ?? '' ),
	wp_kses_post( $attributes['content'] ?? '' )
);
