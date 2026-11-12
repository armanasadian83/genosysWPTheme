<?php
/** SMS verification and customer sign-in for the standalone login page. */
defined( 'ABSPATH' ) || exit;

function genosys_sms_api_key() {
	if ( defined( 'GENOSYS_SMSIR_API_KEY' ) ) {
		return trim( (string) GENOSYS_SMSIR_API_KEY );
	}
	return trim( (string) get_option( 'genosys_smsir_api_key', '' ) );
}

function genosys_sms_template_id() {
	return absint( get_option( 'genosys_smsir_template_id', 163300 ) );
}

function genosys_sms_parameter_name() {
	$name = (string) get_option( 'genosys_smsir_parameter_name', 'CODE' );
	return preg_match( '/^[A-Za-z][A-Za-z0-9_]*$/', $name ) ? $name : 'CODE';
}

function genosys_sms_login_script_settings() {
	if ( ! genosys_is_login() ) {
		return;
	}
	wp_add_inline_script(
		'genosys-login',
		'window.genosysLogin = ' . wp_json_encode( array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'genosys_sms_login' ),
			'homeUrl' => home_url( '/' ),
			'returnUrl' => genosys_login_return_url( isset( $_GET['redirect_to'] ) && is_string( $_GET['redirect_to'] ) ? wp_unslash( $_GET['redirect_to'] ) : '' ),
		) ) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'genosys_sms_login_script_settings', 20 );

function genosys_sms_normalize_phone( $value ) {
	if ( ! is_string( $value ) ) {
		return '';
	}
	$digits = strtr( trim( $value ), array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
		'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
		'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	) );
	$digits = preg_replace( '/[^0-9+]/', '', $digits );
	if ( 0 === strpos( $digits, '+98' ) ) {
		$digits = '0' . substr( $digits, 3 );
	} elseif ( 0 === strpos( $digits, '0098' ) ) {
		$digits = '0' . substr( $digits, 4 );
	} elseif ( 0 === strpos( $digits, '98' ) ) {
		$digits = '0' . substr( $digits, 2 );
	}
	if ( preg_match( '/^9[0-9]{9}$/', $digits ) ) {
		$digits = '0' . $digits;
	}
	return preg_match( '/^09[0-9]{9}$/', $digits ) ? $digits : '';
}

function genosys_sms_fingerprint( $value ) {
	return hash_hmac( 'sha256', $value, wp_salt( 'nonce' ) );
}

function genosys_sms_client_ip() {
	// Do not trust user-controlled forwarding headers for rate limits.
	return isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
}

function genosys_sms_counter( $key ) {
	$value = get_transient( $key );
	if ( ! is_array( $value ) || empty( $value['reset'] ) || (int) $value['reset'] <= time() ) {
		return array( 'count' => 0, 'reset' => 0 );
	}
	return array( 'count' => isset( $value['count'] ) ? (int) $value['count'] : 0, 'reset' => (int) $value['reset'] );
}

function genosys_sms_increment_counter( $key, $period ) {
	$value = genosys_sms_counter( $key );
	$reset = $value['reset'] > time() ? $value['reset'] : time() + $period;
	set_transient( $key, array( 'count' => $value['count'] + 1, 'reset' => $reset ), max( 1, $reset - time() ) );
}

function genosys_sms_rate_error( $phone ) {
	$phone_hash = genosys_sms_fingerprint( $phone );
	$ip_hash    = genosys_sms_fingerprint( genosys_sms_client_ip() );
	$cooldown   = (int) get_transient( 'genosys_sms_cool_' . $phone_hash );
	if ( $cooldown > time() ) {
		return $cooldown - time();
	}
	$phone_count = genosys_sms_counter( 'genosys_sms_day_' . $phone_hash );
	if ( $phone_count['count'] >= 5 ) {
		return max( 1, $phone_count['reset'] - time() );
	}
	$ip_count = genosys_sms_counter( 'genosys_sms_hour_' . $ip_hash );
	if ( $ip_count['count'] >= 20 ) {
		return max( 1, $ip_count['reset'] - time() );
	}
	return 0;
}

function genosys_sms_claim_lock( $value ) {
	$key   = 'genosys_sms_lock_' . genosys_sms_fingerprint( $value );
	$until = time() + 25;
	if ( add_option( $key, $until, '', false ) ) {
		return $key;
	}
	if ( (int) get_option( $key, 0 ) <= time() ) {
		delete_option( $key );
		if ( add_option( $key, $until, '', false ) ) {
			return $key;
		}
	}
	return false;
}

function genosys_sms_json_error( $code, $message, $status = 400, $extra = array() ) {
	wp_send_json_error( array_merge( array( 'code' => $code, 'message' => $message ), $extra ), $status );
}

function genosys_sms_validate_ajax() {
	if ( is_user_logged_in() ) {
		genosys_sms_json_error( 'signed_in', 'شما هم‌اکنون وارد حساب خود شده‌اید.', 403 );
	}
	if ( ! check_ajax_referer( 'genosys_sms_login', 'nonce', false ) ) {
		genosys_sms_json_error( 'invalid_request', 'صفحه را تازه‌سازی کنید و دوباره تلاش کنید.', 403 );
	}
}

function genosys_smsir_send_code( $phone, $code ) {
	$api_key = genosys_sms_api_key();
	if ( '' === $api_key || ! genosys_sms_template_id() ) {
		return new WP_Error( 'not_configured', 'SMS service is not configured.' );
	}
	$response = wp_remote_post( 'https://api.sms.ir/v1/send/verify/', array(
		'timeout'     => 15,
		'redirection' => 0,
		'headers'     => array(
			'X-API-KEY'    => $api_key,
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		),
		'body'        => wp_json_encode( array(
			'mobile'     => $phone,
			'templateId' => genosys_sms_template_id(),
			'parameters' => array( array( 'name' => genosys_sms_parameter_name(), 'value' => $code ) ),
		) ),
	) );
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'sms_unavailable', 'SMS provider request failed.' );
	}
	$http_status = (int) wp_remote_retrieve_response_code( $response );
	$body        = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( $http_status < 200 || $http_status >= 300 || ! is_array( $body ) || ! isset( $body['status'] ) || 1 !== (int) $body['status'] || ! isset( $body['data']['messageId'] ) ) {
		return new WP_Error( 'sms_rejected', 'SMS provider rejected the request.' );
	}
	return true;
}

