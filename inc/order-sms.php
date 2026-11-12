<?php
/** Customer order-submission SMS, separate from login codes and payment handling. */
defined( 'ABSPATH' ) || exit;

function genosys_order_sms_template_id() {
	$value = get_option( 'genosys_order_sms_template_id', '' );
	if ( ! is_scalar( $value ) || is_bool( $value ) ) {
		return 0;
	}
	$value = genosys_checkout_ascii_digits( trim( (string) $value ) );
	return preg_match( '/^[1-9][0-9]{0,9}$/D', $value ) && (float) $value <= 2147483647 ? (int) $value : 0;
}

/** Log only an internal reason and order ID; never provider bodies or credentials. */
function genosys_order_sms_log( $order_id, $reason ) {
	if ( function_exists( 'wc_get_logger' ) ) {
		wc_get_logger()->warning( 'Order submission SMS could not be confirmed.', array( 'source' => 'genosys-order-sms', 'order_id' => absint( $order_id ), 'reason' => sanitize_key( $reason ) ) );
	}
}

/** Enqueue after validated checkout submission, never on order/page reads. */
function genosys_order_sms_queue( $order ) {
	if ( ! $order instanceof WC_Order || 'shop_order' !== $order->get_type() || ! $order->get_id() || $order->has_status( array( 'checkout-draft', 'trash' ) ) || ! genosys_order_sms_template_id() || '' === genosys_sms_api_key() || '' === genosys_sms_normalize_phone( $order->get_billing_phone() ) ) {
		return;
	}
	try {
		$order->read_meta_data( true );
		if ( $order->get_meta( '_genosys_order_sms_attempted_at', true ) ) {
			return;
		}
		$args = array( (int) $order->get_id() );
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			$queued = as_enqueue_async_action( 'genosys_order_sms_send', $args, 'genosys-order-sms', true );
			if ( $queued || ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( 'genosys_order_sms_send', $args, 'genosys-order-sms' ) ) ) {
				return;
			}
		}
		if ( ! wp_next_scheduled( 'genosys_order_sms_send', $args ) && ! wp_schedule_single_event( time(), 'genosys_order_sms_send', $args ) ) {
			genosys_order_sms_log( $order->get_id(), 'queue_unavailable' );
		}
	} catch ( Throwable $exception ) {
		genosys_order_sms_log( $order->get_id(), 'queue_unavailable' );
	}
}

function genosys_order_sms_classic_checkout( $order_id, $posted_data, $order ) {
	genosys_order_sms_queue( $order instanceof WC_Order ? $order : wc_get_order( $order_id ) );
}
add_action( 'woocommerce_checkout_order_processed', 'genosys_order_sms_classic_checkout', 20, 3 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'genosys_order_sms_queue', 20, 1 );

/** Serialize workers without sharing the login or receipt-upload locks. */
function genosys_order_sms_lock( $order_id, $event_id = '' ) {
	global $wpdb;
	$name = 'genosys_order_sms_lock_' . absint( $order_id );
	if ( '' !== $event_id ) {
		$name .= '_' . md5( (string) $event_id );
	}
	$value = wp_json_encode( array( 'time' => time(), 'token' => wp_generate_uuid4() ) );
	if ( add_option( $name, $value, '', false ) ) {
		return array( $name, $value );
	}
	$old = get_option( $name, '' );
	$row = is_string( $old ) ? json_decode( $old, true ) : null;
	if ( is_array( $row ) && isset( $row['time'] ) && time() - (int) $row['time'] > 120 ) {
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, $old ) );
		if ( $deleted ) {
			wp_cache_delete( $name, 'options' );
			if ( add_option( $name, $value, '', false ) ) {
				return array( $name, $value );
			}
		}
	}
	return false;
}

function genosys_order_sms_unlock( $lock ) {
	global $wpdb;
	if ( is_array( $lock ) && 2 === count( $lock ) ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $lock[0], $lock[1] ) );
		wp_cache_delete( $lock[0], 'options' );
	}
}

