<?php
/** Private customer dashboard and current-customer profile updates. */
defined( 'ABSPATH' ) || exit;

/** Account root and native address editing share this shell; other endpoints retain their routes. */
function genosys_is_account_dashboard() {
	return is_user_logged_in() && function_exists( 'is_account_page' ) && function_exists( 'is_wc_endpoint_url' ) && is_account_page() && ( ! is_wc_endpoint_url() || is_wc_endpoint_url( 'edit-address' ) );
}

function genosys_account_dashboard_template( $template ) {
	return genosys_is_account_dashboard() ? get_theme_file_path( '/page-account-dashboard.php' ) : $template;
}
add_filter( 'template_include', 'genosys_account_dashboard_template', 99 );

function genosys_account_dashboard_nocache() {
	if ( genosys_is_account_dashboard() ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	}
}
add_action( 'template_redirect', 'genosys_account_dashboard_nocache', 1 );

function genosys_account_dashboard_assets() {
	if ( ! genosys_is_account_dashboard() ) {
		return;
	}
	wp_enqueue_style( 'genosys-account-dashboard', get_theme_file_uri( '/assets/css/account-dashboard.css' ), array( 'genosys-style' ), '1.5.81' );
	wp_enqueue_script( 'genosys-account-dashboard', get_theme_file_uri( '/assets/js/account-dashboard.js' ), array(), '1.5.83', true );
	wp_localize_script( 'genosys-account-dashboard', 'genosysAccountDashboard', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'genosys_account_dashboard' ),
		'saveAction' => 'genosys_account_save',
		'avatarAction' => 'genosys_account_avatar',
		'notificationAction' => 'genosys_account_notifications_save',
		'notificationGetAction' => 'genosys_account_notifications_get',
		'phoneRequestAction' => 'genosys_account_phone_request',
		'phoneResendAction' => 'genosys_account_phone_resend',
		'phoneVerifyAction' => 'genosys_account_phone_verify',
	) );
}
add_action( 'wp_enqueue_scripts', 'genosys_account_dashboard_assets', 20 );

function genosys_account_dashboard_body_class( $classes ) {
	if ( genosys_is_account_dashboard() ) {
		$classes[] = 'genosys-account-dashboard';
	}
	return $classes;
}
add_filter( 'body_class', 'genosys_account_dashboard_body_class' );

