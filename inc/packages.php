<?php
/** WooCommerce packages use their components' inventory; the package has no independent stock. */
defined( 'ABSPATH' ) || exit;

function genosys_is_package_product( $product ) {
	return $product instanceof WC_Product && 'genosys_package' === $product->get_type();
}

/** Definitions contain distinct product/variation IDs and whole units per package. */
function genosys_package_components( $product ) {
	$product = $product instanceof WC_Product ? $product : wc_get_product( $product );
	if ( ! $product ) { return array(); }
	$raw = $product->get_meta( '_genosys_package_items', true );
	if ( ! is_array( $raw ) || count( $raw ) > 24 ) { return array(); }
	$items = array(); $seen = array();
	foreach ( $raw as $row ) {
		if ( ! is_array( $row ) || ! isset( $row['product_id'], $row['quantity'] ) || ! is_numeric( $row['product_id'] ) || ! is_numeric( $row['quantity'] ) ) { return array(); }
		$id = (int) $row['product_id']; $qty = (int) $row['quantity'];
		if ( $id < 1 || $qty < 1 || $qty > 9999 || (float) $row['product_id'] !== (float) $id || (float) $row['quantity'] !== (float) $qty || isset( $seen[ $id ] ) ) { return array(); }
		$seen[ $id ] = true; $items[] = array( 'product_id' => $id, 'quantity' => $qty );
	}
	return $items;
}

function genosys_package_component_is_eligible( $product ) {
	if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() || ! $product->is_type( array( 'simple', 'variation' ) ) || genosys_is_package_product( $product ) ) { return false; }
	if ( $product->is_type( 'variation' ) ) {
		$parent = wc_get_product( $product->get_parent_id() );
		return $parent && $parent->is_type( 'variable' ) && 'publish' === $parent->get_status();
	}
	return true;
}

/** Null means all eligible components are in stock with no managed quantity limit. */
function genosys_package_stock( $product ) {
	$components = genosys_package_components( $product );
	if ( count( $components ) < 2 ) { return 0; }
	$pools = array();
	foreach ( $components as $row ) {
		$component = wc_get_product( $row['product_id'] );
		if ( ! genosys_package_component_is_eligible( $component ) || ! $component->is_in_stock() ) { return 0; }
		if ( ! $component->managing_stock() ) { if ( 'instock' !== $component->get_stock_status() ) { return 0; } continue; }
		$id = (int) $component->get_stock_managed_by_id(); $pool = wc_get_product( $id );
		if ( ! $pool || ! is_numeric( $pool->get_stock_quantity() ) ) { return 0; }
		if ( ! isset( $pools[ $id ] ) ) { $pools[ $id ] = array( 'stock' => max( 0, (float) $pool->get_stock_quantity() ), 'units' => 0 ); }
		$pools[ $id ]['units'] += $row['quantity'];
	}
	$available = null;
	foreach ( $pools as $pool ) {
		$quantity = (int) floor( $pool['stock'] / $pool['units'] );
		$available = null === $available ? $quantity : min( $available, $quantity );
	}
	return $available;
}

