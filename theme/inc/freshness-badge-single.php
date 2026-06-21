<?php
/**
 * Inject Freshness badge into the single post header.
 *
 * @package plainmark
 * @since 0.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the small DOM placement script through WordPress' script API.
 */
function plainmark_enqueue_single_freshness_badge_script() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$script = <<<'JS'
(function() {
	var template = document.getElementById('plainmark-single-freshness-badge');
	if (!template) {
		return;
	}

	var header = document.querySelector('.single-post__header');
	var title = header ? header.querySelector('.single-post__title') : null;
	if (!header || !title || header.querySelector('.single-post__freshness')) {
		return;
	}

	title.insertAdjacentElement('afterend', template.content.firstElementChild.cloneNode(true));
})();
JS;

	wp_add_inline_script( 'plainmark-article-enhancements', $script );
}
add_action( 'wp_enqueue_scripts', 'plainmark_enqueue_single_freshness_badge_script', 30 );

/**
 * Add the rendered Freshness and CI badge template to the footer.
 */
function plainmark_output_single_freshness_badge_template() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$badge    = function_exists( 'plainmark_render_freshness_badge' ) ? plainmark_render_freshness_badge( get_queried_object_id() ) : '';
	$ci_badge = function_exists( 'plainmark_render_ci_badge' ) ? plainmark_render_ci_badge( get_queried_object_id() ) : '';

	if ( '' === $badge && '' === $ci_badge ) {
		return;
	}
	?>
	<template id="plainmark-single-freshness-badge">
		<div class="single-post__freshness"><?php echo wp_kses_post( $badge . $ci_badge ); ?></div>
	</template>
	<?php
}
add_action( 'wp_footer', 'plainmark_output_single_freshness_badge_template', 20 );
