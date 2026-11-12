<?php
/** Private, consent-based club registrations and permission-scoped administration. */
defined( 'ABSPATH' ) || exit;

function genosys_club_register_storage() {
	register_post_type( 'genosys_club_member', array(
		'label' => 'اعضای باشگاه مشتریان', 'public' => false, 'publicly_queryable' => false,
		'show_ui' => false, 'show_in_menu' => false, 'show_in_rest' => false,
		'exclude_from_search' => true, 'rewrite' => false, 'query_var' => false,
		'supports' => array(), 'map_meta_cap' => false,
		'capabilities' => array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'create_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts' ), 'do_not_allow' ),
	) );
}
add_action( 'init', 'genosys_club_register_storage' );

function genosys_club_can_manage() {
	return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
}

function genosys_club_capability() {
	return current_user_can( 'manage_woocommerce' ) ? 'manage_woocommerce' : 'manage_options';
}

function genosys_club_ascii( $value ) {
	return strtr( (string) $value, array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9' ) );
}

function genosys_club_digits( $value ) {
	return function_exists( 'genosys_persian_number' ) ? genosys_persian_number( $value ) : (string) $value;
}

function genosys_club_normalize( $text ) {
	$text = strtr( genosys_club_ascii( wp_strip_all_tags( (string) $text ) ), array( 'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', '‌' => ' ' ) );
	$text = trim( preg_replace( '/\s+/u', ' ', (string) preg_replace( '/[\x{064B}-\x{065F}\x{0670}ـ]/u', '', $text ) ) );
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
}

function genosys_club_length( $text ) {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
}

function genosys_club_labels() {
	return array(
		'skin' => array( 'نرمال', 'خشک', 'چرب', 'مختلط', 'حساس' ),
		'concerns' => array( 'لک و یکدستی', 'چین و چروک', 'آکنه و جوش', 'خشکی و کدری', 'ریزش مو', 'آفتاب‌سوختگی', 'حساسیت و قرمزی' ),
		'categories' => array( 'مراقبت پوست', 'مراقبت مو و پوست سر', 'ضد آفتاب', 'دور چشم', 'آرایشی' ),
		'services' => array( 'فیشیال و پاکسازی', 'جوانسازی پوست', 'خدمات لک و آکنه', 'مراقبت مو و پوست سر', 'مراقبت پس از خدمات زیبایی', 'سایر' ),
		'client_needs' => array( 'آبرسانی و مراقبت روتین', 'لک و تیرگی', 'آکنه و چربی پوست', 'چین و چروک', 'پوست حساس', 'مراقبت مو و پوست سر', 'سایر' ),
		'source' => array( 'instagram' => 'اینستاگرام', 'friends' => 'دوستان و آشنایان', 'search' => 'جستجوی اینترنتی', 'store' => 'فروشگاه یا کلینیک', 'other' => 'سایر' ),
		'age_range' => array( 'under-18' => 'زیر ۱۸ سال', '18-24' => '۱۸ تا ۲۴ سال', '25-34' => '۲۵ تا ۳۴ سال', '35-44' => '۳۵ تا ۴۴ سال', '45-54' => '۴۵ تا ۵۴ سال', '55-plus' => '۵۵ سال به بالا' ),
		'activity' => array( 'clinic' => 'کلینیک زیبایی', 'therapist' => 'بیوتی تراپیست مستقل', 'facial-center' => 'مرکز فیشیال و مراقبت پوست', 'other' => 'سایر' ),
		'experience' => array( 'starting' => 'در حال راه‌اندازی', 'under-one' => 'کمتر از ۱ سال', 'one-three' => '۱ تا ۳ سال', 'three-five' => '۳ تا ۵ سال', 'five-ten' => '۵ تا ۱۰ سال', 'over-ten' => 'بیشتر از ۱۰ سال' ),
		'monthly_consumption' => array( 'under-five' => 'کمتر از ۵ عدد در ماه', 'five-ten' => '۵ تا ۱۰ عدد در ماه', 'eleven-twenty' => '۱۱ تا ۲۰ عدد در ماه', 'over-twenty' => 'بیشتر از ۲۰ عدد در ماه', 'unknown' => 'هنوز برآوردی ندارم' ),
		'monthly_clients' => array( 'under-twenty' => 'تا ۲۰ نفر', 'twenty-fifty' => '۲۱ تا ۵۰ نفر', 'fifty-hundred' => '۵۱ تا ۱۰۰ نفر', 'hundred-twohundred' => '۱۰۱ تا ۲۰۰ نفر', 'over-twohundred' => 'بیشتر از ۲۰۰ نفر' ),
	);
}

function genosys_club_error( $message, $status = 400, $field = '', $extra = array() ) {
	$data = array_merge( array( 'message' => $message ), $extra );
	if ( $field ) { $data['field'] = $field; }
	nocache_headers();
	wp_send_json_error( $data, $status );
}

/** Iranian mobile or fixed-line syntax, without granting credential verification. */
function genosys_club_phone( $value, $fixed = false ) {
	if ( ! is_string( $value ) ) { return ''; }
	$value = genosys_club_ascii( trim( $value ) );
	if ( preg_match( '/[^0-9+\s().-]/u', $value ) ) { return ''; }
	$value = preg_replace( '/[\s().-]/u', '', $value );
	if ( 0 === strpos( $value, '+98' ) ) { $value = '0' . substr( $value, 3 ); }
	elseif ( 0 === strpos( $value, '0098' ) ) { $value = '0' . substr( $value, 4 ); }
	elseif ( preg_match( '/^98[0-9]{10}$/D', $value ) ) { $value = '0' . substr( $value, 2 ); }
	return preg_match( $fixed ? '/^0[1-8][0-9]{9}$/D' : '/^09[0-9]{9}$/D', $value ) ? $value : '';
}

/** The server validates every step; browser validation is only a convenience. */
function genosys_club_validate( $input ) {
	if ( ! is_array( $input ) || count( $input ) > 30 ) { return new WP_Error( 'invalid', 'اطلاعات پرسشنامه معتبر نیست.' ); }
	$audience = isset( $input['audience'] ) && is_string( $input['audience'] ) ? $input['audience'] : '';
	if ( ! in_array( $audience, array( 'consumer', 'clinic' ), true ) ) { return new WP_Error( 'audience', 'نوع عضویت را انتخاب کنید.', array( 'field' => 'audience' ) ); }
	if ( ! isset( $input['consent'] ) || true !== $input['consent'] ) { return new WP_Error( 'consent', 'برای ثبت عضویت، موافقت خود را مشخص کنید.', array( 'field' => 'consent' ) ); }
	$labels = genosys_club_labels(); $data = array( 'audience' => $audience, 'consent' => true );
	$fields = 'consumer' === $audience ? array( 'full_name' => 150, 'email' => 254, 'city' => 100, 'message' => 3000 ) : array( 'clinic_name' => 150, 'contact_name' => 150, 'clinic_city' => 100, 'clinic_message' => 3000 );
	foreach ( $fields as $key => $limit ) {
		$value = isset( $input[ $key ] ) ? $input[ $key ] : '';
		if ( ! is_string( $value ) || genosys_club_length( $value ) > $limit ) { return new WP_Error( 'length', 'طول یا قالب اطلاعات واردشده معتبر نیست.', array( 'field' => $key ) ); }
		$data[ $key ] = in_array( $key, array( 'message', 'clinic_message' ), true ) ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
	}
	$names = 'consumer' === $audience ? array( 'full_name' ) : array( 'clinic_name', 'contact_name' );
	foreach ( $names as $key ) { if ( '' === $data[ $key ] ) { return new WP_Error( 'name', 'نام را وارد کنید.', array( 'field' => $key ) ); } }
	$phone_key = 'consumer' === $audience ? 'phone' : 'contact_phone';
	$data[ $phone_key ] = genosys_club_phone( isset( $input[ $phone_key ] ) ? $input[ $phone_key ] : '' );
	if ( ! $data[ $phone_key ] ) { return new WP_Error( 'phone', 'شماره موبایل معتبر وارد کنید.', array( 'field' => $phone_key ) ); }
	if ( 'consumer' === $audience ) {
		if ( $data['email'] && ( ! is_email( $data['email'] ) || sanitize_email( $data['email'] ) !== $data['email'] ) ) { return new WP_Error( 'email', 'ایمیل معتبر وارد کنید.', array( 'field' => 'email' ) ); }
		foreach ( array( 'rating' => array( 1, 5 ), 'recommend' => array( 0, 10 ) ) as $key => $range ) {
			$value = isset( $input[ $key ] ) && ( is_string( $input[ $key ] ) || is_int( $input[ $key ] ) ) ? genosys_club_ascii( $input[ $key ] ) : '';
			if ( ! preg_match( '/^[0-9]{1,2}$/D', $value ) || (int) $value < $range[0] || (int) $value > $range[1] ) { return new WP_Error( 'rating', 'امتیاز معتبر را انتخاب کنید.', array( 'field' => $key ) ); }
			$data[ $key ] = (int) $value;
		}
		$skin = isset( $input['skin'] ) && is_string( $input['skin'] ) ? $input['skin'] : '';
		if ( ! in_array( $skin, $labels['skin'], true ) ) { return new WP_Error( 'skin', 'نوع پوست را انتخاب کنید.', array( 'field' => 'skin' ) ); }
		$data['skin'] = $skin;
	} else {
		$landline = isset( $input['clinic_landline'] ) ? $input['clinic_landline'] : '';
		if ( ! is_string( $landline ) || genosys_club_length( $landline ) > 30 ) { return new WP_Error( 'landline', 'شماره ثابت معتبر نیست.', array( 'field' => 'clinic_landline' ) ); }
		$data['clinic_landline'] = '' === trim( $landline ) ? '' : genosys_club_phone( $landline, true );
		if ( '' !== trim( $landline ) && ! $data['clinic_landline'] ) { return new WP_Error( 'landline', 'شماره ثابت را با پیش‌شماره وارد کنید.', array( 'field' => 'clinic_landline' ) ); }
	}
	$enums = 'consumer' === $audience ? array( 'age_range' => false, 'source' => false ) : array( 'activity' => false, 'experience' => true, 'monthly_consumption' => true, 'monthly_clients' => false );
	foreach ( $enums as $key => $required ) {
		$value = isset( $input[ $key ] ) ? $input[ $key ] : '';
		if ( ! is_string( $value ) || ( '' === $value && $required ) || ( '' !== $value && ! isset( $labels[ $key ][ $value ] ) ) ) { return new WP_Error( 'choice', 'گزینه معتبر را انتخاب کنید.', array( 'field' => $key ) ); }
		$data[ $key ] = $value;
	}
	$lists = 'consumer' === $audience ? array( 'concerns' => false, 'categories' => false ) : array( 'services' => true, 'client_needs' => true );
	foreach ( $lists as $key => $required ) {
		$values = isset( $input[ $key ] ) ? $input[ $key ] : array();
		if ( ! is_array( $values ) || count( $values ) > count( $labels[ $key ] ) || ( $required && ! $values ) ) { return new WP_Error( 'choices', 'حداقل یک گزینه معتبر انتخاب کنید.', array( 'field' => $key ) ); }
		foreach ( $values as $value ) { if ( ! is_string( $value ) || ! in_array( $value, $labels[ $key ], true ) ) { return new WP_Error( 'choices', 'گزینه انتخاب‌شده معتبر نیست.', array( 'field' => $key ) ); } }
		$data[ $key ] = array_values( array_unique( $values ) );
	}
	return $data;
}

function genosys_club_same_origin() {
	$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? wp_unslash( $_SERVER['HTTP_ORIGIN'] ) : '';
	if ( ! $origin ) { return true; }
	$origin_parts = wp_parse_url( $origin );
	foreach ( array( home_url(), site_url() ) as $allowed ) {
		$parts = wp_parse_url( $allowed );
		if ( isset( $origin_parts['host'], $origin_parts['scheme'], $parts['host'], $parts['scheme'] ) && strtolower( $origin_parts['host'] ) === strtolower( $parts['host'] ) && $origin_parts['scheme'] === $parts['scheme'] && ( isset( $origin_parts['port'] ) ? (int) $origin_parts['port'] : ( 'https' === $origin_parts['scheme'] ? 443 : 80 ) ) === ( isset( $parts['port'] ) ? (int) $parts['port'] : ( 'https' === $parts['scheme'] ? 443 : 80 ) ) ) { return true; }
	}
	return false;
}

/** The random cookie proves browser ownership only. It never proves a phone number. */
function genosys_club_browser( $create = false ) {
	$value = isset( $_COOKIE['genosys_club_browser'] ) && is_string( $_COOKIE['genosys_club_browser'] ) ? wp_unslash( $_COOKIE['genosys_club_browser'] ) : '';
	if ( ! preg_match( '/^[a-f0-9]{64}$/D', $value ) ) {
		if ( ! $create || headers_sent() ) { return ''; }
		try { $value = bin2hex( random_bytes( 32 ) ); } catch ( Exception $e ) { return ''; }
		if ( ! setcookie( 'genosys_club_browser', $value, array( 'expires' => time() + 30 * DAY_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) ) ) { return ''; }
		$_COOKIE['genosys_club_browser'] = $value;
	}
	return hash_hmac( 'sha256', $value, wp_salt( 'auth' ) );
}

/** add_option is atomic in MySQL; stale-lock deletion compares the stored value. */
function genosys_club_lock( $scope, $ttl = 45 ) {
	global $wpdb;
	$key = 'genosys_club_lock_' . hash( 'sha256', $scope );
	$value = (string) time() . ':' . wp_generate_password( 16, false );
	if ( add_option( $key, $value, '', false ) ) { return array( $key, $value ); }
	$old = get_option( $key );
	if ( is_string( $old ) && (int) $old < time() - $ttl ) {
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $key, $old ) );
		if ( $deleted ) { wp_cache_delete( $key, 'options' ); if ( add_option( $key, $value, '', false ) ) { return array( $key, $value ); } }
	}
	return false;
}

