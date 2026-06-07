<?php
/**
 * The header template
 *
 * @package plainmark
 * @since 0.1.0
 */

$posts_page_id  = (int) get_option( 'page_for_posts' );
$posts_page_url = $posts_page_id ? get_permalink( $posts_page_id ) : home_url( '/blog/' );
$portfolio_url  = post_type_exists( 'portfolio' ) ? get_post_type_archive_link( 'portfolio' ) : home_url( '/#works' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if ( is_single() ) : ?>
<div class="reading-progress" aria-hidden="true">
  <div class="reading-progress__bar"></div>
</div>
<?php endif; ?>

<header class="site-header" id="site-header">
  <div class="site-header__shell">
    <div class="site-header__inner">
      <div class="site-header__brand">
        <?php if ( has_custom_logo() ) : ?>
          <?php the_custom_logo(); ?>
        <?php else : ?>
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-header__logo-text" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
            <span class="site-header__logo-mark" aria-hidden="true">M</span>
            <span class="site-header__logo-name"><?php bloginfo( 'name' ); ?></span>
          </a>
        <?php endif; ?>
      </div>

      <nav class="site-header__nav" aria-label="<?php esc_attr_e( 'メインナビゲーション', 'plainmark' ); ?>">
        <?php if ( has_nav_menu( 'primary' ) ) : ?>
          <?php
          wp_nav_menu(
            array(
              'theme_location' => 'primary',
              'menu_class'     => 'site-header__nav-list',
              'container'      => false,
              'depth'          => 1,
              'fallback_cb'    => false,
            )
          );
          ?>
        <?php else : ?>
          <ul class="site-header__nav-list">
            <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'plainmark' ); ?></a></li>
            <li><a href="<?php echo esc_url( $posts_page_url ); ?>"><?php esc_html_e( 'Blog', 'plainmark' ); ?></a></li>
            <?php if ( $portfolio_url ) : ?>
              <li><a href="<?php echo esc_url( $portfolio_url ); ?>"><?php esc_html_e( 'Works', 'plainmark' ); ?></a></li>
            <?php endif; ?>
            <li><a href="<?php echo esc_url( home_url( '/#about' ) ); ?>"><?php esc_html_e( 'About', 'plainmark' ); ?></a></li>
          </ul>
        <?php endif; ?>
      </nav>

      <div class="site-header__actions">
        <a class="site-header__cta" href="<?php echo esc_url( $posts_page_url ); ?>">
          <?php esc_html_e( '記事を読む', 'plainmark' ); ?>
        </a>

        <button class="dark-mode-toggle" data-dark-mode-toggle aria-label="<?php esc_attr_e( 'ダークモードを切り替える', 'plainmark' ); ?>" aria-pressed="false">
          <svg class="dark-mode-toggle__icon dark-mode-toggle__icon--light" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.66 6.34l1.41-1.41"/>
          </svg>
          <svg class="dark-mode-toggle__icon dark-mode-toggle__icon--dark" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
          </svg>
        </button>

        <button class="site-header__burger" aria-label="<?php esc_attr_e( 'メニューを開く', 'plainmark' ); ?>" aria-expanded="false" aria-controls="mobile-menu">
          <span class="site-header__burger-label"><?php esc_html_e( 'Menu', 'plainmark' ); ?></span>
          <span class="site-header__burger-icon" aria-hidden="true">
            <span class="site-header__burger-line"></span>
            <span class="site-header__burger-line"></span>
          </span>
        </button>
      </div>
    </div>
  </div>
</header>

<div class="mobile-menu" id="mobile-menu" aria-hidden="true">
  <div class="mobile-menu__inner">
    <div class="mobile-menu__header">
      <span class="mobile-menu__eyebrow"><?php esc_html_e( 'NAVIGATION', 'plainmark' ); ?></span>
    </div>
    <nav aria-label="<?php esc_attr_e( 'モバイルナビゲーション', 'plainmark' ); ?>">
      <?php if ( has_nav_menu( 'primary' ) ) : ?>
        <?php
        wp_nav_menu(
          array(
            'theme_location' => 'primary',
            'menu_class'     => 'mobile-menu__list',
            'container'      => false,
            'depth'          => 1,
            'fallback_cb'    => false,
          )
        );
        ?>
      <?php else : ?>
        <ul class="mobile-menu__list">
          <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><span>01</span><?php esc_html_e( 'Home', 'plainmark' ); ?></a></li>
          <li><a href="<?php echo esc_url( $posts_page_url ); ?>"><span>02</span><?php esc_html_e( 'Blog', 'plainmark' ); ?></a></li>
          <?php if ( $portfolio_url ) : ?>
            <li><a href="<?php echo esc_url( $portfolio_url ); ?>"><span>03</span><?php esc_html_e( 'Works', 'plainmark' ); ?></a></li>
          <?php endif; ?>
          <li><a href="<?php echo esc_url( home_url( '/#about' ) ); ?>"><span>04</span><?php esc_html_e( 'About', 'plainmark' ); ?></a></li>
        </ul>
      <?php endif; ?>
    </nav>
  </div>
</div>
<div class="mobile-menu__overlay" id="mobile-menu-overlay"></div>

<div class="site-content">
