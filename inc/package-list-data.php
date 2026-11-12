<?php
/** Live package-list data and permission-protected, reversible administrative actions. */
defined( 'ABSPATH' ) || exit;

function genosys_package_list_state( $source = null, $unslash = true ) {
	$source = is_array( $source ) ? $source : $_GET;
	$read = static function ( $key ) use ( $source, $unslash ) { return isset( $source[ $key ] ) && is_string( $source[ $key ] ) ? ( $unslash ? wp_unslash( $source[ $key ] ) : $source[ $key ] ) : ''; };
	$status = sanitize_key( $read( 'gpl_status' ) ); $order = sanitize_key( $read( 'gpl_order' ) );
	$search = sanitize_text_field( $read( 'gpl_search' ) );
	$search = function_exists( 'mb_substr' ) ? mb_substr( $search, 0, 160, 'UTF-8' ) : wp_html_excerpt( $search, 160, '' );
	$page = $read( 'gpl_page' );
	return array(
		'status' => in_array( $status, array( 'all', 'published', 'draft', 'pending', 'outofstock', 'trash' ), true ) ? $status : 'all',
		'search' => $search, 'order' => in_array( $order, array( 'latest', 'oldest', 'price_asc', 'price_desc', 'name' ), true ) ? $order : 'latest',
		'page' => preg_match( '/^[0-9]{1,9}$/D', $page ) ? max( 1, (int) $page ) : 1,
	);
}

function genosys_package_list_url( $changes = array() ) {
	$state = genosys_package_list_state();
	$args = array( 'page' => 'genosys-package-list', 'gpl_status' => $state['status'], 'gpl_search' => $state['search'], 'gpl_order' => $state['order'], 'gpl_page' => $state['page'] );
	foreach ( array( 'gpl_status', 'gpl_search', 'gpl_order', 'gpl_page', 'gpl_notice', 'gpl_message' ) as $key ) { if ( array_key_exists( $key, $changes ) ) { $args[ $key ] = $changes[ $key ]; } }
	$normalized = genosys_package_list_state( array_map( 'strval', array_intersect_key( $args, array_flip( array( 'gpl_status', 'gpl_search', 'gpl_order', 'gpl_page' ) ) ) ), false );
	foreach ( array( 'status', 'search', 'order', 'page' ) as $key ) { $args[ 'gpl_' . $key ] = $normalized[ $key ]; }
	// WordPress expects new argument values to be encoded before add_query_arg().
	$args = array_map( static function ( $value ) { return rawurlencode( (string) $value ); }, $args );
	return add_query_arg( $args, admin_url( 'admin.php' ) );
}