function genosys_club_unlock( $lock ) {
	if ( ! $lock ) { return; }
	global $wpdb;
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $lock[0], $lock[1] ) );
	wp_cache_delete( $lock[0], 'options' );
}

function genosys_club_rate( $scope, $limit, $window = HOUR_IN_SECONDS ) {
	$key = 'genosys_club_rate_' . hash( 'sha256', $scope );
	$lock = genosys_club_lock( 'rate:' . $scope, 5 );
	if ( ! $lock ) { return false; }
	$entry = get_transient( $key );
	if ( ! is_array( $entry ) || empty( $entry['until'] ) || $entry['until'] <= time() ) { $entry = array( 'count' => 0, 'until' => time() + $window ); }
	$allowed = $entry['count'] < $limit;
	if ( $allowed ) { ++$entry['count']; set_transient( $key, $entry, max( 1, $entry['until'] - time() ) ); }
	genosys_club_unlock( $lock );
	return $allowed;
}

/** Only the enrolled login credential supplies the enrollment phone. */
function genosys_club_enrollment_identity() {
	if ( ! is_user_logged_in() ) { return array( 'userId' => 0, 'phone' => '', 'fullName' => '' ); }
	$id = get_current_user_id();
	wp_cache_delete( $id, 'user_meta' );
	return array( 'userId' => $id, 'phone' => genosys_sms_normalize_phone( (string) get_user_meta( $id, 'genosys_phone', true ) ), 'fullName' => trim( (string) get_user_meta( $id, 'first_name', true ) . ' ' . (string) get_user_meta( $id, 'last_name', true ) ) );
}

function genosys_club_enrollment_nonce_action( $identity ) {
	return 'genosys_club_enroll_' . $identity['userId'] . '_' . genosys_sms_fingerprint( 'club-enrollment:' . wp_get_session_token() . ':' . $identity['phone'] );
}

function genosys_club_enrollment_claim_keys( $user_id, $phone, $audience = '' ) {
	$type = in_array( $audience, array( 'consumer', 'clinic' ), true ) ? $audience . '_' : '';
	return array( 'genosys_club_enrolled_user_' . $type . (int) $user_id, 'genosys_club_enrolled_phone_' . $type . genosys_sms_fingerprint( $phone ) );
}

/** Old claims did not include an audience; only their referenced record can identify it. */
function genosys_club_enrollment_claim_audience( $claim ) {
	if ( false === $claim ) { return ''; }
	if ( ! is_array( $claim ) ) { return new WP_Error( 'membership_claim', 'سابقه عضویت نیاز به بررسی پشتیبانی دارد.' ); }
	if ( in_array( $claim['audience'] ?? '', array( 'consumer', 'clinic' ), true ) ) { return $claim['audience']; }
	$id = genosys_club_claim_member_id( $claim );
	if ( ! $id ) { return new WP_Error( 'membership_claim', 'سابقه عضویت نیاز به بررسی پشتیبانی دارد.' ); }
	$post = $id ? get_post( $id ) : null;
	// An explicitly deleted old record has no recoverable audience and no longer reserves either type.
	if ( ! $post ) { return ''; }
	if ( 'genosys_club_member' !== $post->post_type ) { return new WP_Error( 'membership_claim', 'سابقه عضویت نیاز به بررسی پشتیبانی دارد.' ); }
	$record = get_post_meta( $id, '_genosys_club_record', true );
	$type = is_array( $record ) ? ( $record['data']['audience'] ?? '' ) : '';
	if ( ! in_array( $type, array( 'consumer', 'clinic' ), true ) ) { $type = get_post_meta( $id, '_genosys_club_audience', true ); }
	return in_array( $type, array( 'consumer', 'clinic' ), true ) ? $type : new WP_Error( 'membership_type', 'نوع عضویت قبلی مشخص نیست. برای بررسی با پشتیبانی تماس بگیرید.' );
}

