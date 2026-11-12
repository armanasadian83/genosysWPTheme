<?php
/** Explicit admin-reviewed club SMS. No enrollment/read operation sends messages. */
defined( 'ABSPATH' ) || exit;

function genosys_club_sms_sender() {
	$sender = defined( 'GENOSYS_CLUB_SMS_SENDER' ) ? GENOSYS_CLUB_SMS_SENDER : get_option( 'genosys_club_sms_sender', '' );
	return is_scalar( $sender ) && preg_match( '/^[1-9][0-9]{2,14}$/D', (string) $sender ) ? (string) $sender : '';
}

function genosys_club_sms_available() {
	return '' !== genosys_sms_api_key() && '' !== genosys_club_sms_sender();
}

function genosys_club_sms_authorize() {
	nocache_headers();
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! genosys_club_can_manage() ) {
		wp_send_json_error( array( 'message' => 'اجازه‌ی ارسال پیامک ندارید.' ), 403 );
	}
	check_ajax_referer( 'genosys_club_admin', 'nonce' );
}

function genosys_club_sms_settings() {
	genosys_club_sms_authorize();
	if ( ! current_user_can( 'manage_options' ) || defined( 'GENOSYS_CLUB_SMS_SENDER' ) ) {
		wp_send_json_error( array( 'message' => 'تنظیم خط ارسال توسط مدیر سایت انجام می‌شود.' ), 403 );
	}
	$sender = isset( $_POST['sender'] ) && is_string( $_POST['sender'] ) ? trim( wp_unslash( $_POST['sender'] ) ) : '';
	if ( ! preg_match( '/^[1-9][0-9]{2,14}$/D', $sender ) ) {
		wp_send_json_error( array( 'message' => 'شماره خط ارسال SMS.ir را با ارقام انگلیسی وارد کنید.' ), 400 );
	}
	update_option( 'genosys_club_sms_sender', $sender, false );
	wp_send_json_success( array( 'available' => genosys_club_sms_available(), 'message' => 'خط ارسال ذخیره شد. کلید API از تنظیمات سرویس پیامک قالب خوانده می‌شود.' ) );
}
add_action( 'wp_ajax_genosys_club_sms_settings', 'genosys_club_sms_settings' );

function genosys_club_sms_preview() {
	genosys_club_sms_authorize();
	$filters = isset( $_POST['filters'] ) && is_string( $_POST['filters'] ) ? json_decode( wp_unslash( $_POST['filters'] ), true ) : array();
	$filters = is_array( $filters ) ? $filters : array();
	$cancelled_view = isset( $filters['status'] ) && 'cancelled' === $filters['status'];
	$filters['status'] = 'active';
	$single = isset( $_POST['member_id'] ) ? absint( $_POST['member_id'] ) : 0;
	$ids = $single ? array( $single ) : ( $cancelled_view ? array() : genosys_club_filtered_member_ids( $filters ) );
	$recipients = array();
	foreach ( $ids as $id ) {
		$member = genosys_club_member_data( $id, false, false );
		if ( ! is_array( $member ) || 'active' !== $member['status'] ) { continue; }
		if ( function_exists( 'genosys_account_notification_club_allowed' ) ? ! genosys_account_notification_club_allowed( $member ) : empty( $member['consent'] ) ) { continue; }
		$phone = genosys_sms_normalize_phone( $member['phone'] );
		if ( $phone && ! isset( $recipients[ $phone ] ) ) { $recipients[ $phone ] = (int) $id; }
	}
	$count = count( $recipients );
	$available = genosys_club_sms_available() && $count > 0 && $count <= 100;
	$message = $available ? 'گیرندگان بررسی شدند؛ متن را پیش از تأیید نهایی مرور کنید.' : ( $count > 100 ? 'حداکثر ۱۰۰ گیرنده در هر ارسال پشتیبانی می‌شود. با فیلترها گروه کوچک‌تری انتخاب کنید.' : ( 0 === $count ? 'عضو فعالِ موافق دریافت پیامک در این انتخاب وجود ندارد.' : 'ابتدا کلید API سرویس پیامک و شماره خط ارسال SMS.ir را تنظیم کنید.' ) );
	$campaign = '';
	if ( $available ) {
		$campaign = bin2hex( random_bytes( 16 ) );
		set_transient( 'genosys_club_sms_' . $campaign, array( 'actor' => get_current_user_id(), 'recipients' => $recipients, 'sender' => genosys_club_sms_sender(), 'created' => time() ), 15 * MINUTE_IN_SECONDS );
	}
	wp_send_json_success( array( 'campaignId' => $campaign, 'count' => $count, 'available' => $available, 'message' => $message ) );
}
add_action( 'wp_ajax_genosys_club_sms_preview', 'genosys_club_sms_preview' );

