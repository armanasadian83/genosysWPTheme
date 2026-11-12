<?php
/** Theme settings and their WooCommerce shipping rate. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize a delivery price without changing the store's currency unit.
 * An empty value means WooCommerce's existing shipping setup remains in use.
 *
 * @param mixed $value Submitted option value.
 * @return string
 */
function genosys_sanitize_shipping_cost( $value ) {
	$previous = get_option( 'genosys_shipping_cost', '' );
	if ( ! is_scalar( $value ) ) {
		add_settings_error( 'genosys_shipping_cost', 'invalid_shipping_cost', 'هزینه ارسال باید یک عدد معتبر و غیرمنفی باشد.', 'error' );
		return (string) $previous;
	}

	$amount = trim( (string) $value );
	if ( '' === $amount ) {
		return '';
	}

	$amount = strtr(
		$amount,
		array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
			'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
			'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
			'٫' => '.',
		)
	);
	$amount = preg_replace( '/[\s\x{00A0}\x{200C}\x{202F},٬،]+/u', '', $amount );
	if ( ! preg_match( '/^\d+(?:\.\d+)?$/D', $amount ) ) {
		add_settings_error( 'genosys_shipping_cost', 'invalid_shipping_cost', 'هزینه ارسال باید یک عدد معتبر و غیرمنفی باشد.', 'error' );
		return (string) $previous;
	}

	list( $whole, $fraction ) = array_pad( explode( '.', $amount, 2 ), 2, '' );
	$whole    = ltrim( $whole, '0' );
	$whole    = '' === $whole ? '0' : $whole;
	$fraction = rtrim( $fraction, '0' );
	$decimals = function_exists( 'wc_get_price_decimals' ) ? max( 0, (int) wc_get_price_decimals() ) : 2;
	if ( strlen( $whole ) > 15 || strlen( $fraction ) > $decimals ) {
		add_settings_error( 'genosys_shipping_cost', 'invalid_shipping_cost', 'مبلغ واردشده بیش از حد بزرگ است یا رقم اعشار آن با تنظیمات فروشگاه سازگار نیست.', 'error' );
		return (string) $previous;
	}

	return $whole . ( '' === $fraction ? '' : '.' . $fraction );
}

/** Keep bank-transfer details editable without publishing sample card numbers. */
function genosys_checkout_ascii_digits( $value ) {
	return strtr( (string) $value, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
		'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
		'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	) );
}

function genosys_sanitize_checkout_card_number( $value ) {
	$number = preg_replace( '/[\s\-]+/u', '', genosys_checkout_ascii_digits( $value ) );
	if ( '' === $number || preg_match( '/^\d{16}$/D', $number ) ) {
		return $number;
	}
	add_settings_error( 'genosys_checkout_card_number', 'invalid_card_number', 'شماره کارت باید ۱۶ رقم باشد.', 'error' );
	return (string) get_option( 'genosys_checkout_card_number', '' );
}

function genosys_sanitize_checkout_whatsapp( $value ) {
	$number = preg_replace( '/[\s()\-]+/u', '', genosys_checkout_ascii_digits( $value ) );
	if ( '' === $number || preg_match( '/^(?:\+?98|0)?9\d{9}$/D', $number ) ) {
		return $number;
	}
	add_settings_error( 'genosys_checkout_whatsapp', 'invalid_whatsapp', 'شماره واتساپ را به شکل 09… یا +989… وارد کنید.', 'error' );
	return (string) get_option( 'genosys_checkout_whatsapp', '' );
}

function genosys_sanitize_checkout_name( $value ) {
	$name = sanitize_text_field( (string) $value );
	return function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 100 ) : substr( $name, 0, 100 );
}

