<?php
/**
 * Read-only order access for verified phone owners and the guest checkout browser.
 * Guest verification is purpose-bound and never signs in or creates an account.
 *
 * @package Genosys
 */

defined( 'ABSPATH' ) || exit;

/** The browser holds an opaque bearer token; personal data stays on the server. */
function genosys_order_access_cookie_name() {
	return 'genosys_order_access';
}

function genosys_order_access_token() {
	$name  = genosys_order_access_cookie_name();
	$token = isset( $_COOKIE[ $name ] ) && is_string( $_COOKIE[ $name ] ) ? wp_unslash( $_COOKIE[ $name ] ) : '';
	return preg_match( '/^[a-f0-9]{64}$/', $token ) ? $token : '';
}

function genosys_order_access_record_key( $token ) {
	return 'genosys_order_access_browser_' . genosys_sms_fingerprint( 'order-browser:' . $token );
}

function genosys_order_access_set_cookie( $token, $expires ) {
	if ( headers_sent() ) {
		return false;
	}
	$stored = setcookie( genosys_order_access_cookie_name(), $token, array(
		'expires'  => (int) $expires,
		'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
		'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	) );
	if ( $stored ) {
		$_COOKIE[ genosys_order_access_cookie_name() ] = $token;
	}
	return $stored;
}

/** Do not extend an expired proof merely because its cookie remains present. */
function genosys_order_access_record( $create = false ) {
	$token  = genosys_order_access_token();
	$record = $token ? get_transient( genosys_order_access_record_key( $token ) ) : false;
	if ( is_array( $record ) && isset( $record['expires'], $record['orders'] ) && (int) $record['expires'] > time() && is_array( $record['orders'] ) ) {
		return $record;
	}
	if ( ! $create || headers_sent() ) {
		return false;
	}
	$token  = bin2hex( random_bytes( 32 ) );
	$record = array( 'expires' => time() + 30 * DAY_IN_SECONDS, 'orders' => array(), 'phone' => '', 'phone_expires' => 0, 'phone_user_id' => 0 );
	if ( ! set_transient( genosys_order_access_record_key( $token ), $record, 30 * DAY_IN_SECONDS ) || ! genosys_order_access_set_cookie( $token, $record['expires'] ) ) {
		delete_transient( genosys_order_access_record_key( $token ) );
		return false;
	}
	return $record;
}

function genosys_order_access_save_record( $record, $renew = false ) {
	$token = genosys_order_access_token();
	if ( ! $token || ! is_array( $record ) || empty( $record['expires'] ) ) {
		return false;
	}
	$key     = genosys_order_access_record_key( $token );
	$current = get_transient( $key );
	// A concurrent clear/revocation must not be undone by an older request.
	if ( ! is_array( $current ) || empty( $current['expires'] ) || (int) $current['expires'] <= time() ) {
		return false;
	}
	if ( $renew ) {
		$record['expires'] = time() + 30 * DAY_IN_SECONDS;
	}
	$ttl = (int) $record['expires'] - time();
	if ( $ttl <= 0 ) {
		return false;
	}
	$saved = set_transient( $key, $record, $ttl );
	// WordPress also returns false for an unchanged value.
	if ( ! $saved && get_transient( $key ) !== $record ) {
		return false;
	}
	return ! $renew || genosys_order_access_set_cookie( $token, $record['expires'] );
}

/** Serialize short browser-record writes, never the external SMS request. */
function genosys_order_access_browser_lock( $token ) {
	for ( $attempt = 0; $attempt < 8; $attempt++ ) {
		$lock = genosys_sms_claim_lock( 'order-access-browser:' . $token );
		if ( $lock ) {
			return $lock;
		}
		usleep( 25000 );
	}
	return false;
}

function genosys_order_access_mutate_record( $callback, $renew = false ) {
	$token = genosys_order_access_token();
	$lock  = $token ? genosys_order_access_browser_lock( $token ) : false;
	if ( ! $lock ) {
		return false;
	}
	try {
		$record = genosys_order_access_record();
		if ( ! $record ) {
			return false;
		}
		$updated = call_user_func( $callback, $record );
		return is_array( $updated ) && genosys_order_access_save_record( $updated, $renew );
	} finally {
		delete_option( $lock );
	}
}

/** A nonce from another guest's cached page cannot authenticate this browser. */
function genosys_order_access_nonce_action() {
	$token = genosys_order_access_token();
	return $token ? 'genosys_order_access_' . genosys_sms_fingerprint( 'order-nonce:' . $token ) : '';
}

function genosys_order_access_client_config() {
	$record = genosys_order_access_record( true );
	return array(
		'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
		'nonce'         => $record ? wp_create_nonce( genosys_order_access_nonce_action() ) : '',
		'requestAction' => 'genosys_order_access_request',
		'verifyAction'  => 'genosys_order_access_verify',
		'clearAction'   => 'genosys_order_access_clear',
	);
}

/** Billing phone is editable contact information, never an account credential. */
function genosys_order_access_phone() {
	$user_id = get_current_user_id();
	if ( is_user_logged_in() ) {
		$phone = genosys_sms_normalize_phone( (string) get_user_meta( $user_id, 'genosys_phone', true ) );
		if ( $phone ) {
			return $phone;
		}
	}
	$record = genosys_order_access_record();
	// A guest or another account's phone proof is never inherited after sign-in/out.
	return $record && ! empty( $record['phone_expires'] ) && (int) $record['phone_expires'] > time() && isset( $record['phone'], $record['phone_user_id'] ) && (int) $record['phone_user_id'] === $user_id ? genosys_sms_normalize_phone( $record['phone'] ) : '';
}

function genosys_order_access_valid_order( $order ) {
	return $order instanceof WC_Order && $order->get_id() > 0 && 'shop_order' === $order->get_type() && ! in_array( $order->get_status(), array( 'draft', 'auto-draft', 'checkout-draft', 'trash' ), true );
}

/** All phone and browser grants are limited to orders that still belong to a guest. */
function genosys_order_access_can_view( $order ) {
	if ( ! genosys_order_access_valid_order( $order ) ) {
		return false;
	}
	$owner = (int) $order->get_customer_id();
	if ( $owner > 0 ) {
		return is_user_logged_in() && get_current_user_id() === $owner;
	}
	$phone = genosys_order_access_phone();
	if ( $phone && $phone === genosys_sms_normalize_phone( (string) $order->get_billing_phone() ) ) {
		return true;
	}
	if ( function_exists( 'genosys_account_phone_historical_order_allowed' ) && genosys_account_phone_historical_order_allowed( $order ) ) { return true; }
	$record = genosys_order_access_record();
	$grant  = $record && isset( $record['orders'][ $order->get_id() ] ) ? $record['orders'][ $order->get_id() ] : null;
	return is_array( $grant ) && isset( $grant['key'], $grant['expires'] ) && is_string( $grant['key'] ) && '' !== $grant['key'] && (int) $grant['expires'] > time() && hash_equals( (string) $order->get_order_key(), $grant['key'] );
}

function genosys_order_access_order_ids() {
	$record = genosys_order_access_record();
	$ids    = array();
	if ( ! $record || ! function_exists( 'wc_get_order' ) ) {
		return $ids;
	}
	foreach ( array_slice( $record['orders'], -50, null, true ) as $id => $grant ) {
		if ( ! is_array( $grant ) || empty( $grant['expires'] ) || (int) $grant['expires'] <= time() ) {
			continue;
		}
		$order = wc_get_order( absint( $id ) );
		if ( $order && 0 === (int) $order->get_customer_id() && genosys_order_access_can_view( $order ) ) {
			$ids[] = (int) $order->get_id();
		}
	}
	return array_values( array_unique( $ids ) );
}

function genosys_order_access_has_proof() {
	return is_user_logged_in() || '' !== genosys_order_access_phone() || (bool) genosys_order_access_order_ids();
}

/** Only server-side checkout data or a constant-time validated order key creates a grant. */
function genosys_order_access_grant_order( $order ) {
	if ( ! genosys_order_access_valid_order( $order ) || 0 !== (int) $order->get_customer_id() || '' === (string) $order->get_order_key() ) {
		return false;
	}
	$record = genosys_order_access_record( true );
	if ( ! $record ) {
		return false;
	}
	return genosys_order_access_mutate_record( function ( $current ) use ( $order ) {
		$id = (int) $order->get_id();
		unset( $current['orders'][ $id ] );
		$current['orders'][ $id ] = array( 'key' => (string) $order->get_order_key(), 'expires' => time() + 30 * DAY_IN_SECONDS );
		$current['orders']        = array_slice( $current['orders'], -50, null, true );
		return $current;
	}, true );
}

function genosys_order_access_classic_checkout( $order_id, $posted_data, $order ) {
	if ( ! $order instanceof WC_Order && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( $order_id );
	}
	genosys_order_access_grant_order( $order );
}
add_action( 'woocommerce_checkout_order_processed', 'genosys_order_access_classic_checkout', 2, 3 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'genosys_order_access_grant_order', 2, 1 );

/** Run before WooCommerce clears order_awaiting_payment on the received page. */
function genosys_order_access_bootstrap() {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$orders_page = function_exists( 'genosys_is_customer_orders' ) && genosys_is_customer_orders();
	$received    = function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' );
	$checkout    = function_exists( 'is_checkout' ) && is_checkout();
	if ( ! $orders_page && ! $received && ! $checkout ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	if ( $received ) {
		global $wp;
		$order_id = isset( $wp->query_vars['order-received'] ) ? absint( $wp->query_vars['order-received'] ) : absint( get_query_var( 'order-received', 0 ) );
		$key      = isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? wp_unslash( $_GET['key'] ) : '';
		$order    = $order_id ? wc_get_order( $order_id ) : null;
		if ( genosys_order_access_valid_order( $order ) && '' !== $key && hash_equals( (string) $order->get_order_key(), $key ) ) {
			genosys_order_access_grant_order( $order );
		}
	}
	// The Woo session is server-side and bound to its own signed checkout cookie.
	$record = genosys_order_access_record();
	if ( ( $orders_page || $received ) && empty( $record['ignore_checkout_session'] ) && function_exists( 'WC' ) && WC()->session && WC()->session->has_session() ) {
		foreach ( array( 'order_awaiting_payment', 'store_api_draft_order' ) as $session_key ) {
			$order_id = absint( WC()->session->get( $session_key, 0 ) );
			if ( $order_id ) {
				genosys_order_access_grant_order( wc_get_order( $order_id ) );
			}
		}
	}
	// Establish a stable token before the first checkout AJAX request/two tabs.
	if ( $orders_page || $checkout ) {
		genosys_order_access_record( true );
	}
}
add_action( 'template_redirect', 'genosys_order_access_bootstrap', 0 );

function genosys_order_access_request_string( $key ) {
	return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
}

function genosys_order_access_validate_ajax() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || ! genosys_order_access_record() || ! check_ajax_referer( genosys_order_access_nonce_action(), 'nonce', false ) ) {
		genosys_sms_json_error( 'invalid_request', 'صفحه را تازه‌سازی کنید و دوباره تلاش کنید.', 403 );
	}
}

function genosys_order_access_otp_key( $request_id ) {
	return 'genosys_order_access_otp_' . $request_id;
}

function genosys_order_access_active_key( $phone ) {
	return 'genosys_order_access_active_' . genosys_sms_fingerprint( $phone );
}

function genosys_order_access_delete_otp( $request_id, $phone ) {
	delete_transient( genosys_order_access_otp_key( $request_id ) );
	$key = genosys_order_access_active_key( $phone );
	if ( $request_id === get_transient( $key ) ) {
		delete_transient( $key );
	}
}

/** Reuse the existing provider and its global phone/IP limits, with a separate OTP purpose. */
function genosys_order_access_ajax_request() {
	genosys_order_access_validate_ajax();
	$phone = genosys_sms_normalize_phone( genosys_order_access_request_string( 'phone' ) );
	if ( ! $phone ) {
		genosys_sms_json_error( 'invalid_phone', 'شماره تماس معتبر نیست (مثال: 09123456789).' );
	}
	if ( '' === genosys_sms_api_key() || ! genosys_sms_template_id() ) {
		genosys_sms_json_error( 'not_configured', 'ارسال کد در حال حاضر در دسترس نیست. لطفاً بعداً تلاش کنید.', 503 );
	}
	$retry = genosys_sms_rate_error( $phone );
	if ( $retry > 0 ) {
		genosys_sms_json_error( 'rate_limited', 'برای دریافت کد بعدی کمی صبر کنید.', 429, array( 'retry_after' => $retry ) );
	}
	$ip_lock = genosys_sms_claim_lock( 'ip:' . genosys_sms_client_ip() );
	if ( ! $ip_lock ) {
		genosys_sms_json_error( 'busy', 'چند لحظه صبر کنید و دوباره تلاش کنید.', 429, array( 'retry_after' => 5 ) );
	}
	$phone_lock = genosys_sms_claim_lock( 'phone:' . $phone );
	if ( ! $phone_lock ) {
		delete_option( $ip_lock );
		genosys_sms_json_error( 'busy', 'چند لحظه صبر کنید و دوباره تلاش کنید.', 429, array( 'retry_after' => 5 ) );
	}
	$result     = null;
	$request_id = '';
	try {
		$retry = genosys_sms_rate_error( $phone );
		if ( $retry > 0 ) {
			$result = new WP_Error( 'rate_limited', 'برای دریافت کد بعدی کمی صبر کنید.', array( 'status' => 429, 'retry_after' => $retry ) );
		} else {
			$code       = (string) random_int( 100000, 999999 );
			$request_id = bin2hex( random_bytes( 16 ) );
			$sent       = genosys_smsir_send_code( $phone, $code );
			if ( is_wp_error( $sent ) ) {
				$result = new WP_Error( 'sms_unavailable', 'ارسال کد انجام نشد. لطفاً دوباره تلاش کنید.', array( 'status' => 503 ) );
			} else {
				$phone_hash = genosys_sms_fingerprint( $phone );
				$ip_hash    = genosys_sms_fingerprint( genosys_sms_client_ip() );
				set_transient( 'genosys_sms_cool_' . $phone_hash, time() + 120, 120 );
				genosys_sms_increment_counter( 'genosys_sms_day_' . $phone_hash, DAY_IN_SECONDS );
				genosys_sms_increment_counter( 'genosys_sms_hour_' . $ip_hash, HOUR_IN_SECONDS );
				$otp = array(
					'purpose'  => 'orders',
					'browser'  => genosys_sms_fingerprint( 'order-browser:' . genosys_order_access_token() ),
					'user_id'  => get_current_user_id(),
					'phone'    => $phone,
					'hash'     => hash_hmac( 'sha256', 'genosys-order-access:' . $phone . ':' . $code, wp_salt( 'auth' ) ),
					'expires'  => time() + 120,
					'attempts' => 0,
				);
				$active_key = genosys_order_access_active_key( $phone );
				$old_id     = get_transient( $active_key );
				if ( ! set_transient( genosys_order_access_otp_key( $request_id ), $otp, 120 ) || ! set_transient( $active_key, $request_id, 120 ) ) {
					genosys_order_access_delete_otp( $request_id, $phone );
					$result = new WP_Error( 'storage_unavailable', 'ارسال کد در حال حاضر در دسترس نیست. لطفاً بعداً تلاش کنید.', array( 'status' => 503 ) );
				} elseif ( is_string( $old_id ) && preg_match( '/^[a-f0-9]{32}$/', $old_id ) ) {
					delete_transient( genosys_order_access_otp_key( $old_id ) );
				}
			}
		}
	} catch ( Throwable $exception ) {
		$result = new WP_Error( 'sms_unavailable', 'ارسال کد انجام نشد. لطفاً دوباره تلاش کنید.', array( 'status' => 503 ) );
	} finally {
		delete_option( $phone_lock );
		delete_option( $ip_lock );
	}
	if ( is_wp_error( $result ) ) {
		$data = $result->get_error_data();
		genosys_sms_json_error( $result->get_error_code(), $result->get_error_message(), isset( $data['status'] ) ? $data['status'] : 400, isset( $data['retry_after'] ) ? array( 'retry_after' => $data['retry_after'] ) : array() );
	}
	wp_send_json_success( array( 'request_id' => $request_id, 'expires_in' => 120 ) );
}
add_action( 'wp_ajax_nopriv_genosys_order_access_request', 'genosys_order_access_ajax_request' );
add_action( 'wp_ajax_genosys_order_access_request', 'genosys_order_access_ajax_request' );

function genosys_order_access_ajax_verify() {
	genosys_order_access_validate_ajax();
	$request_id = genosys_order_access_request_string( 'request_id' );
	if ( ! preg_match( '/^[a-f0-9]{32}$/', $request_id ) ) {
		genosys_sms_json_error( 'invalid_code', 'کد تأیید معتبر نیست یا منقضی شده است.' );
	}
	$lock = genosys_sms_claim_lock( 'order-access-otp:' . $request_id );
	if ( ! $lock ) {
		genosys_sms_json_error( 'busy', 'چند لحظه صبر کنید و دوباره تلاش کنید.', 429 );
	}
	$result = new WP_Error( 'invalid_code', 'کد تأیید معتبر نیست یا منقضی شده است.' );
	$phone  = '';
	try {
		$otp     = get_transient( genosys_order_access_otp_key( $request_id ) );
		$browser = genosys_sms_fingerprint( 'order-browser:' . genosys_order_access_token() );
		if ( is_array( $otp ) && isset( $otp['purpose'], $otp['browser'], $otp['user_id'], $otp['phone'], $otp['hash'], $otp['expires'], $otp['attempts'] ) && 'orders' === $otp['purpose'] && is_string( $otp['browser'] ) && hash_equals( $otp['browser'], $browser ) && (int) $otp['user_id'] === get_current_user_id() && (int) $otp['expires'] > time() && (int) $otp['attempts'] < 5 && $request_id === get_transient( genosys_order_access_active_key( $otp['phone'] ) ) ) {
			$code = strtr( trim( genosys_order_access_request_string( 'code' ) ), array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9' ) );
			$otp['attempts']++;
			if ( ! set_transient( genosys_order_access_otp_key( $request_id ), $otp, max( 1, (int) $otp['expires'] - time() ) ) ) {
				$result = new WP_Error( 'storage_unavailable', 'تأیید شماره انجام نشد. دوباره تلاش کنید.' );
			} elseif ( preg_match( '/^[0-9]{6}$/', $code ) && hash_equals( $otp['hash'], hash_hmac( 'sha256', 'genosys-order-access:' . $otp['phone'] . ':' . $code, wp_salt( 'auth' ) ) ) ) {
				$saved = genosys_order_access_mutate_record( function ( $current ) use ( $otp ) {
					$current['phone']         = $otp['phone'];
					$current['phone_expires'] = time() + 12 * HOUR_IN_SECONDS;
					$current['phone_user_id'] = get_current_user_id();
					return $current;
				}, true );
				if ( $saved ) {
					$phone  = $otp['phone'];
					$result = true;
					genosys_order_access_delete_otp( $request_id, $phone );
				} else {
					$result = new WP_Error( 'storage_unavailable', 'تأیید شماره انجام نشد. دوباره تلاش کنید.' );
				}
			} elseif ( (int) $otp['attempts'] >= 5 ) {
				genosys_order_access_delete_otp( $request_id, $otp['phone'] );
			}
		}
	} catch ( Throwable $exception ) {
		$result = new WP_Error( 'storage_unavailable', 'تأیید شماره انجام نشد. دوباره تلاش کنید.' );
	} finally {
		delete_option( $lock );
	}
	if ( is_wp_error( $result ) ) {
		genosys_sms_json_error( $result->get_error_code(), $result->get_error_message() );
	}
	wp_send_json_success( array( 'verified' => true, 'phone' => $phone, 'nonce' => wp_create_nonce( genosys_order_access_nonce_action() ) ) );
}
add_action( 'wp_ajax_nopriv_genosys_order_access_verify', 'genosys_order_access_ajax_verify' );
add_action( 'wp_ajax_genosys_order_access_verify', 'genosys_order_access_ajax_verify' );

/** Forget this browser's guest proof, without affecting WordPress authentication. */
function genosys_order_access_ajax_clear() {
	genosys_order_access_validate_ajax();
	$token = genosys_order_access_token();
	$lock  = genosys_order_access_browser_lock( $token );
	if ( ! $lock ) {
		genosys_sms_json_error( 'busy', 'چند لحظه صبر کنید و دوباره تلاش کنید.', 429 );
	}
	try {
		delete_transient( genosys_order_access_record_key( $token ) );
	} finally {
		delete_option( $lock );
	}
	unset( $_COOKIE[ genosys_order_access_cookie_name() ] );
	$record = genosys_order_access_record( true );
	if ( ! $record ) {
		genosys_sms_json_error( 'storage_unavailable', 'دوباره تلاش کنید.', 503 );
	}
	// A subsequent visit must not restore a proof the visitor deliberately forgot.
	if ( ! genosys_order_access_mutate_record( function ( $current ) {
		$current['ignore_checkout_session'] = true;
		return $current;
	} ) ) {
		genosys_sms_json_error( 'storage_unavailable', 'دوباره تلاش کنید.', 503 );
	}
	wp_send_json_success( array( 'verified' => false, 'phone' => '', 'nonce' => wp_create_nonce( genosys_order_access_nonce_action() ) ) );
}
add_action( 'wp_ajax_nopriv_genosys_order_access_clear', 'genosys_order_access_ajax_clear' );
add_action( 'wp_ajax_genosys_order_access_clear', 'genosys_order_access_ajax_clear' );
