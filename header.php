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
<?php
$on_banner = ( is_front_page() && ! is_404() && genosys_home_has_first_slider() ) || genosys_is_brand();
$brand_name = (string) genosys_site_setting( 'site_name', 'ژنوسیس' );
$mobile_logo_id = (int) get_theme_mod( 'custom_logo', 0 );
$mobile_logo_fallback = $mobile_logo_id ? wp_get_attachment_image_url( $mobile_logo_id, 'full' ) : '';
$mobile_logo = genosys_site_url( 'header_logo_home', $mobile_logo_fallback ?: get_theme_file_uri( '/assets/images/genosys-logo-light-1.2.4.svg' ) );
?>
<header class="site-header <?php echo $on_banner ? 'site-header--banner' : 'site-header--standard'; ?>">
<div class="header-inner">
<div class="site-branding">
<?php
$header_logo = genosys_site_setting( $on_banner ? 'header_logo_home' : 'header_logo_internal', '' );
if ( '' === $header_logo && has_custom_logo() ) : the_custom_logo(); else :
?>
<a class="brand-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $brand_name . ' — صفحه اصلی' ); ?>">
<img class="brand-logo" src="<?php echo esc_url( ( '' !== $header_logo ? $header_logo : get_theme_file_uri( $on_banner ? '/assets/images/genosys-logo-light-1.2.4.svg' : '/assets/images/genosys-logo-1.1.1.svg' ) ) ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>" width="265" height="58">
</a>
<?php endif; ?>
</div>
<nav id="primary-navigation" class="primary-navigation" aria-label="منوی اصلی">
<div class="mobile-menu-topbar">
<a class="mobile-menu-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $brand_name . ' — صفحه اصلی' ); ?>"><img src="<?php echo esc_url( $mobile_logo ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>" width="265" height="58"></a>
<button class="mobile-menu-close" type="button" aria-label="بستن منو"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="m5 5 14 14M19 5 5 19"/></svg></button>
</div>
<?php echo genosys_mobile_menu_commerce(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Links and account data are escaped in the renderer. ?>
<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_id' => 'primary-menu', 'menu_class' => 'primary-menu', 'fallback_cb' => 'genosys_fallback_menu', 'depth' => 2 ) ); ?>
</nav>
<?php echo genosys_header_actions(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Links and account data are escaped in the renderer. ?>
<button class="menu-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false" aria-label="باز کردن منو"><svg class="menu-open-icon" viewBox="0 0 28 24" fill="none" stroke="currentColor" stroke-width="3.3" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M4 4h20M6 12h16M4 20h20"/></svg><svg class="menu-close-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="m5 5 14 14M19 5 5 19"/></svg></button>
</div>
</header>

