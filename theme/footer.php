<?php
/**
 * The footer template
 *
 * @package plainmark
 * @since 0.1.0
 */
?>

</div><!-- .site-content -->

<footer class="site-footer">
  <div class="site-footer__inner">

    <!-- Footer top: Logo, Nav, SNS icons -->
    <div class="site-footer__top">

      <!-- Logo -->
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-footer__logo">
        <?php bloginfo( 'name' ); ?><span class="site-footer__logo-dot">.</span>
      </a>

      <!-- Footer nav -->
      <nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'フッターナビゲーション', 'plainmark' ); ?>">
        <?php
        wp_nav_menu( array(
          'theme_location' => 'footer',
          'menu_class'     => 'site-footer__nav-list',
          'container'      => false,
          'depth'          => 1,
          'fallback_cb'    => false,
        ) );
        ?>
      </nav>

      <!-- SNS icons -->
      <div class="site-footer__sns">
        <?php
        $github  = get_theme_mod( 'plainmark_github_url', '' );
        $twitter = get_theme_mod( 'plainmark_twitter_url', '' );
        $zenn    = get_theme_mod( 'plainmark_zenn_url', '' );

        if ( $github ) :
        ?>
          <a href="<?php echo esc_url( $github ); ?>" class="site-footer__sns-link" target="_blank" rel="noopener noreferrer" aria-label="GitHub">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
          </a>
        <?php endif; ?>

        <?php if ( $twitter ) : ?>
          <a href="<?php echo esc_url( $twitter ); ?>" class="site-footer__sns-link" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4l11.733 16H20L8.267 4z"/><path d="M4 20l6.768-6.768M20 4l-6.768 6.768"/></svg>
          </a>
        <?php endif; ?>

        <?php if ( $zenn ) : ?>
          <a href="<?php echo esc_url( $zenn ); ?>" class="site-footer__sns-link" target="_blank" rel="noopener noreferrer" aria-label="Zenn">
            <svg width="18" height="18" viewBox="0 0 88 88" fill="currentColor" aria-hidden="true"><path d="M9.01 77.3 48.67 8.04c.87-1.57 3.09-1.57 3.96 0l8.22 14.85L30.41 77.3c-.87 1.57-3.09 1.57-3.96 0L9.01 77.3z"/><path d="M56.3 77.3H76.3c1.74 0 2.83-1.87 1.96-3.39L51.7 27.14l-8.22 14.85L56.3 77.3z"/></svg>
          </a>
        <?php endif; ?>
      </div>

    </div>

    <!-- Footer bottom: Copyright -->
    <div class="site-footer__bottom">
      <p class="site-footer__copy">
        &copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.
      </p>
      <p class="site-footer__credit">
        Built with <a href="https://github.com/YOUR_USERNAME/plainmark" target="_blank" rel="noopener noreferrer">plainmark</a>
      </p>
    </div>

  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
