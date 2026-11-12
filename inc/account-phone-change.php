<?php
/** Session-bound verification changes the current customer's credential, never their identity. */
defined( 'ABSPATH' ) || exit;

function genosys_account_phone_binding( $user_id ) {
	$session = wp_get_session_token();
	return $session ? hash_hmac( 'sha256', 'account-phone-change:' . (int) $user_id . ':' . $session, wp_salt( 'auth' ) ) : '';
}

function genosys_account_phone_error( $code, $message, $status = 400, $extra = array() ) {
	wp_send_json_error( array_merge( array( 'code' => $code, 'message' => $message, 'serverTime' => time() ), $extra ), $status );
}

/** Separate purpose, strict fields, and a current authenticated WordPress session. */
function genosys_account_phone_authorize( $fields ) {
	nocache_headers();
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { genosys_account_phone_error( 'invalid_method', 'روش درخواست معتبر نیست.', 405 ); }
	if ( ! is_user_logged_in() ) { genosys_account_phone_error( 'signed_out', 'برای تغییر شماره وارد حساب شوید.', 401 ); }
	$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'genosys_account_dashboard' ) ) { genosys_account_phone_error( 'invalid_request', 'اعتبار درخواست منقضی شده است. صفحه را تازه کنید.', 403 ); }
	foreach ( $_POST as $key => $value ) {
		if ( ! is_string( $value ) || ! in_array( $key, array_merge( array( 'action', 'nonce' ), $fields ), true ) ) { genosys_account_phone_error( 'invalid_request', 'اطلاعات درخواست معتبر نیست.' ); }
	}
	if ( ! genosys_account_phone_binding( get_current_user_id() ) ) { genosys_account_phone_error( 'session_mismatch', 'دوباره وارد حساب شوید.', 403 ); }
	return get_current_user_id();
}

function genosys_account_phone_value( $key ) {
	return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
}

function genosys_account_phone_challenge_key( $token ) {
	return 'genosys_account_phone_challenge_' . $token;
}

function genosys_account_phone_read( $token, $user_id ) {
	if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/D', $token ) ) { return new WP_Error( 'expired_code', 'کد جدید دریافت کنید.' ); }
	$record = get_transient( genosys_account_phone_challenge_key( $token ) );
	if ( ! is_array( $record ) || 'account-phone-change' !== ( $record['purpose'] ?? '' ) || (int) ( $record['userId'] ?? 0 ) !== (int) $user_id || ! isset( $record['binding'] ) || ! is_string( $record['binding'] ) || ! hash_equals( $record['binding'], genosys_account_phone_binding( $user_id ) ) ) { return new WP_Error( 'session_mismatch', 'درخواست به این نشست حساب تعلق ندارد. کد جدید دریافت کنید.' ); }
	if ( $token !== get_transient( 'genosys_account_phone_active_' . $user_id ) ) { return new WP_Error( 'expired_code', 'این کد دیگر فعال نیست. کد جدید دریافت کنید.' ); }
	wp_cache_delete( $user_id, 'user_meta' );
	$old = genosys_sms_normalize_phone( (string) get_user_meta( $user_id, 'genosys_phone', true ) );
	if ( $old !== ( $record['oldPhone'] ?? null ) ) { return new WP_Error( 'expired_code', 'شماره حساب تغییر کرده است. کد جدید دریافت کنید.' ); }
	return $record;
}

function genosys_account_phone_deadlines( $token, $record ) {
	return array( 'challengeId' => $token, 'phone' => $record['phone'], 'expiresAt' => (int) $record['expires'], 'resendAt' => (int) $record['resendAt'], 'serverTime' => time() );
}

function genosys_account_phone_release( $locks ) {
	foreach ( array_reverse( $locks ) as $lock ) { delete_option( $lock ); }
}

/** Existing login limits are shared; account limits also cover switching target numbers. */
function genosys_account_phone_rate( $user_id, $phone ) {
	$wait = genosys_sms_rate_error( $phone );
	foreach ( array( 'hour' => 5, 'day' => 10 ) as $window => $limit ) {
		$counter = genosys_sms_counter( 'genosys_account_phone_' . $window . '_' . genosys_sms_fingerprint( (string) $user_id ) );
		if ( $counter['count'] >= $limit ) { $wait = max( $wait, $counter['reset'] - time() ); }
	}
	$cooldown = (int) get_transient( 'genosys_account_phone_cool_' . $user_id );
	return max( 0, $wait, $cooldown - time() );
}

