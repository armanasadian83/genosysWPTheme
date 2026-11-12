<?php
/** Current-customer notification choices and an explicitly subscribed monthly digest. */
defined( 'ABSPATH' ) || exit;

function genosys_account_notification_keys() {
	return array( 'order_status_sms', 'payment_sms', 'promotions', 'newsletter' );
}

/** Preserve actual historic club consent; a membership badge alone is not consent. */
function genosys_account_notification_club_consent( $user_id ) {
	if ( ! $user_id || ! function_exists( 'genosys_club_member_raw' ) ) { return false; }
	$ids = get_posts( array( 'post_type' => 'genosys_club_member', 'post_status' => 'private', 'posts_per_page' => 20, 'fields' => 'ids', 'no_found_rows' => true, 'suppress_filters' => true, 'meta_query' => array( array( 'key' => '_genosys_club_owner_user', 'value' => (int) $user_id ) ) ) );
	foreach ( $ids as $id ) {
		$record = genosys_club_member_raw( $id );
		if ( $record && (int) ( $record['userId'] ?? 0 ) === (int) $user_id && 'active' === ( $record['status'] ?? '' ) && true === ( $record['data']['consent'] ?? false ) ) { return true; }
	}
	return false;
}

function genosys_account_notification_preferences( $user_id = 0, $fresh = false ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( $fresh ) { wp_cache_delete( $user_id, 'user_meta' ); }
	$defaults = array( 'order_status_sms' => true, 'payment_sms' => true, 'promotions' => null, 'newsletter' => false );
	$result = array();
	foreach ( $defaults as $key => $default ) {
		$value = get_user_meta( $user_id, 'genosys_notify_' . $key, true );
		if ( metadata_exists( 'user', $user_id, 'genosys_notify_' . $key ) ) {
			$result[ $key ] = in_array( $value, array( '1', 1, true ), true );
		} else {
			$result[ $key ] = null === $default ? genosys_account_notification_club_consent( $user_id ) : $default;
		}
	}
	return $result;
}

/** An atomic named lock avoids duplicate first-time usermeta rows and lost same-key writes. */
function genosys_account_notification_lock( $scope, $ttl = 60 ) {
	global $wpdb;
	$key = 'genosys_notification_lock_' . hash( 'sha256', (string) $scope );
	$value = time() . ':' . wp_generate_uuid4();
	if ( add_option( $key, $value, '', false ) ) { return array( $key, $value ); }
	$old = get_option( $key );
	if ( is_string( $old ) && (int) $old < time() - $ttl ) {
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $key, $old ) );
		if ( $deleted ) { wp_cache_delete( $key, 'options' ); if ( add_option( $key, $value, '', false ) ) { return array( $key, $value ); } }
	}
	return false;
}

function genosys_account_notification_unlock( $lock ) {
	if ( ! $lock ) { return; }
	global $wpdb;
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $lock[0], $lock[1] ) );
	wp_cache_delete( $lock[0], 'options' );
}

function genosys_account_notifications_get() {
	$user_id = genosys_account_dashboard_authorize( array() );
	wp_send_json_success( array( 'preferences' => genosys_account_notification_preferences( $user_id, true ), 'message' => '' ) );
}
add_action( 'wp_ajax_genosys_account_notifications_get', 'genosys_account_notifications_get' );
add_action( 'wp_ajax_nopriv_genosys_account_notifications_get', 'genosys_account_notifications_get' );