/** A blank order template disables SMS; invalid values preserve the saved ID. */
function genosys_sanitize_order_sms_template_id( $value ) {
	$previous = get_option( 'genosys_order_sms_template_id', '' );
	if ( is_scalar( $value ) && ! is_bool( $value ) ) {
		$id = ltrim( genosys_checkout_ascii_digits( trim( (string) $value ) ), '0' );
		if ( '' === trim( (string) $value ) ) {
			return '';
		}
		if ( preg_match( '/^[1-9][0-9]{0,9}$/D', $id ) && (float) $id <= 2147483647 ) {
			return $id;
		}
	}
	add_settings_error( 'genosys_order_sms_template_id', 'invalid_order_sms_template_id', 'شناسه قالب پیامک ثبت سفارش باید یک عدد صحیح مثبت باشد. برای غیرفعال کردن ارسال، کادر را خالی بگذارید.', 'error' );
	return is_scalar( $previous ) ? (string) $previous : '';
}

/** Keep status notifications independently configurable from submission SMS. */
function genosys_sanitize_order_status_sms_template_id( $value ) {
	$previous = get_option( 'genosys_order_status_sms_template_id', '' );
	if ( is_scalar( $value ) && ! is_bool( $value ) ) {
		$id = ltrim( genosys_checkout_ascii_digits( trim( (string) $value ) ), '0' );
		if ( '' === trim( (string) $value ) ) {
			return '';
		}
		if ( preg_match( '/^[1-9][0-9]{0,9}$/D', $id ) && (float) $id <= 2147483647 ) {
			return $id;
		}
	}
	add_settings_error( 'genosys_order_status_sms_template_id', 'invalid_order_status_sms_template_id', 'شناسه قالب پیامک تغییر وضعیت سفارش باید یک عدد صحیح مثبت باشد. برای غیرفعال کردن ارسال، کادر را خالی بگذارید.', 'error' );
	return is_scalar( $previous ) ? (string) $previous : '';
}

/** Validate the two receipt templates without changing other SMS settings. */
function genosys_sanitize_receipt_sms_template_id( $value, $status ) {
	$option = 'genosys_receipt_' . $status . '_sms_template_id';
	$previous = get_option( $option, '' );
	if ( is_scalar( $value ) && ! is_bool( $value ) ) {
		$id = ltrim( genosys_checkout_ascii_digits( trim( (string) $value ) ), '0' );
		if ( '' === trim( (string) $value ) ) {
			return '';
		}
		if ( preg_match( '/^[1-9][0-9]{0,9}$/D', $id ) && (float) $id <= 2147483647 ) {
			return $id;
		}
	}
	$label = 'approved' === $status ? 'تایید فیش' : 'رد فیش';
	add_settings_error( $option, 'invalid_receipt_' . $status . '_sms_template_id', 'شناسه قالب پیامک ' . $label . ' باید یک عدد صحیح مثبت باشد. برای غیرفعال کردن ارسال، کادر را خالی بگذارید.', 'error' );
	return is_scalar( $previous ) ? (string) $previous : '';
}

function genosys_sanitize_receipt_approved_sms_template_id( $value ) {
	return genosys_sanitize_receipt_sms_template_id( $value, 'approved' );
}

function genosys_sanitize_receipt_rejected_sms_template_id( $value ) {
	return genosys_sanitize_receipt_sms_template_id( $value, 'rejected' );
}

