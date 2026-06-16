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
 * Add a rendered Freshness badge to the single post title area.
 */
function plainmark_output_single_freshness_badge_template() {
	if ( ! is_singular( 'post' ) || ! function_exists( 'plainmark_render_freshness_badge' ) ) {
		return;
	}

	$badge = plainmark_render_freshness_badge( get_queried_object_id() );
	if ( '' === $badge ) {
		return;
	}
	?>
	<template id="plainmark-single-freshness-badge">
		<div class="single-post__freshness"><?php echo wp_kses_post( $badge ); ?></div>
	</template>
	<script>
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
	</script>
	<?php
}
add_action( 'wp_footer', 'plainmark_output_single_freshness_badge_template', 20 );