function genosys_sms_ajax_request() {
	genosys_sms_validate_ajax();
	$raw_phone = isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '';
	$phone     = genosys_sms_normalize_phone( $raw_phone );
	if ( '' === $phone ) {
		genosys_sms_json_error( 'invalid_phone', 'شماره تماس معتبر نیست (مثال: 09123456789).' );
	}
	if ( '' === genosys_sms_api_key() || ! genosys_sms_template_id() ) {
		genosys_sms_json_error( 'not_configured', 'ارسال کد در حال حاضر در دسترس نیست. لطفاً بعداً تلاش کنید.', 503 );
	}
	$retry_after = genosys_sms_rate_error( $phone );
	if ( $retry_after > 0 ) {
		genosys_sms_json_error( 'rate_limited', 'برای دریافت کد بعدی کمی صبر کنید.', 429, array( 'retry_after' => $retry_after ) );
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

	// Recheck after locking so parallel requests do not bypass the limits.
	$retry_after = genosys_sms_rate_error( $phone );
	if ( $retry_after > 0 ) {
		delete_option( $phone_lock );
		delete_option( $ip_lock );
		genosys_sms_json_error( 'rate_limited', 'برای دریافت کد بعدی کمی صبر کنید.', 429, array( 'retry_after' => $retry_after ) );
	}

	$code       = (string) random_int( 100000, 999999 );
	$request_id = bin2hex( random_bytes( 16 ) );
	$otp_key    = 'genosys_sms_otp_' . $request_id;
	$record     = array(
		'phone'   => $phone,
		'hash'    => hash_hmac( 'sha256', $phone . ':' . $code, wp_salt( 'auth' ) ),
		'expires' => 0,
		'attempts'=> 0,
	);
	$sent = genosys_smsir_send_code( $phone, $code );
	if ( is_wp_error( $sent ) ) {
		delete_option( $phone_lock );
		delete_option( $ip_lock );
		genosys_sms_json_error( 'sms_unavailable', 'ارسال کد انجام نشد. لطفاً دوباره تلاش کنید.', 503 );
	}
	$record['expires'] = time() + 120;
	if ( ! set_transient( $otp_key, $record, 120 ) ) {
		delete_option( $phone_lock );
		delete_option( $ip_lock );
		genosys_sms_json_error( 'storage_unavailable', 'ارسال کد در حال حاضر در دسترس نیست. لطفاً بعداً تلاش کنید.', 503 );
	}
	$phone_hash = genosys_sms_fingerprint( $phone );
	$ip_hash    = genosys_sms_fingerprint( genosys_sms_client_ip() );
	$active_key = 'genosys_sms_active_' . $phone_hash;
	$old_id     = get_transient( $active_key );
	if ( is_string( $old_id ) && preg_match( '/^[a-f0-9]{32}$/', $old_id ) ) {
		delete_transient( 'genosys_sms_otp_' . $old_id );
	}
	set_transient( $active_key, $request_id, 120 );
	set_transient( 'genosys_sms_cool_' . $phone_hash, time() + 120, 120 );
	genosys_sms_increment_counter( 'genosys_sms_day_' . $phone_hash, DAY_IN_SECONDS );
	genosys_sms_increment_counter( 'genosys_sms_hour_' . $ip_hash, HOUR_IN_SECONDS );
	delete_option( $phone_lock );
	delete_option( $ip_lock );

	wp_send_json_success( array( 'request_id' => $request_id, 'expires_in' => 120 ) );
}
add_action( 'wp_ajax_nopriv_genosys_sms_request', 'genosys_sms_ajax_request' );
add_action( 'wp_ajax_genosys_sms_request', 'genosys_sms_ajax_request' );

function genosys_sms_phone_variants( $phone ) {
	$local = substr( $phone, 1 );
	$base  = array( $phone, $local, '+98' . $local, '0098' . $local, '98' . $local );
	$variants = $base;
	$map   = array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
		'5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' );
	foreach ( $base as $variant ) {
		$variants[] = strtr( $variant, $map );
	}
	return array_values( array_unique( $variants ) );
}

