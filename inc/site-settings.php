<?php
/** Editable presentation values. Unconfigured values retain the installed design. */
defined( 'ABSPATH' ) || exit;

function genosys_site_defaults() {
    static $defaults = null;
    if ( null !== $defaults ) { return $defaults; }
    $image = static function ( $file ) { return get_theme_file_uri( '/assets/images/' . $file ); };
    $defaults = array(
        'site_name' => 'ژنوسیس', 'site_slogan' => 'Gene Re-birth System',
        'primary_color' => '#c8103b', 'deep_color' => '#9f1536',
        'header_logo_home' => '', 'header_logo_internal' => '', 'header_height' => 100,
        'header_show_search' => 'auto', 'header_show_account' => 'auto', 'header_show_cart' => 'auto',
        'header_search_placeholder' => 'نام محصول یا کد SKU...', 'header_login_text' => 'ورود / ثبت‌نام',
        'header_icon_color_home' => '#f4f6f7', 'header_icon_color_internal' => '#3b4553', 'header_icon_hover' => '#c8103b',
        'header_link_color_home' => '#f4f6f7', 'header_link_color_internal' => '#3b4553',
        'header_link_hover_home' => '#f2a5b8', 'header_link_hover_internal' => '#c8103b',
        'header_background_home' => '', 'header_background_internal' => '#ffffff',
        'mobile_menu_background' => '', 'mobile_menu_color' => '#e9edef', 'mobile_menu_hover' => '#efafc0',
        'slider_interval' => 6000,
        'brand_video_id' => 0,
        'slides' => array(
            array( 'image' => $image( 'banner-01-skincare-1.2.2.webp' ), 'alt' => 'مجموعه کرم‌ها و محصولات مراقبت از پوست ژنوسیس روی پایه‌های سفید', 'class' => 'slide-skincare', 'title' => '', 'link' => '' ),
            array( 'image' => $image( 'banner-02-eco-gel-1.2.2.webp' ), 'alt' => 'ژل مراقبت از پوست ژنوسیس در کنار گوی‌های شفاف و قطره‌های ژل', 'class' => 'slide-eco', 'title' => '', 'link' => '' ),
            array( 'image' => $image( 'banner-03-hair-1.2.2.webp' ), 'alt' => 'محصولات مراقبت از مو و دستگاه ژنوسیس در برابر دیوار گرم و روشن', 'class' => 'slide-hair', 'title' => '', 'link' => '' ),
            array( 'image' => $image( 'banner-04-roller-1.2.2.webp' ), 'alt' => 'درمارولر ژنوسیس و ویال‌های مراقبت از پوست روی سطح سنگی روشن', 'class' => 'slide-ampoules', 'title' => '', 'link' => '' ),
        ),
        'home_sections' => array(
            array( 'id' => 'slider', 'visible' => 1 ), array( 'id' => 'cards', 'visible' => 1 ),
            array( 'id' => 'latest', 'visible' => 1 ), array( 'id' => 'categories', 'visible' => 1 ), array( 'id' => 'featured', 'visible' => 1 ),
        ),
        'club_card_image' => '', 'club_card_title' => 'باشگاه مشتریان', 'club_card_button' => 'ورود به باشگاه', 'club_card_url' => '',
        'products_card_image' => '', 'products_card_title' => 'محصولات ژنوسیس', 'products_card_button' => 'مشاهده محصولات', 'products_card_url' => '',
        'latest_title' => 'جدیدترین محصولات', 'latest_count' => 8, 'latest_all_text' => 'مشاهده همه',
        'categories_title' => 'مراقبتی که', 'categories_highlight' => 'مخصوص شماست',
        'categories_description' => 'با محصولات تخصصی ژنوسیس، پوست و موی خود را به متخصصان بسپارید.',
        'categories_button' => 'مشاهده دسته‌بندی‌ها', 'categories_brand_text' => 'یا با برند ما آشنا شوید', 'categories_brand_url' => '',
        'category_cards' => array(
            array( 'title' => 'مراقبت مو و پوست سر', 'image' => $image( 'category-hair-1.5.7.webp' ), 'size' => 'short', 'term_id' => 0, 'terms' => array( 'مراقبت مو و پوست سر', 'مراقبت از مو و پوست سر', 'محصولات مو' ) ),
            array( 'title' => 'مراقبت پوست', 'image' => $image( 'category-skin-1.5.7.webp' ), 'size' => 'tall', 'term_id' => 0, 'terms' => array( 'مراقبت پوست', 'مراقبت از پوست', 'محصولات مراقبت از پوست' ) ),
            array( 'title' => 'ضد آفتاب‌ها', 'image' => $image( 'category-sun-1.5.7.webp' ), 'size' => 'short', 'term_id' => 0, 'terms' => array( 'ضد آفتاب‌ها', 'ضد آفتاب', 'ضدآفتاب' ) ),
            array( 'title' => 'میست و اسپری', 'image' => $image( 'category-mist-1.5.7.webp' ), 'size' => 'tall', 'term_id' => 0, 'terms' => array( 'میست و اسپری', 'میست', 'اسپری' ) ),
            array( 'title' => 'محصولات آرایشی', 'image' => $image( 'category-makeup-1.5.7.webp' ), 'size' => 'short', 'term_id' => 0, 'terms' => array( 'محصولات آرایشی', 'آرایش', 'محصولات آرایش' ) ),
            array( 'title' => 'مراقبت دور چشم', 'image' => $image( 'category-eye-1.5.7.webp' ), 'size' => 'tall', 'term_id' => 0, 'terms' => array( 'مراقبت دور چشم', 'محصولات دور چشم', 'دور چشم' ) ),
        ),
        'stats_visible' => 1,
        'stats' => array(
            array( 'count' => 16, 'label' => 'سال سابقه', 'icon' => 'calendar' ),
            array( 'count' => 2000, 'label' => 'مشتری ثابت', 'icon' => 'people' ),
            array( 'count' => 50, 'label' => 'محصول تخصصی', 'icon' => 'flask' ),
            array( 'count' => 50, 'label' => 'کشور جهان', 'icon' => 'globe' ),
        ),
        'featured_eyebrow' => 'فروشگاه ژنوسیس', 'featured_title' => 'محصولات', 'featured_highlight' => 'منتخب', 'featured_suffix' => 'ما',
        'featured_description' => 'دسته‌بندی موردنظرتان را انتخاب کنید و محصولات پرفروش آن را ببینید.',
        'featured_link_text' => 'مشاهده‌ی تمامی دسته‌بندی‌ها', 'featured_count' => 6,
        'featured_tabs' => array(
            array( 'label' => 'پکیج‌های ویژه', 'term_id' => 0, 'names' => array( 'پکیج‌های ویژه', 'پکیج های ویژه', 'پکیج ویژه' ) ),
            array( 'label' => 'آبرسان‌ها', 'term_id' => 0, 'names' => array( 'آبرسان‌ها', 'آبرسان ها', 'آبرسان' ) ),
            array( 'label' => 'ضدآفتاب‌ها', 'term_id' => 0, 'names' => array( 'ضدآفتاب‌ها', 'ضد آفتاب‌ها', 'ضد آفتاب ها', 'ضدآفتاب', 'ضد آفتاب' ) ),
        ),
        'invitation_line1' => 'بیایید با هم، <em>پوستی</em>', 'invitation_line2' => '<em>درخشان</em> بسازیم',
        'invitation_button' => 'دریافت کاتالوگ محصولات', 'invitation_url' => '', 'invitation_image' => '',
        'footer_logo' => '', 'footer_description' => 'ژنوسیس، مراقبت علمی پوست و مو با تکیه بر فناوری بازسازی <br>ژنی.',
        'footer_contact_text' => 'تماس با ما', 'footer_contact_url' => '',
        'footer_copyright' => '© ژنوسیس — تمامی حقوق محفوظ است', 'footer_tagline' => 'GENOSYS - Gene Re-birth System',
        'social_instagram' => '', 'social_telegram' => '', 'social_whatsapp' => '', 'social_linkedin' => '',
        'shop_title' => 'فروشگاه', 'shop_highlight' => 'ژنوسیس', 'shop_per_page' => 12,
        'orders_title' => 'سفارشات من',
        'account_title' => 'حساب کاربری', 'account_profile_label' => 'اطلاعات حساب', 'account_addresses_label' => 'آدرس‌ها',
        'account_notifications_label' => 'اعلان‌ها', 'account_club_label' => 'باشگاه مشتریان', 'account_orders_label' => 'سفارشات من',
        'club_eyebrow' => 'باشگاه مشتریان', 'club_title_before' => 'عضو خانواده‌ی', 'club_title_highlight' => 'ژنوسیس', 'club_title_after' => 'شوید',
        'club_description_line1' => 'پرسشنامه‌ی کوتاه زیر را تکمیل کنید تا مراقبت‌ها و پیشنهادهای ویژه‌ی باشگاه را',
        'club_description_line2' => 'متناسب با پوست شما آماده کنیم.',
        'club_form_title' => 'پرسشنامه‌ی عضویت', 'club_form_description' => 'حدود ۲ دقیقه زمان می‌برد. موارد دارای ستاره الزامی است.',
        'club_default_audience' => 'clinic', 'club_consumer_label' => 'مصرف کننده', 'club_clinic_label' => 'کلینیک ها و بیوتی تراپیست ها',
        'clinic_eyebrow' => 'باشگاه همکاران حرفه‌ای', 'clinic_title_before' => 'همراه حرفه‌ای', 'clinic_title_highlight' => 'ژنوسیس', 'clinic_title_after' => 'شوید',
        'clinic_description_line1' => 'نیازهای کلینیک و خدمات حرفه‌ای خود را با ما در میان بگذارید',
        'clinic_description_line2' => 'تا پیشنهادهای ژنوسیس را متناسب با مشتریان و مصرف مجموعه‌ی شما آماده کنیم.',
        'clinic_form_title' => 'پرسشنامه‌ی همکاری حرفه‌ای', 'clinic_form_description' => 'کمی از مجموعه و نیازهای کاری خود بگویید. موارد دارای ستاره الزامی است.',
        'product_cart_animation' => 1, 'product_related_count' => 4,
        'product_related_title' => '', 'product_description_title' => '', 'product_information_title' => '', 'product_reviews_title' => '',
    );
    if ( function_exists( 'genosys_contact_defaults' ) ) { $defaults = array_merge( $defaults, genosys_contact_defaults() ); }
    return $defaults;
}