/** Read both independent enrollments in bounded batches, including cancelled/trash records. */
function genosys_club_enrollment_prior( $user_id, $phone ) {
	global $wpdb;
	$empty = array( 'exists' => false, 'ownId' => 0, 'ownStatus' => '', 'record' => null );
	$found = array( 'consumer' => $empty, 'clinic' => $empty );
	foreach ( array_keys( $found ) as $audience ) {
		foreach ( genosys_club_enrollment_claim_keys( $user_id, $phone, $audience ) as $key ) {
			if ( false !== get_option( $key, false ) ) { $found[ $audience ]['exists'] = true; }
		}
	}
	foreach ( genosys_club_enrollment_claim_keys( $user_id, $phone ) as $key ) {
		$type = genosys_club_enrollment_claim_audience( get_option( $key, false ) );
		if ( is_wp_error( $type ) ) { return $type; }
		if ( $type ) { $found[ $type ]['exists'] = true; }
	}
	$cursor = 0;
	do {
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND ID > %d ORDER BY ID ASC LIMIT 200", 'genosys_club_member', $cursor ) );
		if ( $wpdb->last_error ) { return new WP_Error( 'database', 'بررسی عضویت ممکن نشد. دوباره تلاش کنید.' ); }
		foreach ( $ids as $id ) {
			$id = (int) $id; $cursor = $id;
			wp_cache_delete( $id, 'post_meta' );
			$post = get_post( $id );
			$record = get_post_meta( $id, '_genosys_club_record', true );
			if ( $wpdb->last_error ) { return new WP_Error( 'database', 'بررسی عضویت ممکن نشد. دوباره تلاش کنید.' ); }
			$owner = (int) get_post_meta( $id, '_genosys_club_owner_user', true );
			if ( $wpdb->last_error ) { return new WP_Error( 'database', 'بررسی عضویت ممکن نشد. دوباره تلاش کنید.' ); }
			$record_owner = is_array( $record ) ? (int) ( $record['userId'] ?? 0 ) : 0;
			$same_user = $user_id > 0 && ( $owner === (int) $user_id || $record_owner === (int) $user_id || ( $post && (int) $post->post_author === (int) $user_id ) );
			$same_phone = false;
			foreach ( array( 'phone', 'contact_phone' ) as $field ) {
				$value = is_array( $record ) && isset( $record['data'][ $field ] ) && is_string( $record['data'][ $field ] ) ? $record['data'][ $field ] : '';
				if ( $phone && $phone === genosys_sms_normalize_phone( $value ) ) { $same_phone = true; }
			}
			if ( $phone && $phone === genosys_sms_normalize_phone( (string) get_post_meta( $id, '_genosys_club_phone', true ) ) ) { $same_phone = true; }
			$type = is_array( $record ) ? ( $record['data']['audience'] ?? '' ) : '';
			if ( ! in_array( $type, array( 'consumer', 'clinic' ), true ) ) { $type = get_post_meta( $id, '_genosys_club_audience', true ); }
			if ( ! in_array( $type, array( 'consumer', 'clinic' ), true ) ) {
				if ( $same_user || $same_phone ) { return new WP_Error( 'membership_type', 'نوع عضویت قبلی مشخص نیست. برای بررسی با پشتیبانی تماس بگیرید.' ); }
				continue;
			}
			if ( $same_user || $same_phone ) { $found[ $type ]['exists'] = true; }
			// Only consistent private account ownership may expose a retry result or own status.
			if ( ! $found[ $type ]['ownId'] && $post && 'private' === $post->post_status && (int) $post->post_author === (int) $user_id && $owner === (int) $user_id && $record_owner === (int) $user_id && $user_id > 0 && is_array( $record ) ) {
				$found[ $type ]['ownId'] = $id; $found[ $type ]['record'] = $record;
				$found[ $type ]['ownStatus'] = in_array( $record['status'] ?? '', array( 'active', 'cancelled' ), true ) ? $record['status'] : '';
			}
		}
	} while ( 200 === count( $ids ) );
	return $found;
}

/** Public enrollment state contains no details of another account or a historic guest record. */
function genosys_club_enrollment_context( $selected = '' ) {
	$identity = genosys_club_enrollment_identity();
	$state = array( 'alreadySubmitted' => false, 'canSubmit' => false, 'priorStatus' => '', 'message' => '' );
	$context = array( 'isLoggedIn' => $identity['userId'] > 0, 'phone' => $identity['phone'], 'fullName' => $identity['fullName'], 'identityKey' => $identity['userId'] ? genosys_sms_fingerprint( 'club-client:' . $identity['userId'] . ':' . wp_get_session_token() ) : '', 'alreadySubmitted' => false, 'canSubmit' => false, 'priorStatus' => '', 'message' => '', 'audience' => in_array( $selected, array( 'consumer', 'clinic' ), true ) ? $selected : '', 'audiences' => array( 'consumer' => $state, 'clinic' => $state ) );
	$unavailable = static function ( $message ) use ( $context ) {
		$context['message'] = $message;
		foreach ( array( 'consumer', 'clinic' ) as $type ) { $context['audiences'][ $type ]['message'] = $message; }
		return $context;
	};
	if ( ! $identity['userId'] ) { return $unavailable( 'برای عضویت در باشگاه ابتدا وارد حساب شوید.' ); }
	if ( ! $identity['phone'] ) { return $unavailable( 'برای عضویت در باشگاه، ابتدا شماره موبایل خود را در حساب کاربری تأیید کنید.' ); }
	$prior = genosys_club_enrollment_prior( $identity['userId'], $identity['phone'] );
	if ( is_wp_error( $prior ) ) { return $unavailable( $prior->get_error_message() ); }
	foreach ( $prior as $type => $entry ) {
		$message = '';
		if ( $entry['exists'] ) {
			$label = 'clinic' === $type ? 'کلینیک / بیوتی تراپیست' : 'مصرف‌کننده';
			$message = 'active' === $entry['ownStatus'] ? 'عضویت ' . $label . ' شما قبلاً ثبت شده است.' : ( 'cancelled' === $entry['ownStatus'] ? 'عضویت ' . $label . ' شما قبلاً ثبت و لغو شده است و امکان ثبت دوباره همین نوع وجود ندارد.' : 'این نوع عضویت برای حساب یا شماره شما قبلاً ثبت شده است و امکان ثبت دوباره آن وجود ندارد.' );
		}
		$context['audiences'][ $type ] = array( 'alreadySubmitted' => $entry['exists'], 'canSubmit' => ! $entry['exists'], 'priorStatus' => $entry['ownStatus'], 'message' => $message );
	}
	$context['alreadySubmitted'] = $prior['consumer']['exists'] && $prior['clinic']['exists'];
	$context['canSubmit'] = ! $context['alreadySubmitted'];
	if ( ! $context['audience'] ) { $context['audience'] = ! $prior['clinic']['exists'] ? 'clinic' : 'consumer'; }
	$context['priorStatus'] = $context['audiences'][ $context['audience'] ]['priorStatus'];
	$context['message'] = $context['alreadySubmitted'] ? 'هر دو نوع عضویت شما قبلاً ثبت شده است و امکان ثبت دوباره وجود ندارد.' : $context['audiences'][ $context['audience'] ]['message'];
	return $context;
}

function genosys_club_prepare() {
	nocache_headers();
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || ! genosys_club_same_origin() ) { genosys_club_error( 'درخواست معتبر نیست.', 403 ); }
	if ( ! is_user_logged_in() ) { genosys_club_error( 'برای عضویت ابتدا وارد حساب شوید.', 401, '', array( 'authRequired' => true ) ); }
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
	if ( ! genosys_club_rate( 'prepare:' . $ip, 120 ) ) { genosys_club_error( 'کمی بعد دوباره تلاش کنید.', 429 ); }
	$audience = isset( $_POST['audience'] ) && is_string( $_POST['audience'] ) ? wp_unslash( $_POST['audience'] ) : '';
	$context = genosys_club_enrollment_context( $audience );
	$context['nonce'] = $context['canSubmit'] ? wp_create_nonce( genosys_club_enrollment_nonce_action( array( 'userId' => get_current_user_id(), 'phone' => $context['phone'] ) ) ) : '';
	wp_send_json_success( $context );
}
add_action( 'wp_ajax_genosys_club_prepare', 'genosys_club_prepare' );
add_action( 'wp_ajax_nopriv_genosys_club_prepare', 'genosys_club_prepare' );

function genosys_club_member_raw( $id ) {
	$post = get_post( (int) $id );
	if ( ! $post || 'genosys_club_member' !== $post->post_type || 'private' !== $post->post_status ) { return null; }
	$record = get_post_meta( $post->ID, '_genosys_club_record', true );
	return is_array( $record ) ? $record : null;
}

function genosys_club_owned_id( $user_id, $browser ) {
	$args = array( 'post_type' => 'genosys_club_member', 'post_status' => 'private', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'orderby' => 'ID', 'order' => 'ASC', 'suppress_filters' => true );
	if ( $user_id ) {
		$args['meta_query'] = array( array( 'key' => '_genosys_club_owner_user', 'value' => $user_id, 'compare' => '=' ) );
		$ids = get_posts( $args );
		if ( $ids ) { return (int) $ids[0]; }
	}
	$args['meta_query'] = array( 'relation' => 'AND', array( 'key' => '_genosys_club_owner_browser', 'value' => $browser ), array( 'key' => '_genosys_club_owner_user', 'value' => 0 ) );
	$ids = get_posts( $args );
	return $ids ? (int) $ids[0] : 0;
}

function genosys_club_update_membership_flag( $user_id ) {
	if ( ! $user_id || ! get_userdata( $user_id ) ) { return true; }
	$ids = get_posts( array( 'post_type' => 'genosys_club_member', 'post_status' => 'private', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => array( 'relation' => 'AND', array( 'key' => '_genosys_club_owner_user', 'value' => (int) $user_id ), array( 'key' => '_genosys_club_status', 'value' => 'active' ) ) ) );
	if ( $ids ) {
		$was_active = in_array( get_user_meta( $user_id, 'genosys_club_member', true ), array( true, 1, '1', 'yes' ), true );
		update_user_meta( $user_id, 'genosys_club_member', '1' );
		if ( ! $was_active || ! get_user_meta( $user_id, 'genosys_club_joined_at', true ) ) { update_user_meta( $user_id, 'genosys_club_joined_at', time() ); }
	} else { update_user_meta( $user_id, 'genosys_club_member', '0' ); }
	return ( $ids ? '1' : '0' ) === get_user_meta( $user_id, 'genosys_club_member', true ) && ( ! $ids || (int) get_user_meta( $user_id, 'genosys_club_joined_at', true ) > 0 );
}