function genosys_sms_can_use_legacy_phone( $user ) {
	$roles = (array) $user->roles;
	return $roles && ! array_diff( $roles, array( 'customer', 'subscriber' ) );
}

function genosys_sms_find_existing_user( $phone ) {
	$variants = genosys_sms_phone_variants( $phone );
	$candidates = array();
	$matches    = array();
	$unlinked_privileged = false;
	foreach ( $variants as $variant ) {
		$user = get_user_by( 'login', $variant );
		if ( $user && $phone === genosys_sms_normalize_phone( $user->user_login ) ) {
			$candidates[ $user->ID ] = $user;
		}
	}
	$meta_keys  = array( 'genosys_phone', 'billing_phone', 'mobile', 'phone_number' );
	$meta_query = array( 'relation' => 'OR' );
	foreach ( $meta_keys as $key ) {
		$meta_query[] = array( 'key' => $key, 'value' => $variants, 'compare' => 'IN' );
	}
	$ids = get_users( array( 'fields' => 'ID', 'number' => 50, 'count_total' => false, 'cache_results' => false, 'meta_query' => $meta_query ) );
	foreach ( $ids as $id ) {
		wp_cache_delete( (int) $id, 'user_meta' );
		foreach ( $meta_keys as $key ) {
			if ( $phone === genosys_sms_normalize_phone( (string) get_user_meta( $id, $key, true ) ) ) {
				$user = get_user_by( 'id', $id );
				if ( $user ) {
					$candidates[ $user->ID ] = $user;
				}
				break;
			}
		}
	}
	foreach ( $candidates as $user ) {
		wp_cache_delete( $user->ID, 'user_meta' );
		$enrolled = genosys_sms_normalize_phone( (string) get_user_meta( $user->ID, 'genosys_phone', true ) );
		if ( $enrolled ) {
			if ( $phone === $enrolled ) {
				$matches[ $user->ID ] = $user;
			}
		} elseif ( genosys_sms_can_use_legacy_phone( $user ) && ! get_user_meta( $user->ID, 'genosys_phone_changed_at', true ) ) {
			$matches[ $user->ID ] = $user;
		} else {
			$unlinked_privileged = true;
		}
	}
	if ( count( $matches ) > 1 || count( $ids ) >= 50 ) {
		return new WP_Error( 'ambiguous_phone', 'More than one account uses this phone.' );
	}
	if ( $unlinked_privileged && ! $matches ) {
		return new WP_Error( 'phone_not_enrolled', 'A privileged account must explicitly enroll this phone.' );
	}
	return $matches ? reset( $matches ) : null;
}

function genosys_sms_name_username( $first_name, $last_name, $current_id = 0 ) {
	$full_name = trim( $first_name . ' ' . $last_name );
	if ( '' === trim( $first_name ) || '' === trim( $last_name ) || preg_match( '/[|\x00-\x1F\x7F]/', $full_name ) ) {
		return new WP_Error( 'invalid_username', 'The customer name cannot be used as a username.' );
	}
	$user_login = $full_name;
	$suffix = 2;
	while ( $existing_id = username_exists( $user_login ) ) {
		if ( (int) $existing_id === (int) $current_id ) {
			break;
		}
		$user_login = $full_name . ' ' . $suffix++;
	}
	if ( mb_strlen( $user_login, 'UTF-8' ) > 60 ) {
		return new WP_Error( 'username_too_long', 'Full name exceeds the WordPress username limit.' );
	}
	return $user_login;
}

