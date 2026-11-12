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
/** Refresh bundled logo images after the symbol correction without altering uploaded logos. */
function genosys_logo_asset_uri( $url, $file ) {
    $logos = array(
        'assets/images/genosys-logo-1.1.1.svg',
        'assets/images/genosys-logo-light-1.2.4.svg',
        'assets/images/genosys-logo-white-1.2.2.svg',
        'assets/images/logo.svg',
        'assets/images/genosys-mark.svg',
    );
    return in_array( ltrim( $file, '/' ), $logos, true ) ? add_query_arg( 'ver', '1.5.82', $url ) : $url;
}
add_filter( 'theme_file_uri', 'genosys_logo_asset_uri', 10, 2 );
function genosys_assets() {
    wp_enqueue_style( 'genosys-style', get_stylesheet_uri(), array(), '1.5.48' );
    wp_enqueue_style( 'genosys-account-menu', get_theme_file_uri( '/assets/css/account-menu.css' ), array( 'genosys-style' ), '1.5.74' );
    wp_enqueue_style( 'genosys-footer', get_theme_file_uri( '/assets/css/footer.css' ), array( 'genosys-style' ), '1.5.14' );
    wp_enqueue_script( 'genosys-navigation', get_theme_file_uri( '/assets/js/navigation.js' ), array(), '1.2.3', true );
    wp_enqueue_script( 'genosys-account-menu', get_theme_file_uri( '/assets/js/account-menu.js' ), array(), '1.5.50', true );
    if ( is_front_page() && ! is_404() ) {
        wp_enqueue_script( 'genosys-banner', get_theme_file_uri( '/assets/js/banner.js' ), array(), '1.5.48', true );
        if ( function_exists( 'wc_get_products' ) ) {
            wp_enqueue_style( 'genosys-products', get_theme_file_uri( '/assets/css/products.css' ), array( 'genosys-style' ), '1.5.80' );
            wp_enqueue_script( 'genosys-favorites', get_theme_file_uri( '/assets/js/favorites.js' ), array(), '1.4.0', true );
            wp_enqueue_style( 'genosys-featured-products', get_theme_file_uri( '/assets/css/featured-products.css' ), array( 'genosys-products' ), '1.5.52' );
            wp_enqueue_script( 'genosys-featured-products', get_theme_file_uri( '/assets/js/featured-products.js' ), array(), '1.5.17', true );
        }
        wp_enqueue_style( 'genosys-home-categories', get_theme_file_uri( '/assets/css/home-categories.css' ), array( 'genosys-style' ), '1.5.69' );
        wp_enqueue_script( 'genosys-category-stats', get_theme_file_uri( '/assets/js/category-stats.js' ), array(), '1.5.10', false );
        wp_enqueue_script( 'genosys-products-drag', get_theme_file_uri( '/assets/js/products-drag.js' ), array(), '1.5.69', true );
        wp_enqueue_script( 'genosys-category-loop', get_theme_file_uri( '/assets/js/category-loop.js' ), array( 'genosys-products-drag' ), '1.5.70', true );
    }
    if ( function_exists( 'is_product' ) && is_product() ) {
        wp_enqueue_style( 'genosys-products', get_theme_file_uri( '/assets/css/products.css' ), array( 'genosys-style' ), '1.5.80' );
        wp_enqueue_script( 'genosys-favorites', get_theme_file_uri( '/assets/js/favorites.js' ), array(), '1.4.0', true );
        wp_enqueue_script( 'genosys-products-drag', get_theme_file_uri( '/assets/js/products-drag.js' ), array(), '1.5.69', true );
        wp_enqueue_style( 'genosys-single-product', get_theme_file_uri( '/assets/css/single-product.css' ), array( 'genosys-style' ), '1.5.6' );
        wp_enqueue_script( 'genosys-single-product', get_theme_file_uri( '/assets/js/single-product.js' ), array( 'jquery' ), '1.5.3', true );
        wp_enqueue_style( 'genosys-add-to-cart-animation', get_theme_file_uri( '/assets/css/add-to-cart-animation.css' ), array( 'genosys-single-product', 'genosys-account-menu' ), '1.5.79' );
        wp_enqueue_script( 'genosys-add-to-cart-animation', get_theme_file_uri( '/assets/js/add-to-cart-animation.js' ), array( 'genosys-single-product' ), '1.5.79', true );
        wp_add_inline_script( 'genosys-add-to-cart-animation', 'window.genosysCartAnimation = ' . wp_json_encode( array(
            'endpoint' => WC_AJAX::get_endpoint( 'genosys_product_add_to_cart' ),
            'nonce'    => wp_create_nonce( 'genosys_product_add_to_cart' ),
            'cartUrl'  => wc_get_cart_url(),
            'enabled'  => (bool) genosys_site_setting( 'product_cart_animation', 1 ),
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
    }
    if ( genosys_is_brand() ) {
        wp_enqueue_style( 'genosys-brand', get_theme_file_uri( '/assets/css/brand.css' ), array( 'genosys-style' ), '1.5.76' );
        wp_enqueue_script( 'genosys-brand', get_theme_file_uri( '/assets/js/brand.js' ), array(), '1.5.76', true );
    }
    if ( genosys_is_company() ) {
        wp_enqueue_style( 'genosys-company', get_theme_file_uri( '/assets/css/company.css' ), array( 'genosys-style' ), '1.5.78' );
        wp_enqueue_script( 'genosys-company', get_theme_file_uri( '/assets/js/company.js' ), array(), '1.5.78', true );
    }
    if ( genosys_is_contact() ) {
        wp_enqueue_style( 'genosys-contact', get_theme_file_uri( '/assets/css/contact.css' ), array( 'genosys-style' ), '1.5.71' );
        wp_enqueue_script( 'genosys-contact', get_theme_file_uri( '/assets/js/contact.js' ), array(), '1.5.71', true );
        wp_add_inline_script( 'genosys-contact', 'window.genosysContact = ' . wp_json_encode( array(
            'ajaxUrl' => admin_url( 'admin-ajax.php', 'relative' ),
            'nonce' => wp_create_nonce( 'genosys_contact_submit' ),
            'maxFileBytes' => 5 * 1024 * 1024,
        ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
    }
    if ( genosys_is_fans_club() ) {
        wp_enqueue_style( 'genosys-fans-club', get_theme_file_uri( '/assets/css/fans-club.css' ), array( 'genosys-style' ), '1.5.85' );
        wp_enqueue_script( 'genosys-fans-club', get_theme_file_uri( '/assets/js/fans-club.js' ), array(), '1.5.85', true );
        wp_localize_script( 'genosys-fans-club', 'genosysClubRegistration', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php', 'relative' ),
            'prepareAction' => 'genosys_club_prepare',
            'registerAction' => 'genosys_club_register',
        ) );
    }
    if ( genosys_is_login() ) {
        wp_enqueue_style( 'genosys-login', get_theme_file_uri( '/assets/css/login.css' ), array( 'genosys-style' ), '1.5.21' );
        wp_enqueue_script( 'genosys-login', get_theme_file_uri( '/assets/js/login.js' ), array(), '1.5.84', true );
    }
    if ( is_page( 'cart' ) || ( function_exists( 'is_cart' ) && is_cart() ) ) {
        wp_enqueue_style( 'genosys-cart', get_theme_file_uri( '/assets/css/cart.css' ), array( 'genosys-style' ), '1.5.25' );
        wp_enqueue_script( 'genosys-cart', get_theme_file_uri( '/assets/js/cart.js' ), array(), '1.5.24', true );
    }
    if ( function_exists( 'genosys_is_customer_orders' ) && genosys_is_customer_orders() ) {
        wp_enqueue_style( 'genosys-customer-orders', get_theme_file_uri( '/assets/css/customer-orders.css' ), array( 'genosys-style' ), '1.5.42' );
        wp_enqueue_script( 'genosys-customer-orders', get_theme_file_uri( '/assets/js/customer-orders.js' ), array(), '1.5.42', true );
        wp_enqueue_style( 'genosys-order-access', get_theme_file_uri( '/assets/css/order-access.css' ), array( 'genosys-customer-orders' ), '1.5.47' );
        wp_enqueue_script( 'genosys-order-access', get_theme_file_uri( '/assets/js/order-access.js' ), array(), '1.5.47', true );
    }
    if ( function_exists( 'WC' ) && ( is_page( 'checkout' ) || ( function_exists( 'is_checkout' ) && is_checkout() ) ) ) {
        wp_enqueue_style( 'genosys-checkout', get_theme_file_uri( '/assets/css/checkout.css' ), array( 'genosys-style' ), '1.5.36' );
        wp_enqueue_script( 'wc-checkout' );
        wp_enqueue_script( 'genosys-checkout', get_theme_file_uri( '/assets/js/checkout.js' ), array( 'jquery', 'wc-checkout' ), '1.5.34', true );
        if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
            wp_enqueue_style( 'genosys-receipt-uploader', get_theme_file_uri( '/assets/css/receipt-uploader.css' ), array( 'genosys-checkout' ), '1.5.37' );
            wp_enqueue_script( 'genosys-receipt-uploader', get_theme_file_uri( '/assets/js/receipt-uploader.js' ), array(), '1.5.41', true );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'genosys_assets' );
function genosys_fallback_menu() {
    $items = array( 'brand' => 'برند', 'products' => 'محصولات', 'company' => 'شرکت', 'contact' => 'تماس با ما' );
    echo '<ul id="primary-menu" class="primary-menu">';
    foreach ( $items as $slug => $label ) {
        $destination = 'products' === $slug ? 'shop' : $slug;
        echo '<li><a href="' . esc_url( home_url( '/' . $destination . '/' ) ) . '">' . esc_html( $label ) . '</a></li>';
    }
    echo '</ul>';
}

/** Primary navigation links follow their current theme pages. */
function genosys_primary_products_shop_link( $attributes, $item, $args, $depth ) {
    if ( 'primary' === ( $args->theme_location ?? '' ) && 0 === $depth && 'محصولات' === trim( wp_strip_all_tags( $item->title ) ) ) {
        $attributes['href'] = home_url( '/shop/' );
    }
    if ( 'primary' === ( $args->theme_location ?? '' ) && 0 === $depth && 'تماس با ما' === trim( wp_strip_all_tags( $item->title ) ) ) {
        $attributes['href'] = home_url( '/contact/' );
    }
    if ( 'primary' === ( $args->theme_location ?? '' ) && 0 === $depth && 'برند' === trim( wp_strip_all_tags( $item->title ) ) ) {
        $attributes['href'] = home_url( '/brand/' );
    }
    if ( 'primary' === ( $args->theme_location ?? '' ) && 0 === $depth && 'شرکت' === trim( wp_strip_all_tags( $item->title ) ) ) {
        $attributes['href'] = home_url( '/company/' );
    }
    return $attributes;
}
add_filter( 'nav_menu_link_attributes', 'genosys_primary_products_shop_link', 10, 4 );

/** Render the reference navbar controls without changing their account destinations. */
function genosys_header_actions() {
    $signed_in = is_user_logged_in();
    $login_url = home_url( '/login/' );
    $has_wc_account = function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'myaccount' ) > 0;
    $account_url = $has_wc_account ? wc_get_page_permalink( 'myaccount' ) : admin_url( 'profile.php' );
    $orders_url = $has_wc_account && function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'orders' ) : $account_url;
    $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
    $show_cart = genosys_site_visibility( 'header_show_cart', $signed_in || ( function_exists( 'is_product' ) && is_product() ) );
    $cart_count = $show_cart && function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
    $customer = $signed_in ? wp_get_current_user() : null;
    $name = $customer ? trim( $customer->first_name . ' ' . $customer->last_name ) : '';
    if ( '' === $name && $customer ) {
        $name = trim( $customer->display_name );
    }
    if ( '' === $name ) {
        $name = 'حساب کاربری';
    }
    $initial = '•';
    if ( preg_match( '/^./u', $name, $initial_match ) ) {
        $initial = $initial_match[0];
    }
    $phone = $customer ? (string) get_user_meta( $customer->ID, 'genosys_phone', true ) : '';
    if ( '' === $phone && $customer ) {
        $phone = (string) get_user_meta( $customer->ID, 'billing_phone', true );
    }
    if ( preg_match( '/^(\d{4})(\d{3})(\d{4})$/', $phone, $phone_parts ) ) {
        $phone = $phone_parts[1] . ' ' . $phone_parts[2] . ' ' . $phone_parts[3];
    }
    if ( function_exists( 'genosys_persian_number' ) ) {
        $phone = genosys_persian_number( $phone );
    }
    $order_count = $customer && function_exists( 'wc_get_customer_order_count' ) ? (int) wc_get_customer_order_count( $customer->ID ) : 0;
    $search_placeholder = (string) genosys_site_setting( 'header_search_placeholder' );

    ob_start();
    ?>
    <div class="header-actions">
        <?php if ( $show_cart ) : ?>
            <a class="nav-icon-button nav-cart-link" href="<?php echo esc_url( $cart_url ); ?>" aria-label="سبد خرید<?php echo $cart_count ? '، ' . esc_attr( (string) $cart_count ) . ' کالا' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 4h2.3l2.2 11.2h11.2l2-8.2H6"/><path d="M7.5 17.5h11"/><circle cx="9" cy="20" r="1"/><circle cx="17.5" cy="20" r="1"/></svg>
                <?php if ( $cart_count > 0 ) : ?><span class="nav-count-badge"><?php echo esc_html( function_exists( 'genosys_persian_number' ) ? genosys_persian_number( $cart_count > 99 ? '99+' : (string) $cart_count ) : ( $cart_count > 99 ? '99+' : (string) $cart_count ) ); ?></span><?php endif; ?>
            </a>
        <?php endif; ?>
        <div class="nav-account-menu<?php echo $signed_in ? '' : ' nav-account-menu--guest'; ?>" data-account-menu<?php echo genosys_site_visibility( 'header_show_account' ) ? '' : ' hidden'; ?>>
            <button class="nav-icon-button nav-account-toggle" type="button" aria-label="باز کردن منوی حساب کاربری" aria-expanded="false" aria-controls="nav-account-dropdown" data-account-toggle>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="7.5" r="3.4"/><path d="M4.8 20c.4-4.1 3-6.2 7.2-6.2s6.8 2.1 7.2 6.2"/></svg>
                <?php if ( ! $signed_in ) : ?><span class="nav-guest-indicator" aria-hidden="true"></span><?php endif; ?>
            </button>
            <div class="nav-account-dropdown" id="nav-account-dropdown" data-account-dropdown hidden dir="rtl">
                <?php if ( $signed_in ) : ?>
                    <div class="account-panel-identity">
                        <span class="account-panel-avatar" aria-hidden="true"><?php echo esc_html( $initial ); ?></span>
                        <span class="account-panel-identity-copy" data-account-header-identity><strong><?php echo esc_html( $name ); ?></strong><?php if ( '' !== $phone ) : ?><small dir="auto"><?php echo esc_html( $phone ); ?></small><?php endif; ?></span>
                    </div>
                    <a class="account-panel-row" href="<?php echo esc_url( $account_url ); ?>">
                        <span class="account-panel-row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="7.5" r="3"/><path d="M5.5 20c.2-3.7 2.6-5.5 6.5-5.5s6.3 1.8 6.5 5.5"/></svg></span>
                        <span class="account-panel-row-copy"><strong>حساب کاربری</strong><small>اطلاعات و آدرس‌ها</small></span>
                    </a>
                    <a class="account-panel-row" href="<?php echo esc_url( $orders_url ); ?>">
                        <span class="account-panel-row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8.5h14l-1.2 11H6.2L5 8.5Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg></span>
                        <span class="account-panel-row-copy"><strong>سفارشات</strong><small>پیگیری و سابقه‌ی خرید</small></span>
                        <?php if ( $order_count > 0 ) : ?><span class="account-panel-order-count"><?php echo esc_html( function_exists( 'genosys_persian_number' ) ? genosys_persian_number( $order_count > 99 ? '99+' : (string) $order_count ) : (string) $order_count ); ?></span><?php endif; ?>
                    </a>
                    <div class="account-panel-divider" aria-hidden="true"></div>
                    <a class="account-panel-row account-panel-row--logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
                        <span class="account-panel-row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 4H6.5A2.5 2.5 0 0 0 4 6.5v11A2.5 2.5 0 0 0 6.5 20H13"/><path d="m14 8 4 4-4 4m4-4H9"/></svg></span>
                        <span class="account-panel-row-copy"><strong>خروج</strong></span>
                    </a>
                <?php else : ?>
                    <div class="account-panel-identity account-panel-identity--guest">
                        <span class="account-panel-avatar" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7.5" r="3.4"/><path d="M4.8 20c.4-4.1 3-6.2 7.2-6.2s6.8 2.1 7.2 6.2"/></svg></span>
                        <span class="account-panel-identity-copy"><strong>به ژنوسیس خوش آمدید</strong><small>برای ثبت و پیگیری سفارش وارد شوید.</small></span>
                    </div>
                    <a class="account-panel-login" href="<?php echo esc_url( $login_url ); ?>"><span><?php echo esc_html( genosys_site_setting( 'header_login_text' ) ); ?></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6"/></svg></a>
                    <p class="account-panel-login-note">ورود فقط با شماره‌ی موبایل و کد تأیید</p>
                    <div class="account-panel-divider" aria-hidden="true"></div>
                    <a class="account-panel-row" href="<?php echo esc_url( home_url( '/fans-club/' ) ); ?>">
                        <span class="account-panel-row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 9h17v11h-17zM2.5 6h19v3h-19zM12 6v14"/><path d="M12 6C9 6 7 5.2 7 3.7 7 2.5 7.8 2 9 2c1.7 0 3 1.8 3 4Zm0 0c3 0 5-0.8 5-2.3C17 2.5 16.2 2 15 2c-1.7 0-3 1.8-3 4Z"/></svg></span>
                        <span class="account-panel-row-copy"><strong>باشگاه مشتریان</strong><small>مزایا و پیشنهادهای ویژه</small></span>
                    </a>
                    <a class="account-panel-row" href="<?php echo esc_url( $orders_url ); ?>">
                        <span class="account-panel-row-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8.5h14l-1.2 11H6.2L5 8.5Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg></span>
                        <span class="account-panel-row-copy"><strong>پیگیری سفارش</strong><small>با شماره‌ی سفارش</small></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="header-search" data-header-search<?php echo genosys_site_visibility( 'header_show_search' ) ? '' : ' hidden'; ?>>
            <button class="nav-icon-button header-search-toggle" type="button" aria-label="باز کردن جستجو" aria-expanded="false" aria-controls="header-search-form" data-search-toggle><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.2 4.2"/></svg></button>
            <form class="header-search-form" id="header-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-search-form hidden>
                <button type="submit" aria-label="جستجو"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.2 4.2"/></svg></button>
                <input type="hidden" name="post_type" value="product">
                <label class="screen-reader-text" for="header-search-input">جستجوی محصول با نام یا کد SKU</label>
                <input id="header-search-input" type="search" name="s" placeholder="<?php echo esc_attr( $search_placeholder ); ?>" value="<?php echo esc_attr( get_search_query( false ) ); ?>" maxlength="160" required enterkeyhint="search" data-search-input>
                <kbd aria-hidden="true">/</kbd>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/** Keep mobile account and cart destinations aligned with the desktop controls. */
function genosys_mobile_menu_commerce() {
    $signed_in = is_user_logged_in();
    $login_url = home_url( '/login/' );
    $has_wc_account = function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'myaccount' ) > 0;
    $account_url = $has_wc_account ? wc_get_page_permalink( 'myaccount' ) : admin_url( 'profile.php' );
    $orders_url = $has_wc_account && function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'orders' ) : $account_url;
    $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
    $cart_count = function_exists( 'WC' ) && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
    $cart_count_text = function_exists( 'genosys_persian_number' ) ? genosys_persian_number( $cart_count > 99 ? '99+' : (string) $cart_count ) : ( $cart_count > 99 ? '99+' : (string) $cart_count );
    $cart_label = 'سبد خرید' . ( $cart_count ? '، ' . $cart_count . ' کالا' : '' );
    $customer = $signed_in ? wp_get_current_user() : null;
    $name = $customer ? trim( $customer->first_name . ' ' . $customer->last_name ) : '';
    if ( '' === $name && $customer ) {
        $name = trim( $customer->display_name );
    }
    if ( '' === $name ) {
        $name = 'حساب کاربری';
    }
    $initial = '•';
    if ( preg_match( '/^./u', $name, $initial_match ) ) {
        $initial = $initial_match[0];
    }
    $phone = $customer ? (string) get_user_meta( $customer->ID, 'genosys_phone', true ) : '';
    if ( '' === $phone && $customer ) {
        $phone = (string) get_user_meta( $customer->ID, 'billing_phone', true );
    }
    if ( preg_match( '/^(\d{4})(\d{3})(\d{4})$/', $phone, $phone_parts ) ) {
        $phone = $phone_parts[1] . ' ' . $phone_parts[2] . ' ' . $phone_parts[3];
    }
    if ( function_exists( 'genosys_persian_number' ) ) {
        $phone = genosys_persian_number( $phone );
    }
    $order_count = $customer && function_exists( 'wc_get_customer_order_count' ) ? (int) wc_get_customer_order_count( $customer->ID ) : 0;
    $order_count_text = function_exists( 'genosys_persian_number' ) ? genosys_persian_number( $order_count > 99 ? '99+' : (string) $order_count ) : ( $order_count > 99 ? '99+' : (string) $order_count );

    ob_start();
    ?>
    <section class="mobile-menu-commerce<?php echo $signed_in ? '' : ' mobile-menu-commerce--guest'; ?>" aria-label="حساب کاربری و سبد خرید" dir="rtl">
        <div class="mobile-menu-commerce__profile"<?php echo genosys_site_visibility( 'header_show_account' ) ? '' : ' hidden'; ?>>
            <span class="mobile-menu-commerce__avatar" aria-hidden="true"><?php if ( $signed_in ) : ?><?php echo esc_html( $initial ); ?><?php else : ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="7.5" r="3.4"/><path d="M4.8 20c.4-4.1 3-6.2 7.2-6.2s6.8 2.1 7.2 6.2"/></svg><?php endif; ?></span>
            <span class="mobile-menu-commerce__profile-copy"<?php echo $signed_in ? ' data-account-header-identity' : ''; ?>>
                <strong><?php echo esc_html( $signed_in ? $name : 'به ژنوسیس خوش آمدید' ); ?></strong>
                <?php if ( $signed_in && '' !== $phone ) : ?><small dir="auto"><?php echo esc_html( $phone ); ?></small><?php elseif ( ! $signed_in ) : ?><small>برای ثبت و پیگیری سفارش وارد شوید.</small><?php endif; ?>
            </span>
        </div>
        <div class="mobile-menu-commerce__cards">
            <a class="mobile-menu-commerce__card mobile-menu-commerce__card--cart" href="<?php echo esc_url( $cart_url ); ?>" aria-label="<?php echo esc_attr( $cart_label ); ?>"<?php echo genosys_site_visibility( 'header_show_cart' ) ? '' : ' hidden'; ?>>
                <span class="mobile-menu-commerce__card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 4h2.3l2.2 11.2h11.2l2-8.2H6"/><path d="M7.5 17.5h11"/><circle cx="9" cy="20" r="1"/><circle cx="17.5" cy="20" r="1"/></svg></span>
                <span class="mobile-menu-commerce__card-copy"><strong>سبد خرید</strong><small><?php echo esc_html( $cart_count ? $cart_count_text . ' کالا در سبد' : 'مشاهده‌ی سبد خرید' ); ?></small></span>
                <?php if ( $cart_count > 0 ) : ?><span class="mobile-menu-commerce__count" aria-hidden="true"><?php echo esc_html( $cart_count_text ); ?></span><?php endif; ?>
            </a>
            <?php if ( $signed_in ) : ?>
                <a class="mobile-menu-commerce__card mobile-menu-commerce__card--account" href="<?php echo esc_url( $account_url ); ?>"<?php echo genosys_site_visibility( 'header_show_account' ) ? '' : ' hidden'; ?>>
                    <span class="mobile-menu-commerce__card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7.5" r="3.4"/><path d="M4.8 20c.4-4.1 3-6.2 7.2-6.2s6.8 2.1 7.2 6.2"/></svg></span>
                    <span class="mobile-menu-commerce__card-copy"><strong>حساب کاربری</strong><small>اطلاعات و آدرس‌ها</small></span>
                </a>
            <?php else : ?>
                <a class="mobile-menu-commerce__card mobile-menu-commerce__card--login" href="<?php echo esc_url( $login_url ); ?>"<?php echo genosys_site_visibility( 'header_show_account' ) ? '' : ' hidden'; ?>>
                    <span class="mobile-menu-commerce__card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7.5" r="3.4"/><path d="M4.8 20c.4-4.1 3-6.2 7.2-6.2s6.8 2.1 7.2 6.2"/></svg></span>
                    <span class="mobile-menu-commerce__card-copy"><strong><?php echo esc_html( genosys_site_setting( 'header_login_text' ) ); ?></strong><small>با شماره‌ی موبایل و کد تأیید</small></span>
                </a>
            <?php endif; ?>
        </div>
        <div class="mobile-menu-commerce__secondary">
            <?php if ( $signed_in ) : ?>
                <a class="mobile-menu-commerce__secondary-link" href="<?php echo esc_url( $orders_url ); ?>">
                    <span class="mobile-menu-commerce__card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8.5h14l-1.2 11H6.2L5 8.5Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg></span>
                    <span class="mobile-menu-commerce__card-copy"><strong>سفارشات</strong><small>پیگیری و سابقه‌ی خرید</small></span>
                    <?php if ( $order_count > 0 ) : ?><span class="mobile-menu-commerce__count" aria-hidden="true"><?php echo esc_html( $order_count_text ); ?></span><?php endif; ?>
                </a>
                <a class="mobile-menu-commerce__secondary-link mobile-menu-commerce__secondary-link--logout" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
                    <span class="mobile-menu-commerce__card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M13 4H6.5A2.5 2.5 0 0 0 4 6.5v11A2.5 2.5 0 0 0 6.5 20H13"/><path d="m14 8 4 4-4 4m4-4H9"/></svg></span>
                    <span class="mobile-menu-commerce__card-copy"><strong>خروج</strong></span>
                </a>
            <?php else : ?>
                <a class="mobile-menu-commerce__secondary-link" href="<?php echo esc_url( home_url( '/fans-club/' ) ); ?>">
                    <span class="mobile-menu-commerce__card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 9h17v11h-17zM2.5 6h19v3h-19zM12 6v14"/><path d="M12 6C9 6 7 5.2 7 3.7 7 2.5 7.8 2 9 2c1.7 0 3 1.8 3 4Zm0 0c3 0 5-0.8 5-2.3C17 2.5 16.2 2 15 2c-1.7 0-3 1.8-3 4Z"/></svg></span>
                    <span class="mobile-menu-commerce__card-copy"><strong>باشگاه مشتریان</strong><small>مزایا و پیشنهادهای ویژه</small></span>
                </a>
                <a class="mobile-menu-commerce__secondary-link" href="<?php echo esc_url( $orders_url ); ?>">
                    <span class="mobile-menu-commerce__card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8.5h14l-1.2 11H6.2L5 8.5Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg></span>
                    <span class="mobile-menu-commerce__card-copy"><strong>پیگیری سفارش</strong><small>با شماره‌ی سفارش</small></span>
                </a>
            <?php endif; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

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

/** Hide MailPoet's optional marketing opt-in from WooCommerce checkout. */
add_filter( 'mailpoet_woocommerce_checkout_optin_template', '__return_empty_string' );

require_once get_theme_file_path( '/inc/contact.php' );
require_once get_theme_file_path( '/inc/contact-settings.php' );
require_once get_theme_file_path( '/inc/contact-route.php' );
require_once get_theme_file_path( '/inc/brand.php' );
require_once get_theme_file_path( '/inc/company.php' );
require_once get_theme_file_path( '/inc/site-settings.php' );
require_once get_theme_file_path( '/inc/settings-dashboard.php' );
require_once get_theme_file_path( '/inc/css-editor.php' );
require_once get_theme_file_path( '/inc/css-editor-view.php' );
require_once get_theme_file_path( '/inc/php-editor-validator.php' );
require_once get_theme_file_path( '/inc/php-editor.php' );
require_once get_theme_file_path( '/inc/php-editor-view.php' );
require_once get_theme_file_path( '/inc/products.php' );
require_once get_theme_file_path( '/inc/single-product.php' );
require_once get_theme_file_path( '/inc/fans-club.php' );
require_once get_theme_file_path( '/inc/login.php' );
require_once get_theme_file_path( '/inc/login-auth.php' );
require_once get_theme_file_path( '/inc/order-access.php' );
require_once get_theme_file_path( '/inc/theme-settings.php' );
require_once get_theme_file_path( '/inc/jalali-date.php' );
require_once get_theme_file_path( '/inc/order-sms.php' );
require_once get_theme_file_path( '/inc/order-status-sms.php' );
require_once get_theme_file_path( '/inc/cart-animation.php' );
require_once get_theme_file_path( '/inc/checkout-integration.php' );
require_once get_theme_file_path( '/inc/receipts.php' );
require_once get_theme_file_path( '/inc/receipt-sms.php' );
require_once get_theme_file_path( '/inc/receipt-admin.php' );
require_once get_theme_file_path( '/inc/customer-orders.php' );
require_once get_theme_file_path( '/inc/account-dashboard.php' );
require_once get_theme_file_path( '/inc/account-notifications.php' );
require_once get_theme_file_path( '/inc/account-phone-change.php' );
require_once get_theme_file_path( '/inc/shop.php' );
require_once get_theme_file_path( '/inc/product-search.php' );
require_once get_theme_file_path( '/inc/packages.php' );
require_once get_theme_file_path( '/inc/package-visibility.php' );
require_once get_theme_file_path( '/inc/package-builder.php' );
require_once get_theme_file_path( '/inc/package-publishing.php' );
require_once get_theme_file_path( '/inc/package-list-data.php' );
require_once get_theme_file_path( '/inc/package-list.php' );
require_once get_theme_file_path( '/inc/club-members.php' );
require_once get_theme_file_path( '/inc/club-sms.php' );
require_once get_theme_file_path( '/inc/club-admin.php' );
