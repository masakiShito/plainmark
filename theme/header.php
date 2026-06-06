<?php
/**
 * The header template
 *
 * @package plainmark
 * @since 0.1.0
 */
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

<!-- Reading progress bar -->
<?php if ( is_single() ) : ?>
<div class="reading-progress" aria-hidden="true">
  <div class="reading-progress__bar"></div>
</div>
<?php endif; ?>

<header class="site-header" id="site-header">
  <div class="site-header__inner">

    <!-- Logo -->
    <div class="site-header__logo">
      <?php if ( has_custom_logo() ) : ?>
        <?php the_custom_logo(); ?>
      <?php else : ?>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-header__logo-text">
          <?php bloginfo( 'name' ); ?><span class="site-header__logo-dot">.</span>
        </a>
      <?php endif; ?>
    </div>

    <!-- PC Navigation -->
    <nav class="site-header__nav" aria-label="<?php esc_attr_e( 'メインナビゲーション', 'plainmark' ); ?>">
      <?php
      wp_nav_menu( array(
        'theme_location' => 'primary',
        'menu_class'     => 'site-header__nav-list',
        'container'      => false,
        'depth'          => 1,
        'fallback_cb'    => false,
      ) );
      ?>
    </nav>

    <!-- Actions -->
    <div class="site-header__actions">
      <!-- Dark mode toggle -->
      <button class="dark-mode-toggle" data-dark-mode-toggle aria-label="<?php esc_attr_e( 'Switch to dark mode', 'plainmark' ); ?>" aria-pressed="false">
        <svg class="dark-mode-toggle__icon dark-mode-toggle__icon--light" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <circle cx="12" cy="12" r="5"/>
          <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
        </svg>
        <svg class="dark-mode-toggle__icon dark-mode-toggle__icon--dark" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
        </svg>
      </button>

      <!-- Hamburger button (SP) -->
      <button class="site-header__burger" aria-label="<?php esc_attr_e( 'メニューを開く', 'plainmark' ); ?>" aria-expanded="false" aria-controls="mobile-menu">
        <span class="site-header__burger-line"></span>
        <span class="site-header__burger-line"></span>
      </button>
    </div>

  </div>
</header>

<!-- Mobile menu -->
<div class="mobile-menu" id="mobile-menu" aria-hidden="true">
  <nav aria-label="<?php esc_attr_e( 'モバイルナビゲーション', 'plainmark' ); ?>">
    <?php
    wp_nav_menu( array(
      'theme_location' => 'primary',
      'menu_class'     => 'mobile-menu__list',
      'container'      => false,
      'depth'          => 1,
      'fallback_cb'    => false,
    ) );
    ?>
  </nav>
</div>
<div class="mobile-menu__overlay" id="mobile-menu-overlay"></div>

<div class="site-content">