/** Provider contract: https://github.com/cryptommer/smsir/blob/main/src/Classes/Send.php */
function genosys_club_sms_request( $phones, $text, $sender ) {
	$result = array( 'sent' => 0, 'failed' => 0, 'unconfirmed' => count( $phones ) );
	$response = wp_remote_post( 'https://api.sms.ir/v1/send/bulk', array(
		'timeout' => 15, 'redirection' => 0,
		'headers' => array( 'X-API-KEY' => genosys_sms_api_key(), 'Content-Type' => 'application/json', 'Accept' => 'application/json' ),
		'body' => wp_json_encode( array( 'lineNumber' => (int) $sender, 'MessageText' => $text, 'Mobiles' => array_values( $phones ), 'SendDateTime' => null ) ),
	) );
	if ( is_wp_error( $response ) ) { return $result; }
	$http = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( $http >= 400 && $http < 500 ) { return array( 'sent' => 0, 'failed' => count( $phones ), 'unconfirmed' => 0 ); }
	if ( $http < 200 || $http >= 300 || ! is_array( $body ) ) { return $result; }
	$status = $body['status'] ?? $body['Status'] ?? null;
	$data = $body['data'] ?? $body['Data'] ?? array();
	$message_ids = is_array( $data ) ? ( $data['messageIds'] ?? $data['MessageIds'] ?? null ) : null;
	if ( ! in_array( $status, array( 1, '1' ), true ) || ! is_array( $message_ids ) || count( $message_ids ) !== count( $phones ) ) { return $result; }
	$result = array( 'sent' => 0, 'failed' => 0, 'unconfirmed' => 0 );
	foreach ( $message_ids as $message_id ) {
		if ( ( is_int( $message_id ) || is_string( $message_id ) ) && preg_match( '/^[1-9][0-9]*$/D', (string) $message_id ) ) { ++$result['sent']; }
		elseif ( null === $message_id || 0 === $message_id || '0' === $message_id ) { ++$result['failed']; }
		else { ++$result['unconfirmed']; }
	}
	return $result;
}

