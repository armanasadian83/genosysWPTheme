<?php
/** Customer SMS for committed receipt approvals and rejections. */
defined( 'ABSPATH' ) || exit;

function genosys_receipt_sms_template_id( $status ) {
	if ( ! in_array( $status, array( 'approved', 'rejected' ), true ) ) {
		return 0;
	}
	$value = get_option( 'genosys_receipt_' . $status . '_sms_template_id', '' );
	if ( ! is_scalar( $value ) || is_bool( $value ) ) {
		return 0;
	}
	$value = genosys_checkout_ascii_digits( trim( (string) $value ) );
	return preg_match( '/^[1-9][0-9]{0,9}$/D', $value ) && (float) $value <= 2147483647 ? (int) $value : 0;
}

function genosys_receipt_sms_log( $order_id, $reason ) {
	if ( function_exists( 'wc_get_logger' ) ) {
		wc_get_logger()->warning( 'Receipt review SMS could not be confirmed.', array( 'source' => 'genosys-receipt-review-sms', 'order_id' => absint( $order_id ), 'reason' => sanitize_key( $reason ) ) );
	}
}

function genosys_receipt_sms_event_key( $event_id ) {
	return is_string( $event_id ) && preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $event_id ) ? '_genosys_receipt_sms_event_' . str_replace( '-', '', $event_id ) : '';
}

/** Capture once per decision; a replacement receipt cannot erase its notification. */
function genosys_receipt_sms_queue( $receipt_id, $order_id, $from, $to, $event_id ) {
	$event_key = genosys_receipt_sms_event_key( $event_id );
	if ( ! $event_key || ! absint( $receipt_id ) || ! absint( $order_id ) || $from === $to || ! in_array( $from, array( 'pending', 'approved', 'rejected' ), true ) || ! in_array( $to, array( 'approved', 'rejected' ), true ) || ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	try {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || 'shop_order' !== $order->get_type() ) {
			return;
		}
		$order->read_meta_data( true );
		$event = $order->get_meta( $event_key, true );
		if ( function_exists( 'genosys_account_notification_order_allowed' ) && ! genosys_account_notification_order_allowed( $order, 'payment_sms', is_array( $event ) ? ( $event['phone'] ?? '' ) : '' ) ) {
			if ( is_array( $event ) && empty( $event['attempted_at'] ) ) {
				$event['attempted_at'] = time(); $event['status'] = 'suppressed';
				$order->update_meta_data( $event_key, $event ); $order->save_meta_data();
			}
			return;
		}
		if ( ! $event ) {
			$template_id = genosys_receipt_sms_template_id( $to );
			$phone = genosys_sms_normalize_phone( $order->get_billing_phone() );
			$current = genosys_receipt_get_current( $order );
			if ( ! $template_id || '' === genosys_sms_api_key() || '' === $phone || ! $current || (int) $current['id'] !== absint( $receipt_id ) || $to !== $current['reviewStatus'] ) {
				return;
			}
			$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			$event = array(
				'id' => $event_id,
				'receipt_id' => absint( $receipt_id ),
				'from' => $from,
				'to' => $to,
				'template_id' => $template_id,
				'phone' => $phone,
				'parameters' => array( array( 'name' => 'NAME', 'value' => sanitize_text_field( '' !== $name ? $name : 'مشتری' ) ) ),
				'created_at' => time(),
				'attempted_at' => 0,
				'status' => 'pending',
			);
			$order->update_meta_data( $event_key, $event );
			$order->save_meta_data();
			$order->read_meta_data( true );
			if ( $event !== $order->get_meta( $event_key, true ) ) {
				throw new RuntimeException( 'snapshot_unavailable' );
			}
		}
		if ( ! is_array( $event ) || $event_id !== ( $event['id'] ?? '' ) || absint( $receipt_id ) !== ( $event['receipt_id'] ?? 0 ) || $from !== ( $event['from'] ?? '' ) || $to !== ( $event['to'] ?? '' ) || ! empty( $event['attempted_at'] ) ) {
			return;
		}
		$args = array( (int) $order_id, $event_id );
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			$queued = as_enqueue_async_action( 'genosys_receipt_sms_send', $args, 'genosys-receipt-review-sms', true );
			if ( $queued || ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( 'genosys_receipt_sms_send', $args, 'genosys-receipt-review-sms' ) ) ) {
				return;
			}
		}
		if ( ! wp_next_scheduled( 'genosys_receipt_sms_send', $args ) && ! wp_schedule_single_event( time(), 'genosys_receipt_sms_send', $args ) ) {
			genosys_receipt_sms_log( $order_id, 'queue_unavailable' );
		}
	} catch ( Throwable $exception ) {
		genosys_receipt_sms_log( $order_id, 'queue_unavailable' );
	}
}
add_action( 'genosys_receipt_review_status_changed', 'genosys_receipt_sms_queue', 1, 5 );