function genosys_package_classes() {
	if ( ! class_exists( 'WC_Product_Simple' ) || class_exists( 'WC_Product_Genosys_Package' ) ) { return; }
	class WC_Product_Genosys_Package extends WC_Product_Simple {
		public function get_type() { return 'genosys_package'; }
		public function get_manage_stock( $context = 'view' ) { return false; }
		public function managing_stock() { return false; }
		public function get_virtual( $context = 'view' ) { return false; }
		public function get_downloadable( $context = 'view' ) { return false; }
		public function backorders_allowed() { return false; }
		public function is_on_backorder( $qty_in_cart = 0 ) { return false; }
		public function get_stock_quantity( $context = 'view' ) { return 'edit' === $context ? parent::get_stock_quantity( $context ) : genosys_package_stock( $this ); }
		public function get_stock_status( $context = 'view' ) { return 'edit' === $context ? parent::get_stock_status( $context ) : ( 0 === genosys_package_stock( $this ) ? 'outofstock' : 'instock' ); }
		public function is_in_stock() { return 0 !== genosys_package_stock( $this ); }
		public function is_purchasable() { return parent::is_purchasable() && count( genosys_package_components( $this ) ) >= 2; }
		public function has_enough_stock( $quantity ) { $stock = genosys_package_stock( $this ); return $quantity > 0 && floor( (float) $quantity ) === (float) $quantity && ( null === $stock || $stock >= $quantity ); }
		public function get_max_purchase_quantity() { $stock = genosys_package_stock( $this ); return $this->is_sold_individually() ? 1 : ( null === $stock ? -1 : $stock ); }
		public function get_weight( $context = 'view' ) {
			if ( 'edit' === $context ) { return parent::get_weight( $context ); }
			$weight = 0;
			foreach ( genosys_package_components( $this ) as $row ) { $component = wc_get_product( $row['product_id'] ); if ( genosys_package_component_is_eligible( $component ) ) { $weight += (float) $component->get_weight() * $row['quantity']; } }
			return $weight ? (string) $weight : '';
		}
		protected function get_availability_text() {
			$stock = genosys_package_stock( $this );
			return 0 === $stock ? __( 'Out of stock', 'woocommerce' ) : ( null === $stock ? __( 'In stock', 'woocommerce' ) : sprintf( '%s پکیج موجود', function_exists( 'genosys_persian_number' ) ? genosys_persian_number( (string) $stock ) : $stock ) );
		}
	}
	/** Only native reservation sees these temporary inventory items; none are saved as order lines. */
	class Genosys_Package_Inventory_Order extends WC_Order {
		private $inventory_items = array();
		public function set_inventory_items( $items ) { $this->inventory_items = $items; }
		public function get_items( $types = 'line_item' ) { return in_array( 'line_item', (array) $types, true ) ? $this->inventory_items : parent::get_items( $types ); }
	}
}
add_action( 'woocommerce_init', 'genosys_package_classes', 0 );
add_filter( 'woocommerce_product_class', function ( $class, $type ) { return 'genosys_package' === $type ? 'WC_Product_Genosys_Package' : $class; }, 10, 2 );
add_filter( 'product_type_selector', function ( $types ) { $types['genosys_package'] = 'پکیج'; return $types; } );
add_action( 'woocommerce_genosys_package_add_to_cart', 'woocommerce_simple_add_to_cart' );
add_filter( 'woocommerce_add_to_cart_handler', function ( $type ) { return 'genosys_package' === $type ? 'simple' : $type; } );

function genosys_package_component_snapshot( $product ) {
	if ( 0 === genosys_package_stock( $product ) ) { throw new Exception( 'محصولات این پکیج در حال حاضر موجود نیستند.' ); }
	$snapshot = array();
	foreach ( genosys_package_components( $product ) as $row ) {
		$component = wc_get_product( $row['product_id'] );
		if ( ! genosys_package_component_is_eligible( $component ) ) { throw new Exception( 'یکی از محصولات پکیج دیگر قابل فروش نیست.' ); }
		$snapshot[] = array( 'product_id' => $component->get_id(), 'quantity' => $row['quantity'], 'stock_pool_id' => (int) $component->get_stock_managed_by_id(), 'managed' => (bool) $component->managing_stock(), 'name' => $component->get_name(), 'sku' => $component->get_sku() );
	}
	return $snapshot;
}