/** The write touches one key only; concurrent switches cannot overwrite one another. */
function genosys_account_notifications_save() {
	$user_id = genosys_account_dashboard_authorize( array( 'preference', 'enabled' ) );
	$key = isset( $_POST['preference'] ) && is_string( $_POST['preference'] ) ? wp_unslash( $_POST['preference'] ) : '';
	$value = isset( $_POST['enabled'] ) && is_string( $_POST['enabled'] ) ? wp_unslash( $_POST['enabled'] ) : '';
	if ( ! in_array( $key, genosys_account_notification_keys(), true ) || ! in_array( $value, array( '1', '0' ), true ) ) { genosys_account_dashboard_error( 'گزینه و وضعیت اعلان معتبر نیست.', 400 ); }
	$user = get_userdata( $user_id );
	if ( 'newsletter' === $key && '1' === $value && ( ! $user || ! is_email( $user->user_email ) ) ) { genosys_account_dashboard_error( 'برای دریافت خبرنامه ابتدا ایمیل معتبر خود را در اطلاعات حساب ثبت کنید.', 400, 'newsletter' ); }
	$lock = genosys_account_notification_lock( 'preference:' . $user_id . ':' . $key );
	if ( ! $lock ) { genosys_account_dashboard_error( 'ذخیره این گزینه در حال انجام است. دوباره تلاش کنید.', 409 ); }
	try {
		$before = genosys_account_notification_preferences( $user_id, true );
		if ( 'newsletter' === $key && '1' === $value && ( ! $before['newsletter'] || ! get_user_meta( $user_id, 'genosys_newsletter_unsubscribe_key', true ) ) ) {
			$token = bin2hex( random_bytes( 32 ) );
			update_user_meta( $user_id, 'genosys_newsletter_unsubscribe_key', $token );
			if ( $token !== get_user_meta( $user_id, 'genosys_newsletter_unsubscribe_key', true ) ) { throw new RuntimeException( 'token_write_failed' ); }
		}
		update_user_meta( $user_id, 'genosys_notify_' . $key, $value );
		wp_cache_delete( $user_id, 'user_meta' );
		if ( $value !== get_user_meta( $user_id, 'genosys_notify_' . $key, true ) ) { throw new RuntimeException( 'preference_write_failed' ); }
		$preferences = genosys_account_notification_preferences( $user_id );
	} catch ( Throwable $error ) {
		genosys_account_notification_unlock( $lock );
		genosys_account_dashboard_error( 'ذخیره تنظیمات انجام نشد. دوباره تلاش کنید.', 500 );
	}
	genosys_account_notification_unlock( $lock );
	wp_send_json_success( array( 'preferences' => $preferences, 'preference' => $key, 'enabled' => '1' === $value, 'message' => 'تنظیمات اعلان‌ها ذخیره شد.' ) );
}
add_action( 'wp_ajax_genosys_account_notifications_save', 'genosys_account_notifications_save' );
add_action( 'wp_ajax_nopriv_genosys_account_notifications_save', 'genosys_account_notifications_save' );

/** Only an account's verified login phone can connect a guest message to its preferences. */
function genosys_account_notification_phone_user( $phone ) {
	$phone = genosys_sms_normalize_phone( $phone );
	if ( ! $phone ) { return 0; }
	$ids = get_users( array( 'meta_key' => 'genosys_phone', 'meta_value' => $phone, 'number' => 2, 'fields' => 'ID', 'count_total' => false ) );
	return count( $ids ) > 1 ? -1 : ( 1 === count( $ids ) ? (int) $ids[0] : 0 );
}

function genosys_account_notification_order_allowed( $order, $key, $phone = '' ) {
	if ( ! in_array( $key, array( 'order_status_sms', 'payment_sms' ), true ) || ! $order instanceof WC_Order ) { return false; }
	$user_id = (int) $order->get_customer_id();
	$recipient_id = genosys_account_notification_phone_user( $phone ?: $order->get_billing_phone() );
	if ( $recipient_id < 0 ) { return false; }
	foreach ( array_unique( array_filter( array( $user_id, $recipient_id ) ) ) as $account_id ) {
		if ( get_userdata( $account_id ) && ! genosys_account_notification_preferences( $account_id, true )[ $key ] ) { return false; }
	}
	return true;
}

/** Account opt-out suppresses promotional sends, without cancelling club membership. */
function genosys_account_notification_club_allowed( $member ) {
	if ( ! is_array( $member ) || 'active' !== ( $member['status'] ?? '' ) ) { return false; }
	$user_id = (int) ( $member['userId'] ?? 0 );
	$consent = ! empty( $member['consent'] );
	if ( $user_id > 0 && get_userdata( $user_id ) && ! empty( $member['id'] ) && function_exists( 'genosys_club_member_raw' ) ) {
		$record = genosys_club_member_raw( (int) $member['id'] );
		$owned = $record && 'active' === ( $record['status'] ?? '' ) && (int) ( $record['userId'] ?? 0 ) === $user_id && (int) get_post_meta( (int) $member['id'], '_genosys_club_owner_user', true ) === $user_id;
		wp_cache_delete( $user_id, 'user_meta' );
		if ( $owned && metadata_exists( 'user', $user_id, 'genosys_notify_promotions' ) ) {
			// A later explicit choice by the real owner supersedes the historical signup choice.
			$consent = genosys_account_notification_preferences( $user_id )['promotions'];
		}
	}
	if ( ! $consent ) { return false; }
	$recipient_id = genosys_account_notification_phone_user( $member['phone'] ?? '' );
	if ( $recipient_id < 0 ) { return false; }
	foreach ( array_unique( array_filter( array( $user_id, $recipient_id ) ) ) as $account_id ) {
		if ( get_userdata( $account_id ) && ! genosys_account_notification_preferences( $account_id, true )['promotions'] ) { return false; }
	}
	return true;
}