function genosys_club_sms_send() {
	genosys_club_sms_authorize();
	$campaign_id = isset( $_POST['campaign_id'] ) && is_string( $_POST['campaign_id'] ) ? wp_unslash( $_POST['campaign_id'] ) : '';
	$request_id = isset( $_POST['request_id'] ) && is_string( $_POST['request_id'] ) ? wp_unslash( $_POST['request_id'] ) : '';
	$text = isset( $_POST['text'] ) && is_string( $_POST['text'] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) ) : '';
	if ( ! preg_match( '/^[a-f0-9]{32}$/D', $campaign_id ) || ! preg_match( '/^[a-f0-9]{32}$/D', $request_id ) || '' === $text || genosys_club_length( $text ) > 1000 ) {
		wp_send_json_error( array( 'message' => 'متن پیامک و تأیید گیرندگان معتبر نیست.' ), 400 );
	}
	$claim_key = 'genosys_club_sms_attempt_' . $campaign_id;
	$previous = get_option( $claim_key );
	if ( is_array( $previous ) ) {
		if ( (int) $previous['actor'] !== get_current_user_id() ) { wp_send_json_error( array( 'message' => 'دسترسی مجاز نیست.' ), 403 ); }
		if ( ! hash_equals( $previous['request'], $request_id ) || ! hash_equals( $previous['textHash'], hash( 'sha256', $text ) ) ) {
			wp_send_json_error( array( 'message' => 'متن این ارسال تغییر کرده است. ارسال قبلی را در پنل سرویس بررسی کنید.' ), 409 );
		}
		if ( isset( $previous['result'] ) ) { wp_send_json_success( $previous['result'] ); }
		wp_send_json_error( array( 'message' => 'این ارسال قبلاً آغاز شده است. برای جلوگیری از پیامک تکراری، وضعیت را در پنل سرویس بررسی کنید.' ), 409 );
	}
	$campaign = get_transient( 'genosys_club_sms_' . $campaign_id );
	if ( ! is_array( $campaign ) || (int) $campaign['actor'] !== get_current_user_id() || ! genosys_club_sms_available() || $campaign['sender'] !== genosys_club_sms_sender() ) {
		wp_send_json_error( array( 'message' => 'تأیید گیرندگان منقضی شده است. فهرست را دوباره بررسی کنید.' ), 409 );
	}
	$scopes = array(); $locks = array();
	foreach ( $campaign['recipients'] as $id ) {
		$record = genosys_club_member_raw( $id );
		if ( ! $record ) { wp_send_json_error( array( 'message' => 'فهرست گیرندگان تغییر کرده است. دوباره بررسی کنید.' ), 409 ); }
		$scopes[] = 'browser:' . get_post_meta( $id, '_genosys_club_owner_browser', true );
		if ( $record['userId'] ) { $scopes[] = 'user:' . (int) $record['userId']; }
	}
	$scopes = array_unique( $scopes ); sort( $scopes );
	foreach ( $scopes as $scope ) {
		$lock = genosys_club_lock( $scope );
		if ( ! $lock ) {
			foreach ( $locks as $held ) { genosys_club_unlock( $held ); }
			wp_send_json_error( array( 'message' => 'درخواست دیگری در حال انجام است. کمی بعد تلاش کنید.' ), 409 );
		}
		$locks[] = $lock;
	}
	foreach ( $campaign['recipients'] as $phone => $id ) {
		// Scope discovery may have cached a record before a concurrent cancellation committed.
		wp_cache_delete( (int) $id, 'post_meta' );
		$fresh = genosys_club_member_raw( $id );
		$required = array( 'browser:' . get_post_meta( $id, '_genosys_club_owner_browser', true ) );
		if ( $fresh && $fresh['userId'] ) { $required[] = 'user:' . (int) $fresh['userId']; }
		if ( ! $fresh || array_diff( $required, $scopes ) ) {
			foreach ( $locks as $held ) { genosys_club_unlock( $held ); }
			wp_send_json_error( array( 'message' => 'مالکیت گیرندگان تغییر کرده است؛ فهرست را دوباره بررسی کنید.' ), 409 );
		}
		$member = genosys_club_member_data( $id, false, false );
		if ( ! is_array( $member ) || 'active' !== $member['status'] || genosys_sms_normalize_phone( $member['phone'] ) !== (string) $phone || ( function_exists( 'genosys_account_notification_club_allowed' ) ? ! genosys_account_notification_club_allowed( $member ) : empty( $member['consent'] ) ) ) {
			foreach ( $locks as $held ) { genosys_club_unlock( $held ); }
			wp_send_json_error( array( 'message' => 'وضعیت گیرندگان تغییر کرده است؛ پیش از ارسال فهرست را دوباره بررسی کنید.' ), 409 );
		}
	}
	// Atomic durable claim precedes the only outbound request. Even ambiguous timeouts cannot auto-retry.
	$claim = array( 'actor' => get_current_user_id(), 'created' => time(), 'request' => $request_id, 'textHash' => hash( 'sha256', $text ) );
	$claimed = add_option( $claim_key, $claim, '', false );
	foreach ( $locks as $held ) { genosys_club_unlock( $held ); }
	if ( ! $claimed ) { wp_send_json_error( array( 'message' => 'ارسال قبلاً آغاز شده است.' ), 409 ); }
	wp_schedule_single_event( time() + DAY_IN_SECONDS, 'genosys_club_sms_cleanup', array( $claim_key ) );
	$phones = array();
	foreach ( $campaign['recipients'] as $phone => $id ) {
		$member = genosys_club_member_data( $id, false, false );
		if ( function_exists( 'genosys_account_notification_club_allowed' ) ? genosys_account_notification_club_allowed( $member ) : ( is_array( $member ) && 'active' === $member['status'] && ! empty( $member['consent'] ) ) ) { $phones[] = (string) $phone; }
	}
	$result = $phones ? genosys_club_sms_request( $phones, $text, $campaign['sender'] ) : array( 'sent' => 0, 'failed' => 0, 'unconfirmed' => 0 );
	$result['message'] = sprintf( 'پذیرفته‌شده توسط سرویس: %d؛ ناموفق: %d؛ وضعیت نامشخص: %d. پذیرش سرویس به معنی تحویل قطعی نیست.', $result['sent'], $result['failed'], $result['unconfirmed'] );
	$claim['result'] = $result;
	update_option( $claim_key, $claim, false );
	delete_transient( 'genosys_club_sms_' . $campaign_id );
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_genosys_club_sms_send', 'genosys_club_sms_send' );

function genosys_club_sms_cleanup( $key ) {
	if ( is_string( $key ) && preg_match( '/^genosys_club_sms_attempt_[a-f0-9]{32}$/D', $key ) ) { delete_option( $key ); }
}
add_action( 'genosys_club_sms_cleanup', 'genosys_club_sms_cleanup' );