function genosys_account_phone_send( $resend = false ) {
	$user_id = genosys_account_phone_authorize( $resend ? array( 'challengeId' ) : array( 'phone' ) );
	$user_lock = genosys_account_notification_lock( 'phone-change-user:' . $user_id, 600 );
	if ( ! $user_lock ) { genosys_account_phone_error( 'busy', 'درخواست دیگری در حال انجام است. کمی بعد تلاش کنید.', 429, array( 'retry_after' => 5 ) ); }
	$locks = array();
	$error = null;
	$response = null;
	try {
		if ( $resend ) {
			$record = genosys_account_phone_read( genosys_account_phone_value( 'challengeId' ), $user_id );
			if ( is_wp_error( $record ) ) { throw new RuntimeException( $record->get_error_code() ); }
			$phone = $record['phone'];
		} else {
			$raw = genosys_account_dashboard_digits_ascii( genosys_account_phone_value( 'phone' ) );
			if ( strlen( $raw ) > 24 || preg_match( '/[^0-9+\s().-]/u', $raw ) ) { throw new RuntimeException( 'invalid_phone' ); }
			$phone = genosys_sms_normalize_phone( $raw );
			if ( ! $phone ) { throw new RuntimeException( 'invalid_phone' ); }
		}
		wp_cache_delete( $user_id, 'user_meta' );
		$old = genosys_sms_normalize_phone( (string) get_user_meta( $user_id, 'genosys_phone', true ) );
		if ( $phone === $old ) { throw new RuntimeException( 'phone_unchanged' ); }
		if ( ! genosys_sms_api_key() || ! genosys_sms_template_id() ) { throw new RuntimeException( 'not_configured' ); }
		foreach ( array( 'ip:' . genosys_sms_client_ip(), 'phone:' . $phone ) as $scope ) {
			$lock = genosys_sms_claim_lock( $scope );
			if ( ! $lock ) { throw new RuntimeException( 'busy' ); }
			$locks[] = $lock;
		}
		$retry = genosys_account_phone_rate( $user_id, $phone );
		if ( $retry ) { $error = array( 'code' => 'rate_limited', 'retry_after' => $retry ); throw new RuntimeException( 'rate_limited' ); }
		$existing = genosys_sms_find_existing_user( $phone );
		if ( is_wp_error( $existing ) || ( $existing && (int) $existing->ID !== (int) $user_id ) ) { throw new RuntimeException( 'phone_in_use' ); }
		// Charge attempts before the provider call, including provider rejection/timeouts.
		$resend_at = time() + 120;
		set_transient( 'genosys_account_phone_cool_' . $user_id, $resend_at, 120 );
		set_transient( 'genosys_sms_cool_' . genosys_sms_fingerprint( $phone ), $resend_at, 120 );
		$counter_windows = array( 'genosys_sms_day_' . genosys_sms_fingerprint( $phone ) => DAY_IN_SECONDS, 'genosys_sms_hour_' . genosys_sms_fingerprint( genosys_sms_client_ip() ) => HOUR_IN_SECONDS, 'genosys_account_phone_hour_' . genosys_sms_fingerprint( (string) $user_id ) => HOUR_IN_SECONDS, 'genosys_account_phone_day_' . genosys_sms_fingerprint( (string) $user_id ) => DAY_IN_SECONDS );
		foreach ( $counter_windows as $counter_key => $window ) {
			$before = genosys_sms_counter( $counter_key );
			genosys_sms_increment_counter( $counter_key, $window );
			$after = genosys_sms_counter( $counter_key );
			if ( $after['count'] < $before['count'] + 1 ) { throw new RuntimeException( 'storage_unavailable' ); }
		}
		if ( $resend_at !== (int) get_transient( 'genosys_account_phone_cool_' . $user_id ) || $resend_at !== (int) get_transient( 'genosys_sms_cool_' . genosys_sms_fingerprint( $phone ) ) ) { throw new RuntimeException( 'storage_unavailable' ); }
		$token = bin2hex( random_bytes( 32 ) );
		$code = (string) random_int( 100000, 999999 );
		$binding = genosys_account_phone_binding( $user_id );
		$hash = hash_hmac( 'sha256', 'account-phone-change:' . $user_id . ':' . $binding . ':' . $token . ':' . $phone . ':' . $code, wp_salt( 'auth' ) );
		$sent = genosys_smsir_send_code( $phone, $code );
		unset( $code );
		if ( is_wp_error( $sent ) ) { $error = array( 'code' => 'sms_unavailable', 'retry_after' => max( 1, $resend_at - time() ) ); throw new RuntimeException( 'sms_unavailable' ); }
		$record = array( 'purpose' => 'account-phone-change', 'userId' => (int) $user_id, 'binding' => $binding, 'oldPhone' => $old, 'phone' => $phone, 'hash' => $hash, 'expires' => time() + 120, 'resendAt' => $resend_at, 'attempts' => 0, 'consumed' => false );
		if ( ! set_transient( genosys_account_phone_challenge_key( $token ), $record, 900 ) || ! set_transient( 'genosys_account_phone_active_' . $user_id, $token, 900 ) ) { throw new RuntimeException( 'storage_unavailable' ); }
		$response = genosys_account_phone_deadlines( $token, $record );
		$response['message'] = 'کد تأیید به شماره جدید ارسال شد.';
	} catch ( Throwable $exception ) {
		if ( ! $error ) { $error = array( 'code' => $exception->getMessage() ); }
	} finally {
		genosys_account_phone_release( $locks );
		genosys_account_notification_unlock( $user_lock );
	}
	if ( $error ) { genosys_account_phone_fail( $error ); }
	wp_send_json_success( $response );
}