function genosys_package_snapshot_line_item( $item, $cart_key = '', $values = array(), $order = null ) {
	if ( ! $item instanceof WC_Order_Item_Product || $item->get_meta( '_genosys_package_components', true ) ) { return; }
	$product = isset( $values['data'] ) ? $values['data'] : $item->get_product();
	if ( genosys_is_package_product( $product ) ) {
		$quantity = (float) $item->get_quantity();
		if ( $quantity < 1 || floor( $quantity ) !== $quantity ) { throw new Exception( 'تعداد پکیج باید عدد صحیح و مثبت باشد.' ); }
		$item->add_meta_data( '_genosys_package_components', genosys_package_component_snapshot( $product ), true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'genosys_package_snapshot_line_item', 10, 4 );
add_action( 'woocommerce_new_order_item', function ( $item_id, $item ) {
	if ( $item instanceof WC_Order_Item_Product && genosys_is_package_product( $item->get_product() ) && ! $item->get_meta( '_genosys_package_components', true ) ) { genosys_package_snapshot_line_item( $item ); $item->save(); }
}, 10, 2 );

function genosys_package_order_has_packages( $order ) {
	if ( ! $order instanceof WC_Order ) { return false; }
	foreach ( $order->get_items() as $item ) { if ( $item->get_meta( '_genosys_package_components', true ) ) { return true; } }
	return false;
}

/** Pools are frozen when an order first consumes stock, including ordinary lines in mixed orders. */
function genosys_package_line_pools( $item ) {
	$ledger = $item->get_meta( '_genosys_package_stock_ledger', true );
	if ( is_array( $ledger ) && isset( $ledger['pools'] ) ) { return $ledger['pools']; }
	$snapshot = $item->get_meta( '_genosys_package_components', true ); $pools = array();
	if ( is_array( $snapshot ) && $snapshot ) {
		foreach ( $snapshot as $row ) {
			if ( empty( $row['managed'] ) ) { continue; }
			$id = (int) $row['stock_pool_id'];
			if ( ! isset( $pools[ $id ] ) ) { $pools[ $id ] = array( 'units' => 0, 'strict' => true ); }
			$pools[ $id ]['units'] += (float) $row['quantity'];
		}
	} else {
		$product = $item->get_product();
		if ( $product && $product->managing_stock() ) { $pools[ $product->get_stock_managed_by_id() ] = array( 'units' => 1, 'strict' => ! $product->backorders_allowed() ); }
	}
	return $pools;
}

function genosys_package_add_demand( &$demand, $pools, $quantity ) {
	foreach ( $pools as $id => $pool ) {
		$id = (int) $id;
		if ( ! isset( $demand[ $id ] ) ) { $demand[ $id ] = array( 'total' => 0, 'strict' => 0 ); }
		$units = (float) $pool['units'] * $quantity;
		$demand[ $id ]['total'] += $units;
		if ( ! empty( $pool['strict'] ) ) { $demand[ $id ]['strict'] += $units; }
	}
}

/** A locking read sees reservations committed while this transaction waited for a pool lock. */
function genosys_package_current_held_stock( $pool, $exclude_order_id ) {
	$previous = ! empty( $GLOBALS['genosys_package_read_current_holds'] );
	$GLOBALS['genosys_package_read_current_holds'] = true;
	try { return wc_get_held_stock_quantity( $pool, $exclude_order_id ); }
	finally { $GLOBALS['genosys_package_read_current_holds'] = $previous; }
}
add_filter( 'woocommerce_query_for_reserved_stock', function ( $query ) {
	return ! empty( $GLOBALS['genosys_package_read_current_holds'] ) && ! preg_match( '/FOR\s+UPDATE\s*$/i', $query ) ? rtrim( $query, "; \t\n\r" ) . ' FOR UPDATE' : $query;
}, 100 );

/** Add/update/cart checks include standalone products and every package sharing the same pool. */
function genosys_package_check_cart_demand( $extra_product = null, $extra_quantity = 0, $exclude_key = '' ) {
	if ( ! WC()->cart ) { return true; }
	$lines = array(); $has_package = genosys_is_package_product( $extra_product );
	foreach ( WC()->cart->get_cart() as $key => $values ) {
		if ( $key === $exclude_key ) { continue; }
		$lines[] = array( $values['data'], (float) $values['quantity'] );
		$has_package = $has_package || genosys_is_package_product( $values['data'] );
	}
	if ( $extra_product && $extra_quantity > 0 ) { $lines[] = array( $extra_product, (float) $extra_quantity ); }
	if ( ! $has_package ) { return true; }
	$demand = array();
	foreach ( $lines as $line ) {
		list( $product, $qty ) = $line;
		if ( genosys_is_package_product( $product ) ) {
			if ( $qty < 1 || floor( $qty ) !== $qty ) { return new WP_Error( 'package_quantity', 'تعداد پکیج باید عدد صحیح و مثبت باشد.' ); }
			try { $snapshot = genosys_package_component_snapshot( $product ); } catch ( Exception $error ) { return new WP_Error( 'package_stock', $error->getMessage() ); }
			$pools = array();
			foreach ( $snapshot as $row ) {
				if ( ! $row['managed'] ) { continue; }
				$id = $row['stock_pool_id'];
				if ( ! isset( $pools[ $id ] ) ) { $pools[ $id ] = array( 'units' => 0, 'strict' => true ); }
				$pools[ $id ]['units'] += $row['quantity'];
			}
		} else {
			$pools = $product && $product->managing_stock() ? array( $product->get_stock_managed_by_id() => array( 'units' => 1, 'strict' => ! $product->backorders_allowed() ) ) : array();
		}
		genosys_package_add_demand( $demand, $pools, $qty );
	}
	$exclude_order = WC()->session ? (int) WC()->session->get( 'order_awaiting_payment' ) : 0;
	foreach ( $demand as $id => $row ) {
		$pool = wc_get_product( $id );
		if ( $row['strict'] > 0 && ( ! $pool || (float) $pool->get_stock_quantity() - wc_get_held_stock_quantity( $pool, $exclude_order ) < $row['strict'] ) ) {
			return new WP_Error( 'package_stock', sprintf( 'موجودی «%s» برای مجموع محصولات و پکیج‌های سبد کافی نیست.', $pool ? $pool->get_name() : '#' . $id ) );
		}
	}
	return true;
}
add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id, $quantity, $variation_id = 0 ) {
	if ( ! $passed ) { return false; }
	$result = genosys_package_check_cart_demand( wc_get_product( $variation_id ?: $product_id ), $quantity );
	if ( is_wp_error( $result ) ) { wc_add_notice( $result->get_error_message(), 'error' ); return false; }
	return true;
}, 20, 4 );
add_filter( 'woocommerce_update_cart_validation', function ( $passed, $key, $values, $quantity ) {
	if ( ! $passed ) { return false; }
	$result = genosys_package_check_cart_demand( $values['data'], $quantity, $key );
	if ( is_wp_error( $result ) ) { wc_add_notice( $result->get_error_message(), 'error' ); return false; }
	return true;
}, 20, 4 );
add_action( 'woocommerce_check_cart_items', function () { $result = genosys_package_check_cart_demand(); if ( is_wp_error( $result ) ) { wc_add_notice( $result->get_error_message(), 'error' ); } }, 20 );