/** Register the setting with WordPress' capability and nonce-protected settings form. */
function genosys_register_theme_settings() {
	register_setting(
		'genosys_theme_settings',
		'genosys_shipping_cost',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'genosys_sanitize_shipping_cost',
			'default'           => '',
		)
	);
	register_setting( 'genosys_theme_settings', 'genosys_checkout_card_number', array( 'type' => 'string', 'sanitize_callback' => 'genosys_sanitize_checkout_card_number', 'default' => '' ) );
	register_setting( 'genosys_theme_settings', 'genosys_checkout_card_holder', array( 'type' => 'string', 'sanitize_callback' => 'genosys_sanitize_checkout_name', 'default' => '' ) );
	register_setting( 'genosys_theme_settings', 'genosys_checkout_bank_name', array( 'type' => 'string', 'sanitize_callback' => 'genosys_sanitize_checkout_name', 'default' => '' ) );
	register_setting( 'genosys_theme_settings', 'genosys_checkout_whatsapp', array( 'type' => 'string', 'sanitize_callback' => 'genosys_sanitize_checkout_whatsapp', 'default' => '' ) );
	register_setting( 'genosys_theme_settings', 'genosys_order_sms_template_id', array( 'type' => 'string', 'sanitize_callback' => 'genosys_sanitize_order_sms_template_id', 'default' => '' ) );
	register_setting( 'genosys_theme_settings', 'genosys_order_status_sms_template_id', array( 'type' => 'string', 'sanitize_callback' => 'genosys_sanitize_order_status_sms_template_id', 'default' => '' ) );
	register_setting( 'genosys_theme_settings', 'genosys_receipt_approved_sms_template_id', array( 'type' => 'string', 'sanitize_callback' => 'genosys_sanitize_receipt_approved_sms_template_id', 'default' => '' ) );
	register_setting( 'genosys_theme_settings', 'genosys_receipt_rejected_sms_template_id', array( 'type' => 'string', 'sanitize_callback' => 'genosys_sanitize_receipt_rejected_sms_template_id', 'default' => '' ) );
}
add_action( 'admin_init', 'genosys_register_theme_settings' );

function genosys_add_theme_settings_page() {
	add_menu_page(
		'تنظیمات قالب',
		'تنظیمات قالب',
		'manage_options',
		'genosys-theme-settings',
		'genosys_render_theme_settings_page',
		'dashicons-admin-generic',
		61
	);
}
add_action( 'admin_menu', 'genosys_add_theme_settings_page' );

/**
 * Apply the configured amount to WooCommerce's enabled flat-rate method.
 * The regular WooCommerce rate calculation still determines shipping taxes.
 */
function genosys_flat_rate_cost( $args, $method ) {
	$cost = get_option( 'genosys_shipping_cost', '' );
	if ( ! is_object( $method ) || ! isset( $method->id ) || 'flat_rate' !== $method->id || ! method_exists( $method, 'get_rate_id' ) || ! isset( $args['id'] ) || $args['id'] !== $method->get_rate_id() || '' === $cost || ! is_numeric( $cost ) || (float) $cost < 0 ) {
		return $args;
	}

	$args['cost'] = (string) $cost;
	return $args;
}
add_filter( 'woocommerce_shipping_method_add_rate_args', 'genosys_flat_rate_cost', 10, 2 );

/**
 * A WooCommerce flat-rate method with an empty native cost creates no rate.
 * Add its existing rate when the theme setting supplies a cost instead.
 */
function genosys_add_configured_flat_rate( $method, $rate ) {
	$cost = get_option( 'genosys_shipping_cost', '' );
	if ( ! is_object( $method ) || ! isset( $method->id, $method->rates ) || 'flat_rate' !== $method->id || ! is_array( $rate ) || ! isset( $rate['id'] ) || '' === $cost || ! is_numeric( $cost ) || (float) $cost < 0 ) {
		return;
	}

	if ( ! isset( $method->rates[ $rate['id'] ] ) ) {
		$rate['cost'] = (string) $cost;
		$method->add_rate( $rate );
	}
}
add_action( 'woocommerce_flat_rate_shipping_add_rate', 'genosys_add_configured_flat_rate', 10, 2 );

/**
 * Offer the saved delivery cost as a real WooCommerce rate in the Iran zone.
 * The store currently uses Free Shipping there, so a flat-rate-only override
 * cannot affect the cart or checkout totals. Keep coupon-granted free shipping
 * and any existing paid or pickup methods available.
 *
 * @param array $rates   Calculated WooCommerce rates for one package.
 * @param array $package The package and its destination.
 * @return array
 */
