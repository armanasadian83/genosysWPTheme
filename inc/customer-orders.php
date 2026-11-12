<?php
/**
 * Authorized WooCommerce orders: scoped routing and read-only presentation.
 *
 * @package Genosys
 */

defined( 'ABSPATH' ) || exit;

/** Leave every other account endpoint and the account dashboard untouched. */
function genosys_is_customer_orders() {
	return function_exists( 'is_account_page' ) && function_exists( 'is_wc_endpoint_url' ) && is_account_page() && is_wc_endpoint_url( 'orders' );
}

function genosys_customer_orders_template( $template ) {
	return genosys_is_customer_orders() ? get_theme_file_path( '/page-account-orders.php' ) : $template;
}
add_filter( 'template_include', 'genosys_customer_orders_template', 99 );

/** Customer information and receipt nonces must never enter a public page cache. */
function genosys_customer_orders_nocache() {
	if ( genosys_is_customer_orders() ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	}
}
add_action( 'template_redirect', 'genosys_customer_orders_nocache', 1 );

/** Localize only visible text, never URLs, markup, or numeric database values. */
function genosys_customer_orders_digits( $value ) {
	return function_exists( 'genosys_persian_number' ) ? genosys_persian_number( $value ) : strtr( (string) $value, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
}

/** Historical amounts retain their own order currency and WooCommerce formatting. */
function genosys_customer_orders_price( $amount, $order = null ) {
	$currency = is_object( $order ) && method_exists( $order, 'get_currency' ) ? $order->get_currency() : ( is_string( $order ) ? $order : '' );
	$html     = wc_price( $amount, array( 'currency' => $currency ) );
	if ( function_exists( 'genosys_checkout_localize_html' ) ) {
		return wp_kses_post( genosys_checkout_localize_html( $html ) );
	}
	$parts = wp_html_split( $html );
	foreach ( $parts as &$part ) {
		if ( '' !== $part && '<' !== $part[0] ) {
			$part = esc_html( genosys_customer_orders_digits( html_entity_decode( $part, ENT_QUOTES, 'UTF-8' ) ) );
		}
	}
	return wp_kses_post( implode( '', $parts ) );
}

/** The six reference filters are always present; unsupported stages show zero. */
function genosys_customer_orders_filter_labels() {
	return array(
		'all'       => 'همه',
		'pending'   => 'در انتظار پرداخت',
		'review'    => 'در حال بررسی',
		'preparing' => 'در حال آماده‌سازی',
		'shipped'   => 'ارسال شده',
		'delivered' => 'تحویل شده',
		'cancelled' => 'لغو شده',
	);
}

/** This classification describes existing data; it never changes an order status. */
function genosys_customer_orders_classification( $order, $receipt = null ) {
	$status = (string) $order->get_status();
	$labels = genosys_customer_orders_filter_labels();
	if ( in_array( $status, array( 'pending', 'on-hold' ), true ) ) {
		$group = $receipt ? 'review' : 'pending';
		$note  = $receipt ? 'در انتظار تأیید پرداخت' : ( function_exists( 'genosys_receipt_order_is_eligible' ) && genosys_receipt_order_is_eligible( $order ) ? 'منتظر فیش' : 'منتظر پرداخت' );
		return array( 'group' => $group, 'label' => $labels[ $group ], 'stage' => 2, 'stageNote' => $note );
	}
	$known = array( 'processing' => array( 'preparing', 3 ), 'shipped' => array( 'shipped', 4 ), 'delivered' => array( 'delivered', 5 ), 'completed' => array( 'delivered', 5 ), 'cancelled' => array( 'cancelled', 0 ) );
	if ( isset( $known[ $status ] ) ) {
		$group = $known[ $status ][0];
		return array( 'group' => $group, 'label' => $labels[ $group ], 'stage' => $known[ $status ][1], 'stageNote' => '' );
	}
	// Custom and exceptional statuses keep WooCommerce's real caption and a separate filter.
	$exception_labels = array( 'failed' => 'پرداخت ناموفق', 'refunded' => 'مسترد شده' );
	return array( 'group' => sanitize_key( $status ), 'label' => isset( $exception_labels[ $status ] ) ? $exception_labels[ $status ] : wc_get_order_status_name( $status ), 'stage' => 0, 'stageNote' => '' );
}

/** Reject foreign orders even if a caller supplies an otherwise valid WC_Order. */
function genosys_customer_order_presentation( $order ) {
	if ( ! genosys_order_access_can_view( $order ) ) {
		return null;
	}
	$receipt = function_exists( 'genosys_receipt_get_current' ) ? genosys_receipt_get_current( $order ) : null;
	if ( $receipt && function_exists( 'genosys_receipt_customer_descriptor' ) ) {
		$receipt = genosys_receipt_customer_descriptor( $order, $receipt );
	}
	$data     = genosys_customer_orders_classification( $order, $receipt );
	$date     = $order->get_date_created();
	$eligible = function_exists( 'genosys_receipt_order_is_eligible' ) && genosys_receipt_order_is_eligible( $order );
	$config   = $eligible && function_exists( 'genosys_receipt_customer_context' ) ? genosys_receipt_customer_context( $order ) : null;
	$data['status']          = (string) $order->get_status();
	$data['statuskey']       = $data['group'];
	$data['receipt']         = $receipt;
	$data['receiptEligible'] = $eligible;
	$data['receiptConfig']   = $config;
	$data['customerConfig']  = $config;
	// WC_DateTime::date() shifts the timestamp first; emit the real instant so
	// browser formatting applies the configured store timezone exactly once.
	$data['dateISO']         = $date ? gmdate( DATE_ATOM, $date->getTimestamp() ) : '';
	$data['dateText']        = $date ? genosys_customer_orders_digits( wp_date( 'j F Y، ساعت H:i', $date->getTimestamp(), wp_timezone() ) ) : '';
	return $data;
}

/** GET links work without JavaScript and retain the configured Woo account URL. */
function genosys_customer_orders_url( $filter = 'all', $page = 1 ) {
	$url  = wc_get_account_endpoint_url( 'orders' );
	$args = array();
	if ( 'all' !== $filter ) {
		$args['gco_status'] = sanitize_key( $filter );
	}
	if ( $page > 1 ) {
		$args['gco_page'] = absint( $page );
	}
	return $args ? add_query_arg( $args, $url ) : $url;
}

/**
 * Merge account-owned orders, verified-phone guest orders and browser grants.
 * Never reassign customer_id or match another registered customer's billing phone.
 */
function genosys_customer_orders_history() {
	$history = array();
	$queries = array();
	if ( is_user_logged_in() ) {
		$queries[] = array( 'customer_id' => get_current_user_id() );
	}
	if ( genosys_order_access_phone() ) {
		// Woo's native phone query uses exact matches. Historical numbers may have
		// spaces, Arabic/Persian digits or international prefixes, so normalize
		// guest orders in bounded CRUD batches for both CPT and HPOS storage.
		$queries[] = array( 'customer_id' => 0 );
	}
	foreach ( $queries as $query ) {
		$batch = 1;
		do {
			$orders = wc_get_orders( array_merge( array( 'type' => 'shop_order', 'limit' => 200, 'page' => $batch, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects', 'paginate' => false ), $query ) );
			foreach ( $orders as $order ) {
				if ( ! genosys_order_access_can_view( $order ) ) {
					continue;
				}
				$date = $order->get_date_created();
				$history[ $order->get_id() ] = $date ? $date->getTimestamp() : 0;
			}
			$batch++;
		} while ( count( $orders ) === 200 );
	}
	$granted_ids = genosys_order_access_order_ids();
	if ( function_exists( 'genosys_account_phone_historical_order_ids' ) ) { $granted_ids = array_unique( array_merge( $granted_ids, genosys_account_phone_historical_order_ids() ) ); }
	foreach ( $granted_ids as $id ) {
		if ( isset( $history[ $id ] ) ) {
			continue;
		}
		$order = wc_get_order( $id );
		if ( genosys_order_access_can_view( $order ) ) {
			$date = $order->get_date_created();
			$history[ $id ] = $date ? $date->getTimestamp() : 0;
		}
	}
	// Date first, then ID, keeps merged histories and pagination deterministic.
	$ids = array_keys( $history );
	usort( $ids, static function ( $a, $b ) use ( $history ) {
		return $history[ $b ] <=> $history[ $a ] ?: $b <=> $a;
	} );
	return $ids;
}

/**
 * Count the complete history through Woo CRUD, in bounded batches for both stores.
 * Receipt-derived filters cannot be inferred from Woo status counts alone.
 */
function genosys_customer_orders_context() {
	static $context = null;
	if ( null !== $context ) {
		return $context;
	}
	$labels  = genosys_customer_orders_filter_labels();
	$context = array( 'orders' => array(), 'filters' => array(), 'total' => 0, 'filteredTotal' => 0, 'pages' => 1, 'currentPage' => 1, 'filter' => 'all', 'missingCount' => 0, 'missingOrderId' => 0, 'missingOrderURL' => '' );
	foreach ( $labels as $key => $label ) {
		$context['filters'][ $key ] = array( 'label' => $label, 'count' => 0, 'url' => genosys_customer_orders_url( $key ) );
	}
	if ( ! function_exists( 'wc_get_orders' ) || ! genosys_order_access_has_proof() ) {
		return $context;
	}
	$requested   = isset( $_GET['gco_status'] ) && is_scalar( $_GET['gco_status'] ) ? sanitize_key( wp_unslash( (string) $_GET['gco_status'] ) ) : 'all';
	$page_raw    = isset( $_GET['gco_page'] ) && is_scalar( $_GET['gco_page'] ) ? wp_unslash( (string) $_GET['gco_page'] ) : get_query_var( 'orders', 1 );
	$page        = max( 1, absint( $page_raw ) );
	$groups      = array_fill_keys( array_keys( $labels ), array() );
	foreach ( genosys_customer_orders_history() as $order_id ) {
		$order = wc_get_order( $order_id );
		// Recheck authority at presentation time even if a query plugin widens scope.
		if ( ! genosys_order_access_can_view( $order ) ) {
			continue;
		}
		$receipt = function_exists( 'genosys_receipt_get_current' ) ? genosys_receipt_get_current( $order ) : null;
		$data    = genosys_customer_orders_classification( $order, $receipt );
		$key     = $data['group'];
		$id      = (int) $order->get_id();
		if ( ! isset( $groups[ $key ] ) ) {
			$groups[ $key ]            = array();
			$context['filters'][ $key ] = array( 'label' => $data['label'], 'count' => 0, 'url' => genosys_customer_orders_url( $key ) );
		}
		$groups['all'][] = $id;
		$groups[ $key ][] = $id;
		$context['filters']['all']['count']++;
		$context['filters'][ $key ]['count']++;
		if ( function_exists( 'genosys_receipt_order_is_eligible' ) && genosys_receipt_order_is_eligible( $order ) ) {
			$context['missingCount']++;
			if ( ! $context['missingOrderId'] ) {
				$context['missingOrderId']  = $id;
				$missing_group              = $key;
				$missing_page               = (int) ceil( count( $groups[ $key ] ) / 12 );
				$context['missingOrderURL'] = genosys_customer_orders_url( $missing_group, $missing_page ) . '#gco-order-' . $id;
			}
		}
	}
	$filter                   = isset( $groups[ $requested ] ) ? $requested : 'all';
	$context['total']         = count( $groups['all'] );
	$context['filteredTotal'] = count( $groups[ $filter ] );
	$context['pages']         = max( 1, (int) ceil( $context['filteredTotal'] / 12 ) );
	$context['currentPage']   = min( $page, $context['pages'] );
	$context['filter']        = $filter;
	foreach ( array_slice( $groups[ $filter ], ( $context['currentPage'] - 1 ) * 12, 12 ) as $id ) {
		$order = wc_get_order( $id );
		if ( genosys_order_access_can_view( $order ) ) {
			$context['orders'][] = $order;
		}
	}
	return $context;
}