/** Intercept the native reservation entry point, including its direct Store API calls. */
function genosys_package_reservation_minutes( $minutes, $order ) {
	if ( ! $minutes || $order instanceof Genosys_Package_Inventory_Order || ! genosys_package_order_has_packages( $order ) ) { return $minutes; }
	$demand = array();
	foreach ( $order->get_items() as $item ) {
		$ledger = $item->get_meta( '_genosys_package_stock_ledger', true );
		$quantity = max( 0, (float) $item->get_quantity() - ( is_array( $ledger ) ? (float) $ledger['refunded'] + (float) $ledger['remaining'] : 0 ) );
		genosys_package_add_demand( $demand, genosys_package_line_pools( $item ), $quantity );
	}
	$view = new Genosys_Package_Inventory_Order( $order->get_id() ); $items = array();
	ksort( $demand, SORT_NUMERIC );
	foreach ( $demand as $id => $row ) {
		if ( $row['strict'] <= 0 ) { continue; }
		$pool = wc_get_product( $id );
		if ( ! $pool ) { throw new Automattic\WooCommerce\Checkout\Helpers\ReserveStockException( 'package_component_missing', 'یکی از محصولات سفارش دیگر موجود نیست.', 403 ); }
		$pool = clone $pool; $pool->set_manage_stock( true ); $pool->set_backorders( 'no' ); $pool->set_stock_status( 'instock' );
		// get_product() normally reloads by ID; retain the reservation-only stock properties.
		$item = new class( $pool, $row['strict'] ) extends WC_Order_Item_Product {
			private $inventory_product;
			public function __construct( $product, $quantity ) { parent::__construct(); $this->inventory_product = $product; $this->set_product( $product ); $this->set_quantity( $quantity ); }
			public function get_product() { return $this->inventory_product; }
		};
		$items[] = $item;
	}
	$view->set_inventory_items( $items );
	$reserve = new Automattic\WooCommerce\Checkout\Helpers\ReserveStock(); $reserve->reserve_stock_for_order( $view, $minutes );
	return 0; // The native outer reservation must not overwrite the aggregated component holds.
}
add_filter( 'woocommerce_order_hold_stock_minutes', 'genosys_package_reservation_minutes', 20, 2 );

