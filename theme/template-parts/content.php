<?php
/**
 * Template part for displaying posts in listing
 *
 * @package plainmark
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

$verification    = function_exists( 'plainmark_get_verification_data' ) ? plainmark_get_verification_data( get_the_ID() ) : null;
$freshness_badge = function_exists( 'plainmark_render_freshness_badge' ) ? plainmark_render_freshness_badge( get_the_ID() ) : '';
$categories      = get_the_category();
$technologies    = get_the_terms( get_the_ID(), 'technology' );
$difficulty      = get_post_meta( get_the_ID(), '_plainmark_difficulty', true );
$series_name     = get_post_meta( get_the_ID(), '_plainmark_series_name', true );
$content         = get_the_content();
$word_count      = mb_strlen( wp_strip_all_tags( $content ) );
$minutes         = max( 1, ceil( $word_count / 400 ) );
$excerpt         = get_the_excerpt() ?: wp_strip_all_tags( $content );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
	<div class="post-card__inner">
		<?php if ( has_post_thumbnail() ) : ?>
			<a href="<?php the_permalink(); ?>" class="post-card__thumb" aria-label="<?php the_title_attribute(); ?>">
				<?php
				the_post_thumbnail(
					'medium_large',
					array(
						'class'   => 'post-card__thumb-img',
						'loading' => 'lazy',
						'alt'     => get_the_title(),
					)
				);
				?>
			</a>
		<?php endif; ?>

		<div class="post-card__content">
			<div class="post-card__topline">
				<?php if ( $categories ) : ?>
					<a class="post-card__category" href="<?php echo esc_url( get_category_link( $categories[0]->term_id ) ); ?>">
						<?php echo esc_html( $categories[0]->name ); ?>
					</a>
				<?php endif; ?>

				<span>
					<time class="post-card__date" datetime="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>">
						<?php echo esc_html( get_the_date( 'Y.m.d' ) ); ?>
					</time>
					<?php echo wp_kses_post( $freshness_badge ); ?>
				</span>
			</div>

			<h2 class="post-card__title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h2>

			<p class="post-card__excerpt">
				<?php echo esc_html( wp_trim_words( $excerpt, 54, '...' ) ); ?>
			</p>

			<?php if ( $technologies && ! is_wp_error( $technologies ) ) : ?>
				<div class="post-card__techs" aria-label="<?php esc_attr_e( '使用技術', 'plainmark' ); ?>">
					<?php foreach ( array_slice( $technologies, 0, 5 ) as $technology ) : ?>
						<a class="post-card__tech" href="<?php echo esc_url( get_term_link( $technology ) ); ?>">
							<?php echo esc_html( $technology->name ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="post-card__footer">
				<div class="post-card__badges">
					<?php if ( $difficulty ) : ?>
						<span class="post-card__badge post-card__badge--difficulty">
							<?php echo esc_html( $difficulty ); ?>
						</span>
					<?php endif; ?>

					<?php if ( $verification ) : ?>
						<span class="post-card__badge post-card__badge--<?php echo esc_attr( $verification['status'] ); ?>">
							<?php echo esc_html( function_exists( 'plainmark_get_verification_label' ) ? plainmark_get_verification_label( $verification['status'] ) : $verification['status'] ); ?>
						</span>
					<?php endif; ?>

					<?php if ( $series_name ) : ?>
						<span class="post-card__badge post-card__badge--series">
							<?php echo esc_html( $series_name ); ?>
						</span>
					<?php endif; ?>
				</div>

				<span class="post-card__readtime">
					<?php echo esc_html( $minutes ); ?> min read
				</span>
			</div>
		</div>
	</div>
</article>