/** Values are merged at read time; updating the panel never installs new storefront defaults. */
function genosys_site_setting( $key, $fallback = null ) {
    $defaults = genosys_site_defaults();
    if ( null === $fallback && array_key_exists( $key, $defaults ) ) { $fallback = $defaults[ $key ]; }
    $values = get_option( 'genosys_site_settings', array() );
    if ( ! is_array( $values ) || ! array_key_exists( $key, $values ) ) { return $fallback; }
    $value = $values[ $key ];
    if ( is_array( $fallback ) ) { return is_array( $value ) ? $value : $fallback; }
    return is_scalar( $value ) ? $value : $fallback;
}

function genosys_site_url( $key, $fallback ) {
    $url = genosys_site_setting( $key, '' );
    return is_string( $url ) && '' !== trim( $url ) ? $url : $fallback;
}

function genosys_site_visibility( $key, $automatic = true ) {
    $value = genosys_site_setting( $key, 'auto' );
    return 'auto' === $value ? (bool) $automatic : 'yes' === $value;
}

function genosys_home_sections() {
    $defaults = genosys_site_defaults()['home_sections'];
    $sections = genosys_site_setting( 'home_sections', $defaults );
    $allowed = array_column( $defaults, 'id' );
    $valid = array();
    $seen = array();
    foreach ( $sections as $section ) {
        if ( ! is_array( $section ) || ! isset( $section['id'] ) || ! in_array( $section['id'], $allowed, true ) || in_array( $section['id'], $seen, true ) ) { continue; }
        $seen[] = $section['id'];
        $valid[] = array( 'id' => $section['id'], 'visible' => ! empty( $section['visible'] ) );
    }
    // Missing rows are restored, so a partial save cannot silently remove a section.
    foreach ( $defaults as $section ) { if ( ! in_array( $section['id'], $seen, true ) ) { $valid[] = $section; } }
    return $valid;
}