/** A database commit succeeds before any public success response is produced. */
function genosys_club_store( $data, $user_id, $browser, $request_id ) {
	global $wpdb;
	if ( ! is_array( $data ) || ! in_array( $data['audience'] ?? '', array( 'consumer', 'clinic' ), true ) ) { return new WP_Error( 'audience', 'نوع عضویت معتبر نیست.' ); }
	$identity = genosys_club_enrollment_identity();
	if ( ! $identity['userId'] || (int) $user_id !== (int) $identity['userId'] || ! $identity['phone'] ) { return new WP_Error( 'identity', 'شماره تأییدشده حساب در دسترس نیست. صفحه را تازه کنید.' ); }
	$phone = $identity['phone'];
	$phone_field = 'clinic' === $data['audience'] ? 'contact_phone' : 'phone';
	if ( ( $data[ $phone_field ] ?? '' ) !== $phone || ! preg_match( '/^[a-f0-9]{32}$/D', $request_id ) ) { return new WP_Error( 'identity', 'شماره حساب تغییر کرده است. صفحه را تازه کنید.' ); }
	$hash = hash( 'sha256', wp_json_encode( $data ) );
	$audience = $data['audience'];
	$locks = array(); $phone_lock = false; $started = false; $id = 0; $claim_keys = genosys_club_enrollment_claim_keys( $user_id, $phone, $audience );
	try {
		foreach ( array( 'user:' . $user_id, 'enrollment' ) as $scope ) {
			$lock = genosys_club_lock( $scope, 600 );
			if ( ! $lock ) { return new WP_Error( 'busy', 'ثبت درخواست دیگری در حال انجام است. کمی بعد تلاش کنید.' ); }
			$locks[] = $lock;
		}
		// No browser lookup or upsert: old guest/foreign/cancelled records remain untouched.
		$prior = genosys_club_enrollment_prior( $user_id, $phone );
		if ( is_wp_error( $prior ) ) { return $prior; }
		$prior = $prior[ $audience ];
		if ( $prior['ownId'] && is_array( $prior['record'] ) && is_string( $prior['record']['requestId'] ?? null ) && hash_equals( $prior['record']['requestId'], $request_id ) ) {
			if ( ! is_string( $prior['record']['requestHash'] ?? null ) || ! hash_equals( $prior['record']['requestHash'], $hash ) ) { return new WP_Error( 'idempotency', 'اطلاعات این درخواست تغییر کرده است؛ دوباره تلاش کنید.' ); }
			return (int) $prior['ownId'];
		}
		if ( $prior['exists'] ) { return new WP_Error( 'already_submitted', 'این نوع عضویت برای حساب یا شماره شما قبلاً ثبت شده است و امکان ثبت دوباره آن وجود ندارد.' ); }
		// Legacy normalization scan finishes before the short lock shared with phone changes.
		$phone_lock = genosys_sms_claim_lock( 'phone:' . $phone );
		if ( ! $phone_lock ) { return new WP_Error( 'busy', 'شماره حساب در حال بررسی است. کمی بعد تلاش کنید.' ); }
		$fresh = genosys_club_enrollment_identity();
		if ( $fresh['phone'] !== $phone || (int) $fresh['userId'] !== (int) $user_id ) { return new WP_Error( 'identity', 'شماره حساب تغییر کرده است. صفحه را تازه کنید.' ); }
		foreach ( array( $wpdb->posts, $wpdb->postmeta, $wpdb->usermeta, $wpdb->options ) as $table_name ) {
			$table = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS LIKE %s', $wpdb->esc_like( $table_name ) ), ARRAY_A );
			if ( ! is_array( $table ) || 'innodb' !== strtolower( (string) ( $table['Engine'] ?? '' ) ) ) { throw new RuntimeException( 'transaction unavailable' ); }
		}
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { throw new RuntimeException( 'transaction unavailable' ); }
		$started = true;
		$credentials = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s FOR UPDATE", (int) $user_id, 'genosys_phone' ) );
		if ( ! $credentials || $wpdb->last_error ) { throw new RuntimeException( 'credential unavailable' ); }
		foreach ( $credentials as $credential ) { if ( $phone !== genosys_sms_normalize_phone( (string) $credential ) ) { throw new RuntimeException( 'credential changed' ); } }
		$now = time();
		$record = array( 'version' => 1, 'data' => $data, 'userId' => (int) $user_id, 'status' => 'active', 'joined' => $now, 'created' => $now, 'updated' => $now, 'consentedAt' => $now, 'events' => array( array( 'label' => 'عضویت در باشگاه مشتریان', 'timestamp' => $now ) ), 'requestId' => $request_id, 'requestHash' => $hash );
		$name = 'clinic' === $data['audience'] ? $data['contact_name'] : $data['full_name'];
		$city = 'clinic' === $data['audience'] ? $data['clinic_city'] : $data['city'];
		$metadata = array( '_genosys_club_record' => $record, '_genosys_club_owner_user' => (int) $user_id, '_genosys_club_owner_browser' => $browser, '_genosys_club_status' => 'active', '_genosys_club_audience' => $data['audience'], '_genosys_club_city' => $city, '_genosys_club_skin' => isset( $data['skin'] ) ? $data['skin'] : '', '_genosys_club_rating' => isset( $data['rating'] ) ? $data['rating'] : '', '_genosys_club_recommend' => isset( $data['recommend'] ) ? $data['recommend'] : '', '_genosys_club_source' => isset( $data['source'] ) ? $data['source'] : '', '_genosys_club_joined' => $now, '_genosys_club_phone' => $phone, '_genosys_club_search' => genosys_club_normalize( implode( ' ', array( $name, $phone, isset( $data['email'] ) ? $data['email'] : '', isset( $data['clinic_name'] ) ? $data['clinic_name'] : '' ) ) ) );
		$post = array( 'post_title' => $name, 'post_type' => 'genosys_club_member', 'post_status' => 'private', 'post_author' => (int) $user_id, 'post_content' => '', 'post_excerpt' => '', 'comment_status' => 'closed', 'ping_status' => 'closed' );
		$result = wp_insert_post( wp_slash( $post ), true );
		if ( is_wp_error( $result ) || ! $result ) { throw new RuntimeException( 'write failed' ); }
		$id = (int) $result;
		foreach ( $metadata as $key => $value ) {
			update_post_meta( $id, $key, wp_slash( $value ) );
			if ( get_post_meta( $id, $key, true ) != $value ) { throw new RuntimeException( 'metadata write failed' ); }
		}
		if ( ! genosys_club_update_membership_flag( $user_id ) ) { throw new RuntimeException( 'membership write failed' ); }
		$claim = array( 'userId' => (int) $user_id, 'memberId' => $id, 'audience' => $audience, 'requestId' => $request_id, 'requestHash' => $hash );
		foreach ( $claim_keys as $key ) {
			if ( ! add_option( $key, $claim, '', false ) || $claim !== get_option( $key ) ) { throw new RuntimeException( 'claim write failed' ); }
		}
		if ( false === $wpdb->query( 'COMMIT' ) ) { throw new RuntimeException( 'commit failed' ); }
		$started = false;
		return $id;
	} catch ( Throwable $e ) {
		if ( $started ) { $wpdb->query( 'ROLLBACK' ); }
		if ( $id ) { clean_post_cache( $id ); wp_cache_delete( $id, 'post_meta' ); }
		if ( $user_id ) { clean_user_cache( $user_id ); wp_cache_delete( $user_id, 'user_meta' ); }
		foreach ( $claim_keys as $key ) { wp_cache_delete( $key, 'options' ); }
		wp_cache_delete( 'notoptions', 'options' );
		return new WP_Error( 'database', 'اطلاعات ذخیره نشد. دوباره تلاش کنید.' );
	} finally {
		if ( $phone_lock ) { delete_option( $phone_lock ); }
		foreach ( array_reverse( $locks ) as $held ) { genosys_club_unlock( $held ); }
	}
}

