<?php
/** Customer notifications for saved WooCommerce status changes. */
defined( 'ABSPATH' ) || exit;

function genosys_order_status_sms_template_id() {
	$value = get_option( 'genosys_order_status_sms_template_id', '' );
	if ( ! is_scalar( $value ) || is_bool( $value ) ) {
		return 0;
	}
	$value = genosys_checkout_ascii_digits( trim( (string) $value ) );
	return preg_match( '/^[1-9][0-9]{0,9}$/D', $value ) && (float) $value <= 2147483647 ? (int) $value : 0;
}

function genosys_order_status_sms_log( $order_id, $reason ) {
	if ( function_exists( 'wc_get_logger' ) ) {
		wc_get_logger()->warning( 'Order status SMS could not be confirmed.', array( 'source' => 'genosys-order-status-sms', 'order_id' => absint( $order_id ), 'reason' => sanitize_key( $reason ) ) );
	}
}

/** Suppress only the first checkout request's immediate gateway status setup. */
function genosys_order_status_sms_initial_request( $order_id, $mark = false ) {
	static $initial_orders = array();
	if ( $mark ) {
		$initial_orders[ absint( $order_id ) ] = true;
	}
	return isset( $initial_orders[ absint( $order_id ) ] );
}

function genosys_order_status_sms_initialize_checkout( $order ) {
	if ( ! $order instanceof WC_Order || 'shop_order' !== $order->get_type() || ! $order->get_id() ) {
		return;
	}
	try {
		$order->read_meta_data( true );
		if ( ! $order->get_meta( '_genosys_order_status_sms_checkout_initialized', true ) ) {
			genosys_order_status_sms_initial_request( $order->get_id(), true );
			$order->update_meta_data( '_genosys_order_status_sms_checkout_initialized', time() );
			$order->save_meta_data();
		}
	} catch ( Throwable $exception ) {
		genosys_order_status_sms_log( $order->get_id(), 'initialization_unavailable' );
	}
}

function genosys_order_status_sms_initialize_classic( $order_id, $posted_data, $order ) {
	genosys_order_status_sms_initialize_checkout( $order instanceof WC_Order ? $order : wc_get_order( $order_id ) );
}
add_action( 'woocommerce_checkout_order_processed', 'genosys_order_status_sms_initialize_classic', 1, 3 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'genosys_order_status_sms_initialize_checkout', 1, 1 );

function genosys_order_status_sms_event_key( $event_id ) {
	return is_string( $event_id ) && preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $event_id ) ? '_genosys_order_status_sms_event_' . str_replace( '-', '', $event_id ) : '';
}

/** Persist an immutable snapshot alongside each actual status save. */
function genosys_order_status_sms_capture( $order ) {
	if ( ! $order instanceof WC_Order || 'shop_order' !== $order->get_type() || ! $order->get_id() || genosys_order_status_sms_initial_request( $order->get_id() ) ) {
		return;
	}
	try {
		$changes = $order->get_changes();
		if ( ! isset( $changes['status'] ) ) {
			return;
		}
		// WC_Data::get_data() contains persisted properties, before dirty changes.
		$data = $order->get_data();
		$from = isset( $data['status'] ) ? (string) $data['status'] : '';
		$to = (string) $changes['status'];
		$ignored = array( '', 'draft', 'auto-draft', 'checkout-draft', 'trash' );
		$template_id = genosys_order_status_sms_template_id();
		$phone = genosys_sms_normalize_phone( $order->get_billing_phone() );
		if ( function_exists( 'genosys_account_notification_order_allowed' ) && ! genosys_account_notification_order_allowed( $order, 'order_status_sms', $phone ) ) { return; }
		if ( $from === $to || in_array( $from, $ignored, true ) || in_array( $to, $ignored, true ) || ! $template_id || '' === genosys_sms_api_key() || '' === $phone ) {
			return;
		}
		$event_id = wp_generate_uuid4();
		$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		$event = array(
			'id' => $event_id,
			'from' => $from,
			'to' => $to,
			'template_id' => $template_id,
			'phone' => $phone,
			'parameters' => array(
				array( 'name' => 'NAME', 'value' => sanitize_text_field( '' !== $name ? $name : 'مشتری' ) ),
				array( 'name' => 'STATUS', 'value' => sanitize_text_field( wc_get_order_status_name( $to ) ) ),
			),
			'created_at' => time(),
			'attempted_at' => 0,
			'status' => 'pending',
		);
		$order->update_meta_data( '_genosys_order_status_sms_transition_id', $event_id );
		$order->update_meta_data( genosys_order_status_sms_event_key( $event_id ), $event );
	} catch ( Throwable $exception ) {
		genosys_order_status_sms_log( $order->get_id(), 'snapshot_unavailable' );
	}
}
add_action( 'woocommerce_before_order_object_save', 'genosys_order_status_sms_capture', 9999, 1 );