function genosys_home_has_first_slider() {
    foreach ( genosys_home_sections() as $section ) {
        if ( ! $section['visible'] ) { continue; }
        return 'slider' === $section['id'] && count( genosys_site_setting( 'slides' ) ) > 0;
    }
    return false;
}

function genosys_configured_home_body_class( $classes ) {
    if ( is_front_page() && ! is_404() && ! genosys_home_has_first_slider() ) { $classes[] = 'genosys-home-standard-header'; }
    return $classes;
}
add_filter( 'body_class', 'genosys_configured_home_body_class' );

/** Apply display-only preferences after the existing catalog query has been built. */
function genosys_configured_shop_page_size( $query ) {
    if ( ! is_admin() && $query instanceof WP_Query && $query->is_main_query() && $query->get( 'genosys_shop_query' ) ) {
        $query->set( 'posts_per_page', max( 3, min( 48, (int) genosys_site_setting( 'shop_per_page', 12 ) ) ) );
    }
}
add_action( 'pre_get_posts', 'genosys_configured_shop_page_size', 99 );

/** Keep desktop, banner and mobile navigation preferences in their own contexts. */
function genosys_header_preferences_css( $color, $primary ) {
    $css = '';
    // A home page without a leading slider uses the same header as internal pages.
    if ( is_front_page() && ! is_404() && ! genosys_home_has_first_slider() ) {
        $css .= 'body.home .site-header--standard{position:relative;background:#fff;color:#3b4553;}body.home .site-header--standard .header-actions .nav-icon-button{background:#f4f4f4;color:#3b4553;}';
        $css .= '@media(min-width:821px){body.home .site-header--standard .primary-menu>li>a{color:#3b4553;}body.home .site-header--standard .primary-menu>li>a:hover,body.home .site-header--standard .primary-menu>li>a:focus-visible,body.home .site-header--standard .primary-menu>.current-menu-item>a,body.home .site-header--standard .primary-menu>.current-menu-ancestor>a{color:#c8103b;}body.home .site-header--standard .primary-menu>li>a::after{background:#c8103b;}}';
    }
    $icon_hover = $color( 'header_icon_hover' );
    foreach ( array( 'home' => 'banner', 'internal' => 'standard' ) as $context => $class ) {
        $scope = 'body .site-header.site-header--' . $class;
        $background = $color( 'header_background_' . $context );
        if ( $background ) { $css .= $scope . '{background:' . $background . ';}'; }
        $text = $color( 'header_link_color_' . $context );
        $hover = $color( 'header_link_hover_' . $context );
        $hover = $hover ?: ( $primary ?: ( 'home' === $context ? '#f2a5b8' : '#c8103b' ) );
        if ( $text || $color( 'header_link_hover_' . $context ) || $primary ) {
            $link = $scope . ' .primary-menu>li:not(.menu-item-login)>a:not(.nav-login)';
            $states = $link . ':hover,' . $link . ':focus-visible,' . $scope . ' .primary-menu>li.current-menu-item>a:not(.nav-login),' . $scope . ' .primary-menu>li.current-menu-ancestor>a:not(.nav-login)';
            $css .= '@media(min-width:821px){';
            if ( $text ) { $css .= $link . '{color:' . $text . ';-webkit-text-fill-color:currentColor;background-image:none;}'; }
            $css .= $states . '{color:' . $hover . ';-webkit-text-fill-color:currentColor;background-image:none;}' . $link . '::after{background:' . $hover . ';}';
            $css .= '}';
        }
        $icon = $color( 'header_icon_color_' . $context );
        // The signed-in account button keeps its contrasting white expanded state.
        $buttons = array( $scope . ' .header-actions .nav-icon-button:not(.nav-account-toggle)', $scope . ' .header-actions .nav-account-toggle[aria-expanded="false"]', $scope . ' .header-actions .nav-account-menu--guest .nav-account-toggle[aria-expanded]' );
        if ( $icon ) { $css .= implode( ',', $buttons ) . '{color:' . $icon . ';}'; }
        if ( $icon || $icon_hover || $primary ) {
            $hover_color = $icon_hover ?: ( $primary ?: ( 'home' === $context ? '#f2a5b8' : '#c8103b' ) );
            $states = array();
            foreach ( $buttons as $button ) { $states[] = $button . ':hover'; $states[] = $button . ':focus-visible'; }
            $css .= implode( ',', $states ) . '{color:' . $hover_color . ';}';
        }
        // The menu button is separate from the hidden desktop basket on mobile.
        if ( $icon || $icon_hover ) {
            $css .= '@media(max-width:820px){';
            if ( $icon ) { $css .= $scope . ' .menu-toggle{color:' . $icon . ';}'; }
            $css .= $scope . ' .menu-toggle:hover,' . $scope . ' .menu-toggle:focus-visible{color:' . ( $icon_hover ?: '#c8103b' ) . ';}}';
        }
    }
    $mobile_background = $color( 'mobile_menu_background' );
    $mobile_text = $color( 'mobile_menu_color' );
    $mobile_hover = $color( 'mobile_menu_hover' );
    if ( $mobile_background || $mobile_text || $mobile_hover || $primary ) {
        $scope = 'body .site-header .primary-navigation';
        $link = $scope . ' .primary-menu a:not(.nav-login)';
        $css .= '@media(max-width:820px){';
        if ( $mobile_background ) { $css .= '.js ' . $scope . '{background:' . $mobile_background . ';}'; }
        if ( $mobile_text ) { $css .= $link . ',' . $scope . ' .mobile-menu-close{color:' . $mobile_text . ';-webkit-text-fill-color:currentColor;background-image:none;}'; }
        if ( $mobile_text || $mobile_hover || $primary ) {
            $hover = $mobile_hover ?: ( $primary ?: '#efafc0' );
            $css .= $link . ':hover,' . $link . ':focus-visible,' . $scope . ' .primary-menu .current-menu-item>a:not(.nav-login),' . $scope . ' .primary-menu .current-menu-ancestor>a:not(.nav-login),' . $scope . ' .mobile-menu-close:hover,' . $scope . ' .mobile-menu-close:focus-visible{color:' . $hover . ';-webkit-text-fill-color:currentColor;}';
        }
        $css .= '}';
    }
    return $css;
}

