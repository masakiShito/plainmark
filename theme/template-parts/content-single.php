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

// Check if post was modified.
$is_modified = function_exists( 'plainmark_is_post_modified' ) && plainmark_is_post_modified();

// Get series info.
$series_info = function_exists( 'plainmark_get_series_posts' ) ? plainmark_get_series_posts() : array();

// Get related posts.
$related_posts = function_exists( 'plainmark_get_related_posts' ) ? plainmark_get_related_posts( get_the_ID(), 3 ) : array();

// Check if we have any article info to display.
$has_article_info = $article_type_label || $difficulty_label || $target_reader || $prerequisites || $github_url || $official_docs_url;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'single-post' ); ?>>
	<?php if ( ! empty( $series_info ) && ! empty( $series_info['posts'] ) ) : ?>
		<div class="series-badge">
			<span class="series-badge__label"><?php esc_html_e( 'シリーズ', 'plainmark' ); ?></span>
			<span class="series-badge__name"><?php echo esc_html( $series_info['name'] ); ?></span>
			<span class="series-badge__part">
				<?php
				printf(
					/* translators: 1: current part, 2: total parts */
					esc_html__( 'Part %1$d / %2$d', 'plainmark' ),
					(int) $series_info['current_part'],
					(int) $series_info['total']
				);
				?>
			</span>
		</div>
	<?php endif; ?>

	<header class="single-post__header">
		<div class="single-post__meta">
			<time class="single-post__date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
				<?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?>
			</time>

			<?php if ( $is_modified ) : ?>
				<span class="single-post__updated">
					<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<path d="M1 4v6h6"/>
						<path d="M23 20v-6h-6"/>
						<path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/>
					</svg>
					<?php
					printf(
						/* translators: %s: modified date */
						esc_html__( '更新: %s', 'plainmark' ),
						esc_html( get_the_modified_date( 'Y.m.d' ) )
					);
					?>
				</span>
			<?php endif; ?>

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

	<?php if ( ! empty( $series_info ) && ! empty( $series_info['posts'] ) ) : ?>
		<nav class="series-nav" aria-label="<?php esc_attr_e( 'シリーズ記事', 'plainmark' ); ?>">
			<div class="series-nav__header">
				<div class="series-nav__info">
					<span class="series-nav__badge"><?php esc_html_e( 'シリーズ', 'plainmark' ); ?></span>
					<span class="series-nav__name"><?php echo esc_html( $series_info['name'] ); ?></span>
				</div>
				<span class="series-nav__progress">
					<?php
					printf(
						/* translators: 1: current part, 2: total parts */
						esc_html__( '%1$d / %2$d', 'plainmark' ),
						(int) $series_info['current_part'],
						(int) $series_info['total']
					);
					?>
				</span>
			</div>

			<div class="series-nav__steps">
				<?php foreach ( $series_info['posts'] as $index => $series_post ) : ?>
					<?php
					$is_current = ( $series_post->ID === get_the_ID() );
					$is_done    = ( $index < $series_info['current_index'] );
					$step_class = 'series-nav__step';
					if ( $is_current ) {
						$step_class .= ' series-nav__step--current';
					} elseif ( $is_done ) {
						$step_class .= ' series-nav__step--done';
					}
					?>
					<div class="<?php echo esc_attr( $step_class ); ?>">
						<?php if ( $is_current ) : ?>
							<span class="series-nav__step-number"><?php echo esc_html( $index + 1 ); ?></span>
							<span class="series-nav__step-title"><?php echo esc_html( get_the_title( $series_post ) ); ?></span>
							<span class="series-nav__step-label"><?php esc_html_e( '現在', 'plainmark' ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( get_permalink( $series_post ) ); ?>" class="series-nav__step-link">
								<span class="series-nav__step-number"><?php echo esc_html( $index + 1 ); ?></span>
								<span class="series-nav__step-title"><?php echo esc_html( get_the_title( $series_post ) ); ?></span>
							</a>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $series_info['next_post'] ) : ?>
				<div class="series-nav__next">
					<a href="<?php echo esc_url( get_permalink( $series_info['next_post'] ) ); ?>" class="series-nav__next-link">
						<span class="series-nav__next-label"><?php esc_html_e( '次の記事', 'plainmark' ); ?></span>
						<span class="series-nav__next-title"><?php echo esc_html( get_the_title( $series_info['next_post'] ) ); ?></span>
						<span class="series-nav__next-arrow">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
								<path d="M5 12h14M12 5l7 7-7 7"/>
							</svg>
						</span>
					</a>
				</div>
			<?php endif; ?>
		</nav>
	<?php endif; ?>

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

	<!-- Share Buttons -->
	<div class="share-buttons">
		<span class="share-buttons__label"><?php esc_html_e( 'Share', 'plainmark' ); ?></span>
		<div class="share-buttons__list">
			<button type="button" class="share-button share-button--twitter" data-share="twitter" aria-label="<?php esc_attr_e( 'Twitterでシェア', 'plainmark' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
					<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
				</svg>
				<span class="share-button__text">Tweet</span>
			</button>
			<button type="button" class="share-button share-button--hatena" data-share="hatena" aria-label="<?php esc_attr_e( 'はてなブックマークに追加', 'plainmark' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
					<path d="M20.47 21.22a2.69 2.69 0 0 1-.78 1.93 2.59 2.59 0 0 1-1.87.82 2.71 2.71 0 0 1-1.94-.79 2.63 2.63 0 0 1-.81-1.96 2.65 2.65 0 0 1 .81-1.91 2.69 2.69 0 0 1 1.94-.77 2.65 2.65 0 0 1 1.87.77 2.67 2.67 0 0 1 .78 1.91zm-1.39-7.79h-3.24V.97h3.24v12.46zM12.68 9.23a7.59 7.59 0 0 1-1.06 2.84 5.51 5.51 0 0 1-2 1.88 6.73 6.73 0 0 1-2.62.73l-.72.03H.97v-3.38h3.38a3.94 3.94 0 0 0 2.35-.53 2.77 2.77 0 0 0 1.07-1.88H.97V5.54h6.8c-.06-.48-.35-.89-.91-1.24a3.94 3.94 0 0 0-2.08-.53H.97V.39h5.31a6.76 6.76 0 0 1 2.62.72 5.48 5.48 0 0 1 2 1.88 7.57 7.57 0 0 1 1.06 2.84c.19 1.1.28 2.36.28 3.4s-.09 2.3-.28 3.4z"/>
				</svg>
				<span class="share-button__text">Bookmark</span>
			</button>
			<button type="button" class="share-button share-button--copy" data-share="copy" aria-label="<?php esc_attr_e( 'URLをコピー', 'plainmark' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
					<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
				</svg>
				<span class="share-button__text"><?php esc_html_e( 'Copy', 'plainmark' ); ?></span>
			</button>
		</div>
	</div>

	<!-- Feedback -->
	<div class="article-feedback" data-post-id="<?php the_ID(); ?>">
		<p class="article-feedback__question"><?php esc_html_e( 'この記事は役に立ちましたか？', 'plainmark' ); ?></p>
		<div class="article-feedback__buttons">
			<button type="button" class="article-feedback__button article-feedback__button--yes" data-feedback="helpful">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
				</svg>
				<?php esc_html_e( '役に立った', 'plainmark' ); ?>
			</button>
			<button type="button" class="article-feedback__button article-feedback__button--no" data-feedback="not_helpful">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"/>
				</svg>
				<?php esc_html_e( '改善が必要', 'plainmark' ); ?>
			</button>
		</div>
	</div>

	<?php if ( ! empty( $series_info ) && $series_info['next_post'] ) : ?>
		<div class="series-continue">
			<div class="series-continue__header">
				<span class="series-continue__badge"><?php echo esc_html( $series_info['name'] ); ?></span>
				<span class="series-continue__progress">
					<?php
					printf(
						/* translators: 1: current part, 2: total parts */
						esc_html__( '%1$d / %2$d 完了', 'plainmark' ),
						(int) $series_info['current_part'],
						(int) $series_info['total']
					);
					?>
				</span>
			</div>
			<a href="<?php echo esc_url( get_permalink( $series_info['next_post'] ) ); ?>" class="series-continue__card">
				<div class="series-continue__content">
					<span class="series-continue__label">
						<?php
						printf(
							/* translators: %d: next part number */
							esc_html__( 'Part %d', 'plainmark' ),
							(int) $series_info['current_part'] + 1
						);
						?>
					</span>
					<span class="series-continue__title"><?php echo esc_html( get_the_title( $series_info['next_post'] ) ); ?></span>
				</div>
				<span class="series-continue__action">
					<?php esc_html_e( '次へ進む', 'plainmark' ); ?>
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<path d="M5 12h14M12 5l7 7-7 7"/>
					</svg>
				</span>
			</a>
		</div>
	<?php endif; ?>

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

		<?php if ( ! empty( $related_posts ) ) : ?>
			<section class="related-posts">
				<h2 class="related-posts__title"><?php esc_html_e( '関連記事', 'plainmark' ); ?></h2>
				<div class="related-posts__list">
					<?php foreach ( $related_posts as $related ) : ?>
						<article class="related-posts__item">
							<a href="<?php echo esc_url( get_permalink( $related ) ); ?>">
								<?php if ( has_post_thumbnail( $related ) ) : ?>
									<div class="related-posts__thumb">
										<?php echo get_the_post_thumbnail( $related, 'thumbnail' ); ?>
									</div>
								<?php endif; ?>
								<div class="related-posts__content">
									<h3 class="related-posts__item-title"><?php echo esc_html( get_the_title( $related ) ); ?></h3>
									<time class="related-posts__date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $related ) ); ?>">
										<?php echo esc_html( get_the_date( 'Y.m.d', $related ) ); ?>
									</time>
								</div>
							</a>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<a class="single-post__back" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			&larr; <?php esc_html_e( '記事一覧へ戻る', 'plainmark' ); ?>
		</a>
	</footer>
</article>