function genosys_club_register() {
	nocache_headers();
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || ! genosys_club_same_origin() ) { genosys_club_error( 'درخواست معتبر نیست.', 403 ); }
	if ( ! is_user_logged_in() ) { genosys_club_error( 'برای عضویت ابتدا وارد حساب شوید.', 401, '', array( 'authRequired' => true ) ); }
	$identity = genosys_club_enrollment_identity();
	if ( ! $identity['phone'] ) { genosys_club_error( 'ابتدا شماره موبایل خود را در حساب کاربری تأیید کنید.', 403, '', array( 'canSubmit' => false ) ); }
	foreach ( $_POST as $key => $value ) { if ( ! is_string( $value ) || ! in_array( $key, array( 'action', 'nonce', 'website', 'request_id', 'data' ), true ) ) { genosys_club_error( 'درخواست معتبر نیست.' ); } }
	$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, genosys_club_enrollment_nonce_action( $identity ) ) ) { genosys_club_error( 'شماره یا نشست حساب تغییر کرده یا اعتبار درخواست تمام شده است. صفحه را تازه کنید.', 403, '', array( 'identityChanged' => true, 'phoneChanged' => true ) ); }
	if ( ! isset( $_POST['website'] ) || ! is_string( $_POST['website'] ) || '' !== trim( wp_unslash( $_POST['website'] ) ) ) { genosys_club_error( 'درخواست معتبر نیست.' ); }
	$request_id = isset( $_POST['request_id'] ) && is_string( $_POST['request_id'] ) ? wp_unslash( $_POST['request_id'] ) : '';
	if ( ! preg_match( '/^[a-f0-9]{32}$/D', $request_id ) ) { genosys_club_error( 'شناسه درخواست معتبر نیست.' ); }
	$json = isset( $_POST['data'] ) && is_string( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : '';
	if ( strlen( $json ) > 30000 ) { genosys_club_error( 'حجم اطلاعات بیش از حد مجاز است.' ); }
	$input = json_decode( $json, true );
	if ( ! is_array( $input ) ) { genosys_club_error( 'اطلاعات پرسشنامه معتبر نیست.' ); }
	$input['phone'] = $identity['phone']; $input['contact_phone'] = $identity['phone'];
	$data = genosys_club_validate( $input );
	if ( is_wp_error( $data ) ) { $extra = $data->get_error_data(); genosys_club_error( $data->get_error_message(), 400, is_array( $extra ) && isset( $extra['field'] ) ? $extra['field'] : '' ); }
	$user_id = $identity['userId']; $browser = genosys_club_browser();
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
	if ( ! genosys_club_rate( 'submit-ip:' . $ip, 60 ) || ! genosys_club_rate( 'submit-owner:user:' . $user_id, 12 ) ) {
		genosys_club_error( 'تعداد درخواست‌ها زیاد است. کمی بعد دوباره تلاش کنید.', 429 );
	}
	$id = genosys_club_store( $data, $user_id, $browser, $request_id );
	if ( is_wp_error( $id ) ) {
		$code = $id->get_error_code();
		$extra = 'already_submitted' === $code ? genosys_club_enrollment_context( $data['audience'] ) : ( 'identity' === $code ? array( 'identityChanged' => true, 'phoneChanged' => true ) : array() );
		genosys_club_error( $id->get_error_message(), in_array( $code, array( 'already_submitted', 'idempotency', 'busy' ), true ) ? 409 : ( 'identity' === $code ? 403 : 500 ), '', $extra );
	}
	$current = genosys_club_member_raw( $id );
	$context = genosys_club_enrollment_context( $data['audience'] );
	$context['message'] = 'active' === $current['status'] ? 'عضویت شما در باشگاه مشتریان ثبت شده است.' : 'این نوع عضویت شما قبلاً ثبت و لغو شده است و امکان ثبت دوباره آن وجود ندارد.';
	$context['memberId'] = (int) $id; $context['audience'] = $current['data']['audience']; $context['status'] = $current['status'];
	wp_send_json_success( $context );
}
add_action( 'wp_ajax_genosys_club_register', 'genosys_club_register' );
add_action( 'wp_ajax_nopriv_genosys_club_register', 'genosys_club_register' );

