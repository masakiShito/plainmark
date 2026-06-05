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

    <!-- Hamburger button (SP) -->
    <button class="site-header__burger" aria-label="<?php esc_attr_e( 'メニューを開く', 'plainmark' ); ?>" aria-expanded="false" aria-controls="mobile-menu">
      <span class="site-header__burger-line"></span>
      <span class="site-header__burger-line"></span>
    </button>

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