/** WordPress has no calendar-month recurrence; schedule each next calendar month explicitly. */
function genosys_newsletter_next_month( $from = 0 ) {
	$now = new DateTimeImmutable( '@' . ( $from ?: time() ) );
	return $now->setTimezone( wp_timezone() )->modify( 'first day of next month' )->setTime( 10, 0, 0 )->getTimestamp();
}

function genosys_newsletter_schedule() {
	if ( ! wp_next_scheduled( 'genosys_newsletter_monthly' ) ) { wp_schedule_single_event( genosys_newsletter_next_month(), 'genosys_newsletter_monthly' ); }
}
add_action( 'init', 'genosys_newsletter_schedule' );

function genosys_newsletter_stop() {
	wp_clear_scheduled_hook( 'genosys_newsletter_monthly' );
	// Completed/in-progress issue records contain no subscriber addresses.
	$events = _get_cron_array();
	foreach ( is_array( $events ) ? $events : array() as $hooks ) {
		foreach ( $hooks['genosys_newsletter_batch'] ?? array() as $event ) { wp_clear_scheduled_hook( 'genosys_newsletter_batch', $event['args'] ); }
	}
}
add_action( 'switch_theme', 'genosys_newsletter_stop' );

/** Snapshot only public, readable editorial content and normal catalog products. */
function genosys_newsletter_content_ids( $cutoff ) {
	$result = array();
	foreach ( array( 'post', 'product' ) as $type ) {
		$args = array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => 6, 'fields' => 'ids', 'no_found_rows' => true, 'has_password' => false, 'orderby' => 'date', 'order' => 'DESC', 'date_query' => array( array( 'before' => gmdate( 'Y-m-d H:i:s', $cutoff ), 'column' => 'post_date_gmt', 'inclusive' => true ) ) );
		if ( 'product' === $type && taxonomy_exists( 'product_type' ) ) { $args['tax_query'] = array( array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'genosys_package' ), 'operator' => 'NOT IN' ) ); }
		foreach ( get_posts( $args ) as $id ) {
			if ( 'product' === $type ) { $product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : false; if ( ! $product || ! in_array( $product->get_catalog_visibility(), array( 'visible', 'catalog' ), true ) ) { continue; } }
			$result[ $type ][] = (int) $id;
			if ( count( $result[ $type ] ) >= 3 ) { break; }
		}
	}
	return $result;
}

function genosys_newsletter_monthly() {
	genosys_newsletter_schedule();
	$issue = ( new DateTimeImmutable( 'now', wp_timezone() ) )->format( 'Y-m' );
	$lock = genosys_account_notification_lock( 'newsletter-issue:' . $issue );
	if ( ! $lock ) { return; }
	global $wpdb;
	$key = 'genosys_newsletter_issue_' . str_replace( '-', '_', $issue );
	try {
		$state = get_option( $key );
		if ( ! is_array( $state ) ) {
			$state = array( 'issue' => $issue, 'cursor' => 0, 'maximum' => (int) $wpdb->get_var( "SELECT MAX(ID) FROM {$wpdb->users}" ), 'content' => genosys_newsletter_content_ids( time() ), 'created' => time(), 'complete' => false );
			if ( ! add_option( $key, $state, '', false ) && ! is_array( get_option( $key ) ) ) { throw new RuntimeException( 'issue_write_failed' ); }
		}
		if ( empty( $state['complete'] ) && ! wp_next_scheduled( 'genosys_newsletter_batch', array( $issue ) ) ) { wp_schedule_single_event( time() + 10, 'genosys_newsletter_batch', array( $issue ) ); }
	} catch ( Throwable $issue_error ) {
		wp_schedule_single_event( time() + 300, 'genosys_newsletter_monthly' );
	} finally { genosys_account_notification_unlock( $lock ); }
}
add_action( 'genosys_newsletter_monthly', 'genosys_newsletter_monthly' );

function genosys_newsletter_unsubscribe_url( $user_id ) {
	$token = get_user_meta( $user_id, 'genosys_newsletter_unsubscribe_key', true );
	if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/D', $token ) ) { return ''; }
	return add_query_arg( array( 'action' => 'genosys_newsletter_unsubscribe', 'subscriber' => (int) $user_id, 'token' => $token ), admin_url( 'admin-post.php' ) );
}