function genosys_club_date( $timestamp ) {
	if ( ! $timestamp ) { return ''; }
	if ( function_exists( 'genosys_sms_shamsi_date' ) ) {
		$parts = explode( '/', substr( genosys_club_ascii( genosys_sms_shamsi_date( (int) $timestamp ) ), 0, 10 ) );
		$months = array( '', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
		if ( count( $parts ) === 3 && isset( $months[ (int) $parts[1] ] ) ) { return genosys_club_digits( (int) $parts[2] . ' ' . $months[ (int) $parts[1] ] . ' ' . $parts[0] ); }
	}
	return wp_date( get_option( 'date_format', 'Y/m/d' ), (int) $timestamp );
}

function genosys_club_ago( $timestamp ) {
	if ( ! $timestamp ) { return ''; }
	$days = max( 0, (int) floor( ( time() - $timestamp ) / DAY_IN_SECONDS ) );
	if ( ! $days ) { return 'امروز'; }
	if ( $days < 30 ) { return genosys_club_digits( $days ) . ' روز پیش'; }
	if ( $days < 365 ) { return genosys_club_digits( (int) floor( $days / 30 ) ) . ' ماه پیش'; }
	return genosys_club_digits( (int) floor( $days / 365 ) ) . ' سال پیش';
}

/** Purchase activity is linked by authenticated account ID, never a typed phone. */
function genosys_club_orders( $user_id ) {
	static $cache = array();
	$empty = array( 'count' => null, 'totalText' => '', 'lastText' => '', 'lastAgo' => '', 'lastTimestamp' => 0 );
	if ( ! $user_id || ! get_userdata( $user_id ) || ! function_exists( 'wc_get_orders' ) ) { return $empty; }
	if ( isset( $cache[ $user_id ] ) ) { return $cache[ $user_id ]; }
	$result = $empty; $result['count'] = 0; $totals = array(); $page = 1;
	$statuses = array_map( static function ( $key ) { return preg_replace( '/^wc-/', '', $key ); }, array_keys( wc_get_order_statuses() ) );
	$statuses = array_values( array_diff( $statuses, array( 'checkout-draft', 'trash', 'auto-draft' ) ) );
	$paid = function_exists( 'wc_get_is_paid_statuses' ) ? wc_get_is_paid_statuses() : array( 'processing', 'completed' );
	do {
		$orders = wc_get_orders( array( 'customer_id' => (int) $user_id, 'type' => 'shop_order', 'status' => $statuses, 'limit' => 100, 'page' => $page, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects' ) );
		foreach ( $orders as $order ) {
			if ( (int) $order->get_customer_id() !== (int) $user_id ) { continue; }
			++$result['count']; $date = $order->get_date_created();
			if ( $date && $date->getTimestamp() > $result['lastTimestamp'] ) { $result['lastTimestamp'] = $date->getTimestamp(); }
			if ( in_array( $order->get_status(), $paid, true ) ) {
				$currency = $order->get_currency(); if ( ! isset( $totals[ $currency ] ) ) { $totals[ $currency ] = 0.0; }
				$totals[ $currency ] += max( 0.0, (float) $order->get_total() - (float) $order->get_total_refunded() );
			}
		}
		++$page;
	} while ( count( $orders ) === 100 );
	$amounts = array();
	foreach ( $totals as $currency => $total ) { $amounts[] = html_entity_decode( wp_strip_all_tags( wc_price( $total, array( 'currency' => $currency ) ) ), ENT_QUOTES, 'UTF-8' ); }
	$result['totalText'] = implode( ' + ', $amounts );
	$result['lastText'] = genosys_club_date( $result['lastTimestamp'] ); $result['lastAgo'] = genosys_club_ago( $result['lastTimestamp'] );
	$cache[ $user_id ] = $result;
	return $result;
}

function genosys_club_member_data( $id, $details = true, $with_orders = true ) {
	if ( ! genosys_club_can_manage() ) { return null; }
	$record = genosys_club_member_raw( $id );
	if ( ! $record || empty( $record['data']['audience'] ) ) { return null; }
	$d = $record['data']; $labels = genosys_club_labels(); $clinic = 'clinic' === $d['audience'];
	$name = $clinic ? $d['contact_name'] : $d['full_name'];
	$initial = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1, 'UTF-8' ) : wp_html_excerpt( $name, 1, '' );
	$avatar_id = $record['userId'] ? absint( get_user_meta( $record['userId'], 'genosys_avatar_id', true ) ) : 0;
	$avatar = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) : '';
	$out = array(
		'id' => (int) $id, 'userId' => (int) $record['userId'], 'audience' => $d['audience'], 'audienceLabel' => $clinic ? 'کلینیک / بیوتی تراپیست' : 'مصرف کننده',
		'name' => $name, 'phone' => $clinic ? $d['contact_phone'] : $d['phone'], 'email' => isset( $d['email'] ) ? $d['email'] : '', 'city' => $clinic ? $d['clinic_city'] : $d['city'], 'status' => $record['status'],
		'avatarUrl' => $avatar ?: '', 'initial' => $initial, 'joinedTimestamp' => (int) $record['joined'], 'joinedText' => genosys_club_date( $record['joined'] ), 'joinedAgo' => genosys_club_ago( $record['joined'] ),
		'ageLabel' => ! $clinic && ! empty( $d['age_range'] ) && isset( $labels['age_range'][ $d['age_range'] ] ) ? $labels['age_range'][ $d['age_range'] ] : '',
		'skin' => isset( $d['skin'] ) ? $d['skin'] : '', 'skinLabel' => isset( $d['skin'] ) ? $d['skin'] : '', 'concerns' => isset( $d['concerns'] ) ? $d['concerns'] : array(), 'categories' => isset( $d['categories'] ) ? $d['categories'] : array(),
		'rating' => isset( $d['rating'] ) ? (int) $d['rating'] : null, 'nps' => isset( $d['recommend'] ) ? (int) $d['recommend'] : null,
		'sourceLabel' => ! empty( $d['source'] ) && isset( $labels['source'][ $d['source'] ] ) ? $labels['source'][ $d['source'] ] : '', 'message' => $clinic ? $d['clinic_message'] : $d['message'], 'consent' => ! empty( $d['consent'] ),
		'clinicName' => $clinic ? $d['clinic_name'] : '', 'contactName' => $clinic ? $d['contact_name'] : '', 'landline' => $clinic ? $d['clinic_landline'] : '',
		'activityLabel' => $clinic && ! empty( $d['activity'] ) && isset( $labels['activity'][ $d['activity'] ] ) ? $labels['activity'][ $d['activity'] ] : '',
		'experienceLabel' => $clinic && isset( $labels['experience'][ $d['experience'] ] ) ? $labels['experience'][ $d['experience'] ] : '', 'services' => $clinic ? $d['services'] : array(), 'clientNeeds' => $clinic ? $d['client_needs'] : array(),
		'consumptionLabel' => $clinic && isset( $labels['monthly_consumption'][ $d['monthly_consumption'] ] ) ? $labels['monthly_consumption'][ $d['monthly_consumption'] ] : '',
		'monthlyClientsLabel' => $clinic && ! empty( $d['monthly_clients'] ) && isset( $labels['monthly_clients'][ $d['monthly_clients'] ] ) ? $labels['monthly_clients'][ $d['monthly_clients'] ] : '',
		'orders' => $with_orders ? genosys_club_orders( (int) $record['userId'] ) : array( 'count' => null, 'totalText' => '', 'lastText' => '', 'lastAgo' => '', 'lastTimestamp' => 0 ), 'timeline' => array(),
	);
	if ( $details ) {
		$out['note'] = (string) get_post_meta( (int) $id, '_genosys_club_note', true );
		foreach ( isset( $record['events'] ) ? $record['events'] : array() as $event ) { if ( isset( $event['label'], $event['timestamp'] ) ) { $out['timeline'][] = array( 'label' => $event['label'], 'dateText' => genosys_club_date( $event['timestamp'] ) ); } }
		if ( $out['orders']['lastTimestamp'] ) { $out['timeline'][] = array( 'label' => 'آخرین سفارش', 'dateText' => $out['orders']['lastText'] ); }
	}
	return $out;
}

function genosys_club_filters( $source = null ) {
	$source = is_array( $source ) ? $source : $_GET;
	$read = static function ( $key ) use ( $source ) { return isset( $source[ $key ] ) && ( is_string( $source[ $key ] ) || is_int( $source[ $key ] ) ) ? sanitize_text_field( (string) $source[ $key ] ) : ''; };
	$labels = genosys_club_labels(); $search = $read( 'search' ); $city = $read( 'city' );
	if ( genosys_club_length( $search ) > 160 ) { $search = function_exists( 'mb_substr' ) ? mb_substr( $search, 0, 160, 'UTF-8' ) : ''; }
	if ( genosys_club_length( $city ) > 100 ) { $city = ''; }
	return array( 'status' => 'cancelled' === $read( 'status' ) ? 'cancelled' : 'active', 'search' => $search, 'audience' => in_array( $read( 'audience' ), array( 'consumer', 'clinic' ), true ) ? $read( 'audience' ) : '', 'skin' => in_array( $read( 'skin' ), $labels['skin'], true ) ? $read( 'skin' ) : '', 'city' => $city, 'rating' => preg_match( '/^[1-5]$/D', $read( 'rating' ) ) ? $read( 'rating' ) : '', 'source' => isset( $labels['source'][ $read( 'source' ) ] ) ? $read( 'source' ) : '', 'sort' => 'oldest' === $read( 'sort' ) ? 'oldest' : 'newest', 'page' => preg_match( '/^[0-9]{1,8}$/D', $read( 'page' ) ) ? max( 1, (int) $read( 'page' ) ) : 1 );
}

function genosys_club_query_args( $filters, $page_size = 8 ) {
	$filters = genosys_club_filters( $filters );
	$meta = array( 'relation' => 'AND', array( 'key' => '_genosys_club_status', 'value' => $filters['status'] ) );
	foreach ( array( 'audience', 'skin', 'city', 'rating', 'source' ) as $key ) { if ( '' !== $filters[ $key ] ) { $meta[] = array( 'key' => '_genosys_club_' . $key, 'value' => $filters[ $key ] ); } }
	$search_phone = genosys_club_phone( $filters['search'] );
	$terms = preg_split( '/\s+/u', genosys_club_normalize( $search_phone ?: $filters['search'] ), -1, PREG_SPLIT_NO_EMPTY );
	foreach ( array_slice( $terms, 0, 12 ) as $term ) { $meta[] = array( 'key' => '_genosys_club_search', 'value' => $term, 'compare' => 'LIKE' ); }
	return array( 'post_type' => 'genosys_club_member', 'post_status' => 'private', 'posts_per_page' => $page_size, 'paged' => $filters['page'], 'fields' => 'ids', 'orderby' => array( 'meta_value_num' => 'oldest' === $filters['sort'] ? 'ASC' : 'DESC', 'ID' => 'oldest' === $filters['sort'] ? 'ASC' : 'DESC' ), 'meta_key' => '_genosys_club_joined', 'meta_query' => $meta, 'suppress_filters' => true, 'ignore_sticky_posts' => true );
}

function genosys_club_filtered_member_ids( $filters = null ) {
	if ( ! genosys_club_can_manage() ) { return array(); }
	$args = genosys_club_query_args( genosys_club_filters( $filters ), -1 ); $args['no_found_rows'] = true;
	return array_map( 'intval', get_posts( $args ) );
}

function genosys_club_admin_context( $filters = null ) {
	$filters = genosys_club_filters( $filters );
	$out = array( 'filters' => $filters, 'counts' => array( 'active' => 0, 'cancelled' => 0 ), 'stats' => array( 'active' => 0, 'new30' => 0, 'averageRating' => null, 'nps' => null ), 'options' => array( 'cities' => array(), 'skins' => array(), 'sources' => array() ), 'members' => array(), 'total' => 0, 'from' => 0, 'to' => 0, 'page' => 1, 'pages' => 1, 'pageSize' => 8, 'exportUrl' => '' );
	if ( ! genosys_club_can_manage() ) { return $out; }
	global $wpdb;
	$counts = $wpdb->get_results( $wpdb->prepare( "SELECT m.meta_value AS status, COUNT(DISTINCT p.ID) AS amount FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key=%s WHERE p.post_type=%s AND p.post_status='private' GROUP BY m.meta_value", '_genosys_club_status', 'genosys_club_member' ), ARRAY_A );
	foreach ( $counts as $row ) { if ( isset( $out['counts'][ $row['status'] ] ) ) { $out['counts'][ $row['status'] ] = (int) $row['amount']; } }
	$stats = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(CASE WHEN CAST(j.meta_value AS UNSIGNED)>=%d THEN 1 ELSE 0 END) AS new30, AVG(CASE WHEN a.meta_value='consumer' AND r.meta_value REGEXP '^[1-5]$' THEN CAST(r.meta_value AS DECIMAL(4,2)) ELSE NULL END) AS average_rating, SUM(CASE WHEN a.meta_value='consumer' AND n.meta_value REGEXP '^([0-9]|10)$' THEN 1 ELSE 0 END) AS nps_count, SUM(CASE WHEN a.meta_value='consumer' AND n.meta_value REGEXP '^([0-9]|10)$' AND CAST(n.meta_value AS UNSIGNED)>=9 THEN 1 ELSE 0 END) AS promoters, SUM(CASE WHEN a.meta_value='consumer' AND n.meta_value REGEXP '^([0-9]|10)$' AND CAST(n.meta_value AS UNSIGNED)<=6 THEN 1 ELSE 0 END) AS detractors FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_genosys_club_status' AND s.meta_value='active' LEFT JOIN {$wpdb->postmeta} j ON j.post_id=p.ID AND j.meta_key='_genosys_club_joined' LEFT JOIN {$wpdb->postmeta} a ON a.post_id=p.ID AND a.meta_key='_genosys_club_audience' LEFT JOIN {$wpdb->postmeta} r ON r.post_id=p.ID AND r.meta_key='_genosys_club_rating' LEFT JOIN {$wpdb->postmeta} n ON n.post_id=p.ID AND n.meta_key='_genosys_club_recommend' WHERE p.post_type=%s AND p.post_status='private'", time() - 30 * DAY_IN_SECONDS, 'genosys_club_member' ), ARRAY_A );
	$out['stats']['active'] = $out['counts']['active'];
	$out['stats']['new30'] = $stats ? (int) $stats['new30'] : 0;
	$out['stats']['averageRating'] = $stats && null !== $stats['average_rating'] ? round( (float) $stats['average_rating'], 1 ) : null;
	$out['stats']['nps'] = $stats && (int) $stats['nps_count'] > 0 ? (int) round( ( (int) $stats['promoters'] - (int) $stats['detractors'] ) * 100 / (int) $stats['nps_count'] ) : null;
	$out['options']['cities'] = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT c.meta_value FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} c ON c.post_id=p.ID AND c.meta_key=%s WHERE p.post_type=%s AND p.post_status='private' AND c.meta_value<>'' ORDER BY c.meta_value ASC", '_genosys_club_city', 'genosys_club_member' ) );
	$labels = genosys_club_labels();
	foreach ( $labels['skin'] as $skin ) { $out['options']['skins'][] = array( 'value' => $skin, 'label' => $skin ); }
	foreach ( $labels['source'] as $value => $label ) { $out['options']['sources'][] = array( 'value' => $value, 'label' => $label ); }
	$query = new WP_Query( genosys_club_query_args( $filters ) );
	$out['total'] = (int) $query->found_posts; $out['pages'] = max( 1, (int) $query->max_num_pages ); $out['page'] = min( $filters['page'], $out['pages'] );
	if ( $out['page'] !== $filters['page'] ) { $filters['page'] = $out['page']; $query = new WP_Query( genosys_club_query_args( $filters ) ); }
	$out['filters'] = $filters;
	foreach ( $query->posts as $id ) { $member = genosys_club_member_data( $id, false ); if ( $member ) { $out['members'][] = $member; } }
	$out['from'] = $out['total'] ? ( $out['page'] - 1 ) * 8 + 1 : 0; $out['to'] = min( $out['total'], $out['page'] * 8 );
	$export_args = array( 'action' => 'genosys_club_export', '_wpnonce' => wp_create_nonce( 'genosys_club_admin' ), 'filters' => wp_json_encode( $filters ) );
	$out['exportUrl'] = add_query_arg( array_map( 'rawurlencode', $export_args ), admin_url( 'admin-post.php' ) );
	return $out;
}