function genosys_sms_maybe_rename_legacy_customer( $user, $phone ) {
	if ( ! preg_match( '/^genosys_[a-f0-9]{16}$/', $user->user_login ) || array( 'customer' ) !== (array) $user->roles ) {
		return $user;
	}
	if ( $phone !== genosys_sms_normalize_phone( (string) get_user_meta( $user->ID, 'genosys_phone', true ) ) ) {
		return $user;
	}
	$new_login = genosys_sms_name_username( (string) $user->first_name, (string) $user->last_name, $user->ID );
	if ( is_wp_error( $new_login ) ) {
		return $user;
	}
	global $wpdb;
	$old_login = $user->user_login;
	clean_user_cache( $user );
	$updated = $wpdb->update( $wpdb->users, array( 'user_login' => $new_login ), array( 'ID' => $user->ID, 'user_login' => $old_login ), array( '%s' ), array( '%d', '%s' ) );
	clean_user_cache( $user->ID );
	if ( 1 !== $updated ) {
		return get_user_by( 'id', $user->ID ) ?: $user;
	}
	if ( $old_login === get_user_meta( $user->ID, 'nickname', true ) ) {
		update_user_meta( $user->ID, 'nickname', trim( $user->first_name . ' ' . $user->last_name ) );
	}
	return get_user_by( 'id', $user->ID ) ?: $user;
}

function genosys_sms_create_customer( $phone, $first_name, $last_name, $email = '' ) {
	if ( ! get_role( 'customer' ) ) {
		add_role( 'customer', 'مشتری', array( 'read' => true ) );
	}
	if ( ! get_role( 'customer' ) ) {
		return new WP_Error( 'missing_customer_role', 'Customer role is unavailable.' );
	}
	$user_login = genosys_sms_name_username( $first_name, $last_name );
	if ( is_wp_error( $user_login ) ) {
		return $user_login;
	}
	$full_name = trim( $first_name . ' ' . $last_name );
	$meta = array(
		'genosys_phone'      => $phone,
		'billing_phone'      => $phone,
		'billing_first_name' => $first_name,
		'billing_last_name'  => $last_name,
	);
	if ( '' !== $email ) {
		$meta['billing_email'] = $email;
	}
	// WordPress strips Persian letters when it first sanitizes a new user_login.
	// Restore this verified customer's full name only for this insert.
	$preserve_name = static function ( $login ) use ( $user_login ) {
		return $user_login;
	};
	add_filter( 'pre_user_login', $preserve_name, PHP_INT_MAX );
	try {
		$user_id = wp_insert_user( array(
			'user_login'    => $user_login,
			'user_nicename' => 'customer-' . bin2hex( random_bytes( 8 ) ),
			'user_pass'    => wp_generate_password( 40, true, true ),
			'user_email'   => $email,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'display_name' => $full_name,
			'nickname'     => $full_name,
			'role'         => 'customer',
			'meta_input'   => $meta,
		) );
	} finally {
		remove_filter( 'pre_user_login', $preserve_name, PHP_INT_MAX );
	}
	if ( is_wp_error( $user_id ) ) {
		return $user_id;
	}
	return get_user_by( 'id', $user_id );
}

function genosys_sms_profile_key( $token ) {
	return 'genosys_sms_profile_' . genosys_sms_fingerprint( $token );
}

