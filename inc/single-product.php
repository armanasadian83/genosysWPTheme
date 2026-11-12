<?php
/** Product-only WooCommerce hooks for the Genosys single-product layout. */

defined( 'ABSPATH' ) || exit;

/** Change only the related-products row size; WooCommerce still selects its products. */
function genosys_single_product_related_count( $args ) {
	$args['posts_per_page'] = max( 1, min( 12, (int) genosys_site_setting( 'product_related_count', 4 ) ) );
	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'genosys_single_product_related_count', 99 );

/**
 * Keep WooCommerce's native product template lifecycle, cart forms and tabs.
 * Change presentation hooks only on actual single-product requests.
 */
function genosys_single_product_hooks() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	// Use a dedicated page width and the real WooCommerce breadcrumb.
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	add_action( 'woocommerce_before_main_content', 'genosys_single_product_wrapper_start', 10 );
	add_action( 'woocommerce_after_main_content', 'genosys_single_product_wrapper_end', 10 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

	// The stock/gallery badge replaces WooCommerce's unstyled sale flash.
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
	add_action( 'woocommerce_before_single_product_summary', 'genosys_single_product_badge', 5 );

	// Reorder only the visual information: category, name, excerpt, then price.
	add_action( 'woocommerce_single_product_summary', 'genosys_single_product_category', 1 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	add_action( 'woocommerce_single_product_summary', 'genosys_single_product_price', 25 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	add_action( 'woocommerce_single_product_summary', 'genosys_single_product_sku', 29 );
}
add_action( 'wp', 'genosys_single_product_hooks' );

function genosys_single_product_wrapper_start() {
	echo '<main id="main" class="genosys-product-page" tabindex="-1">';
}

function genosys_single_product_wrapper_end() {
	echo '</main>';
}

/** Show a category from the current product, preferring a real category. */
function genosys_single_product_category() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return;
	}

	$default_id = (int) get_option( 'default_product_cat' );
	$chosen     = null;
	foreach ( $terms as $term ) {
		if ( (int) $term->term_id !== $default_id ) {
			$chosen = $term;
			break;
		}
	}
	if ( ! $chosen ) {
		$chosen = reset( $terms );
	}

	$link = get_term_link( $chosen );
	if ( is_wp_error( $link ) ) {
		return;
	}
	echo '<a class="genosys-product__category" href="' . esc_url( $link ) . '">' . esc_html( $chosen->name ) . '</a>';
}

/** Keep WooCommerce's formatted price data, including variable ranges and sale markup. */
function genosys_single_product_price() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$is_available = $product->is_in_stock();
	$price_html   = genosys_product_price_html( $product );
	$discount     = genosys_product_discount( $product );
	$show_sale    = $is_available && $product->is_on_sale();

	echo '<div class="genosys-product__price" data-product-type="' . esc_attr( $product->get_type() ) . '">';
	echo '<p class="price' . ( $is_available ? '' : ' genosys-product__price--unavailable' ) . '">';
	if ( ! $is_available ) {
		echo esc_html( 'تماس بگیرید' );
	} elseif ( $price_html ) {
		echo wp_kses_post( $price_html );
	} else {
		echo esc_html( 'اطلاعات قیمت موجود نیست' );
	}
	echo '</p>';
	echo '<span class="genosys-product__discount"' . ( $show_sale ? '' : ' hidden' ) . '>';
	echo $show_sale ? esc_html( $discount ? genosys_persian_number( $discount ) . '٪ تخفیف' : 'تخفیف' ) : '';
	echo '</span>';
	echo '</div>';
}

/** Actual stock/sale state from WooCommerce, layered over its real gallery. */
function genosys_single_product_badge() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$is_in_stock = $product->is_in_stock();
	$is_on_sale  = $is_in_stock && $product->is_on_sale();
	$discount    = $is_on_sale ? genosys_product_discount( $product ) : 0;
	$label       = ! $is_in_stock ? 'ناموجود' : ( $is_on_sale ? ( $discount ? genosys_persian_number( $discount ) . '٪ تخفیف' : 'تخفیف' ) : '' );
	$classes     = 'genosys-product__gallery-badge' . ( $is_in_stock ? '' : ' genosys-product__gallery-badge--stock' );
	echo '<span class="' . esc_attr( $classes ) . '"' . ( $label ? '' : ' hidden' ) . '>' . esc_html( $label ) . '</span>';
}

/** SKU is shown only when it exists; native Woo variation JS can update it. */
function genosys_single_product_sku() {
	global $product;
	if ( ! $product instanceof WC_Product || ! wc_product_sku_enabled() ) {
		return;
	}

	$sku = $product->get_sku();
	if ( ! $sku && ! $product->is_type( 'variable' ) ) {
		return;
	}

	echo '<div class="product_meta genosys-product__sku" data-base-sku="' . esc_attr( $sku ) . '">';
	do_action( 'woocommerce_product_meta_start' );
	echo '<span class="sku_wrapper"' . ( $sku ? '' : ' hidden' ) . '>' . esc_html( 'شناسه محصول: ' ) . '<span class="sku">' . esc_html( $sku ) . '</span></span>';
	do_action( 'woocommerce_product_meta_end' );
	echo '</div>';
}

/** Label WooCommerce's existing tabs without changing their callbacks or data. */
function genosys_single_product_tab_labels( $tabs ) {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return $tabs;
	}
	if ( isset( $tabs['description'] ) ) {
		$tabs['description']['title'] = 'توضیحات';
	}
	if ( isset( $tabs['additional_information'] ) ) {
		$tabs['additional_information']['title'] = 'توضیحات تکمیلی';
	}
	if ( isset( $tabs['reviews'] ) ) {
		global $product;
		$count = $product instanceof WC_Product ? $product->get_review_count() : 0;
		$tabs['reviews']['title'] = 'نظرات (' . genosys_persian_number( $count ) . ')';
	}
	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'genosys_single_product_tab_labels', 50 );

/** Use the current product's name as the description section heading. */
function genosys_single_product_description_heading( $heading ) {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return $heading;
	}
	global $product;
	return $product instanceof WC_Product ? $product->get_name() : $heading;
}
add_filter( 'woocommerce_product_description_heading', 'genosys_single_product_description_heading' );

/** Keep the native review form, rating input and validation; style its wording. */
function genosys_single_product_review_form( $args ) {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return $args;
	}
	$args['label_submit'] = 'ثبت';
	if ( ! empty( $args['comment_field'] ) ) {
		$args['comment_field'] = preg_replace_callback(
			'/<textarea\b[^>]*>/i',
			function ( $match ) {
				return false !== stripos( $match[0], 'placeholder=' ) ? $match[0] : substr( $match[0], 0, -1 ) . ' placeholder="دیدگاه شما">';
			},
			$args['comment_field'],
			1
		);
	}
	return $args;
}
add_filter( 'woocommerce_product_review_comment_form_args', 'genosys_single_product_review_form', 20 );

/** Match the review heading in both WooCommerce's empty and populated states. */
function genosys_single_product_reviews_title( $title ) {
	return function_exists( 'is_product' ) && is_product() ? 'نقد و بررسی‌ها' : $title;
}
add_filter( 'woocommerce_reviews_title', 'genosys_single_product_reviews_title' );

function genosys_single_product_empty_reviews_title( $translation, $text ) {
	return function_exists( 'is_product' ) && is_product() && 'Reviews' === $text ? 'نقد و بررسی‌ها' : $translation;
}
add_filter( 'gettext_woocommerce', 'genosys_single_product_empty_reviews_title', 10, 2 );