/** New commerce/account components share the deliberately edited brand palette. */
function genosys_brand_preferences_css( $primary, $deep, $defaults ) {
    if ( ! $primary && ! $deep ) { return ''; }
    $css = '';
    $modules = array(
        '.gaccount' => array( '--gaccount-red', '--gaccount-deep' ),
        '.gorders' => array( '--gorders-crimson', '--gorders-deep' ),
        '.genosys-shop' => array( '--shop-crimson', '--shop-deep' ),
        '.genosys-checkout' => array( '--gco-crimson', '--gco-deep-red' ),
        '.gco-receipt-upload' => array( '--gcr-crimson', '' ),
    );
    foreach ( $modules as $selector => $variables ) {
        $css .= 'body ' . $selector . '{';
        if ( $primary ) { $css .= $variables[0] . ':' . $primary . ';'; }
        if ( $deep && $variables[1] ) { $css .= $variables[1] . ':' . $deep . ';'; }
        $css .= '}';
    }
    $main = $primary ?: $defaults['primary_color'];
    $dark = $deep ?: $defaults['deep_color'];
    $buttons = array(
        'body .home-feature-button', 'body .genosys-categories__button', 'body .genosys-featured-products__view-all',
        'body .genosys-featured-products__tab.is-active', 'body .fans-club-button--primary', 'body .fans-club-audience button[aria-selected="true"]',
        'body .genosys-cart-page .genosys-cart-summary__checkout', 'body .genosys-checkout #place_order', 'body .genosys-login__primary',
        'body.single-product .genosys-product-page .genosys-product__info form.cart .single_add_to_cart_button.button.alt',
        'body.single-product .genosys-product-page .genosys-product__info form.cart .single_add_to_cart_button',
        'body.single-product .genosys-product-page .woocommerce-tabs #review_form input[type="submit"]',
        'body .gaccount .gaccount__button--primary', 'body .gaccount .gaccount__avatar',
        'body .gaccount .gaccount__badge[data-membership="member"]', 'body .gaccount .gaccount__nav-item.is-active', 'body .gaccount .gaccount__nav-item[aria-current="page"]',
        'body .gorders .gorders__filter.is-active', 'body .gorders .gorders__filter[aria-current="page"]',
        'body .gorders .gorder__toggle[aria-expanded="true"]', 'body .gorders .gorder__receipt-picker', 'body .gorders .gorder__receipt-icon',
        'body .product-card__badge:not(.product-card__badge--stock)',
    );
    $states = array();
    foreach ( $buttons as $button ) { $states[] = $button; $states[] = $button . ':hover'; $states[] = $button . ':focus-visible'; }
    $css .= implode( ',', $states ) . '{background-image:linear-gradient(115deg,' . $main . ',' . $dark . ');}';
    if ( $primary ) {
        $css .= 'body .genosys-categories__intro h2 span,body .genosys-featured-products__heading em,body .genosys-featured-products__categories-link,body .products-view-all,body .fans-club-hero h1 span:not(.fans-club-title-context),body .fans-club-hero__eyebrow,body .gaccount .gaccount__button:not(.gaccount__button--primary),body .gaccount .gaccount__nav-item:not(.is-active):not([aria-current="page"]) svg{color:' . $primary . ';}';
        $css .= 'body .gaccount .gaccount__button:not(.gaccount__button--primary){border-color:' . $primary . ';}body .gaccount .gaccount__card-header h2::before,body .gaccount .gaccount__nav-count,body .nav-count-badge,body .nav-guest-indicator,body .mobile-menu-commerce__count{background:' . $primary . ';}';
        $css .= 'body .product-favorite[aria-pressed="true"]{background:' . $primary . ';border-color:' . $primary . ';}';
    }
    return $css;
}