function genosys_sms_ajax_verify() {
	genosys_sms_validate_ajax();
	$request_id = isset( $_POST['request_id'] ) && is_string( $_POST['request_id'] ) ? wp_unslash( $_POST['request_id'] ) : '';
	$raw_code   = isset( $_POST['code'] ) && is_string( $_POST['code'] ) ? wp_unslash( $_POST['code'] ) : '';
	$code       = strtr( $raw_code, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
		'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
		'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	) );
	if ( ! preg_match( '/^[a-f0-9]{32}$/', $request_id ) || ! preg_match( '/^[0-9]{6}$/', $code ) ) {
		genosys_sms_json_error( 'invalid_code', 'کد ۶ رقمی را کامل وارد کنید.' );
	}
	$verify_lock = genosys_sms_claim_lock( 'verify:' . $request_id );
	if ( ! $verify_lock ) {
		genosys_sms_json_error( 'busy', 'چند لحظه صبر کنید و دوباره تلاش کنید.', 429 );
	}
	$otp_key = 'genosys_sms_otp_' . $request_id;
	$record  = get_transient( $otp_key );
	if ( ! is_array( $record ) || empty( $record['phone'] ) || empty( $record['hash'] ) || empty( $record['expires'] ) || $record['expires'] <= time() ) {
		delete_option( $verify_lock );
		genosys_sms_json_error( 'expired_code', 'زمان اعتبار کد تمام شده است. کد جدید دریافت کنید.', 400 );
	}
	$active_key = 'genosys_sms_active_' . genosys_sms_fingerprint( $record['phone'] );
	if ( $request_id !== get_transient( $active_key ) ) {
		delete_option( $verify_lock );
		genosys_sms_json_error( 'expired_code', 'زمان اعتبار کد تمام شده است. کد جدید دریافت کنید.', 400 );
	}
	$expected = hash_hmac( 'sha256', $record['phone'] . ':' . $code, wp_salt( 'auth' ) );
	if ( ! hash_equals( $record['hash'], $expected ) ) {
		$record['attempts'] = isset( $record['attempts'] ) ? (int) $record['attempts'] + 1 : 1;
		if ( $record['attempts'] >= 5 ) {
			delete_transient( $otp_key );
			delete_transient( $active_key );
		} else {
			set_transient( $otp_key, $record, max( 1, $record['expires'] - time() ) );
		}
		delete_option( $verify_lock );
		genosys_sms_json_error( 'invalid_code', 'کد واردشده صحیح نیست.' );
	}

	// A phone change, registration, and login must resolve the same credential serially.
	$phone_lock = genosys_sms_claim_lock( 'phone:' . $record['phone'] );
	if ( ! $phone_lock ) {
		delete_option( $verify_lock );
		genosys_sms_json_error( 'busy', 'چند لحظه صبر کنید و دوباره تلاش کنید.', 429 );
	}
	if ( $record !== get_transient( $otp_key ) || $request_id !== get_transient( $active_key ) || $record['expires'] <= time() ) {
		delete_option( $phone_lock ); delete_option( $verify_lock );
		genosys_sms_json_error( 'expired_code', 'این کد دیگر فعال نیست. کد جدید دریافت کنید.', 400 );
	}
	// Consume the OTP before touching accounts or auth cookies; it cannot be replayed.
	delete_transient( $otp_key );
	delete_transient( $active_key );
	$user = genosys_sms_find_existing_user( $record['phone'] );
	if ( is_wp_error( $user ) ) {
		delete_option( $phone_lock );
		delete_option( $verify_lock );
		if ( 'phone_not_enrolled' === $user->get_error_code() ) {
			genosys_sms_json_error( 'phone_not_enrolled', 'این شماره برای ورود به حساب فعلی فعال نشده است. پس از ورود معمول به حساب، شماره ورود پیامکی را در تنظیمات ثبت کنید.', 409 );
		}
		genosys_sms_json_error( 'ambiguous_phone', 'این شماره به چند حساب متصل است. با پشتیبانی تماس بگیرید.', 409 );
	}
	if ( ! $user ) {
		// A verified new number receives a short-lived, single-use registration handoff.
		// The account and auth cookie are created only after the profile is completed.
		$token = bin2hex( random_bytes( 32 ) );
		$profile = array( 'phone' => $record['phone'], 'expires' => time() + 600 );
		if ( ! set_transient( genosys_sms_profile_key( $token ), $profile, 600 ) ) {
			delete_option( $phone_lock );
			delete_option( $verify_lock );
			genosys_sms_json_error( 'storage_unavailable', 'تأیید شماره انجام شد، اما تکمیل حساب در دسترس نیست. لطفاً دوباره تلاش کنید.', 503 );
		}
		delete_option( $phone_lock ); delete_option( $verify_lock );
		wp_send_json_success( array( 'needs_profile' => true, 'registration_token' => $token ) );
	}
	if ( is_wp_error( $user ) || ! $user ) {
		delete_option( $phone_lock );
		delete_option( $verify_lock );
		genosys_sms_json_error( 'account_unavailable', 'ورود انجام نشد. لطفاً دوباره تلاش کنید.', 503 );
	}
	if ( is_multisite() && ( ! empty( $user->spam ) || ! empty( $user->deleted ) ) ) {
		delete_option( $phone_lock );
		delete_option( $verify_lock );
		genosys_sms_json_error( 'account_unavailable', 'ورود به این حساب امکان‌پذیر نیست.', 403 );
	}
	$user = genosys_sms_maybe_rename_legacy_customer( $user, $record['phone'] );
	if ( ! get_user_meta( $user->ID, 'genosys_phone', true ) ) {
		add_user_meta( $user->ID, 'genosys_phone', $record['phone'], true );
	}
	wp_cache_delete( $user->ID, 'user_meta' );
	if ( $record['phone'] !== genosys_sms_normalize_phone( (string) get_user_meta( $user->ID, 'genosys_phone', true ) ) ) {
		delete_option( $phone_lock ); delete_option( $verify_lock );
		genosys_sms_json_error( 'account_unavailable', 'ثبت شماره ورود انجام نشد. دوباره تلاش کنید.', 503 );
	}
	wp_set_current_user( $user->ID, $user->user_login );
	wp_set_auth_cookie( $user->ID, true );
	do_action( 'wp_login', $user->user_login, $user );
	delete_option( $phone_lock );
	delete_option( $verify_lock );
	wp_send_json_success( array( 'redirect' => genosys_login_return_url( isset( $_POST['redirect_to'] ) && is_string( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '' ) ) );
}
add_action( 'wp_ajax_nopriv_genosys_sms_verify', 'genosys_sms_ajax_verify' );
add_action( 'wp_ajax_genosys_sms_verify', 'genosys_sms_ajax_verify' );

function genosys_sms_profile_error( $code, $message, $status, $profile_lock, $phone_lock = false, $extra = array() ) {
	if ( $phone_lock ) {
		delete_option( $phone_lock );
	}
	delete_option( $profile_lock );
	genosys_sms_json_error( $code, $message, $status, $extra );
}

function genosys_sms_ajax_complete() {
	genosys_sms_validate_ajax();
	$token = isset( $_POST['registration_token'] ) && is_string( $_POST['registration_token'] ) ? wp_unslash( $_POST['registration_token'] ) : '';
	if ( ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
		genosys_sms_json_error( 'invalid_registration', 'زمان تکمیل اطلاعات پایان یافته است. دوباره شماره خود را تأیید کنید.', 400 );
	}
	$profile_lock = genosys_sms_claim_lock( 'profile:' . $token );
	if ( ! $profile_lock ) {
		genosys_sms_json_error( 'busy', 'چند لحظه صبر کنید و دوباره تلاش کنید.', 429 );
	}
	$profile_key = genosys_sms_profile_key( $token );
	$profile = get_transient( $profile_key );
	if ( ! is_array( $profile ) || empty( $profile['phone'] ) || empty( $profile['expires'] ) || $profile['expires'] <= time() || $profile['phone'] !== genosys_sms_normalize_phone( $profile['phone'] ) ) {
		genosys_sms_profile_error( 'invalid_registration', 'زمان تکمیل اطلاعات پایان یافته است. دوباره شماره خود را تأیید کنید.', 400, $profile_lock );
	}

	$first_name = isset( $_POST['first_name'] ) && is_string( $_POST['first_name'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) ) : '';
	$last_name  = isset( $_POST['last_name'] ) && is_string( $_POST['last_name'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) ) : '';
	$raw_email  = isset( $_POST['email'] ) && is_string( $_POST['email'] ) ? trim( wp_unslash( $_POST['email'] ) ) : '';
	$email      = '' === $raw_email ? '' : sanitize_email( $raw_email );
	$first_len  = function_exists( 'mb_strlen' ) ? mb_strlen( $first_name, 'UTF-8' ) : strlen( $first_name );
	$last_len   = function_exists( 'mb_strlen' ) ? mb_strlen( $last_name, 'UTF-8' ) : strlen( $last_name );
	if ( '' === $first_name || $first_len > 80 ) {
		genosys_sms_profile_error( 'invalid_first_name', 'نام خود را وارد کنید (حداکثر ۸۰ نویسه).', 400, $profile_lock, false, array( 'field' => 'first_name' ) );
	}
	if ( preg_match( '/[|\x00-\x1F\x7F]/', $first_name ) ) {
		genosys_sms_profile_error( 'invalid_first_name', 'نام معتبر وارد کنید.', 400, $profile_lock, false, array( 'field' => 'first_name' ) );
	}
	if ( '' === $last_name || $last_len > 80 ) {
		genosys_sms_profile_error( 'invalid_last_name', 'نام خانوادگی خود را وارد کنید (حداکثر ۸۰ نویسه).', 400, $profile_lock, false, array( 'field' => 'last_name' ) );
	}
	if ( preg_match( '/[|\x00-\x1F\x7F]/', $last_name ) || mb_strlen( $first_name . ' ' . $last_name, 'UTF-8' ) > 60 ) {
		genosys_sms_profile_error( 'invalid_last_name', 'نام و نام خانوادگی باید کوتاه‌تر از ۶۰ نویسه باشند.', 400, $profile_lock, false, array( 'field' => 'last_name' ) );
	}
	if ( '' !== $raw_email && ( '' === $email || ! is_email( $email ) ) ) {
		genosys_sms_profile_error( 'invalid_email', 'ایمیل معتبر نیست.', 400, $profile_lock, false, array( 'field' => 'email' ) );
	}

	$phone_lock = genosys_sms_claim_lock( 'phone:' . $profile['phone'] );
	if ( ! $phone_lock ) {
		genosys_sms_profile_error( 'busy', 'چند لحظه صبر کنید و دوباره تلاش کنید.', 429, $profile_lock );
	}
	// Another flow may have claimed this number after its OTP was verified.
	$existing = genosys_sms_find_existing_user( $profile['phone'] );
	if ( is_wp_error( $existing ) || $existing ) {
		delete_transient( $profile_key );
		genosys_sms_profile_error( 'phone_in_use', 'این شماره به حسابی متصل شده است. لطفاً دوباره وارد شوید.', 409, $profile_lock, $phone_lock );
	}
	if ( '' !== $email && email_exists( $email ) ) {
		genosys_sms_profile_error( 'email_in_use', 'این ایمیل قبلاً ثبت شده است.', 409, $profile_lock, $phone_lock, array( 'field' => 'email' ) );
	}
	$user = genosys_sms_create_customer( $profile['phone'], $first_name, $last_name, $email );
	if ( is_wp_error( $user ) || ! $user ) {
		if ( is_wp_error( $user ) && 'existing_user_email' === $user->get_error_code() ) {
			genosys_sms_profile_error( 'email_in_use', 'این ایمیل قبلاً ثبت شده است.', 409, $profile_lock, $phone_lock, array( 'field' => 'email' ) );
		}
		genosys_sms_profile_error( 'account_unavailable', 'ساخت حساب انجام نشد. لطفاً دوباره تلاش کنید.', 503, $profile_lock, $phone_lock );
	}
	delete_transient( $profile_key );
	wp_set_current_user( $user->ID, $user->user_login );
	wp_set_auth_cookie( $user->ID, true );
	do_action( 'wp_login', $user->user_login, $user );
	delete_option( $phone_lock );
	delete_option( $profile_lock );
	wp_send_json_success( array( 'redirect' => genosys_login_return_url( isset( $_POST['redirect_to'] ) && is_string( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '' ) ) );
}
add_action( 'wp_ajax_nopriv_genosys_sms_complete', 'genosys_sms_ajax_complete' );
add_action( 'wp_ajax_genosys_sms_complete', 'genosys_sms_ajax_complete' );

function genosys_sms_admin_menu() {
	add_options_page( 'ورود پیامکی ژنوسیس', 'ورود پیامکی ژنوسیس', 'manage_options', 'genosys-sms-login', 'genosys_sms_settings_page' );
}
add_action( 'admin_menu', 'genosys_sms_admin_menu' );

function genosys_sms_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to manage these settings.', 'genosys' ) );
	}
	check_admin_referer( 'genosys_sms_save_settings' );
	$template_id = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;
	$name = isset( $_POST['parameter_name'] ) && is_string( $_POST['parameter_name'] ) ? trim( wp_unslash( $_POST['parameter_name'] ) ) : '';
	$key = isset( $_POST['api_key'] ) && is_string( $_POST['api_key'] ) ? trim( wp_unslash( $_POST['api_key'] ) ) : '';
	$login_phone_input = isset( $_POST['login_phone'] ) && is_string( $_POST['login_phone'] ) ? trim( wp_unslash( $_POST['login_phone'] ) ) : '';
	$login_phone = '' === $login_phone_input ? '' : genosys_sms_normalize_phone( $login_phone_input );
	$valid_key = '' === $key || ( strlen( $key ) >= 16 && strlen( $key ) <= 200 && preg_match( '/^[\x21-\x7E]+$/D', $key ) );
	if ( ! $template_id || ! preg_match( '/^[A-Za-z][A-Za-z0-9_]*$/', $name ) || ! $valid_key || ( '' !== $login_phone_input && '' === $login_phone ) ) {
		wp_safe_redirect( add_query_arg( 'genosys_error', 'invalid', admin_url( 'options-general.php?page=genosys-sms-login' ) ) );
		exit;
	}
	wp_cache_delete( get_current_user_id(), 'user_meta' );
	$old_login_phone = genosys_sms_normalize_phone( (string) get_user_meta( get_current_user_id(), 'genosys_phone', true ) );
	$credential_phones = array_unique( array_filter( array( $old_login_phone, $login_phone ) ) );
	sort( $credential_phones, SORT_STRING );
	$credential_locks = array();
	foreach ( $credential_phones as $credential_phone ) {
		$credential_lock = genosys_sms_claim_lock( 'phone:' . $credential_phone );
		if ( ! $credential_lock ) {
			foreach ( $credential_locks as $held ) { delete_option( $held ); }
			wp_safe_redirect( add_query_arg( 'genosys_error', 'busy', admin_url( 'options-general.php?page=genosys-sms-login' ) ) ); exit;
		}
		$credential_locks[] = $credential_lock;
	}
	wp_cache_delete( get_current_user_id(), 'user_meta' );
	if ( $old_login_phone !== genosys_sms_normalize_phone( (string) get_user_meta( get_current_user_id(), 'genosys_phone', true ) ) ) {
		foreach ( $credential_locks as $held ) { delete_option( $held ); }
		wp_safe_redirect( add_query_arg( 'genosys_error', 'busy', admin_url( 'options-general.php?page=genosys-sms-login' ) ) ); exit;
	}
	if ( '' !== $login_phone ) {
		$existing = genosys_sms_find_existing_user( $login_phone );
		if ( ( is_wp_error( $existing ) && 'phone_not_enrolled' !== $existing->get_error_code() ) || ( $existing && ! is_wp_error( $existing ) && (int) $existing->ID !== get_current_user_id() ) ) {
			foreach ( $credential_locks as $held ) { delete_option( $held ); }
			wp_safe_redirect( add_query_arg( 'genosys_error', 'duplicate', admin_url( 'options-general.php?page=genosys-sms-login' ) ) );
			exit;
		}
	}
	update_option( 'genosys_smsir_template_id', $template_id, false );
	update_option( 'genosys_smsir_parameter_name', $name, false );
	if ( '' !== $key && ! defined( 'GENOSYS_SMSIR_API_KEY' ) ) {
		update_option( 'genosys_smsir_api_key', $key, false );
	}
	if ( '' === $login_phone ) {
		delete_user_meta( get_current_user_id(), 'genosys_phone' );
	} else {
		update_user_meta( get_current_user_id(), 'genosys_phone', $login_phone );
	}
	foreach ( $credential_locks as $held ) { delete_option( $held ); }
	wp_safe_redirect( add_query_arg( 'genosys_saved', '1', admin_url( 'options-general.php?page=genosys-sms-login' ) ) );
	exit;
}
add_action( 'admin_post_genosys_sms_save_settings', 'genosys_sms_save_settings' );

function genosys_sms_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$key_is_constant = defined( 'GENOSYS_SMSIR_API_KEY' );
	$key_is_set      = '' !== genosys_sms_api_key();
	?>
	<div class="wrap" dir="rtl">
		<h1>ورود پیامکی ژنوسیس</h1>
		<?php if ( isset( $_GET['genosys_saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div>
		<?php elseif ( isset( $_GET['genosys_error'] ) ) : ?>
			<div class="notice notice-error"><p><?php echo 'duplicate' === $_GET['genosys_error'] ? 'این شماره به حساب دیگری متصل است.' : 'یکی از مقادیر واردشده معتبر نیست.'; ?></p></div>
		<?php endif; ?>
		<p>کد ورود از طریق قالب تأیید پیامک sms.ir ارسال می‌شود. کلید API فقط روی سرور ذخیره می‌شود و در صفحه ورود نمایش داده نمی‌شود.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="genosys_sms_save_settings">
			<?php wp_nonce_field( 'genosys_sms_save_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="genosys-sms-key">کلید API پیامک</label></th>
					<td>
						<?php if ( $key_is_constant ) : ?>
							<p>کلید از <code>wp-config.php</code> خوانده می‌شود.</p>
						<?php else : ?>
							<input id="genosys-sms-key" name="api_key" type="password" class="regular-text" autocomplete="new-password" placeholder="<?php echo esc_attr( $key_is_set ? 'کلید ذخیره شده است؛ برای تغییر، کلید جدید را وارد کنید' : 'کلید API را وارد کنید' ); ?>">
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="genosys-sms-template">شناسه قالب</label></th>
					<td><input id="genosys-sms-template" name="template_id" type="number" min="1" class="small-text" value="<?php echo esc_attr( genosys_sms_template_id() ); ?>" required></td>
				</tr>
				<tr>
					<th scope="row"><label for="genosys-sms-parameter">نام متغیر کد در قالب</label></th>
					<td><input id="genosys-sms-parameter" name="parameter_name" type="text" class="regular-text" value="<?php echo esc_attr( genosys_sms_parameter_name() ); ?>" required><p class="description">برای متغیر <code>#CODE#</code> فقط نام <code>CODE</code> ارسال می‌شود. حروف بزرگ و کوچک را مطابق قالب پنل تنظیم کنید.</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="genosys-sms-login-phone">شماره ورود این مدیر</label></th>
					<td><input id="genosys-sms-login-phone" name="login_phone" type="tel" class="regular-text" value="<?php echo esc_attr( (string) get_user_meta( get_current_user_id(), 'genosys_phone', true ) ); ?>" placeholder="09123456789"><p class="description">برای ورود پیامکی به حساب مدیر، شماره خود را اینجا ثبت کنید. شماره‌های صورتحساب به‌تنهایی اجازه ورود به حساب مدیر را نمی‌دهند.</p></td>
				</tr>
			</table>
			<?php submit_button( 'ذخیره تنظیمات' ); ?>
		</form>
	</div>
	<?php
}
