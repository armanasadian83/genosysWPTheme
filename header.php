<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">رفتن به محتوای اصلی</a>
<header class="site-header">
<div class="header-inner">
<div class="site-branding">
<?php if ( has_custom_logo() ) : the_custom_logo(); else : ?>
<a class="brand-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="GENOSYS — صفحه اصلی">
<img class="brand-logo" src="<?php echo esc_url( get_theme_file_uri( is_front_page() && ! is_404() ? '/assets/images/genosys-logo-light-1.2.4.svg' : '/assets/images/genosys-logo-1.1.1.svg' ) ); ?>" alt="GENOSYS" width="265" height="58">
</a>
<?php endif; ?>
</div>
<button class="menu-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false" aria-label="باز کردن منو"><svg class="menu-open-icon" viewBox="0 0 28 24" fill="none" stroke="currentColor" stroke-width="3.3" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M4 4h20M6 12h16M4 20h20"/></svg><svg class="menu-close-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="m5 5 14 14M19 5 5 19"/></svg></button>
<nav id="primary-navigation" class="primary-navigation" aria-label="منوی اصلی">
<div class="mobile-menu-topbar">
<a class="mobile-menu-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="GENOSYS — صفحه اصلی"><img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/genosys-logo-light-1.2.4.svg' ) ); ?>" alt="GENOSYS" width="265" height="58"></a>
<button class="mobile-menu-close" type="button" aria-label="بستن منو"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="m5 5 14 14M19 5 5 19"/></svg></button>
</div>
<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_id' => 'primary-menu', 'menu_class' => 'primary-menu', 'fallback_cb' => 'genosys_fallback_menu', 'depth' => 2 ) ); ?>
</nav>
</div>
</header>