/** Claim the saved event before HTTP; retries cannot send the same decision twice. */
function genosys_receipt_sms_send( $order_id, $event_id ) {
	$order_id = absint( $order_id );
	$event_key = genosys_receipt_sms_event_key( $event_id );
	if ( ! $order_id || ! $event_key || ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$lock = genosys_order_sms_lock( $order_id, 'receipt_' . $event_id );
	if ( ! $lock ) {
		return;
	}
	try {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || 'shop_order' !== $order->get_type() ) {
			return;
		}
		$order->read_meta_data( true );
		$event = $order->get_meta( $event_key, true );
		$api_key = genosys_sms_api_key();
		if ( ! is_array( $event ) || $event_id !== ( $event['id'] ?? '' ) || ! empty( $event['attempted_at'] ) || ! genosys_receipt_sms_template_id( $event['to'] ?? '' ) || '' === $api_key || empty( $event['template_id'] ) || empty( $event['phone'] ) || $event['phone'] !== genosys_sms_normalize_phone( $event['phone'] ) || ! isset( $event['parameters'] ) || ! is_array( $event['parameters'] ) || 1 !== count( $event['parameters'] ) || 'NAME' !== ( $event['parameters'][0]['name'] ?? '' ) ) {
			return;
		}
		if ( function_exists( 'genosys_account_notification_order_allowed' ) && ! genosys_account_notification_order_allowed( $order, 'payment_sms', $event['phone'] ) ) {
			$event['attempted_at'] = time(); $event['status'] = 'suppressed';
			$order->update_meta_data( $event_key, $event ); $order->save_meta_data();
			return;
		}
		$event['attempted_at'] = time();
		$event['status'] = 'attempting';
		$order->update_meta_data( $event_key, $event );
		$order->save_meta_data();
		$order->read_meta_data( true );
		$claim = $order->get_meta( $event_key, true );
		if ( ! is_array( $claim ) || $event['attempted_at'] !== ( $claim['attempted_at'] ?? 0 ) || 'attempting' !== ( $claim['status'] ?? '' ) ) {
			throw new RuntimeException( 'claim_unavailable' );
		}
		$result = genosys_order_sms_request( $event['phone'], (int) $event['template_id'], $event['parameters'], $api_key );
		$event['status'] = $result['status'];
		if ( 'sent' === $result['status'] ) {
			$event['message_id'] = $result['message_id'];
			$event['sent_at'] = time();
		} else {
			genosys_receipt_sms_log( $order_id, $result['reason'] );
		}
		$order->update_meta_data( $event_key, $event );
		$order->save_meta_data();
	} catch ( Throwable $exception ) {
		genosys_receipt_sms_log( $order_id, 'send_unconfirmed' );
	} finally {
		genosys_order_sms_unlock( $lock );
	}
}
add_action( 'genosys_receipt_sms_send', 'genosys_receipt_sms_send', 10, 2 );
