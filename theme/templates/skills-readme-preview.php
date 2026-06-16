<?php
/**
 * Skills README preview template.
 *
 * @package plainmark
 * @since 0.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$markdown = function_exists( 'plainmark_generate_skills_readme' ) ? plainmark_generate_skills_readme() : '';
?>
<div class="skills-readme-preview">
	<pre><code><?php echo esc_html( $markdown ); ?></code></pre>
</div>