/** Only edited presentation values override the installed storefront design. */
function genosys_site_settings_css() {
    $defaults = genosys_site_defaults();
    $deep_token = sanitize_hex_color( genosys_site_setting( 'deep_color', $defaults['deep_color'] ) ) ?: $defaults['deep_color'];
    $css = ':root{--deep:' . $deep_token . ';}';
    $saved = get_option( 'genosys_site_settings', array() );
    $saved = is_array( $saved ) ? $saved : array();
    $color = static function ( $key ) use ( $defaults, $saved ) {
        $value = genosys_site_setting( $key );
        // A saved navbar value is authoritative, even when it equals the field
        // default. Untouched installs still retain their original banner styles.
        $explicit_navigation = ( 0 === strpos( $key, 'header_' ) || 0 === strpos( $key, 'mobile_menu_' ) ) && array_key_exists( $key, $saved );
        return is_string( $value ) && ( $explicit_navigation || $value !== $defaults[ $key ] ) && sanitize_hex_color( $value ) ? $value : '';
    };
    $primary = $color( 'primary_color' );
    $deep = $color( 'deep_color' );
    if ( $primary ) {
        $css .= ':root{--accent:' . $primary . ';}.genosys-footer__headline em{color:' . $primary . ';}.genosys-footer__filled-button{background:' . $primary . ';border-color:' . $primary . ';}';
    }
    if ( $primary || $deep ) {
        $gradient_primary = $primary ?: $defaults['primary_color'];
        $gradient_deep = $deep ?: $defaults['deep_color'];
        $css .= '.home-feature-button,.primary-menu .nav-login{background-image:linear-gradient(120deg,' . $gradient_primary . ',' . $gradient_deep . ',' . $gradient_primary . ');}';
        $css .= '.nav-account-dropdown .account-panel-login{background-image:linear-gradient(110deg,' . $gradient_primary . ',' . $gradient_deep . ');}';
        $css .= '.mobile-menu-commerce__card--login,.mobile-menu-commerce__card--login:hover,.mobile-menu-commerce__card--login:focus-visible{background-image:linear-gradient(115deg,' . $gradient_primary . ',' . $gradient_deep . ');}';
    }
    if ( $deep ) { $css .= '.genosys-footer__filled-button:hover{background:' . $deep . ';border-color:' . $deep . ';}'; }
    $css .= genosys_brand_preferences_css( $primary, $deep, $defaults );
    $height = (int) genosys_site_setting( 'header_height', 100 );
    if ( 100 !== $height ) {
        $height = max( 76, min( 180, $height ) );
        $css .= '@media(min-width:821px){.site-header .header-inner{min-height:' . $height . 'px;padding-block:' . min( 12, max( 0, ( $height - 58 ) / 2 ) ) . 'px;}}';
    }
    $css .= genosys_header_preferences_css( $color, $primary );
    $slides = genosys_site_setting( 'slides' );
    foreach ( $slides as $slide ) {
        if ( is_array( $slide ) && ( ! empty( $slide['title'] ) || ! empty( $slide['link'] ) ) ) {
            $css .= '.hero-slide .genosys-slide-link{position:absolute;inset:0;z-index:2;display:block;}.hero-slide .genosys-slide-caption{position:absolute;inset:auto clamp(28px,7vw,120px) 16%;z-index:3;margin:0;color:#fff;font-size:clamp(24px,4vw,52px);font-weight:700;text-shadow:0 2px 18px #0009;pointer-events:none;}.hero-slide:focus-within{outline:3px solid #fff;outline-offset:-6px;}';
            break;
        }
    }
    foreach ( array( 'social_instagram', 'social_telegram', 'social_whatsapp', 'social_linkedin' ) as $social ) {
        if ( genosys_site_setting( $social, '' ) ) { $css .= '.genosys-footer__social-icon{text-decoration:none;}'; break; }
    }
    if ( $css ) { echo '<style id="genosys-site-preferences">' . $css . '</style>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Colors and numeric values are validated above; remaining CSS is fixed.
}
add_action( 'wp_head', 'genosys_site_settings_css', 99 );

/** An empty product label deliberately retains WooCommerce and plugin translations. */
function genosys_configured_related_title( $title ) {
    $configured = genosys_site_setting( 'product_related_title', '' );
    return is_string( $configured ) && '' !== trim( $configured ) ? $configured : $title;
}
add_filter( 'woocommerce_product_related_products_heading', 'genosys_configured_related_title', 50 );

function genosys_configured_product_tab_titles( $tabs ) {
    foreach ( array( 'description' => 'product_description_title', 'additional_information' => 'product_information_title', 'reviews' => 'product_reviews_title' ) as $tab => $key ) {
        $configured = genosys_site_setting( $key, '' );
        if ( isset( $tabs[ $tab ] ) && is_string( $configured ) && '' !== trim( $configured ) ) { $tabs[ $tab ]['title'] = $configured; }
    }
    return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'genosys_configured_product_tab_titles', 99 );
/** Change the storefront title only after the store name is deliberately edited. */
function genosys_configured_document_title( $parts ) {
    $name = genosys_site_setting( 'site_name', 'ژنوسیس' );
    if ( is_string( $name ) && '' !== trim( $name ) && $name !== genosys_site_defaults()['site_name'] ) {
        if ( is_front_page() ) { $parts['title'] = $name; } else { $parts['site'] = $name; }
    }
    return $parts;
}
add_filter( 'document_title_parts', 'genosys_configured_document_title', 99 );

function genosys_site_footer_tagline() {
    $defaults = genosys_site_defaults();
    $tagline = genosys_site_setting( 'footer_tagline' );
    $slogan = genosys_site_setting( 'site_slogan' );
    return $tagline === $defaults['footer_tagline'] && $slogan !== $defaults['site_slogan'] ? 'GENOSYS - ' . $slogan : $tagline;
}