function genosys_configured_delivery_rate( $rates, $package ) {
	$cost = get_option( 'genosys_shipping_cost', '' );
	if ( ! is_array( $rates ) || ! is_array( $package ) || ! class_exists( 'WC_Shipping_Rate' ) || '' === $cost || ! is_numeric( $cost ) || (float) $cost < 0 ) {
		return $rates;
	}

	$country = isset( $package['destination']['country'] ) ? strtoupper( (string) $package['destination']['country'] ) : '';
	if ( '' !== $country && 'IR' !== $country ) {
		return $rates;
	}

	$has_free_rate = false;
	$has_delivery  = false;
	foreach ( $rates as $rate ) {
		if ( ! $rate instanceof WC_Shipping_Rate ) {
			continue;
		}
		$method_id = $rate->get_method_id();
		$has_free_rate = $has_free_rate || 'free_shipping' === $method_id;
		$has_delivery  = $has_delivery || ! in_array( $method_id, array( 'free_shipping', 'local_pickup', 'legacy_local_pickup' ), true );
	}

	// An explicit free-shipping coupon must keep WooCommerce's existing behavior.
	if ( $has_free_rate && function_exists( 'WC' ) && WC()->cart && method_exists( WC()->cart, 'get_coupons' ) ) {
		foreach ( WC()->cart->get_coupons() as $coupon ) {
			if ( is_object( $coupon ) && method_exists( $coupon, 'get_free_shipping' ) && $coupon->get_free_shipping() ) {
				return $rates;
			}
		}
	}

	// An enabled Flat Rate already receives the saved cost via the hooks above.
	if ( ! $has_free_rate && $has_delivery ) {
		return $rates;
	}

	foreach ( $rates as $rate_id => $rate ) {
		if ( $rate instanceof WC_Shipping_Rate && 'free_shipping' === $rate->get_method_id() ) {
			unset( $rates[ $rate_id ] );
		}
	}

	$taxes = array();
	if ( function_exists( 'wc_tax_enabled' ) && wc_tax_enabled() && class_exists( 'WC_Tax' ) && function_exists( 'WC' ) && WC()->customer && ! WC()->customer->get_is_vat_exempt() ) {
		$taxes = WC_Tax::calc_shipping_tax( (float) $cost, WC_Tax::get_shipping_tax_rates() );
	}

	$rate = new WC_Shipping_Rate( 'genosys_delivery', 'هزینه ارسال', (float) $cost, $taxes, 'genosys_delivery' );
	return array( $rate->get_id() => $rate ) + $rates;
}
add_filter( 'woocommerce_package_rates', 'genosys_configured_delivery_rate', 10, 2 );

/** Recalculate an existing cart's cached rates when this shipping fix is installed. */
function genosys_shipping_package_cache_key( $packages ) {
	$cost = get_option( 'genosys_shipping_cost', '' );
	if ( ! is_array( $packages ) || '' === $cost ) {
		return $packages;
	}

	foreach ( $packages as &$package ) {
		if ( is_array( $package ) ) {
			$package['genosys_delivery_rate_version'] = '1:' . $cost;
		}
	}
	unset( $package );
	return $packages;
}
add_filter( 'woocommerce_cart_shipping_packages', 'genosys_shipping_package_cache_key' );

/** Recalculate WooCommerce's cached shipping rates after this setting changes. */
function genosys_refresh_shipping_cache_for_setting( $option ) {
	if ( 'genosys_shipping_cost' === $option && class_exists( 'WC_Cache_Helper' ) ) {
		WC_Cache_Helper::get_transient_version( 'shipping', true );
	}
}
add_action( 'updated_option', 'genosys_refresh_shipping_cache_for_setting' );
add_action( 'added_option', 'genosys_refresh_shipping_cache_for_setting' );

function genosys_render_theme_settings_page() {
	genosys_render_settings_dashboard();
}