function genosys_newsletter_message( $user, $state, $unsubscribe ) {
	$preferences = add_query_arg( 'ga_tab', 'notifications', function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ) );
	$html = '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"></head><body style="margin:0;background:#f7f5f5;font-family:Tahoma,Arial,sans-serif;color:#242126"><div style="max-width:640px;margin:28px auto;padding:32px;background:white;border-radius:24px"><h1 style="color:#bf0b30;font-size:24px">' . esc_html( 'خبرنامه ماهانه ' . get_bloginfo( 'name' ) ) . '</h1><p>' . esc_html( trim( $user->first_name . ' ' . $user->last_name ) ?: $user->display_name ) . ' عزیز، سلام</p><p>تازه‌های ژنوسیس را در این ماه ببینید.</p>';
	foreach ( array( 'post' => 'تازه‌های مجله', 'product' => 'جدیدترین محصولات' ) as $type => $title ) {
		$items = '';
		foreach ( $state['content'][ $type ] ?? array() as $id ) {
			$post = get_post( $id );
			if ( ! $post || 'publish' !== $post->post_status || $post->post_password || $type !== $post->post_type ) { continue; }
			if ( 'product' === $type ) { $product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : false; if ( ! $product || ! in_array( $product->get_catalog_visibility(), array( 'visible', 'catalog' ), true ) || $product->is_type( 'genosys_package' ) ) { continue; } }
			$items .= '<li style="margin:12px 0"><a style="color:#bf0b30" href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a></li>';
		}
		if ( $items ) { $html .= '<h2 style="font-size:18px">' . esc_html( $title ) . '</h2><ul>' . $items . '</ul>'; }
	}
	$html .= '<p><a href="' . esc_url( home_url( '/' ) ) . '">مشاهده وب‌سایت</a></p><hr style="border:0;border-top:1px solid #eee"><p style="font-size:12px;color:#777">این خبرنامه با انتخاب شما در تنظیمات حساب ارسال شده است.</p><p><a href="' . esc_url( $preferences ) . '">تنظیمات اعلان‌ها</a> · <a href="' . esc_url( $unsubscribe ) . '">لغو اشتراک خبرنامه</a></p></div></body></html>';
	return $html;
}

/** At most twenty-five recipients per request, with fresh consent before each handoff. */
function genosys_newsletter_batch( $issue ) {
	if ( ! is_string( $issue ) || ! preg_match( '/^[0-9]{4}-(0[1-9]|1[0-2])$/D', $issue ) ) { return; }
	$lock = genosys_account_notification_lock( 'newsletter-issue:' . $issue, 600 );
	if ( ! $lock ) { return; }
	global $wpdb;
	$key = 'genosys_newsletter_issue_' . str_replace( '-', '_', $issue );
	$again = false;
	try {
		$state = get_option( $key );
		if ( ! is_array( $state ) || ! empty( $state['complete'] ) ) { return; }
		$again = true;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT u.ID FROM {$wpdb->users} u INNER JOIN {$wpdb->usermeta} m ON m.user_id = u.ID WHERE m.meta_key = %s AND m.meta_value = '1' AND u.ID > %d AND u.ID <= %d ORDER BY u.ID ASC LIMIT 25", 'genosys_notify_newsletter', (int) $state['cursor'], (int) $state['maximum'] ) );
		if ( $wpdb->last_error ) { throw new RuntimeException( 'subscriber_query_failed' ); }
		foreach ( $ids as $id ) {
			$id = (int) $id;
			$user_lock = genosys_account_notification_lock( 'preference:' . $id . ':newsletter' );
			if ( ! $user_lock ) { break; }
			try {
				$user = get_userdata( $id );
				$unsubscribe = genosys_newsletter_unsubscribe_url( $id );
				$attempt = get_user_meta( $id, 'genosys_newsletter_last_attempt', true );
				if ( $user && is_email( $user->user_email ) && $unsubscribe && genosys_account_notification_preferences( $id, true )['newsletter'] && ( ! is_array( $attempt ) || strcmp( (string) ( $attempt['issue'] ?? '' ), $issue ) < 0 ) ) {
					$claim = array( 'issue' => $issue, 'timestamp' => time(), 'status' => 'attempting' );
					update_user_meta( $id, 'genosys_newsletter_last_attempt', $claim );
					if ( $claim !== get_user_meta( $id, 'genosys_newsletter_last_attempt', true ) ) { throw new RuntimeException( 'mail_claim_failed' ); }
					$headers = array( 'Content-Type: text/html; charset=UTF-8', 'List-Unsubscribe: <' . $unsubscribe . '>', 'List-Unsubscribe-Post: List-Unsubscribe=One-Click' );
					try {
						$accepted = wp_mail( $user->user_email, 'خبرنامه ماهانه ' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), genosys_newsletter_message( $user, $state, $unsubscribe ), $headers );
						$claim['status'] = $accepted ? 'accepted' : 'failed';
					} catch ( Throwable $mail_error ) { $claim['status'] = 'unconfirmed'; }
					update_user_meta( $id, 'genosys_newsletter_last_attempt', $claim );
				}
				$state['cursor'] = $id;
				update_option( $key, $state, false );
			} finally { genosys_account_notification_unlock( $user_lock ); }
		}
		$state['complete'] = count( $ids ) < 25 && ( ! $ids || (int) end( $ids ) === (int) $state['cursor'] );
		update_option( $key, $state, false );
		$again = ! $state['complete'];
	} catch ( Throwable $batch_error ) {
		if ( function_exists( 'wc_get_logger' ) ) { wc_get_logger()->warning( 'Monthly newsletter batch will resume.', array( 'source' => 'genosys-newsletter', 'issue' => $issue ) ); }
	} finally {
		genosys_account_notification_unlock( $lock );
		if ( $again && ! wp_next_scheduled( 'genosys_newsletter_batch', array( $issue ) ) ) { wp_schedule_single_event( time() + 60, 'genosys_newsletter_batch', array( $issue ) ); }
	}
}
add_action( 'genosys_newsletter_batch', 'genosys_newsletter_batch' );