function genosys_club_admin_authorize() {
	nocache_headers();
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || ! genosys_club_can_manage() || ! genosys_club_same_origin() ) { genosys_club_error( 'دسترسی مجاز نیست.', 403 ); }
	$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'genosys_club_admin' ) ) { genosys_club_error( 'زمان درخواست گذشته است. صفحه را تازه کنید.', 403 ); }
}

function genosys_club_admin_id() {
	$id = isset( $_POST['id'] ) && is_string( $_POST['id'] ) && preg_match( '/^[1-9][0-9]{0,17}$/D', $_POST['id'] ) ? (int) $_POST['id'] : 0;
	if ( ! $id || ! genosys_club_member_raw( $id ) ) { genosys_club_error( 'عضو پیدا نشد.', 404 ); }
	return $id;
}

function genosys_club_ajax_list() {
	genosys_club_admin_authorize();
	$filters = isset( $_POST['filters'] ) && is_string( $_POST['filters'] ) ? json_decode( wp_unslash( $_POST['filters'] ), true ) : array();
	wp_send_json_success( array( 'context' => genosys_club_admin_context( $filters ) ) );
}
add_action( 'wp_ajax_genosys_club_list', 'genosys_club_ajax_list' );

function genosys_club_ajax_detail() {
	genosys_club_admin_authorize(); $id = genosys_club_admin_id();
	wp_send_json_success( array( 'member' => genosys_club_member_data( $id ) ) );
}
add_action( 'wp_ajax_genosys_club_detail', 'genosys_club_ajax_detail' );

function genosys_club_ajax_note() {
	genosys_club_admin_authorize(); $id = genosys_club_admin_id();
	$note = isset( $_POST['note'] ) && is_string( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : null;
	if ( null === $note || genosys_club_length( $note ) > 5000 ) { genosys_club_error( 'یادداشت باید حداکثر ۵۰۰۰ نویسه باشد.', 400, 'note' ); }
	$lock = genosys_club_lock( 'member:' . $id, 600 );
	if ( ! $lock ) { genosys_club_error( 'درخواست دیگری در حال انجام است. دوباره تلاش کنید.', 409 ); }
	clean_post_cache( $id ); wp_cache_delete( $id, 'post_meta' );
	if ( ! genosys_club_member_raw( $id ) ) { genosys_club_unlock( $lock ); genosys_club_error( 'عضو پیدا نشد.', 404 ); }
	$note = sanitize_textarea_field( $note ); update_post_meta( $id, '_genosys_club_note', wp_slash( $note ) );
	$saved = get_post_meta( $id, '_genosys_club_note', true ) === $note;
	genosys_club_unlock( $lock );
	if ( ! $saved ) { genosys_club_error( 'یادداشت ذخیره نشد.', 500 ); }
	wp_send_json_success( array( 'member' => genosys_club_member_data( $id ) ) );
}
add_action( 'wp_ajax_genosys_club_note', 'genosys_club_ajax_note' );

function genosys_club_ajax_status() {
	genosys_club_admin_authorize(); $id = genosys_club_admin_id();
	$status = isset( $_POST['status'] ) && is_string( $_POST['status'] ) ? wp_unslash( $_POST['status'] ) : '';
	if ( ! in_array( $status, array( 'active', 'cancelled' ), true ) ) { genosys_club_error( 'وضعیت معتبر نیست.' ); }
	$record = genosys_club_member_raw( $id );
	$locks = array(); $scopes = array( 'browser:' . get_post_meta( $id, '_genosys_club_owner_browser', true ), 'member:' . $id );
	if ( $record['userId'] ) { $scopes[] = 'user:' . (int) $record['userId']; }
	sort( $scopes );
	foreach ( $scopes as $scope ) { $lock = genosys_club_lock( $scope ); if ( ! $lock ) { foreach ( $locks as $held ) { genosys_club_unlock( $held ); } genosys_club_error( 'درخواست دیگری در حال انجام است. دوباره تلاش کنید.', 409 ); } $locks[] = $lock; }
	// A request may have read this row before another owner's write completed.
	wp_cache_delete( $id, 'post_meta' );
	$record = genosys_club_member_raw( $id );
	if ( ! $record ) { foreach ( $locks as $held ) { genosys_club_unlock( $held ); } genosys_club_error( 'عضو پیدا نشد.', 404 ); }
	$fresh_scopes = array( 'browser:' . get_post_meta( $id, '_genosys_club_owner_browser', true ) );
	if ( $record['userId'] ) { $fresh_scopes[] = 'user:' . (int) $record['userId']; }
	if ( array_diff( $fresh_scopes, $scopes ) ) { foreach ( $locks as $held ) { genosys_club_unlock( $held ); } genosys_club_error( 'اطلاعات عضو هم‌زمان تغییر کرده است. دوباره تلاش کنید.', 409 ); }
	global $wpdb;
	$failed = false; $started = false;
	try {
		if ( $record['status'] !== $status ) {
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) { throw new RuntimeException( 'transaction failed' ); }
			$started = true; $record['status'] = $status; $record['updated'] = time();
			if ( 'active' === $status ) { $record['joined'] = time(); }
			$record['events'][] = array( 'label' => 'active' === $status ? 'تمدید عضویت توسط مدیر' : 'لغو عضویت در باشگاه مشتریان', 'timestamp' => time() );
			$record['events'] = array_slice( $record['events'], -50 );
			update_post_meta( $id, '_genosys_club_record', wp_slash( $record ) );
			update_post_meta( $id, '_genosys_club_status', $status );
			update_post_meta( $id, '_genosys_club_joined', $record['joined'] );
			$stored = genosys_club_member_raw( $id );
			if ( ! $stored || $stored['status'] !== $status || get_post_meta( $id, '_genosys_club_status', true ) !== $status || (int) get_post_meta( $id, '_genosys_club_joined', true ) !== (int) $record['joined'] || ! genosys_club_update_membership_flag( (int) $record['userId'] ) ) { throw new RuntimeException( 'status write failed' ); }
			if ( false === $wpdb->query( 'COMMIT' ) ) { throw new RuntimeException( 'commit failed' ); }
		}
	} catch ( Throwable $e ) {
		if ( $started ) { $wpdb->query( 'ROLLBACK' ); }
		clean_post_cache( $id ); wp_cache_delete( $id, 'post_meta' );
		if ( $record['userId'] ) { clean_user_cache( $record['userId'] ); wp_cache_delete( $record['userId'], 'user_meta' ); }
		$failed = true;
	}
	foreach ( $locks as $held ) { genosys_club_unlock( $held ); }
	if ( $failed ) { genosys_club_error( 'وضعیت ذخیره نشد.', 500 ); }
	wp_send_json_success( array( 'member' => genosys_club_member_data( $id ) ) );
}
add_action( 'wp_ajax_genosys_club_status', 'genosys_club_ajax_status' );

/** Only exact record references may be released; another enrollment's claims are never removed. */
function genosys_club_claim_member_id( $claim ) {
	$value = is_array( $claim ) ? ( $claim['memberId'] ?? null ) : null;
	return ( is_int( $value ) && $value > 0 ) || ( is_string( $value ) && preg_match( '/^[1-9][0-9]{0,17}$/D', $value ) ) ? (int) $value : 0;
}

function genosys_club_record_user_ids( $id, $record ) {
	$post = get_post( $id );
	$ids = array( (int) ( $record['userId'] ?? 0 ), (int) get_post_meta( $id, '_genosys_club_owner_user', true ), $post ? (int) $post->post_author : 0 );
	$ids = array_values( array_unique( array_filter( $ids, static function ( $value ) { return $value > 0; } ) ) );
	sort( $ids, SORT_NUMERIC );
	return $ids;
}