function genosys_package_clear_order_cache( $order ) {
	wp_cache_delete( $order->get_id(), 'post_meta' );
	$store = $order->get_data_store();
	if ( method_exists( $store->get_current_class_name(), 'clear_cached_data' ) ) { $store->clear_cached_data( array( $order->get_id() ) ); }
	if ( class_exists( 'Automattic\WooCommerce\Caches\OrderCache' ) ) { wc_get_container()->get( Automattic\WooCommerce\Caches\OrderCache::class )->remove( $order->get_id() ); }
}

function genosys_package_log_stock_warning( $order_id, $message ) {
	try { wc_get_logger()->error( $message, array( 'source' => 'genosys-packages', 'order_id' => $order_id ) ); }
	catch ( Throwable $ignored ) { error_log( 'Genosys package order #' . $order_id . ': ' . $message ); }
}

/** A transaction owns both physical pool changes and each item's outstanding-unit ledger. */
function genosys_package_order_stock( $order, $operation, $refund_id = 0, $refunded_lines = array() ) {
	global $wpdb, $genosys_package_stock_busy, $genosys_package_stock_queue;
	$pool_ids = array(); $changes = array(); $transaction = false; $refund = null;
	try {
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { throw new Exception( 'امکان پردازش موجودی فراهم نیست.' ); }
		$transaction = true; $genosys_package_stock_busy = true;
		// Canonical order-item rows are the mutex for legacy storage and HPOS alike.
		$order_items_table = $wpdb->prefix . 'woocommerce_order_items';
		$wpdb->get_col( $wpdb->prepare( "SELECT order_item_id FROM {$order_items_table} WHERE order_id = %d ORDER BY order_item_id FOR UPDATE", $order->get_id() ) );
		if ( $wpdb->last_error ) { throw new Exception( 'قفل موجودی سفارش برقرار نشد.' ); }
		// Read after obtaining the mutex so another request's committed ledger is authoritative.
		$order->read_meta_data( true );
		foreach ( $order->get_items() as $item ) { $item->read_meta_data( true ); }
		if ( $refund_id ) {
			$refund = wc_get_order( $refund_id );
			if ( ! $refund || (int) $refund->get_parent_id() !== $order->get_id() ) { throw new Exception( 'بازپرداخت معتبر نیست.' ); }
			$refund->read_meta_data( true );
			if ( $refund->get_meta( '_genosys_package_restocked', true ) ) { $wpdb->query( 'COMMIT' ); $transaction = false; return; }
		}
		$plans = array(); $demand = array();
		foreach ( $order->get_items() as $id => $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) { continue; }
			if ( 'refund' === $operation && ! isset( $refunded_lines[ $id ]['qty'] ) ) { continue; }
			$ledger = $item->get_meta( '_genosys_package_stock_ledger', true );
			if ( ! is_array( $ledger ) || ! isset( $ledger['pools'], $ledger['remaining'], $ledger['refunded'] ) ) { $ledger = array( 'version' => 1, 'pools' => genosys_package_line_pools( $item ), 'remaining' => 0, 'refunded' => 0 ); }
			$remaining = max( 0, (float) $ledger['remaining'] ); $refund_units = 0;
			if ( 'reduce' === $operation ) { $qty = max( 0, (float) $item->get_quantity() - (float) $ledger['refunded'] - $remaining ); }
			elseif ( 'refund' === $operation ) {
				$requested = is_numeric( $refunded_lines[ $id ]['qty'] ) ? max( 0.0, (float) $refunded_lines[ $id ]['qty'] ) : 0.0;
				if ( $item->get_meta( '_genosys_package_components', true ) && floor( $requested ) !== (float) $requested ) { throw new Exception( 'تعداد پکیج بازپرداخت‌شده باید عدد صحیح باشد.' ); }
				$refund_units = min( max( 0, (float) $item->get_quantity() - (float) $ledger['refunded'] ), $requested );
				$qty = min( $remaining, $refund_units );
			} else { $qty = $remaining; }
			if ( $qty <= 0 && $refund_units <= 0 ) { continue; }
			if ( $qty > 0 ) { genosys_package_add_demand( $demand, $ledger['pools'], $qty ); }
			$ledger['remaining'] = 'reduce' === $operation ? $remaining + $qty : $remaining - $qty;
			if ( 'refund' === $operation ) { $ledger['refunded'] += $refund_units; }
			$plans[] = array( 'item' => $item, 'ledger' => $ledger, 'quantity' => $qty );
		}
		$pool_ids = array_keys( $demand ); sort( $pool_ids, SORT_NUMERIC );
		$stocks = array();
		if ( $pool_ids ) {
			$placeholders = implode( ',', array_fill( 0, count( $pool_ids ), '%d' ) );
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_stock' AND post_id IN ($placeholders) ORDER BY post_id, meta_id FOR UPDATE", $pool_ids ) );
			foreach ( $rows as $row ) { if ( isset( $stocks[ $row->post_id ] ) || ! is_numeric( $row->meta_value ) ) { throw new Exception( 'اطلاعات موجودی یکی از محصولات معتبر نیست.' ); } $stocks[ $row->post_id ] = (float) $row->meta_value; }
			if ( count( $stocks ) !== count( $pool_ids ) ) { throw new Exception( 'موجودی یکی از محصولات سفارش دیگر قابل مدیریت نیست.' ); }
		}
		foreach ( $pool_ids as $id ) {
			wp_cache_delete( $id, 'post_meta' ); $pool = wc_get_product( $id );
			if ( ! $pool ) { throw new Exception( 'یکی از محصولات سفارش حذف شده است.' ); }
			$held_pool = clone $pool; $held_pool->set_manage_stock( true );
			if ( 'reduce' === $operation && $demand[ $id ]['strict'] > 0 && $stocks[ $id ] - genosys_package_current_held_stock( $held_pool, $order->get_id() ) < $demand[ $id ]['strict'] ) { throw new Exception( sprintf( 'موجودی «%s» برای این سفارش کافی نیست.', $pool->get_name() ) ); }
		}
		foreach ( $pool_ids as $id ) {
			$pool = wc_get_product( $id ); $amount = $demand[ $id ]['total']; $mode = 'reduce' === $operation ? 'decrease' : 'increase';
			$expected = $stocks[ $id ] + ( 'decrease' === $mode ? -$amount : $amount );
			do_action( $pool->is_type( 'variation' ) ? 'woocommerce_variation_before_set_stock' : 'woocommerce_product_before_set_stock', $pool );
			// Native arithmetic and lookup/cache updates, followed by a fresh read instead of its snapshot-based return value.
			$store = WC_Data_Store::load( 'product' ); $new_stock = $store->update_product_stock( $id, $amount, $mode );
			$actual_rows = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_stock' FOR UPDATE", $id ) );
			$actual = 1 === count( $actual_rows ) ? $actual_rows[0] : null;
			if ( is_wp_error( $new_stock ) || ! is_numeric( $actual ) || abs( (float) $actual - $expected ) > 0.000001 ) { throw new Exception( 'تغییر موجودی کامل نشد؛ هیچ موجودی‌ای از این سفارش تغییر نکرد.' ); }
			$store->read_stock_quantity( $pool, $actual );
			// Restore the frozen owner without changing its settings if management subsequently moved elsewhere.
			if ( $pool->managing_stock() && (int) $pool->get_stock_managed_by_id() === (int) $id ) { $pool->save(); }
			do_action( $pool->is_type( 'variation' ) ? 'woocommerce_variation_set_stock' : 'woocommerce_product_set_stock', $pool );
			$changes[] = array( 'product' => $pool, 'from' => $stocks[ $id ], 'to' => $expected );
		}
		foreach ( $plans as $plan ) {
			$item = $plan['item']; $ledger = $plan['ledger']; $item->update_meta_data( '_genosys_package_stock_ledger', $ledger );
			if ( $item->get_meta( '_genosys_package_components', true ) ) { $item->delete_meta_data( '_reduced_stock' ); }
			elseif ( $ledger['pools'] ) { $item->update_meta_data( '_reduced_stock', $ledger['remaining'] ); }
			if ( 'refund' === $operation ) { $item->update_meta_data( '_restock_refunded_items', $ledger['refunded'] ); }
			$item->save();
			$stored = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d AND meta_key = '_genosys_package_stock_ledger'", $item->get_id() ) );
			if ( maybe_unserialize( $stored ) !== $ledger ) { throw new Exception( 'ثبت سابقه موجودی سفارش انجام نشد.' ); }
		}
		if ( $refund ) { $refund->update_meta_data( '_genosys_package_restocked', true ); $refund->save(); $refund->read_meta_data( true ); if ( ! $refund->get_meta( '_genosys_package_restocked', true ) ) { throw new Exception( 'ثبت سابقه بازگردانی موجودی انجام نشد.' ); } }
		if ( $order->get_meta( '_genosys_package_stock_error', true ) ) {
			$order->delete_meta_data( '_genosys_package_stock_error' ); $order->save_meta_data(); $order->read_meta_data( true );
			if ( $order->get_meta( '_genosys_package_stock_error', true ) ) { throw new Exception( 'پاک‌کردن خطای موجودی سفارش انجام نشد.' ); }
		}
		if ( in_array( $operation, array( 'reduce', 'restore' ), true ) ) {
			$expected_flag = 'reduce' === $operation;
			$order->get_data_store()->set_stock_reduced( $order, $expected_flag );
			genosys_package_clear_order_cache( $order );
			if ( (bool) $order->get_data_store()->get_stock_reduced( $order->get_id() ) !== $expected_flag ) { throw new Exception( 'ثبت وضعیت موجودی سفارش انجام نشد.' ); }
		}
		if ( false === $wpdb->query( 'COMMIT' ) ) { throw new Exception( 'ثبت تغییر موجودی انجام نشد.' ); }
		$transaction = false;
	} catch ( Throwable $error ) {
		if ( $transaction ) { $wpdb->query( 'ROLLBACK' ); }
		genosys_package_clear_order_cache( $order );
		$fresh_order = wc_get_order( $order->get_id() );
		if ( $fresh_order ) { $order->set_order_stock_reduced( $fresh_order->get_order_stock_reduced() ); }
		$order->read_meta_data( true );
		foreach ( $order->get_items() as $item ) { $item->read_meta_data( true ); }
		// WC catches stock-hook exceptions during status changes; preserve an inspectable failure after rollback.
		$order->update_meta_data( '_genosys_package_stock_error', array( 'operation' => $operation, 'message' => $error->getMessage(), 'timestamp' => time() ) );
		$order->save_meta_data();
		$order->add_order_note( 'پردازش موجودی پکیج متوقف شد: ' . $error->getMessage() );
		throw $error;
	} finally {
		$genosys_package_stock_busy = false;
		foreach ( $pool_ids as $id ) {
			try { wp_cache_delete( $id, 'post_meta' ); wc_delete_product_transients( $id ); }
			catch ( Throwable $error ) { genosys_package_log_stock_warning( $order->get_id(), 'Stock cache refresh failed: ' . $error->getMessage() ); }
		}
		$queued = is_array( $genosys_package_stock_queue ) ? array_keys( $genosys_package_stock_queue ) : array(); $genosys_package_stock_queue = array();
		foreach ( array_unique( array_merge( $pool_ids, $queued ) ) as $id ) {
			try { genosys_package_component_changed( $id ); }
			catch ( Throwable $error ) { genosys_package_log_stock_warning( $order->get_id(), 'Package lookup refresh failed: ' . $error->getMessage() ); }
		}
	}
	if ( $changes ) {
		try {
			if ( 'reduce' === $operation ) { wc_trigger_stock_change_notifications( $order, $changes ); }
			else { $notes = array(); foreach ( $changes as $change ) { $notes[] = '#' . $change['product']->get_id() . ': ' . $change['from'] . ' → ' . $change['to']; } $order->add_order_note( 'موجودی اجزای سفارش بازگردانده شد: ' . implode( '، ', $notes ) ); }
		} catch ( Throwable $error ) { genosys_package_log_stock_warning( $order->get_id(), 'Stock notification failed after commit: ' . $error->getMessage() ); }
	}
}

