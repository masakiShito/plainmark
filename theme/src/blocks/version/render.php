<?php
/**
 * Version badge block server-side render.
 *
 * @package plainmark
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

// Use shared rendering function from shortcodes.php.
echo plainmark_render_version(
	sanitize_text_field( $attributes['label'] ?? '' ),
	sanitize_text_field( $attributes['version'] ?? '' )
);
