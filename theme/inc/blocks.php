<?php
/**
 * Gutenberg blocks registration.
 *
 * @package plainmark
 * @since 0.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register custom Gutenberg blocks.
 */
function plainmark_register_blocks() {
	$blocks_dir = get_template_directory() . '/build/blocks';

	// Check if blocks are built.
	if ( ! is_dir( $blocks_dir ) ) {
		return;
	}

	// Register each block.
	$blocks = array(
		'alert',
		'environment',
		'version',
	);

	foreach ( $blocks as $block ) {
		$block_path = $blocks_dir . '/' . $block;
		if ( is_dir( $block_path ) ) {
			register_block_type( $block_path );
		}
	}
}
add_action( 'init', 'plainmark_register_blocks' );

/**
 * Register block category for plainmark blocks.
 *
 * @param array                   $categories Block categories.
 * @param WP_Block_Editor_Context $context    Block editor context.
 * @return array Modified block categories.
 */
function plainmark_register_block_category( $categories, $context ) {
	return array_merge(
		array(
			array(
				'slug'  => 'plainmark',
				'title' => __( 'Plainmark', 'plainmark' ),
				'icon'  => null,
			),
		),
		$categories
	);
}
add_filter( 'block_categories_all', 'plainmark_register_block_category', 10, 2 );