add_filter( 'woocommerce_can_reduce_order_stock', function ( $allowed, $order ) {
	if ( ! $allowed || ! genosys_package_order_has_packages( $order ) ) { return $allowed; }
	genosys_package_order_stock( $order, 'reduce' ); return false;
}, 20, 2 );
add_filter( 'woocommerce_can_restore_order_stock', function ( $allowed, $order ) {
	if ( ! $allowed || ! genosys_package_order_has_packages( $order ) ) { return $allowed; }
	genosys_package_order_stock( $order, 'restore' ); return false;
}, 20, 2 );
add_filter( 'woocommerce_can_restock_refunded_items', function ( $allowed, $order ) {
	if ( ! genosys_package_order_has_packages( $order ) ) { return $allowed; }
	$GLOBALS['genosys_package_refund_restock_allowed'][ $order->get_id() ] = (bool) $allowed;
	return false;
}, PHP_INT_MAX, 2 );
add_action( 'woocommerce_refund_created', function ( $refund_id, $args ) {
	if ( empty( $args['restock_items'] ) || empty( $args['line_items'] ) ) { return; }
	$refund = wc_get_order( $refund_id ); $order = $refund ? wc_get_order( $refund->get_parent_id() ) : false;
	if ( ! genosys_package_order_has_packages( $order ) ) { return; }
	if ( isset( $GLOBALS['genosys_package_refund_restock_allowed'][ $order->get_id() ] ) && ! $GLOBALS['genosys_package_refund_restock_allowed'][ $order->get_id() ] ) { return; }
	try { genosys_package_order_stock( $order, 'refund', $refund_id, $args['line_items'] ); }
	catch ( Throwable $error ) { wc_get_logger()->error( 'Package refund inventory failed: ' . $error->getMessage(), array( 'source' => 'genosys-packages', 'order_id' => $order->get_id(), 'refund_id' => $refund_id ) ); }
}, 20, 2 );

