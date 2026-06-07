<?php
/**
 * Template part for displaying single posts
 *
 * @package plainmark
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

$categories   = get_the_category();
$tags         = get_the_tags();
$technologies = get_the_terms( get_the_ID(), 'technology' );
$article_meta = function_exists( 'plainmark_get_article_meta' ) ? plainmark_get_article_meta() : array();

// Get values from article meta.
$article_type       = $article_meta['article_type'] ?? '';
$article_type_label = $article_meta['article_type_label'] ?? '';
$difficulty         = $article_meta['difficulty'] ?? '';
$difficulty_label   = $article_meta['difficulty_label'] ?? '';
$target_reader      = $article_meta['target_reader'] ?? '';
$prerequisites      = $article_meta['prerequisites'] ?? '';
$github_url         = $article_meta['github_url'] ?? '';
$official_docs_url  = $article_meta['official_docs_url'] ?? '';
$show_toc           = $article_meta['show_toc'] ?? true;

// Check if we have any article info to display.
$has_article_info = $article_type_label || $difficulty_label || $target_reader || $prerequisites || $github_url || $official_docs_url;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-post' ); ?>>
	<header class="single-post__header">
		<div class="single-post__meta">
			<time class="single-post__date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
				<?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?>
			</time>

			<?php if ( $categories ) : ?>
				<?php foreach ( $categories as $category ) : ?>
					<?php
					$category_url = get_category_link( $category->term_id );
					if ( is_wp_error( $category_url ) ) {
						continue;
					}
					?>
					<a class="single-post__cat" href="<?php echo esc_url( $category_url ); ?>">
						<?php echo esc_html( $category->name ); ?>
					</a>
				<?php endforeach; ?>
			<?php endif; ?>

			<span class="reading-time" aria-label="<?php esc_attr_e( '読了時間', 'plainmark' ); ?>">
				<svg class="reading-time__icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<circle cx="12" cy="12" r="10"/>
					<polyline points="12 6 12 12 16 14"/>
				</svg>
				<span class="reading-time__value">--</span>
				<span class="reading-time__unit"><?php esc_html_e( '分', 'plainmark' ); ?></span>
			</span>
		</div>

		<?php the_title( '<h1 class="single-post__title">', '</h1>' ); ?>
	</header>

	<?php if ( $has_article_info ) : ?>
		<div class="single-post__info">
			<?php if ( $article_type_label || $difficulty_label ) : ?>
				<div class="single-post__badges">
					<?php if ( $article_type_label ) : ?>
						<span class="single-post__badge single-post__badge--type single-post__badge--<?php echo esc_attr( $article_type ); ?>">
							<?php echo esc_html( $article_type_label ); ?>
						</span>
					<?php endif; ?>

					<?php if ( $difficulty_label ) : ?>
						<span class="single-post__badge single-post__badge--difficulty single-post__badge--<?php echo esc_attr( $difficulty ); ?>">
							<?php echo esc_html( $difficulty_label ); ?>
						</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( $target_reader || $prerequisites || $github_url || $official_docs_url ) : ?>
				<dl class="single-post__info-list">
					<?php if ( $target_reader ) : ?>
						<div class="single-post__info-item">
							<dt class="single-post__info-label"><?php esc_html_e( '対象読者', 'plainmark' ); ?></dt>
							<dd class="single-post__info-value"><?php echo esc_html( $target_reader ); ?></dd>
						</div>
					<?php endif; ?>

					<?php if ( $prerequisites ) : ?>
						<div class="single-post__info-item">
							<dt class="single-post__info-label"><?php esc_html_e( '前提知識', 'plainmark' ); ?></dt>
							<dd class="single-post__info-value"><?php echo nl2br( esc_html( $prerequisites ) ); ?></dd>
						</div>
					<?php endif; ?>

					<?php if ( $github_url ) : ?>
						<div class="single-post__info-item">
							<dt class="single-post__info-label">GitHub</dt>
							<dd class="single-post__info-value">
								<a class="single-post__info-link" href="<?php echo esc_url( $github_url ); ?>" target="_blank" rel="noopener noreferrer">
									<svg class="single-post__info-icon" width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
										<path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/>
									</svg>
									View Source
								</a>
							</dd>
						</div>
					<?php endif; ?>

					<?php if ( $official_docs_url ) : ?>
						<div class="single-post__info-item">
							<dt class="single-post__info-label">Docs</dt>
							<dd class="single-post__info-value">
								<a class="single-post__info-link" href="<?php echo esc_url( $official_docs_url ); ?>" target="_blank" rel="noopener noreferrer">
									<svg class="single-post__info-icon" width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
										<path d="M3 2.5h7.5a2 2 0 0 1 2 2v9a.5.5 0 0 1-.5.5H4a1 1 0 0 1-1-1V2.5z"/>
										<path d="M3 12h9"/>
										<path d="M5.5 5.5h5M5.5 8h3"/>
									</svg>
									Official Docs
								</a>
							</dd>
						</div>
					<?php endif; ?>
				</dl>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $technologies && ! is_wp_error( $technologies ) ) : ?>
		<div class="single-post__technologies" aria-label="<?php esc_attr_e( '技術スタック', 'plainmark' ); ?>">
			<?php foreach ( $technologies as $technology ) : ?>
				<?php
				$tech_url = get_term_link( $technology );
				if ( is_wp_error( $tech_url ) ) {
					continue;
				}
				?>
				<a class="single-post__technology" href="<?php echo esc_url( $tech_url ); ?>">
					<?php echo esc_html( $technology->name ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="single-post__thumbnail">
			<?php
			the_post_thumbnail(
				'plainmark-featured',
				array(
					'class' => 'single-post__thumbnail-img',
				)
			);
			?>
		</figure>
	<?php endif; ?>

	<?php
	if ( $show_toc && function_exists( 'plainmark_get_toc' ) ) :
		$toc_html = plainmark_get_toc( get_the_content() );
		if ( $toc_html ) :
			?>
		<nav class="article-toc" aria-label="<?php esc_attr_e( '目次', 'plainmark' ); ?>">
			<div class="article-toc__header">
				<span class="article-toc__title"><?php esc_html_e( '目次', 'plainmark' ); ?></span>
			</div>
			<div class="article-toc__body">
				<?php echo $toc_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</nav>
			<?php
		endif;
	endif;
	?>

	<div class="single-post__content">
		<?php
		the_content();

		wp_link_pages(
			array(
				'before' => '<nav class="page-links" aria-label="' . esc_attr__( '投稿ページ', 'plainmark' ) . '"><span class="page-links-title">' . esc_html__( 'ページ:', 'plainmark' ) . '</span>',
				'after'  => '</nav>',
			)
		);
		?>
	</div>

	<footer class="single-post__footer">
		<?php if ( $tags ) : ?>
			<div class="single-post__tags" aria-label="<?php esc_attr_e( 'タグ', 'plainmark' ); ?>">
				<?php foreach ( $tags as $tag ) : ?>
					<?php
					$tag_url = get_tag_link( $tag->term_id );
					if ( is_wp_error( $tag_url ) ) {
						continue;
					}
					?>
					<a class="single-post__tag" href="<?php echo esc_url( $tag_url ); ?>">
						#<?php echo esc_html( $tag->name ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( get_previous_post() || get_next_post() ) : ?>
			<nav class="single-post__nav" aria-label="<?php esc_attr_e( '前後の記事', 'plainmark' ); ?>">
				<div class="single-post__nav-item single-post__nav-item--prev">
					<?php previous_post_link( '%link', '&larr; ' . esc_html__( '前の記事:', 'plainmark' ) . ' %title' ); ?>
				</div>
				<div class="single-post__nav-item single-post__nav-item--next">
					<?php next_post_link( '%link', esc_html__( '次の記事:', 'plainmark' ) . ' %title &rarr;' ); ?>
				</div>
			</nav>
		<?php endif; ?>

		<a class="single-post__back" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			&larr; <?php esc_html_e( '記事一覧へ戻る', 'plainmark' ); ?>
		</a>
	</footer>
</article>