function genosys_package_list_normalize( $text ) {
	$map = function_exists( 'genosys_product_search_character_map' ) ? genosys_product_search_character_map() : array( 'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', '‌' => ' ', '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9' );
	$text = strtr( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' ), $map );
	$text = preg_replace( '/[\x{064B}-\x{065F}\x{0670}ـ]/u', '', $text );
	$text = trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
}

function genosys_package_list_can_read( $id ) {
	$post = get_post( $id );
	if ( ! $post || ! in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) { return false; }
	$status = 'trash' === $post->post_status ? get_post_meta( $id, '_wp_trash_meta_status', true ) : $post->post_status;
	if ( 'private' === $status && (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'read_private_products' ) && ! current_user_can( 'edit_private_products' ) ) { return false; }
	return current_user_can( 'read_post', $id ) || current_user_can( 'edit_post', $id );
}

function genosys_package_list_search_text( $product ) {
	$text = array( $product->get_name(), $product->get_sku(), (string) $product->get_id(), '#' . $product->get_id() );
	foreach ( genosys_package_components( $product ) as $row ) {
		$component = wc_get_product( $row['product_id'] );
		if ( ! $component || ! genosys_package_list_can_read( $component->get_id() ) ) { continue; }
		$parent = $component->is_type( 'variation' ) ? wc_get_product( $component->get_parent_id() ) : null;
		if ( $parent && ! genosys_package_list_can_read( $parent->get_id() ) ) { continue; }
		$text[] = $component->get_name(); $text[] = $component->get_sku();
		if ( $component->is_type( 'variation' ) ) {
			$text[] = wc_get_formatted_variation( $component, true, true, false );
			if ( $parent && genosys_package_list_can_read( $parent->get_id() ) ) { $text[] = $parent->get_name(); $text[] = $parent->get_sku(); }
		}
	}
	return genosys_package_list_normalize( implode( ' ', $text ) );
}

/** Counts are permission-scoped live totals; status/search affect the six-item result page. */
function genosys_package_list_context() {
	$state = genosys_package_list_state(); $counts = array_fill_keys( array( 'all', 'published', 'draft', 'pending', 'outofstock', 'trash' ), 0 );
	$result = array_merge( $state, array( 'per_page' => 6, 'products' => array(), 'filtered_total' => 0, 'total' => 0, 'pages' => 1, 'counts' => $counts, 'stats' => array( 'all' => 0, 'published' => 0, 'low' => 0, 'outofstock' => 0 ) ) );
	if ( ! function_exists( 'wc_get_product' ) || ! current_user_can( 'manage_woocommerce' ) ) { return $result; }
	global $wpdb;
	// An administrative inventory read must not inherit the storefront's package-category visibility filters.
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->term_relationships} rel ON rel.object_id = p.ID INNER JOIN {$wpdb->term_taxonomy} tax ON tax.term_taxonomy_id = rel.term_taxonomy_id INNER JOIN {$wpdb->terms} term ON term.term_id = tax.term_id WHERE p.post_type = %s AND p.post_status IN ('publish','private','draft','auto-draft','pending','trash') AND tax.taxonomy = %s AND term.slug = %s ORDER BY p.ID DESC", 'product', 'product_type', 'genosys_package' ) );
	$terms = preg_split( '/\s+/u', genosys_package_list_normalize( $state['search'] ), -1, PREG_SPLIT_NO_EMPTY );
	$threshold = max( 0, (float) get_option( 'woocommerce_notify_low_stock_amount', 2 ) ); $matches = array();
	foreach ( $ids as $id ) {
		if ( ! genosys_package_list_can_read( $id ) ) { continue; }
		$product = wc_get_product( $id );
		if ( ! genosys_is_package_product( $product ) ) { continue; }
		$status = $product->get_status(); $published = in_array( $status, array( 'publish', 'private' ), true );
		$stock = $published ? genosys_package_stock( $product ) : null;
		$groups = array();
		if ( 'trash' === $status ) { $groups[] = 'trash'; }
		else { $groups[] = 'all'; if ( $published ) { $groups[] = 'published'; if ( 0 === $stock ) { $groups[] = 'outofstock'; } elseif ( null !== $stock && $stock <= $threshold ) { ++$result['stats']['low']; } } elseif ( in_array( $status, array( 'draft', 'auto-draft' ), true ) ) { $groups[] = 'draft'; } elseif ( 'pending' === $status ) { $groups[] = 'pending'; } }
		foreach ( $groups as $group ) { ++$result['counts'][ $group ]; }
		if ( ! in_array( $state['status'], $groups, true ) ) { continue; }
		if ( $terms ) {
			$haystack = genosys_package_list_search_text( $product ); $found = true;
			foreach ( $terms as $term ) { if ( false === strpos( $haystack, $term ) ) { $found = false; break; } }
			if ( ! $found ) { continue; }
		}
		$matches[] = $product;
	}
	usort( $matches, static function ( $a, $b ) use ( $state ) {
		if ( in_array( $state['order'], array( 'price_asc', 'price_desc' ), true ) ) {
			$ap = $a->get_price( 'edit' ); $bp = $b->get_price( 'edit' );
			if ( '' === $ap || '' === $bp ) { $compare = ( '' === $ap ? 1 : 0 ) <=> ( '' === $bp ? 1 : 0 ); }
			else { $compare = (float) genosys_package_display_price( $ap, $a ) <=> (float) genosys_package_display_price( $bp, $b ); if ( 'price_desc' === $state['order'] ) { $compare *= -1; } }
		} elseif ( 'name' === $state['order'] ) { $compare = strcmp( genosys_package_list_normalize( $a->get_name() ), genosys_package_list_normalize( $b->get_name() ) ); }
		else { $ad = $a->get_date_created(); $bd = $b->get_date_created(); $compare = ( $ad ? $ad->getTimestamp() : 0 ) <=> ( $bd ? $bd->getTimestamp() : 0 ); if ( 'latest' === $state['order'] ) { $compare *= -1; } }
		return $compare ?: ( $a->get_id() <=> $b->get_id() ) * ( 'latest' === $state['order'] ? -1 : 1 );
	} );
	$result['total'] = $result['counts']['all']; $result['filtered_total'] = count( $matches );
	$result['pages'] = max( 1, (int) ceil( $result['filtered_total'] / $result['per_page'] ) ); $result['page'] = min( $state['page'], $result['pages'] );
	$result['products'] = array_slice( $matches, ( $result['page'] - 1 ) * $result['per_page'], $result['per_page'] );
	$result['stats']['all'] = $result['total']; $result['stats']['published'] = $result['counts']['published']; $result['stats']['outofstock'] = $result['counts']['outofstock'];
	return $result;
}