/** Keep Woo's lookup status and dependency index current for normal catalog queries. */
function genosys_package_sync_product( $package ) {
	static $syncing = array();
	$package = $package instanceof WC_Product ? $package : wc_get_product( $package );
	if ( ! genosys_is_package_product( $package ) || isset( $syncing[ $package->get_id() ] ) ) { return; }
	$syncing[ $package->get_id() ] = true;
	try {
		$ids = array();
		foreach ( genosys_package_components( $package ) as $row ) {
			$ids[] = $row['product_id']; $component = wc_get_product( $row['product_id'] );
			if ( $component ) { $ids[] = $component->get_stock_managed_by_id(); if ( $component->is_type( 'variation' ) ) { $ids[] = $component->get_parent_id(); } }
		}
		$package->delete_meta_data( '_genosys_package_component_id' );
		foreach ( array_unique( array_filter( array_map( 'absint', $ids ) ) ) as $id ) { $package->add_meta_data( '_genosys_package_component_id', $id, false ); }
		$package->set_manage_stock( false ); $package->set_stock_quantity( null ); $package->set_backorders( 'no' ); $package->set_stock_status( 0 === genosys_package_stock( $package ) ? 'outofstock' : 'instock' ); $package->save();
	} finally { unset( $syncing[ $package->get_id() ] ); }
}

