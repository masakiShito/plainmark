<?php
/**
 * Learning Paths page template.
 *
 * @package plainmark
 * @since 0.7.0
 */

defined( 'ABSPATH' ) || exit;

$paths             = plainmark_generate_learning_paths();
$difficulty_labels = plainmark_get_difficulty_options();

get_header();
?>
<main id="main" class="learning-paths-page">
	<section class="feature-page-hero">
		<div class="container container--wide">
			<p class="feature-page-eyebrow">LEARNING PATHS</p>
			<h1><?php esc_html_e( 'どこから読めばいい？', 'plainmark' ); ?></h1>
			<p><?php esc_html_e( '技術タグ・難易度・シリーズ情報から、おすすめの読む順番を自動生成しています。', 'plainmark' ); ?></p>
		</div>
	</section>

	<section class="learning-paths-section">
		<div class="container container--wide">
			<?php if ( $paths ) : ?>
				<div class="learning-paths-list">
					<?php foreach ( $paths as $path ) : ?>
						<article class="learning-path" id="path-<?php echo esc_attr( $path['term']->slug ); ?>">
							<div class="learning-path__header">
								<h2>
									<a href="<?php echo esc_url( get_term_link( $path['term'] ) ); ?>">
										<?php echo esc_html( $path['term']->name ); ?>
									</a>
								</h2>
								<span class="learning-path__count">
									<?php printf( esc_html__( '%d 記事', 'plainmark' ), esc_html( (string) $path['count'] ) ); ?>
								</span>
							</div>

							<?php if ( ! empty( $path['series'] ) ) : ?>
								<div class="learning-path__series-list">
									<?php foreach ( $path['series'] as $series_name => $series_posts ) : ?>
										<span class="learning-path__series-chip">
											<?php echo esc_html( $series_name ); ?>
											<small>(<?php echo esc_html( (string) count( $series_posts ) ); ?>)</small>
										</span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<ol class="learning-path__steps">
								<?php foreach ( $path['posts'] as $index => $entry ) : ?>
									<?php
									$post       = $entry['post'];
									$difficulty = $entry['difficulty'];
									$label      = $difficulty_labels[ $difficulty ] ?? '';
									$is_stale   = 'stale' === $entry['freshness']['rank'];
									?>
									<li class="learning-path__step<?php echo $is_stale ? ' is-stale' : ''; ?>">
										<span class="learning-path__step-number"><?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
										<div class="learning-path__step-body">
											<a class="learning-path__step-link" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
												<?php echo esc_html( get_the_title( $post ) ); ?>
											</a>
											<div class="learning-path__step-meta">
												<?php if ( $label ) : ?>
													<span class="learning-path__difficulty learning-path__difficulty--<?php echo esc_attr( $difficulty ); ?>">
														<?php echo esc_html( $label ); ?>
													</span>
												<?php endif; ?>
												<?php if ( $entry['series_name'] ) : ?>
													<span class="learning-path__series-tag">
														<?php echo esc_html( $entry['series_name'] ); ?>
														Part <?php echo esc_html( (string) $entry['series_order'] ); ?>
													</span>
												<?php endif; ?>
												<?php if ( $is_stale ) : ?>
													<span class="learning-path__stale-badge">
														<?php esc_html_e( '要更新', 'plainmark' ); ?>
													</span>
												<?php endif; ?>
											</div>
										</div>
									</li>
								<?php endforeach; ?>
							</ol>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="feature-empty">
					<?php esc_html_e( '技術タグと難易度を記事に設定すると、学習パスが自動生成されます。', 'plainmark' ); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer();