/** Email scanners can view the confirmation safely; only an explicit POST unsubscribes. */
function genosys_newsletter_unsubscribe() {
	nocache_headers();
	$source = 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ? array_merge( $_GET, $_POST ) : $_GET;
	$id = isset( $source['subscriber'] ) && is_string( $source['subscriber'] ) && preg_match( '/^[1-9][0-9]*$/D', $source['subscriber'] ) ? (int) $source['subscriber'] : 0;
	$token = isset( $source['token'] ) && is_string( $source['token'] ) ? wp_unslash( $source['token'] ) : '';
	$stored = $id ? get_user_meta( $id, 'genosys_newsletter_unsubscribe_key', true ) : '';
	if ( ! $id || ! preg_match( '/^[a-f0-9]{64}$/D', $token ) || ! is_string( $stored ) || ! hash_equals( $stored, $token ) ) { wp_die( 'پیوند لغو اشتراک معتبر نیست.', '', array( 'response' => 400 ) ); }
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		$lock = genosys_account_notification_lock( 'preference:' . $id . ':newsletter' );
		if ( ! $lock ) { wp_die( 'درخواست دیگری در حال انجام است. دوباره تلاش کنید.', '', array( 'response' => 409 ) ); }
		wp_cache_delete( $id, 'user_meta' );
		$fresh_token = get_user_meta( $id, 'genosys_newsletter_unsubscribe_key', true );
		if ( ! is_string( $fresh_token ) || ! hash_equals( $fresh_token, $token ) ) { genosys_account_notification_unlock( $lock ); wp_die( 'پیوند لغو اشتراک معتبر نیست.', '', array( 'response' => 400 ) ); }
		update_user_meta( $id, 'genosys_notify_newsletter', '0' );
		$success = '0' === get_user_meta( $id, 'genosys_notify_newsletter', true );
		genosys_account_notification_unlock( $lock );
		wp_die( $success ? 'اشتراک خبرنامه شما لغو شد.' : 'لغو اشتراک انجام نشد. دوباره تلاش کنید.', '', array( 'response' => $success ? 200 : 500 ) );
	}
	$action = admin_url( 'admin-post.php' );
	$html = '<div dir="rtl"><h1>لغو اشتراک خبرنامه</h1><p>برای توقف خبرنامه‌های ماهانه، دکمه زیر را انتخاب کنید.</p><form method="post" action="' . esc_url( $action ) . '"><input type="hidden" name="action" value="genosys_newsletter_unsubscribe"><input type="hidden" name="subscriber" value="' . esc_attr( $id ) . '"><input type="hidden" name="token" value="' . esc_attr( $token ) . '"><button type="submit">لغو اشتراک</button></form></div>';
	wp_die( $html, 'لغو اشتراک خبرنامه', array( 'response' => 200 ) );
}
add_action( 'admin_post_genosys_newsletter_unsubscribe', 'genosys_newsletter_unsubscribe' );
add_action( 'admin_post_nopriv_genosys_newsletter_unsubscribe', 'genosys_newsletter_unsubscribe' );