function genosys_package_list_canonical_id( $value ) {
	if ( ! is_string( $value ) && ! is_int( $value ) ) { return 0; }
	$value = (string) $value;
	return preg_match( '/^[1-9][0-9]*$/D', $value ) && (string) (int) $value === $value ? (int) $value : 0;
}

/** Native duplication, with request/SKU metadata cleared before the new draft's first save. */
function genosys_package_list_duplicate( $product ) {
	if ( ! class_exists( 'WC_Admin_Duplicate_Product' ) ) { require_once WC_ABSPATH . 'includes/admin/class-wc-admin-duplicate-product.php'; }
	$prepare = static function ( $duplicate, $original ) use ( $product ) {
		if ( $original->get_id() !== $product->get_id() || ! genosys_is_package_product( $duplicate ) ) { return; }
		$duplicate->set_sku( '' ); $duplicate->set_status( 'draft' );
		foreach ( array( '_genosys_package_request_token', '_genosys_package_request_user', '_wp_trash_meta_status', '_wp_trash_meta_time' ) as $key ) { $duplicate->delete_meta_data( $key ); }
		$term = genosys_package_special_category( true );
		if ( ! $term || is_wp_error( $term ) ) { throw new Exception( 'دسته‌بندی پکیج‌های ویژه در دسترس نیست.' ); }
		$duplicate->set_category_ids( array( (int) $term->term_id ) );
	};
	add_action( 'woocommerce_product_duplicate_before_save', $prepare, 100, 2 );
	try { $duplicate = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product ); }
	finally { remove_action( 'woocommerce_product_duplicate_before_save', $prepare, 100 ); }
	if ( ! $duplicate || ! $duplicate->get_id() || ! genosys_is_package_product( $duplicate ) ) { throw new Exception( 'ساخت نسخه پیش‌نویس انجام نشد.' ); }
	genosys_package_sync_stock( $duplicate->get_id() ); do_action( 'woocommerce_product_duplicate', $duplicate, $product );
	return $duplicate;
}

/** EMPTY_TRASH_DAYS=0 must never turn our reversible action into a permanent deletion. */
function genosys_package_list_trash( $id ) {
	if ( 'trash' === get_post_status( $id ) ) { return true; }
	if ( ! defined( 'EMPTY_TRASH_DAYS' ) || EMPTY_TRASH_DAYS > 0 ) { $result = wp_trash_post( $id ); return $result && 'trash' === get_post_status( $id ); }
	$post = get_post( $id ); if ( ! $post ) { return false; } $previous = $post->post_status;
	$pre = apply_filters( 'pre_trash_post', null, $post, $previous ); if ( null !== $pre ) { return $pre && 'trash' === get_post_status( $id ); }
	do_action( 'wp_trash_post', $id, $previous ); update_post_meta( $id, '_wp_trash_meta_status', $previous );
	// No expiry metadata: zero-day cron retention must not immediately purge this reversible package.
	delete_post_meta( $id, '_wp_trash_meta_time' );
	$result = wp_update_post( array( 'ID' => $id, 'post_status' => 'trash' ), true );
	if ( is_wp_error( $result ) || ! $result ) { delete_post_meta( $id, '_wp_trash_meta_status' ); return false; }
	do_action( 'trashed_post', $id, $previous ); return true;
}

function genosys_package_list_restore( $id ) {
	if ( 'trash' !== get_post_status( $id ) ) { return false; }
	$previous = get_post_meta( $id, '_wp_trash_meta_status', true );
	if ( ! in_array( $previous, array( 'publish', 'private', 'draft', 'auto-draft', 'pending' ), true ) ) { $previous = 'draft'; }
	if ( in_array( $previous, array( 'publish', 'private' ), true ) && ! current_user_can( 'publish_products' ) ) { throw new Exception( 'اجازه بازگردانی پکیج منتشرشده را ندارید.' ); }
	$restore_status = static function ( $status, $post_id ) use ( $id, $previous ) { return (int) $post_id === $id ? $previous : $status; };
	add_filter( 'wp_untrash_post_status', $restore_status, 100, 2 );
	try { $result = wp_untrash_post( $id ); return $result && $previous === get_post_status( $id ); }
	finally { remove_filter( 'wp_untrash_post_status', $restore_status, 100 ); }
}