function genosys_account_phone_fail( $error ) {
	$messages = array(
		'invalid_phone' => array( 'شماره موبایل معتبر وارد کنید.', 400, 'phone' ),
		'phone_unchanged' => array( 'شماره جدید با شماره فعلی یکسان است.', 400, 'phone' ),
		'phone_in_use' => array( 'این شماره قابل اتصال به حساب شما نیست. شماره دیگری انتخاب کنید.', 409, 'phone' ),
		'busy' => array( 'درخواست دیگری در حال انجام است. کمی بعد دوباره تلاش کنید.', 429, '' ),
		'rate_limited' => array( 'برای دریافت کد بعدی کمی صبر کنید.', 429, '' ),
		'session_mismatch' => array( 'این درخواست به نشست فعلی تعلق ندارد. کد جدید دریافت کنید.', 403, '' ),
		'expired_code' => array( 'اعتبار کد تمام شده است. کد جدید دریافت کنید.', 400, 'code' ),
		'invalid_code' => array( 'کد واردشده صحیح نیست.', 400, 'code' ),
		'attempts_exhausted' => array( 'تعداد تلاش‌ها تمام شد. کد جدید دریافت کنید.', 400, 'code' ),
		'not_configured' => array( 'ارسال کد در حال حاضر در دسترس نیست.', 503, '' ),
		'sms_unavailable' => array( 'ارسال کد انجام نشد. کمی بعد دوباره تلاش کنید.', 503, '' ),
		'storage_unavailable' => array( 'ذخیره درخواست انجام نشد. دوباره کد دریافت کنید.', 503, '' ),
	);
	$code = isset( $messages[ $error['code'] ?? '' ] ) ? $error['code'] : 'storage_unavailable';
	$item = $messages[ $code ];
	unset( $error['code'] );
	if ( $item[2] ) { $error['field'] = $item[2]; }
	if ( 'busy' === $code && ! isset( $error['retry_after'] ) ) { $error['retry_after'] = 5; }
	if ( isset( $error['retry_after'] ) ) { $error['resendAt'] = time() + (int) $error['retry_after']; }
	genosys_account_phone_error( $code, $item[0], $item[1], $error );
}

function genosys_account_phone_request() { genosys_account_phone_send( false ); }
function genosys_account_phone_resend() { genosys_account_phone_send( true ); }
add_action( 'wp_ajax_genosys_account_phone_request', 'genosys_account_phone_request' );
add_action( 'wp_ajax_nopriv_genosys_account_phone_request', 'genosys_account_phone_request' );
add_action( 'wp_ajax_genosys_account_phone_resend', 'genosys_account_phone_resend' );
add_action( 'wp_ajax_nopriv_genosys_account_phone_resend', 'genosys_account_phone_resend' );

function genosys_account_phone_order_proof( $order ) {
	return hash_hmac( 'sha256', 'account-phone-order:' . get_current_user_id() . ':' . $order->get_id() . ':' . $order->get_order_key(), wp_salt( 'auth' ) );
}

