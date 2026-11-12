<?php
/** AJAX bridge for the single-product basket animation, using WooCommerce's form handler. */

defined( 'ABSPATH' ) || exit;

/**
 * The animation endpoint is processed after wp_loaded. Do not let a native form
 * field accidentally submit the cart once before the endpoint validates it.
 */
function genosys_cart_animation_defer_native_form() {
	if ( isset( $_GET['wc-ajax'] ) && is_string( $_GET['wc-ajax'] ) && 'genosys_product_add_to_cart' === wp_unslash( $_GET['wc-ajax'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		remove_action( 'wp_loaded', array( 'WC_Form_Handler', 'add_to_cart_action' ), 20 );
	}
}
add_action( 'wp_loaded', 'genosys_cart_animation_defer_native_form', 19 );

/** Keep the usual WooCommerce fragments available to other cart components. */
function genosys_cart_animation_fragments() {
	ob_start();
	woocommerce_mini_cart();
	$mini_cart = ob_get_clean();

	return apply_filters(
		'woocommerce_add_to_cart_fragments',
		array( 'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>' )
	);
}

/**
 * Add the exact submitted product; the client animates only confirmed additions.
 * WC AJAX runs after WooCommerce has initialized the customer session and cart.
 */
function genosys_cart_animation_add_to_cart() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) {
		wp_send_json_error( array( 'message' => 'درخواست نامعتبر است.' ), 405 );
	}

	$nonce = isset( $_POST['security'] ) && is_string( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'genosys_product_add_to_cart' ) ) {
		wp_send_json_error( array( 'message' => 'این صفحه منقضی شده است. صفحه را تازه کنید و دوباره تلاش کنید.' ), 403 );
	}
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->session || ! class_exists( 'WC_Form_Handler' ) ) {
		wp_send_json_error( array( 'message' => 'سبد خرید در دسترس نیست. صفحه را تازه کنید.' ), 503 );
	}

	$product_id = isset( $_POST['genosys_product_id'] ) && is_scalar( $_POST['genosys_product_id'] ) ? absint( wp_unslash( $_POST['genosys_product_id'] ) ) : 0;
	$product    = $product_id ? wc_get_product( $product_id ) : false;
	if ( ! $product || ! $product->is_type( array( 'simple', 'variable', 'variation', 'genosys_package' ) ) ) {
		wp_send_json_error( array( 'message' => 'محصول انتخاب‌شده برای این درخواست معتبر نیست.' ), 400 );
	}
	if ( isset( $_POST['quantity'] ) && ! is_scalar( $_POST['quantity'] ) ) {
		wp_send_json_error( array( 'message' => 'تعداد محصول معتبر نیست.' ), 400 );
	}
	if ( isset( $_POST['variation_id'] ) && ! is_scalar( $_POST['variation_id'] ) ) {
		wp_send_json_error( array( 'message' => 'گزینه‌های محصول معتبر نیست.' ), 400 );
	}
	foreach ( $_POST as $key => $value ) {
		if ( 0 === strpos( $key, 'attribute_' ) && ! is_scalar( $value ) ) {
			wp_send_json_error( array( 'message' => 'گزینه‌های محصول معتبر نیست.' ), 400 );
		}
	}

	$previous_notices = wc_get_notices();
	wc_clear_notices();
	$was_added = false;
	$unexpected_error = false;
	$track_addition = function () use ( &$was_added ) {
		$was_added = true;
	};
	$no_redirect = function () {
		return false;
	};
	$no_cart_redirect = function () {
		return 'no';
	};
	$had_post_id    = array_key_exists( 'add-to-cart', $_POST );
	$had_request_id = array_key_exists( 'add-to-cart', $_REQUEST );
	$old_post_id    = $had_post_id ? $_POST['add-to-cart'] : null;
	$old_request_id = $had_request_id ? $_REQUEST['add-to-cart'] : null;
	$_POST['add-to-cart']    = (string) $product_id;
	$_REQUEST['add-to-cart'] = (string) $product_id;

	add_filter( 'woocommerce_add_to_cart_redirect', $no_redirect, PHP_INT_MAX );
	add_filter( 'pre_option_woocommerce_cart_redirect_after_add', $no_cart_redirect, PHP_INT_MAX );
	add_action( 'woocommerce_add_to_cart', $track_addition, PHP_INT_MAX, 0 );
	$buffer_level = ob_get_level();
	ob_start();
	try {
		// Preserves Woo's simple/variable validation, attributes, stock and extension hooks.
		WC_Form_Handler::add_to_cart_action();
		if ( $was_added && ! wc_notice_count( 'error' ) ) {
			do_action( 'woocommerce_ajax_added_to_cart', $product_id );
		}
	} catch ( Throwable $error ) {
		$unexpected_error = true;
		wc_add_notice( 'نتیجه افزودن محصول مشخص نشد. پیش از تلاش دوباره، سبد خرید را بررسی کنید.', 'error' );
	} finally {
		while ( ob_get_level() > $buffer_level ) {
			ob_end_clean();
		}
		remove_filter( 'woocommerce_add_to_cart_redirect', $no_redirect, PHP_INT_MAX );
		remove_filter( 'pre_option_woocommerce_cart_redirect_after_add', $no_cart_redirect, PHP_INT_MAX );
		remove_action( 'woocommerce_add_to_cart', $track_addition, PHP_INT_MAX );
		if ( $had_post_id ) {
			$_POST['add-to-cart'] = $old_post_id;
		} else {
			unset( $_POST['add-to-cart'] );
		}
		if ( $had_request_id ) {
			$_REQUEST['add-to-cart'] = $old_request_id;
		} else {
			unset( $_REQUEST['add-to-cart'] );
		}
	}

	$errors  = wc_get_notices( 'error' );
	$success = $was_added && ! $unexpected_error && empty( $errors );
	$message = $success ? 'به سبد خرید اضافه شد' : 'افزودن محصول انجام نشد. گزینه‌ها و تعداد محصول را بررسی کنید.';
	if ( ! empty( $errors ) ) {
		$message = implode( ' ', array_map( function ( $notice ) {
			return html_entity_decode( wp_strip_all_tags( $notice['notice'] ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		}, $errors ) );
	}
	$notices = wc_print_notices( true );
	// The current response shows its own notices; preserve notices from earlier requests.
	wc_set_notices( $previous_notices );
	$data = array(
		'message'    => $message,
		'notices'    => $notices,
		'cart_count' => WC()->cart->get_cart_contents_count(),
		'cart_hash'  => WC()->cart->get_cart_hash(),
		'cart_url'   => wc_get_cart_url(),
		'fragments'  => array(),
	);
	if ( $success ) {
		// Cart persistence must not be reported as failed because a fragment renderer fails.
		$buffer_level = ob_get_level();
		try {
			$data['fragments'] = genosys_cart_animation_fragments();
		} catch ( Throwable $error ) {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
		}
		wp_send_json_success( $data );
	}

	wp_send_json_error( $data, $unexpected_error ? 500 : 400 );
}
add_action( 'wc_ajax_genosys_product_add_to_cart', 'genosys_cart_animation_add_to_cart' );
