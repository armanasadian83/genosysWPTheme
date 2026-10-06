<?php
/** Product presentation helpers. WooCommerce remains the only product source. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function genosys_latest_products() {
    if ( ! function_exists( 'wc_get_products' ) ) {
        return array();
    }
    // Query product objects through WooCommerce's data store; never duplicate catalog data.
    $query = array(
        'status' => 'publish',
        'limit' => 8,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
        'visibility' => 'catalog',
    );
    if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
        $query['stock_status'] = array( 'instock', 'onbackorder' );
    }
    return wc_get_products( $query );
}

function genosys_product_discount( $product ) {
    if ( ! $product->is_on_sale() ) { return 0; }
    // A variable/grouped product can have different discounts: show a truthful sale label.
    if ( ! $product->is_type( array( 'simple', 'external' ) ) ) { return 0; }
    $regular = (float) $product->get_regular_price();
    $current = (float) $product->get_price();
    return $regular > 0 && $current < $regular ? (int) round( 100 * ( $regular - $current ) / $regular ) : 0;
}

function genosys_persian_number( $number ) {
    return strtr( (string) $number, array( '0'=>'۰', '1'=>'۱', '2'=>'۲', '3'=>'۳', '4'=>'۴', '5'=>'۵', '6'=>'۶', '7'=>'۷', '8'=>'۸', '9'=>'۹' ) );
}

function genosys_product_price_html( $product ) {
    // Localize visible numerals only; retain WooCommerce prices, currencies and markup.
    $parts = wp_html_split( $product->get_price_html() );
    foreach ( $parts as $index => $part ) {
        if ( '' !== $part && '<' !== $part[0] ) {
            $parts[ $index ] = esc_html( genosys_persian_number( html_entity_decode( $part, ENT_QUOTES, 'UTF-8' ) ) );
        }
    }
    return implode( '', $parts );
}