/** Exact historical guest orders only; an old number never becomes a lasting wildcard. */
function genosys_account_phone_guest_snapshot( $old_phone ) {
	global $wpdb;
	$grants = array();
	if ( ! $old_phone ) { return $grants; }
	if ( ! function_exists( 'wc_get_orders' ) ) { throw new RuntimeException( 'storage_unavailable' ); }
	$batch = 1;
	do {
		$orders = wc_get_orders( array( 'type' => 'shop_order', 'customer_id' => 0, 'limit' => 200, 'page' => $batch, 'orderby' => 'ID', 'order' => 'ASC', 'return' => 'objects' ) );
		if ( ! is_array( $orders ) || $wpdb->last_error ) { throw new RuntimeException( 'storage_unavailable' ); }
		foreach ( $orders as $order ) {
			if ( genosys_order_access_valid_order( $order ) && 0 === (int) $order->get_customer_id() && $old_phone === genosys_sms_normalize_phone( $order->get_billing_phone() ) && genosys_order_access_can_view( $order ) && $order->get_order_key() ) { $grants[ $order->get_id() ] = genosys_account_phone_order_proof( $order ); }
		}
		$batch++;
	} while ( 200 === count( $orders ) );
	return $grants;
}

function genosys_account_phone_historical_order_allowed( $order ) {
	if ( ! is_user_logged_in() || ! genosys_order_access_valid_order( $order ) || 0 !== (int) $order->get_customer_id() || ! $order->get_order_key() ) { return false; }
	$grants = get_user_meta( get_current_user_id(), 'genosys_account_guest_order_grants', true );
	$proof = is_array( $grants ) ? ( $grants[ $order->get_id() ] ?? '' ) : '';
	return is_string( $proof ) && preg_match( '/^[a-f0-9]{64}$/D', $proof ) && hash_equals( $proof, genosys_account_phone_order_proof( $order ) );
}

function genosys_account_phone_historical_order_ids() {
	if ( ! is_user_logged_in() ) { return array(); }
	$grants = get_user_meta( get_current_user_id(), 'genosys_account_guest_order_grants', true );
	$ids = array();
	foreach ( is_array( $grants ) ? $grants : array() as $id => $proof ) {
		if ( preg_match( '/^[1-9][0-9]*$/D', (string) $id ) && is_string( $proof ) && preg_match( '/^[a-f0-9]{64}$/D', $proof ) ) { $ids[] = (int) $id; }
	}
	return $ids;
}