function genosys_package_list_action() {
	$state = genosys_package_list_state( $_POST ); $notice = 'error'; $message = ''; $transaction = false; $affected = array();
	try {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! function_exists( 'wc_get_product' ) || ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_products' ) ) { throw new Exception( 'اجازه انجام این عملیات را ندارید.' ); }
		$operation = isset( $_POST['operation'] ) && is_string( $_POST['operation'] ) ? wp_unslash( $_POST['operation'] ) : '';
		if ( ! in_array( $operation, array( 'duplicate', 'trash', 'restore', 'bulk_trash' ), true ) ) { throw new Exception( 'عملیات انتخاب‌شده معتبر نیست.' ); }
		$id = 'bulk_trash' === $operation ? 0 : genosys_package_list_canonical_id( $_POST['package_id'] ?? null );
		if ( 'bulk_trash' !== $operation && ! $id ) { throw new Exception( 'شناسه پکیج معتبر نیست.' ); }
		$nonce = isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] ) ? wp_unslash( $_POST['_wpnonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'genosys_package_list_action_' . $operation . '_' . $id ) ) { throw new Exception( 'درخواست منقضی یا نامعتبر است؛ صفحه را تازه کنید.' ); }
		$ids = array( $id );
		if ( 'bulk_trash' === $operation ) {
			if ( ! isset( $_POST['ids'] ) || ! is_array( $_POST['ids'] ) || ! $_POST['ids'] || count( $_POST['ids'] ) > 200 ) { throw new Exception( 'پکیج‌های انتخاب‌شده معتبر نیستند.' ); }
			$ids = array(); foreach ( $_POST['ids'] as $value ) { $parsed = genosys_package_list_canonical_id( $value ); if ( ! $parsed ) { throw new Exception( 'یکی از شناسه‌ها معتبر نیست.' ); } $ids[] = $parsed; } $ids = array_values( array_unique( $ids ) );
		}
		$products = array();
		foreach ( $ids as $package_id ) {
			$product = wc_get_product( $package_id );
			if ( ! genosys_is_package_product( $product ) || ! genosys_package_list_can_read( $package_id ) || ! current_user_can( 'edit_post', $package_id ) || ( 'duplicate' !== $operation && ! current_user_can( 'delete_post', $package_id ) ) ) { throw new Exception( 'اجازه تغییر یکی از پکیج‌ها را ندارید.' ); }
			$products[] = $product;
		}
		global $wpdb;
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { throw new Exception( 'عملیات آغاز نشد.' ); } $transaction = true;
		foreach ( $products as $product ) {
			$affected[] = $product->get_id();
			if ( 'duplicate' === $operation ) { $copy = genosys_package_list_duplicate( $product ); $affected[] = $copy->get_id(); }
			elseif ( 'restore' === $operation ) { if ( ! genosys_package_list_restore( $product->get_id() ) ) { throw new Exception( 'بازگردانی پکیج انجام نشد.' ); } genosys_package_sync_stock( $product->get_id() ); }
			elseif ( ! genosys_package_list_trash( $product->get_id() ) ) { throw new Exception( 'انتقال پکیج به زباله‌دان انجام نشد.' ); }
		}
		if ( false === $wpdb->query( 'COMMIT' ) ) { throw new Exception( 'ثبت عملیات انجام نشد.' ); } $transaction = false;
		$notice = array( 'duplicate' => 'duplicated', 'trash' => 'trashed', 'restore' => 'restored', 'bulk_trash' => 'bulk_trashed' )[ $operation ];
	} catch ( Throwable $error ) {
		if ( $transaction ) { $wpdb->query( 'ROLLBACK' ); }
		foreach ( $affected as $package_id ) { clean_post_cache( $package_id ); if ( function_exists( 'wc_delete_product_transients' ) ) { wc_delete_product_transients( $package_id ); } }
		$message = sanitize_text_field( $error->getMessage() ); $message = function_exists( 'mb_substr' ) ? mb_substr( $message, 0, 200, 'UTF-8' ) : wp_html_excerpt( $message, 200, '' );
	}
	$args = array( 'gpl_status' => $state['status'], 'gpl_search' => $state['search'], 'gpl_order' => $state['order'], 'gpl_page' => $state['page'], 'gpl_notice' => $notice );
	if ( $message ) { $args['gpl_message'] = $message; }
	wp_safe_redirect( genosys_package_list_url( $args ), 303 ); exit;
}
add_action( 'admin_post_genosys_package_list_action', 'genosys_package_list_action' );
add_action( 'admin_post_nopriv_genosys_package_list_action', 'genosys_package_list_action' );
