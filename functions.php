<?php
/** Theme setup and assets. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function genosys_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-slider' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'custom-logo', array( 'height' => 84, 'width' => 400, 'flex-height' => true, 'flex-width' => true ) );
    add_theme_support( 'html5', array( 'style', 'script' ) );
    register_nav_menus( array( 'primary' => 'منوی اصلی' ) );
}
add_action( 'after_setup_theme', 'genosys_setup' );
function genosys_assets() {
    wp_enqueue_style( 'genosys-style', get_stylesheet_uri(), array(), '1.5.15' );
    wp_enqueue_style( 'genosys-footer', get_theme_file_uri( '/assets/css/footer.css' ), array( 'genosys-style' ), '1.5.14' );
    wp_enqueue_script( 'genosys-navigation', get_theme_file_uri( '/assets/js/navigation.js' ), array(), '1.2.3', true );
    if ( is_front_page() && ! is_404() ) {
        wp_enqueue_script( 'genosys-banner', get_theme_file_uri( '/assets/js/banner.js' ), array(), '1.2.2', true );
        if ( function_exists( 'wc_get_products' ) ) {
            wp_enqueue_style( 'genosys-products', get_theme_file_uri( '/assets/css/products.css' ), array( 'genosys-style' ), '1.5.5' );
            wp_enqueue_script( 'genosys-favorites', get_theme_file_uri( '/assets/js/favorites.js' ), array(), '1.4.0', true );
        }
        wp_enqueue_style( 'genosys-home-categories', get_theme_file_uri( '/assets/css/home-categories.css' ), array( 'genosys-style' ), '1.5.12' );
        wp_enqueue_script( 'genosys-category-stats', get_theme_file_uri( '/assets/js/category-stats.js' ), array(), '1.5.10', false );
        wp_enqueue_script( 'genosys-products-drag', get_theme_file_uri( '/assets/js/products-drag.js' ), array(), '1.4.2', true );
        wp_enqueue_script( 'genosys-category-loop', get_theme_file_uri( '/assets/js/category-loop.js' ), array( 'genosys-products-drag' ), '1.5.16', true );
    }
    if ( function_exists( 'is_product' ) && is_product() ) {
        wp_enqueue_style( 'genosys-products', get_theme_file_uri( '/assets/css/products.css' ), array( 'genosys-style' ), '1.5.5' );
        wp_enqueue_script( 'genosys-favorites', get_theme_file_uri( '/assets/js/favorites.js' ), array(), '1.4.0', true );
        wp_enqueue_script( 'genosys-products-drag', get_theme_file_uri( '/assets/js/products-drag.js' ), array(), '1.4.2', true );
        wp_enqueue_style( 'genosys-single-product', get_theme_file_uri( '/assets/css/single-product.css' ), array( 'genosys-style' ), '1.5.6' );
        wp_enqueue_script( 'genosys-single-product', get_theme_file_uri( '/assets/js/single-product.js' ), array( 'jquery' ), '1.5.3', true );
    }
    if ( genosys_is_fans_club() ) {
        wp_enqueue_style( 'genosys-fans-club', get_theme_file_uri( '/assets/css/fans-club.css' ), array( 'genosys-style' ), '1.5.11' );
        wp_enqueue_script( 'genosys-fans-club', get_theme_file_uri( '/assets/js/fans-club.js' ), array(), '1.5.11', true );
    }
}
add_action( 'wp_enqueue_scripts', 'genosys_assets' );
function genosys_fallback_menu() {
    $items = array( 'brand' => 'برند', 'products' => 'محصولات', 'company' => 'شرکت', 'contact' => 'تماس با ما' );
    echo '<ul id="primary-menu" class="primary-menu">';
    foreach ( $items as $slug => $label ) {
        echo '<li><a href="' . esc_url( home_url( '/' . $slug . '/' ) ) . '">' . esc_html( $label ) . '</a></li>';
    }
    echo genosys_login_menu_item();
    echo '</ul>';
}

/** Add the same login action to the fallback and assigned primary menus. */
function genosys_login_menu_item() {
    return '<li class="menu-item menu-item-login"><a class="nav-login" href="' . esc_url( wp_login_url() ) . '">ورود</a></li>';
}
function genosys_primary_menu_login( $items, $args ) {
    if ( isset( $args->theme_location ) && 'primary' === $args->theme_location ) {
        $items .= genosys_login_menu_item();
    }
    return $items;
}
add_filter( 'wp_nav_menu_items', 'genosys_primary_menu_login', 10, 2 );

/** Keep RTL without a duplicate HTML dir attribute. */
function genosys_language_attributes( $attributes ) {
    return trim( str_replace( array( 'dir="rtl"', 'dir="ltr"' ), '', $attributes ) ) . ' dir="rtl"';
}
add_filter( 'language_attributes', 'genosys_language_attributes' );

/** The homepage products card follows its configured or existing destination. */
function genosys_home_card_url( $card ) {
    $custom = get_theme_mod( 'genosys_' . $card . '_link', '' );
    if ( is_string( $custom ) && '' !== trim( $custom ) ) {
        return $custom;
    }
    $locations = get_nav_menu_locations();
    if ( ! empty( $locations['primary'] ) ) {
        $items = wp_get_nav_menu_items( $locations['primary'] );
        if ( $items ) {
            foreach ( $items as $item ) {
                if ( 'محصولات' === trim( wp_strip_all_tags( $item->title ) ) && ! empty( $item->url ) ) {
                    return $item->url;
                }
            }
        }
    }
    return home_url( '/products/' );
}

function genosys_customize_home_cards( $wp_customize ) {
    $wp_customize->add_section( 'genosys_home_cards', array( 'title' => 'کارت‌های صفحه اصلی', 'priority' => 35 ) );
    $cards = array(
        'products' => array( 'label' => 'پیوند مشاهده محصولات', 'description' => 'اگر خالی باشد، از پیوند محصولات در منوی اصلی استفاده می‌شود.' ),
    );
    foreach ( $cards as $card => $control ) {
        $setting = 'genosys_' . $card . '_link';
        $wp_customize->add_setting( $setting, array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
        $wp_customize->add_control( $setting, array( 'label' => $control['label'], 'description' => $control['description'], 'section' => 'genosys_home_cards', 'type' => 'url' ) );
    }
}
add_action( 'customize_register', 'genosys_customize_home_cards' );

require_once get_theme_file_path( '/inc/products.php' );
require_once get_theme_file_path( '/inc/single-product.php' );
require_once get_theme_file_path( '/inc/fans-club.php' );