function genosys_order_status_sms_enqueue_event( $order_id, $event_id ) {
	if ( function_exists( 'genosys_account_notification_order_allowed' ) ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) { return; }
		$order->read_meta_data( true );
		$key = genosys_order_status_sms_event_key( $event_id );
		$event = $key ? $order->get_meta( $key, true ) : null;
		if ( ! genosys_account_notification_order_allowed( $order, 'order_status_sms', is_array( $event ) ? ( $event['phone'] ?? '' ) : '' ) ) {
			if ( is_array( $event ) && empty( $event['attempted_at'] ) ) {
				$event['attempted_at'] = time(); $event['status'] = 'suppressed';
				$order->update_meta_data( $key, $event ); $order->save_meta_data();
			}
			return;
		}
	}
	$args = array( (int) $order_id, $event_id );
	if ( function_exists( 'as_enqueue_async_action' ) ) {
		$queued = as_enqueue_async_action( 'genosys_order_status_sms_send', $args, 'genosys-order-status-sms', true );
		if ( $queued || ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( 'genosys_order_status_sms_send', $args, 'genosys-order-status-sms' ) ) ) {
			return;
		}
	}
	if ( ! wp_next_scheduled( 'genosys_order_status_sms_send', $args ) && ! wp_schedule_single_event( time(), 'genosys_order_status_sms_send', $args ) ) {
		genosys_order_status_sms_log( $order_id, 'queue_unavailable' );
	}
}

/** Nested status hooks can change the shared object; use saved event records. */
function genosys_order_status_sms_queue( $order_id, $from, $to, $order ) {
	if ( ! $order instanceof WC_Order || 'shop_order' !== $order->get_type() || absint( $order_id ) !== $order->get_id() || $from === $to || genosys_order_status_sms_initial_request( $order_id ) ) {
		return;
	}
	try {
		$saved_order = wc_get_order( $order_id );
		if ( ! $saved_order instanceof WC_Order || 'shop_order' !== $saved_order->get_type() ) {
			return;
		}
		$saved_order->read_meta_data( true );
		foreach ( $saved_order->get_meta_data() as $meta ) {
			$row = $meta->get_data();
			$event = $row['value'];
			$event_id = is_array( $event ) ? ( $event['id'] ?? '' ) : '';
			if ( ! $event_id || $row['key'] !== genosys_order_status_sms_event_key( $event_id ) || $from !== ( $event['from'] ?? '' ) || $to !== ( $event['to'] ?? '' ) || ! empty( $event['attempted_at'] ) ) {
				continue;
			}
			genosys_order_status_sms_enqueue_event( $order_id, $event_id );
		}
	} catch ( Throwable $exception ) {
		genosys_order_status_sms_log( $order_id, 'queue_unavailable' );
	}
}
add_action( 'woocommerce_order_status_changed', 'genosys_order_status_sms_queue', 1, 4 );

/** Each event is claimed once; changing an order again creates a separate event. */
function genosys_order_status_sms_send( $order_id, $event_id ) {
	$order_id = absint( $order_id );
	$event_key = genosys_order_status_sms_event_key( $event_id );
	if ( ! $order_id || ! $event_key || ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$lock = genosys_order_sms_lock( $order_id, $event_id );
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
		if ( ! is_array( $event ) || $event_id !== ( $event['id'] ?? '' ) || ! empty( $event['attempted_at'] ) || ! genosys_order_status_sms_template_id() || '' === $api_key || empty( $event['template_id'] ) || empty( $event['phone'] ) || $event['phone'] !== genosys_sms_normalize_phone( $event['phone'] ) || ! isset( $event['parameters'] ) || ! is_array( $event['parameters'] ) || 2 !== count( $event['parameters'] ) || 'NAME' !== ( $event['parameters'][0]['name'] ?? '' ) || 'STATUS' !== ( $event['parameters'][1]['name'] ?? '' ) ) {
			return;
		}
		if ( function_exists( 'genosys_account_notification_order_allowed' ) && ! genosys_account_notification_order_allowed( $order, 'order_status_sms', $event['phone'] ) ) {
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
			genosys_order_status_sms_log( $order_id, $result['reason'] );
		}
		$order->update_meta_data( $event_key, $event );
		$order->save_meta_data();
	} catch ( Throwable $exception ) {
		genosys_order_status_sms_log( $order_id, 'send_unconfirmed' );
	} finally {
		genosys_order_sms_unlock( $lock );
	}
}
add_action( 'genosys_order_status_sms_send', 'genosys_order_status_sms_send', 10, 2 );