function genosys_account_dashboard_digits_ascii( $value ) {
	return strtr( (string) $value, array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9' ) );
}

function genosys_account_dashboard_digits( $value ) {
	return function_exists( 'genosys_persian_number' ) ? genosys_persian_number( $value ) : (string) $value;
}

function genosys_account_dashboard_shamsi( $timestamp ) {
	return function_exists( 'genosys_sms_shamsi_date' ) ? substr( genosys_account_dashboard_digits_ascii( genosys_sms_shamsi_date( $timestamp ) ), 0, 10 ) : '';
}

/** Validate against the theme's established calendar, including leap-day and future dates. */
function genosys_account_dashboard_birth( $day, $month, $year ) {
	$empty = array( 'day' => '', 'month' => '', 'year' => '' );
	$raw = array_map( 'genosys_account_dashboard_digits_ascii', array( $day, $month, $year ) );
	if ( array( '', '', '' ) === $raw ) {
		return $empty;
	}
	foreach ( $raw as $part ) {
		if ( ! preg_match( '/^[0-9]{1,4}$/D', $part ) ) {
			return new WP_Error( 'invalid_birth', 'تاریخ تولد معتبر را کامل انتخاب کنید.' );
		}
	}
	list( $d, $m, $y ) = array_map( 'intval', $raw );
	$today = genosys_account_dashboard_shamsi( time() );
	if ( ! $today || $y < 1200 || $y > (int) substr( $today, 0, 4 ) || $m < 1 || $m > 12 || $d < 1 || $d > ( $m <= 6 ? 31 : 30 ) ) {
		return new WP_Error( 'invalid_birth', 'تاریخ تولد معتبر انتخاب کنید.' );
	}
	$canonical = sprintf( '%04d/%02d/%02d', $y, $m, $d );
	if ( $canonical > $today ) {
		return new WP_Error( 'future_birth', 'تاریخ تولد نمی‌تواند در آینده باشد.' );
	}
	// Nowruz falls within this bounded Gregorian range in the supported years.
	$start = null;
	for ( $march = 19; $march <= 22; $march++ ) {
		$date = new DateTimeImmutable( sprintf( '%04d-03-%02d 12:00:00', $y + 621, $march ), wp_timezone() );
		if ( sprintf( '%04d/01/01', $y ) === genosys_account_dashboard_shamsi( $date->getTimestamp() ) ) {
			$start = $date;
			break;
		}
	}
	$offset = $m <= 6 ? ( $m - 1 ) * 31 + $d - 1 : 186 + ( $m - 7 ) * 30 + $d - 1;
	if ( ! $start || $canonical !== genosys_account_dashboard_shamsi( $start->modify( '+' . $offset . ' days' )->getTimestamp() ) ) {
		return new WP_Error( 'invalid_birth', 'روز انتخاب‌شده برای این ماه و سال معتبر نیست.' );
	}
	return array( 'day' => (string) $d, 'month' => (string) $m, 'year' => (string) $y );
}

function genosys_account_dashboard_saved_birth( $user_id ) {
	$raw = get_user_meta( $user_id, 'genosys_birth_date', true );
	$stored = is_scalar( $raw ) ? genosys_account_dashboard_digits_ascii( $raw ) : '';
	if ( preg_match( '/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})$/D', $stored, $parts ) ) {
		$birth = genosys_account_dashboard_birth( $parts[3], $parts[2], $parts[1] );
		if ( ! is_wp_error( $birth ) ) {
			return $birth;
		}
	}
	return array( 'day' => '', 'month' => '', 'year' => '' );
}

/** Genuine account age; club enrolment is a separate explicit datum. */
function genosys_account_dashboard_age( $registered ) {
	$today = new DateTimeImmutable( 'now', wp_timezone() );
	$date = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', (string) $registered, new DateTimeZone( 'UTC' ) );
	if ( ! $date || '0000-00-00 00:00:00' === $registered || $date > $today ) {
		return array( 'days' => 0, 'duration' => 'امروز' );
	}
	$interval = $date->setTimezone( wp_timezone() )->diff( $today );
	$parts = array();
	if ( $interval->y ) {
		$parts[] = genosys_account_dashboard_digits( $interval->y ) . ' سال';
	}
	if ( $interval->m ) {
		$parts[] = genosys_account_dashboard_digits( $interval->m ) . ' ماه';
	}
	if ( ! $parts && $interval->days ) {
		$parts[] = genosys_account_dashboard_digits( $interval->days ) . ' روز';
	}
	return array( 'days' => (int) $interval->days, 'duration' => $parts ? implode( ' و ', $parts ) . ' پیش' : 'امروز' );
}

/** The caller can only receive the signed-in customer's data. No public cache. */
function genosys_account_dashboard_context() {
	if ( ! is_user_logged_in() || ! function_exists( 'WC' ) ) {
		return array();
	}
	$user = get_userdata( get_current_user_id() );
	if ( ! $user ) {
		return array();
	}
	$user_id = (int) $user->ID;
	$customer = new WC_Customer( $user_id );
	$first = (string) get_user_meta( $user_id, 'first_name', true );
	$last = (string) get_user_meta( $user_id, 'last_name', true );
	$name = trim( $first . ' ' . $last );
	if ( '' === $name ) {
		$name = $user->display_name;
	}
	$trusted_phone = function_exists( 'genosys_sms_normalize_phone' ) ? genosys_sms_normalize_phone( (string) get_user_meta( $user_id, 'genosys_phone', true ) ) : '';
	$phone = $trusted_phone ?: (string) $customer->get_billing_phone();
	$age = genosys_account_dashboard_age( $user->user_registered );
	$club = in_array( get_user_meta( $user_id, 'genosys_club_member', true ), array( true, 1, '1', 'yes' ), true );
	$type = $club ? 'member' : ( $age['days'] < 30 ? 'new' : 'returning' );
	$type = apply_filters( 'genosys_account_membership_type', $type, $user, $club );
	$type = in_array( $type, array( 'member', 'new', 'returning' ), true ) ? $type : ( $club ? 'member' : 'new' );
	// A presentation filter cannot invent a club enrolment.
	if ( 'member' === $type && ! $club ) {
		$type = $age['days'] < 30 ? 'new' : 'returning';
	}
	$labels = array( 'member' => 'عضو باشگاه مشتریان', 'new' => 'کاربر جدید', 'returning' => 'کاربر قدیمی' );
	$since = '';
	$joined = get_user_meta( $user_id, 'genosys_club_joined_at', true );
	if ( $club && is_scalar( $joined ) && preg_match( '/^[1-9][0-9]{8,10}$/D', (string) $joined ) && (int) $joined <= time() ) {
		$ymd = explode( '/', genosys_account_dashboard_shamsi( (int) $joined ) );
		$months = array( '', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
		if ( 3 === count( $ymd ) && isset( $months[ (int) $ymd[1] ] ) ) {
			$since = $months[ (int) $ymd[1] ] . ' ' . genosys_account_dashboard_digits( $ymd[0] );
		}
	}
	$avatar_raw = get_user_meta( $user_id, 'genosys_avatar_id', true );
	$avatar_id = is_scalar( $avatar_raw ) && preg_match( '/^[1-9][0-9]*$/D', (string) $avatar_raw ) ? (int) $avatar_raw : 0;
	$avatar = $avatar_id && (int) get_post_field( 'post_author', $avatar_id ) === $user_id && wp_attachment_is_image( $avatar_id ) ? wp_get_attachment_image_url( $avatar_id, 'medium' ) : '';
	$addresses = array();
	$address_count = 0;
	foreach ( array( 'billing' => 'آدرس صورتحساب', 'shipping' => 'آدرس ارسال' ) as $key => $label ) {
		$address = 'billing' === $key ? $customer->get_billing() : $customer->get_shipping();
		$exists = '' !== trim( (string) ( $address['address_1'] ?? '' ) . (string) ( $address['city'] ?? '' ) . (string) ( $address['postcode'] ?? '' ) );
		$formatted = $exists && WC()->countries ? WC()->countries->get_formatted_address( $address ) : '';
		$formatted = html_entity_decode( wp_strip_all_tags( preg_replace( '/<br\s*\/?\s*>/i', "\n", $formatted ) ), ENT_QUOTES, 'UTF-8' );
		$addresses[] = array( 'key' => $key, 'label' => $label, 'formatted' => $formatted, 'editUrl' => wc_get_endpoint_url( 'edit-address', $key, wc_get_page_permalink( 'myaccount' ) ), 'exists' => $exists );
		$address_count += (int) $exists;
	}
	$ids = function_exists( 'genosys_customer_orders_history' ) ? genosys_customer_orders_history() : array();
	$orders_count = 0;
	$orders_active_count = 0;
	$notifications = array();
	$authorized_orders = array();
	$order_urls = array();
	foreach ( $ids as $id ) {
		$order = wc_get_order( $id );
		if ( ! $order || ! function_exists( 'genosys_order_access_can_view' ) || ! genosys_order_access_can_view( $order ) ) {
			continue;
		}
		$orders_count++;
		$authorized_orders[ (int) $id ] = $order;
		// The orders view renders twelve authorized orders per page in this history order.
		$order_page = (int) ceil( $orders_count / 12 );
		$order_urls[ (int) $id ] = ( function_exists( 'genosys_customer_orders_url' ) ? genosys_customer_orders_url( 'all', $order_page ) : wc_get_account_endpoint_url( 'orders' ) ) . '#gco-order-' . (int) $id;
		if ( ! $order->has_status( array( 'cancelled', 'failed', 'refunded', 'completed', 'delivered' ) ) ) {
			$orders_active_count++;
		}
	}
	if ( function_exists( 'wc_get_order_notes' ) ) {
		foreach ( array_chunk( array_keys( $authorized_orders ), 200 ) as $order_ids ) {
			foreach ( wc_get_order_notes( array( 'order__in' => $order_ids, 'type' => 'customer', 'limit' => 30, 'orderby' => 'date_created_gmt', 'order' => 'DESC' ) ) as $note ) {
				$comment = get_comment( $note->id );
				$id = $comment ? (int) $comment->comment_post_ID : 0;
				// Recheck the canonical visibility flag even if another plugin widens the query.
				if ( ! $comment || ! isset( $authorized_orders[ $id ] ) || 'order_note' !== $comment->comment_type || '1' !== (string) $comment->comment_approved || empty( $note->customer_note ) || 1 !== (int) get_comment_meta( $note->id, 'is_customer_note', true ) ) {
					continue;
				}
				$order = $authorized_orders[ $id ];
				$date = $note->date_created;
				$stamp = $date ? $date->getTimestamp() : 0;
				$notifications[] = array( 'id' => (int) $note->id, 'orderId' => (int) $id, 'orderNumber' => (string) $order->get_order_number(), 'content' => html_entity_decode( wp_strip_all_tags( $note->content ), ENT_QUOTES, 'UTF-8' ), 'dateISO' => $stamp ? gmdate( DATE_ATOM, $stamp ) : '', 'dateText' => $stamp && function_exists( 'genosys_sms_shamsi_date' ) ? genosys_sms_shamsi_date( $stamp ) : '', 'url' => $order_urls[ $id ] );
			}
		}
	}
	usort( $notifications, static function ( $a, $b ) { return strcmp( $b['dateISO'], $a['dateISO'] ) ?: $b['id'] <=> $a['id']; } );
	return array(
		'userId' => $user_id, 'firstName' => $first, 'lastName' => $last, 'displayName' => $name,
		'email' => (string) $user->user_email, 'birth' => genosys_account_dashboard_saved_birth( $user_id ),
		'phone' => $phone, 'phoneVerified' => '' !== $trusted_phone, 'avatarUrl' => $avatar ?: '',
		'membershipType' => $type, 'membershipLabel' => $labels[ $type ], 'memberDurationText' => $age['duration'], 'daysMember' => $age['days'],
		'clubActive' => $club, 'clubSinceText' => $since, 'ordersCount' => $orders_count, 'ordersActiveCount' => $orders_active_count,
		'addresses' => $addresses, 'addressCount' => $address_count, 'notifications' => array_slice( $notifications, 0, 30 ),
		'notificationPreferences' => genosys_account_notification_preferences( $user_id ),
		'ordersUrl' => wc_get_account_endpoint_url( 'orders' ), 'accountUrl' => wc_get_page_permalink( 'myaccount' ),
		'logoutUrl' => wc_logout_url( wc_get_page_permalink( 'myaccount' ) ), 'clubUrl' => home_url( '/fans-club/' ),
	);
}

function genosys_account_dashboard_error( $message, $status = 400, $field = '' ) {
	$data = array( 'message' => $message );
	if ( $field ) {
		$data['field'] = $field;
	}
	wp_send_json_error( $data, $status );
}

/** Same authenticated, nonce-bound checks for both current-customer mutations. */
function genosys_account_dashboard_authorize( $allowed ) {
	nocache_headers();
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		genosys_account_dashboard_error( 'روش درخواست معتبر نیست.', 405 );
	}
	if ( ! is_user_logged_in() ) {
		genosys_account_dashboard_error( 'برای ویرایش حساب وارد شوید.', 401 );
	}
	$nonce = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? wp_unslash( $_POST['nonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'genosys_account_dashboard' ) ) {
		genosys_account_dashboard_error( 'اعتبار درخواست منقضی شده است. صفحه را تازه کنید.', 403 );
	}
	foreach ( $_POST as $key => $value ) {
		if ( ! in_array( $key, array_merge( array( 'action', 'nonce' ), $allowed ), true ) || ! is_string( $value ) ) {
			genosys_account_dashboard_error( 'اطلاعات ارسال‌شده معتبر نیست.', 400 );
		}
	}
	return get_current_user_id();
}

function genosys_account_dashboard_request( $key ) {
	return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? trim( wp_unslash( $_POST[ $key ] ) ) : '';
}

/** No phone, club, role, user-ID or historical-order fields can be changed here. */
function genosys_account_dashboard_save() {
	$id = genosys_account_dashboard_authorize( array( 'first_name', 'last_name', 'email', 'birth_day', 'birth_month', 'birth_year' ) );
	$values = array();
	foreach ( array( 'first_name' => 'نام', 'last_name' => 'نام خانوادگی' ) as $key => $label ) {
		$raw = genosys_account_dashboard_request( $key );
		$value = sanitize_text_field( $raw );
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : preg_match_all( '/./us', $value );
		if ( '' === $value || $length > 100 || preg_match( '/[|\x00-\x1F\x7F]/', $raw ) ) {
			genosys_account_dashboard_error( $label . ' معتبر وارد کنید (حداکثر ۱۰۰ نویسه).', 400, $key );
		}
		$values[ $key ] = $value;
	}
	$email_raw = genosys_account_dashboard_request( 'email' );
	$email = sanitize_email( $email_raw );
	if ( '' !== $email_raw && ( ! is_email( $email_raw ) || $email !== $email_raw || strlen( $email ) > 100 ) ) {
		genosys_account_dashboard_error( 'ایمیل معتبر وارد کنید.', 400, 'email' );
	}
	$existing = '' !== $email ? email_exists( $email ) : false;
	if ( $existing && (int) $existing !== $id ) {
		genosys_account_dashboard_error( 'این ایمیل برای حساب دیگری ثبت شده است.', 409, 'email' );
	}
	$birth = genosys_account_dashboard_birth( genosys_account_dashboard_request( 'birth_day' ), genosys_account_dashboard_request( 'birth_month' ), genosys_account_dashboard_request( 'birth_year' ) );
	if ( is_wp_error( $birth ) ) {
		genosys_account_dashboard_error( $birth->get_error_message(), 400, 'birth_day' );
	}
	$updated = wp_update_user( array( 'ID' => $id, 'first_name' => $values['first_name'], 'last_name' => $values['last_name'], 'display_name' => trim( $values['first_name'] . ' ' . $values['last_name'] ), 'user_email' => $email ) );
	if ( is_wp_error( $updated ) ) {
		genosys_account_dashboard_error( 'ذخیره اطلاعات انجام نشد. دوباره تلاش کنید.', 500 );
	}
	foreach ( $values as $key => $value ) {
		update_user_meta( $id, 'billing_' . $key, $value );
	}
	update_user_meta( $id, 'billing_email', $email );
	if ( '' === $birth['year'] ) {
		delete_user_meta( $id, 'genosys_birth_date' );
	} else {
		update_user_meta( $id, 'genosys_birth_date', sprintf( '%04d/%02d/%02d', $birth['year'], $birth['month'], $birth['day'] ) );
	}
	clean_user_cache( $id );
	wp_set_current_user( $id );
	wp_send_json_success( array( 'context' => genosys_account_dashboard_context(), 'message' => 'اطلاعات حساب با موفقیت ذخیره شد.' ) );
}
add_action( 'wp_ajax_genosys_account_save', 'genosys_account_dashboard_save' );
add_action( 'wp_ajax_nopriv_genosys_account_save', 'genosys_account_dashboard_save' );

/** A customer may upload their own avatar without general media-library access. */
function genosys_account_dashboard_avatar() {
	$id = genosys_account_dashboard_authorize( array( 'operation' ) );
	$operation = genosys_account_dashboard_request( 'operation' );
	if ( 'remove' === $operation ) {
		delete_user_meta( $id, 'genosys_avatar_id' );
		wp_send_json_success( array( 'context' => genosys_account_dashboard_context(), 'message' => 'عکس حساب حذف شد.' ) );
	}
	if ( 'upload' !== $operation || 1 !== count( $_FILES ) || empty( $_FILES['avatar'] ) || ! is_array( $_FILES['avatar'] ) ) {
		genosys_account_dashboard_error( 'تصویر معتبر انتخاب کنید.', 400 );
	}
	$file = $_FILES['avatar'];
	if ( ! isset( $file['error'], $file['tmp_name'], $file['name'] ) || ! is_scalar( $file['error'] ) || ! is_string( $file['tmp_name'] ) || ! is_string( $file['name'] ) || UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
		genosys_account_dashboard_error( 'بارگذاری تصویر انجام نشد. دوباره تلاش کنید.', 400 );
	}
	$size = filesize( $file['tmp_name'] );
	if ( false === $size || $size < 1 || $size > 5 * MB_IN_BYTES ) {
		genosys_account_dashboard_error( 'حجم تصویر باید حداکثر ۵ مگابایت باشد.', 400 );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	$mimes = array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' );
	$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $mimes );
	$dimensions = wp_getimagesize( $file['tmp_name'] );
	if ( empty( $checked['ext'] ) || empty( $checked['type'] ) || ! in_array( $checked['type'], $mimes, true ) || ! $dimensions || empty( $dimensions[0] ) || empty( $dimensions[1] ) || $dimensions[0] * $dimensions[1] > 40000000 || $dimensions['mime'] !== $checked['type'] ) {
		genosys_account_dashboard_error( 'فقط تصویر معتبر JPG، PNG یا WebP قابل انتخاب است.', 400 );
	}
	// The image editor must actually decode the file before it enters media storage.
	$editor = wp_get_image_editor( $file['tmp_name'] );
	if ( is_wp_error( $editor ) || ! $editor->get_size() ) {
		genosys_account_dashboard_error( 'تصویر قابل خواندن نیست. تصویر دیگری انتخاب کنید.', 400 );
	}
	unset( $editor );
	$attachment_id = media_handle_upload( 'avatar', 0, array( 'post_author' => $id, 'post_title' => 'تصویر حساب ' . $id ), array( 'test_form' => false, 'mimes' => $mimes ) );
	if ( is_wp_error( $attachment_id ) ) {
		genosys_account_dashboard_error( 'ذخیره تصویر انجام نشد. دوباره تلاش کنید.', 500 );
	}
	update_user_meta( $id, 'genosys_avatar_id', $attachment_id );
	wp_send_json_success( array( 'context' => genosys_account_dashboard_context(), 'message' => 'عکس حساب با موفقیت تغییر کرد.' ) );
}
add_action( 'wp_ajax_genosys_account_avatar', 'genosys_account_dashboard_avatar' );
add_action( 'wp_ajax_nopriv_genosys_account_avatar', 'genosys_account_dashboard_avatar' );