function genosys_package_sync_stock( $product_id ) { genosys_package_sync_product( $product_id ); }

function genosys_package_component_changed( $product_id ) {
	if ( ! function_exists( 'wc_get_product' ) ) { return; }
	global $wpdb, $genosys_package_stock_busy, $genosys_package_stock_queue;
	$id = $product_id instanceof WC_Product ? $product_id->get_id() : (int) $product_id;
	if ( ! $id ) { return; }
	if ( ! empty( $genosys_package_stock_busy ) ) { $genosys_package_stock_queue[ $id ] = true; return; }
	$product = wc_get_product( $id );
	if ( genosys_is_package_product( $product ) ) { genosys_package_sync_product( $product ); return; }
	$packages = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_genosys_package_component_id' AND meta_value = %s", (string) $id ) );
	foreach ( $packages as $package_id ) { genosys_package_sync_product( $package_id ); }
}
add_action( 'woocommerce_product_set_stock', 'genosys_package_component_changed' );
add_action( 'woocommerce_variation_set_stock', 'genosys_package_component_changed' );
add_action( 'woocommerce_product_set_stock_status', 'genosys_package_component_changed' );
add_action( 'woocommerce_variation_set_stock_status', 'genosys_package_component_changed' );
add_action( 'woocommerce_new_product', 'genosys_package_component_changed' );
add_action( 'woocommerce_update_product', 'genosys_package_component_changed' );
add_action( 'woocommerce_update_product_variation', 'genosys_package_component_changed' );
add_action( 'transition_post_status', function ( $new, $old, $post ) { if ( $new !== $old && in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) { genosys_package_component_changed( $post->ID ); } }, 20, 3 );
add_action( 'deleted_post', function ( $id, $post ) { if ( $post && in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) { genosys_package_component_changed( $id ); } }, 20, 2 );