/** Build only the template's three values from saved WooCommerce order data. */
function genosys_order_sms_parameters( $order ) {
	$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	$date = $order->get_date_created();
	if ( ! $date ) {
		return null;
	}
	// Use the real instant; WC_DateTime::date() adds a local offset before formatting.
	$date_text = genosys_sms_shamsi_date( $date->getTimestamp() );
	return array(
		array( 'name' => 'NAME', 'value' => sanitize_text_field( '' !== $name ? $name : 'مشتری' ) ),
		array( 'name' => 'ORDERID', 'value' => sanitize_text_field( (string) $order->get_order_number() ) ),
		array( 'name' => 'DATE', 'value' => sanitize_text_field( $date_text ) ),
	);
}

/** Shared provider transport; callers persist their own attempt before sending. */
function genosys_order_sms_request( $phone, $template_id, $parameters, $api_key ) {
	$response = wp_remote_post( 'https://api.sms.ir/v1/send/verify/', array(
		'timeout' => 15,
		'redirection' => 0,
		'headers' => array( 'X-API-KEY' => $api_key, 'Content-Type' => 'application/json', 'Accept' => 'application/json' ),
		'body' => wp_json_encode( array( 'mobile' => $phone, 'templateId' => $template_id, 'parameters' => $parameters ) ),
	) );
	$http_status = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
	$body = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
	$message_id = is_array( $body ) && isset( $body['data']['messageId'] ) && ( is_string( $body['data']['messageId'] ) || is_int( $body['data']['messageId'] ) ) ? (string) $body['data']['messageId'] : '';
	$sent = $http_status >= 200 && $http_status < 300 && is_array( $body ) && isset( $body['status'] ) && in_array( $body['status'], array( 1, '1' ), true ) && preg_match( '/^[1-9][0-9]*$/D', $message_id );
	return array(
		'status' => $sent ? 'sent' : ( $http_status >= 400 && $http_status < 500 ? 'failed' : 'unconfirmed' ),
		'message_id' => $sent ? $message_id : '',
		'reason' => $sent ? '' : ( is_wp_error( $response ) ? 'provider_unavailable' : 'provider_rejected' ),
	);
}

/** A durable attempt claim prevents duplicate sends, including ambiguous timeouts. */
function genosys_order_sms_send( $order_id ) {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$order_id = absint( $order_id );
	$lock = $order_id ? genosys_order_sms_lock( $order_id ) : false;
	if ( ! $lock ) {
		return;
	}
	try {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || 'shop_order' !== $order->get_type() || $order->has_status( array( 'checkout-draft', 'trash' ) ) ) {
			return;
		}
		$order->read_meta_data( true );
		if ( $order->get_meta( '_genosys_order_sms_attempted_at', true ) ) {
			return;
		}
		$template_id = genosys_order_sms_template_id();
		$api_key = genosys_sms_api_key();
		$phone = genosys_sms_normalize_phone( $order->get_billing_phone() );
		$parameters = genosys_order_sms_parameters( $order );
		if ( ! $template_id || '' === $api_key || '' === $phone || ! $parameters ) {
			return;
		}
		$attempted_at = time();
		$order->update_meta_data( '_genosys_order_sms_attempted_at', $attempted_at );
		$order->update_meta_data( '_genosys_order_sms_template_id', $template_id );
		$order->update_meta_data( '_genosys_order_sms_status', 'attempting' );
		$order->save_meta_data();
		$order->read_meta_data( true );
		if ( $attempted_at !== (int) $order->get_meta( '_genosys_order_sms_attempted_at', true ) ) {
			throw new RuntimeException( 'claim_unavailable' );
		}
		$result = genosys_order_sms_request( $phone, $template_id, $parameters, $api_key );
		if ( 'sent' === $result['status'] ) {
			$order->update_meta_data( '_genosys_order_sms_status', 'sent' );
			$order->update_meta_data( '_genosys_order_sms_message_id', $result['message_id'] );
			$order->update_meta_data( '_genosys_order_sms_sent_at', time() );
		} else {
			$order->update_meta_data( '_genosys_order_sms_status', $result['status'] );
			genosys_order_sms_log( $order_id, $result['reason'] );
		}
		$order->save_meta_data();
	} catch ( Throwable $exception ) {
		genosys_order_sms_log( $order_id, 'send_unconfirmed' );
	} finally {
		genosys_order_sms_unlock( $lock );
	}
}
add_action( 'genosys_order_sms_send', 'genosys_order_sms_send', 10, 1 );