/** Administrator deletion releases this record's two claims and preserves every other membership. */
function genosys_club_delete_member( $id ) {
	if ( ! genosys_club_can_manage() ) { return new WP_Error( 'forbidden', 'دسترسی مجاز نیست.' ); }
	$record = genosys_club_member_raw( $id );
	if ( ! $record ) { return new WP_Error( 'not_found', 'عضو پیدا نشد.' ); }
	$user_ids = genosys_club_record_user_ids( $id, $record );
	$browser = (string) get_post_meta( $id, '_genosys_club_owner_browser', true );
	$scopes = array_map( static function ( $user_id ) { return 'user:' . $user_id; }, $user_ids );
	$scopes[] = 'enrollment';
	$scopes[] = 'member:' . $id;
	$scopes[] = 'browser:' . $browser;
	$locks = array(); $claims = array(); $started = false;
	global $wpdb;
	try {
		foreach ( $scopes as $scope ) {
			$lock = genosys_club_lock( $scope, 600 );
			if ( ! $lock ) { return new WP_Error( 'busy', 'درخواست دیگری در حال انجام است. دوباره تلاش کنید.' ); }
			$locks[] = $lock;
		}
		clean_post_cache( $id ); wp_cache_delete( $id, 'post_meta' );
		$record = genosys_club_member_raw( $id );
		if ( ! $record ) { return new WP_Error( 'not_found', 'عضو پیدا نشد.' ); }
		if ( $user_ids !== genosys_club_record_user_ids( $id, $record ) || $browser !== (string) get_post_meta( $id, '_genosys_club_owner_browser', true ) ) { return new WP_Error( 'busy', 'اطلاعات عضو هم‌زمان تغییر کرده است. دوباره تلاش کنید.' ); }
		foreach ( array( $wpdb->posts, $wpdb->postmeta, $wpdb->usermeta, $wpdb->options, $wpdb->comments, $wpdb->commentmeta, $wpdb->term_relationships ) as $table_name ) {
			$table = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS LIKE %s', $wpdb->esc_like( $table_name ) ), ARRAY_A );
			if ( ! is_array( $table ) || 'innodb' !== strtolower( (string) ( $table['Engine'] ?? '' ) ) ) { throw new RuntimeException( 'transaction unavailable' ); }
		}
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { throw new RuntimeException( 'transaction unavailable' ); }
		$started = true;
		// Existing untyped and new typed claims may all refer to this one record.
		$cursor = 0;
		do {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_id, option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND option_id > %d ORDER BY option_id ASC LIMIT 200", $wpdb->esc_like( 'genosys_club_enrolled_' ) . '%', $cursor ), ARRAY_A );
			if ( $wpdb->last_error ) { throw new RuntimeException( 'claim read failed' ); }
			foreach ( $rows as $row ) {
				$cursor = (int) $row['option_id'];
				if ( genosys_club_claim_member_id( maybe_unserialize( $row['option_value'] ) ) !== (int) $id ) { continue; }
				$claims[] = $row['option_name'];
				if ( 1 !== $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_id = %d AND option_value = %s", $cursor, $row['option_value'] ) ) ) { throw new RuntimeException( 'claim delete failed' ); }
				wp_cache_delete( $row['option_name'], 'options' );
			}
		} while ( count( $rows ) === 200 );
		$deleted = wp_delete_post( (int) $id, true );
		if ( ! $deleted || (int) $deleted->ID !== (int) $id ) { throw new RuntimeException( 'record delete failed' ); }
		$remaining_post = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE ID = %d", $id ) );
		if ( $wpdb->last_error || '0' !== (string) $remaining_post ) { throw new RuntimeException( 'record delete failed' ); }
		$remaining_meta = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d", $id ) );
		if ( $wpdb->last_error || '0' !== (string) $remaining_meta ) { throw new RuntimeException( 'metadata delete failed' ); }
		$remaining_comments = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_post_ID = %d", $id ) );
		if ( $wpdb->last_error || '0' !== (string) $remaining_comments ) { throw new RuntimeException( 'comment delete failed' ); }
		$remaining_terms = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_relationships} WHERE object_id = %d", $id ) );
		if ( $wpdb->last_error || '0' !== (string) $remaining_terms ) { throw new RuntimeException( 'relationship delete failed' ); }
		foreach ( $user_ids as $user_id ) {
			if ( ! genosys_club_update_membership_flag( $user_id ) ) { throw new RuntimeException( 'membership write failed' ); }
		}
		if ( false === $wpdb->query( 'COMMIT' ) ) { throw new RuntimeException( 'commit failed' ); }
		$started = false;
		return true;
	} catch ( Throwable $e ) {
		if ( $started ) { $wpdb->query( 'ROLLBACK' ); }
		return new WP_Error( 'database', 'اطلاعات حذف نشد. دوباره تلاش کنید.' );
	} finally {
		// WordPress caches are not transactional, including a rollback after wp_delete_post().
		clean_post_cache( $id ); wp_cache_delete( $id, 'post_meta' );
		foreach ( $user_ids as $user_id ) { clean_user_cache( $user_id ); wp_cache_delete( $user_id, 'user_meta' ); }
		foreach ( $claims as $key ) { wp_cache_delete( $key, 'options' ); }
		wp_cache_delete( 'alloptions', 'options' ); wp_cache_delete( 'notoptions', 'options' );
		foreach ( array_reverse( $locks ) as $held ) { genosys_club_unlock( $held ); }
	}
}

function genosys_club_ajax_delete() {
	genosys_club_admin_authorize();
	foreach ( $_POST as $key => $value ) {
		if ( ! is_string( $value ) || ! in_array( $key, array( 'action', 'nonce', 'id', 'confirm', 'filters' ), true ) ) { genosys_club_error( 'درخواست معتبر نیست.', 400 ); }
	}
	if ( ! isset( $_POST['confirm'] ) || 'delete' !== wp_unslash( $_POST['confirm'] ) ) { genosys_club_error( 'حذف کامل عضویت باید تأیید شود.', 400 ); }
	$filters = isset( $_POST['filters'] ) ? json_decode( wp_unslash( $_POST['filters'] ), true ) : array();
	if ( ! is_array( $filters ) ) { genosys_club_error( 'فیلترها معتبر نیستند.', 400 ); }
	$id = genosys_club_admin_id();
	$result = genosys_club_delete_member( $id );
	if ( is_wp_error( $result ) ) {
		$status = 'busy' === $result->get_error_code() ? 409 : ( 'not_found' === $result->get_error_code() ? 404 : ( 'forbidden' === $result->get_error_code() ? 403 : 500 ) );
		genosys_club_error( $result->get_error_message(), $status );
	}
	wp_send_json_success( array( 'deletedId' => (int) $id, 'context' => genosys_club_admin_context( $filters ), 'message' => 'این عضویت به‌طور کامل حذف شد. ثبت دوباره همین نوع عضویت امکان‌پذیر است.' ) );
}
add_action( 'wp_ajax_genosys_club_delete', 'genosys_club_ajax_delete' );

/** Leading zeroes and spreadsheet formula characters stay literal in CSV readers. */
function genosys_club_csv_cell( $value, $phone = false ) {
	$value = str_replace( array( "\r", "\n", "\t", "\0" ), ' ', (string) $value );
	if ( $phone && '' !== $value ) { return "'" . $value; }
	return preg_match( '/^[\s\x{FEFF}]*(?:[=+@-]|[\x{FF1D}\x{FF0B}\x{FF0D}\x{FF20}])/u', $value ) ? "'" . $value : $value;
}

function genosys_club_export() {
	nocache_headers();
	$nonce = isset( $_GET['_wpnonce'] ) && is_string( $_GET['_wpnonce'] ) ? wp_unslash( $_GET['_wpnonce'] ) : '';
	if ( ! genosys_club_can_manage() || ! wp_verify_nonce( $nonce, 'genosys_club_admin' ) ) { wp_die( 'دسترسی مجاز نیست.', 'دسترسی مجاز نیست', array( 'response' => 403 ) ); }
	$filters = isset( $_GET['filters'] ) && is_string( $_GET['filters'] ) ? json_decode( wp_unslash( $_GET['filters'] ), true ) : array();
	$filters = genosys_club_filters( $filters ); $filters['page'] = 1;
	if ( headers_sent() ) { wp_die( 'خروجی در دسترس نیست.' ); }
	header( 'Content-Type: text/csv; charset=UTF-8' ); header( 'Content-Disposition: attachment; filename="genosys-club-members-' . wp_date( 'Y-m-d' ) . '.csv"' ); header( 'X-Content-Type-Options: nosniff' );
	$output = fopen( 'php://output', 'w' ); fwrite( $output, "\xEF\xBB\xBF" );
	fputcsv( $output, array( 'شناسه', 'نوع عضو', 'نام', 'موبایل', 'ایمیل', 'شهر', 'وضعیت', 'تاریخ عضویت', 'نوع پوست', 'دغدغه‌ها', 'دسته محصولات', 'رضایت از ۵', 'احتمال معرفی از ۱۰', 'منبع آشنایی', 'نظر', 'نام کلینیک', 'شماره ثابت', 'نوع فعالیت', 'سابقه کاری', 'خدمات', 'نیاز مشتریان', 'مصرف ماهانه', 'مشتریان ماهانه', 'رضایت به دریافت اطلاعات' ) );
	do {
		$args = genosys_club_query_args( $filters, 100 ); $args['no_found_rows'] = true; $ids = get_posts( $args );
		foreach ( $ids as $id ) {
			$m = genosys_club_member_data( $id, false, false ); if ( ! $m ) { continue; }
			$row = array( $m['id'], $m['audienceLabel'], $m['name'], genosys_club_csv_cell( $m['phone'], true ), $m['email'], $m['city'], 'active' === $m['status'] ? 'فعال' : 'لغو شده', $m['joinedText'], $m['skinLabel'], implode( '، ', $m['concerns'] ), implode( '، ', $m['categories'] ), null === $m['rating'] ? '' : $m['rating'], null === $m['nps'] ? '' : $m['nps'], $m['sourceLabel'], $m['message'], $m['clinicName'], genosys_club_csv_cell( $m['landline'], true ), $m['activityLabel'], $m['experienceLabel'], implode( '، ', $m['services'] ), implode( '، ', $m['clientNeeds'] ), $m['consumptionLabel'], $m['monthlyClientsLabel'], $m['consent'] ? 'موافق' : 'ناموافق' );
			foreach ( $row as $key => $value ) { if ( ! in_array( $key, array( 3, 16 ), true ) ) { $row[ $key ] = genosys_club_csv_cell( $value ); } }
			fputcsv( $output, $row );
		}
		++$filters['page'];
	} while ( count( $ids ) === 100 );
	fclose( $output ); exit;
}
add_action( 'admin_post_genosys_club_export', 'genosys_club_export' );