function genosys_account_phone_verify() {
	$user_id = genosys_account_phone_authorize( array( 'challengeId', 'code' ) );
	$token = genosys_account_phone_value( 'challengeId' );
	$code = genosys_account_dashboard_digits_ascii( genosys_account_phone_value( 'code' ) );
	if ( ! preg_match( '/^[0-9]{6}$/D', $code ) ) { genosys_account_phone_fail( array( 'code' => 'invalid_code' ) ); }
	$user_lock = genosys_account_notification_lock( 'phone-change-user:' . $user_id, 600 );
	if ( ! $user_lock ) { genosys_account_phone_fail( array( 'code' => 'busy' ) ); }
	$locks = array(); $error = null; $changed = false; $transaction = false;
	global $wpdb;
	try {
		$record = genosys_account_phone_read( $token, $user_id );
		if ( is_wp_error( $record ) ) { throw new RuntimeException( $record->get_error_code() ); }
		if ( ! empty( $record['consumed'] ) || (int) $record['expires'] <= time() ) { throw new RuntimeException( 'expired_code' ); }
		if ( (int) $record['attempts'] >= 5 ) { throw new RuntimeException( 'attempts_exhausted' ); }
		$hash = hash_hmac( 'sha256', 'account-phone-change:' . $user_id . ':' . $record['binding'] . ':' . $token . ':' . $record['phone'] . ':' . $code, wp_salt( 'auth' ) );
		unset( $code );
		if ( ! hash_equals( $record['hash'], $hash ) ) {
			$record['attempts']++;
			if ( ! set_transient( genosys_account_phone_challenge_key( $token ), $record, 900 ) ) { throw new RuntimeException( 'storage_unavailable' ); }
			$error = genosys_account_phone_deadlines( $token, $record );
			$error['code'] = $record['attempts'] >= 5 ? 'attempts_exhausted' : 'invalid_code';
			throw new RuntimeException( $error['code'] );
		}
		// A full scan is outside the shared 25-second phone locks and has no 50-order browser cap.
		$grants = genosys_account_phone_guest_snapshot( $record['oldPhone'] );
		$phones = array_unique( array_filter( array( $record['oldPhone'], $record['phone'] ) ) ); sort( $phones, SORT_STRING );
		foreach ( $phones as $phone ) {
			$lock = genosys_sms_claim_lock( 'phone:' . $phone );
			if ( ! $lock ) { throw new RuntimeException( 'busy' ); }
			$locks[] = $lock;
		}
		wp_cache_delete( $user_id, 'user_meta' );
		if ( $record['oldPhone'] !== genosys_sms_normalize_phone( (string) get_user_meta( $user_id, 'genosys_phone', true ) ) ) { throw new RuntimeException( 'expired_code' ); }
		$existing = genosys_sms_find_existing_user( $record['phone'] );
		if ( is_wp_error( $existing ) || ( $existing && (int) $existing->ID !== (int) $user_id ) ) { throw new RuntimeException( 'phone_in_use' ); }
		$table = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS LIKE %s', $wpdb->esc_like( $wpdb->usermeta ) ), ARRAY_A );
		if ( ! is_array( $table ) || 'innodb' !== strtolower( (string) ( $table['Engine'] ?? '' ) ) ) { throw new RuntimeException( 'storage_unavailable' ); }
		if ( (int) $record['expires'] <= time() ) { throw new RuntimeException( 'expired_code' ); }
		$record['consumed'] = true;
		if ( ! set_transient( genosys_account_phone_challenge_key( $token ), $record, 900 ) ) { throw new RuntimeException( 'storage_unavailable' ); }
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { throw new RuntimeException( 'storage_unavailable' ); }
		$transaction = true;
		$stored = get_user_meta( $user_id, 'genosys_account_guest_order_grants', true );
		$all_grants = array_replace( is_array( $stored ) ? $stored : array(), $grants );
		update_user_meta( $user_id, 'genosys_account_guest_order_grants', $all_grants );
		update_user_meta( $user_id, 'genosys_phone', $record['phone'] );
		$billing = (string) get_user_meta( $user_id, 'billing_phone', true );
		$update_billing = '' === trim( $billing ) || ( $record['oldPhone'] && $record['oldPhone'] === genosys_sms_normalize_phone( $billing ) );
		if ( $update_billing ) { update_user_meta( $user_id, 'billing_phone', $record['phone'] ); }
		$changed_at = time();
		update_user_meta( $user_id, 'genosys_phone_changed_at', $changed_at );
		wp_cache_delete( $user_id, 'user_meta' );
		if ( $all_grants !== get_user_meta( $user_id, 'genosys_account_guest_order_grants', true ) || $record['phone'] !== get_user_meta( $user_id, 'genosys_phone', true ) || (string) $changed_at !== (string) get_user_meta( $user_id, 'genosys_phone_changed_at', true ) || ( $update_billing && $record['phone'] !== get_user_meta( $user_id, 'billing_phone', true ) ) ) { throw new RuntimeException( 'storage_unavailable' ); }
		$sessions = WP_Session_Tokens::get_instance( $user_id );
		$sessions->destroy_others( wp_get_session_token() );
		if ( ! $sessions->verify( wp_get_session_token() ) || 1 !== count( $sessions->get_all() ) ) { throw new RuntimeException( 'storage_unavailable' ); }
		if ( false === $wpdb->query( 'COMMIT' ) ) { throw new RuntimeException( 'storage_unavailable' ); }
		$transaction = false; $changed = true;
		delete_transient( genosys_account_phone_challenge_key( $token ) );
		delete_transient( 'genosys_account_phone_active_' . $user_id );
		// Already issued login OTPs for either credential cannot bridge the change.
		foreach ( $phones as $phone ) {
			$active = 'genosys_sms_active_' . genosys_sms_fingerprint( $phone );
			$old_token = get_transient( $active );
			if ( is_string( $old_token ) && preg_match( '/^[a-f0-9]{32}$/D', $old_token ) ) { delete_transient( 'genosys_sms_otp_' . $old_token ); }
			delete_transient( $active );
		}
	} catch ( Throwable $exception ) {
		if ( $transaction ) { $wpdb->query( 'ROLLBACK' ); }
		if ( ! $error ) { $error = array( 'code' => $exception->getMessage() ); }
	} finally {
		wp_cache_delete( $user_id, 'user_meta' ); clean_user_cache( $user_id );
		genosys_account_phone_release( $locks );
		genosys_account_notification_unlock( $user_lock );
	}
	if ( ! $changed ) { genosys_account_phone_fail( $error ?: array( 'code' => 'storage_unavailable' ) ); }
	try { $context = genosys_account_dashboard_context(); } catch ( Throwable $error ) { $context = array( 'userId' => $user_id, 'phone' => $record['phone'], 'phoneVerified' => true ); }
	wp_send_json_success( array( 'context' => $context, 'message' => 'شماره موبایل با موفقیت تغییر کرد.' ) );
}
add_action( 'wp_ajax_genosys_account_phone_verify', 'genosys_account_phone_verify' );
add_action( 'wp_ajax_nopriv_genosys_account_phone_verify', 'genosys_account_phone_verify' );
