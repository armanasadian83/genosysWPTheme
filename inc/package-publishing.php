<?php
/** Capability-checked native WooCommerce package publishing and editing. */
defined( 'ABSPATH' ) || exit;

/** Admin prices use the store's base rates, never an administrator's customer/VAT session. */
function genosys_package_price_basis( $price, $product, $to_display ) {
	$amount = (float) $price; $display_includes_tax = 'incl' === get_option( 'woocommerce_tax_display_shop' );
	$from_includes_tax = $to_display ? wc_prices_include_tax() : $display_includes_tax;
	$to_includes_tax = $to_display ? $display_includes_tax : wc_prices_include_tax();
	if ( wc_tax_enabled() && $product->is_taxable() && $from_includes_tax !== $to_includes_tax ) {
		$rates = WC_Tax::get_base_tax_rates( $product->get_tax_class() );
		$taxes = WC_Tax::calc_tax( $amount, $rates, $from_includes_tax );
		$tax = $to_display && $to_includes_tax && 'yes' !== get_option( 'woocommerce_tax_round_at_subtotal' ) ? array_sum( array_map( 'wc_round_tax_total', $taxes ) ) : array_sum( $taxes );
		$amount += $to_includes_tax ? $tax : -$tax;
	}
	return wc_format_decimal( max( 0, $amount ), $to_display ? wc_get_price_decimals() : wc_get_rounding_precision() );
}
function genosys_package_entered_price( $price, $product ) { return genosys_package_price_basis( $price, $product, false ); }
function genosys_package_display_price( $price, $product ) { return genosys_package_price_basis( $price, $product, true ); }

function genosys_package_builder_data( $product ) {
	$items = array();
	foreach ( genosys_package_components( $product ) as $component ) {
		$payload = genosys_package_catalog_payload( wc_get_product( $component['product_id'] ) );
		if ( ! $payload ) {
			$payload = array( 'id' => $component['product_id'], 'name' => 'محصول حذف‌شده یا غیرفعال', 'sku' => '', 'price_minor' => null, 'image' => '', 'stock_quantity' => 0, 'stock_pool_id' => $component['product_id'], 'stock_status' => 'outofstock', 'stock_state' => 'outofstock', 'stock_label' => 'ناموجود' );
		}
		$items[] = array( 'product' => $payload, 'quantity' => $component['quantity'] );
	}
	$visibility = 'private' === $product->get_status() ? 'private' : ( 'hidden' === $product->get_catalog_visibility() ? 'hidden' : ( in_array( $product->get_status(), array( 'draft', 'pending' ), true ) && 'private' === $product->get_meta( '_genosys_package_visibility', true ) ? 'private' : 'public' ) );
	return array(
		'id' => $product->get_id(), 'name' => $product->get_name(), 'description' => $product->get_description(),
		'items' => $items, 'image' => array( 'id' => $product->get_image_id(), 'url' => $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'large' ) : '' ),
		'price' => '' === $product->get_price( 'edit' ) ? '' : genosys_package_display_price( $product->get_price( 'edit' ), $product ), 'sku' => $product->get_sku(),
		'status' => 'private' === $product->get_status() ? 'publish' : $product->get_status(),
		'visibility' => $visibility, 'permalink' => $product->get_permalink(),
	);
}

function genosys_package_save_response( $product, $replayed = false ) {
	return array( 'package_id' => $product->get_id(), 'permalink' => $product->get_permalink(), 'edit_url' => admin_url( 'admin.php?page=genosys-add-package&package_id=' . $product->get_id() ),
		'message' => $replayed ? 'این پکیج قبلاً ذخیره شده است.' : ( in_array( $product->get_status(), array( 'publish', 'private' ), true ) ? 'پکیج با موفقیت منتشر شد.' : 'پکیج با موفقیت ذخیره شد.' ),
		'replayed' => $replayed, 'package' => genosys_package_builder_data( $product ) );
}

function genosys_package_save_ajax() {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_send_json_error( array( 'message' => 'درخواست نامعتبر است.' ), 405 ); }
	if ( ! function_exists( 'wc_get_product' ) || ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_products' ) ) { wp_send_json_error( array( 'message' => 'اجازه ذخیره پکیج را ندارید.' ), 403 ); }
	if ( ! check_ajax_referer( 'genosys_package_save', 'nonce', false ) ) { wp_send_json_error( array( 'message' => 'صفحه را تازه کنید و دوباره تلاش کنید.' ), 403 ); }
	$string = static function ( $key ) { return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; };
	foreach ( array( 'package_id', 'image_id' ) as $key ) {
		$value = $string( $key );
		if ( ! preg_match( '/^(?:0|[1-9][0-9]*)$/D', $value ) || (string) (int) $value !== $value ) { wp_send_json_error( array( 'message' => 'شناسه پکیج یا تصویر معتبر نیست.' ), 400 ); }
	}
	$id = absint( $string( 'package_id' ) );
	$token = $string( 'request_token' );
	if ( ! preg_match( '/^[a-zA-Z0-9-]{16,100}$/D', $token ) ) { wp_send_json_error( array( 'message' => 'شناسه درخواست معتبر نیست؛ صفحه را تازه کنید.' ), 400 ); }
	$product = $id ? wc_get_product( $id ) : null;
	if ( $id && ( ! $product || ! genosys_is_package_product( $product ) || ! current_user_can( 'edit_post', $id ) ) ) { wp_send_json_error( array( 'message' => 'پکیج برای ویرایش در دسترس نیست.' ), 403 ); }
	$lock = 'genosys_pkg_save_' . hash( 'sha256', $id ? 'edit:' . $id : 'create:' . get_current_user_id() . ':' . $token );
	$locked = add_option( $lock, time(), '', false );
	if ( ! $locked ) {
		$previous = get_option( $lock );
		if ( is_numeric( $previous ) && (int) $previous < time() - 180 ) {
			global $wpdb;
			if ( $wpdb->delete( $wpdb->options, array( 'option_name' => $lock, 'option_value' => (string) $previous ) ) ) {
				wp_cache_delete( $lock, 'options' ); wp_cache_delete( 'notoptions', 'options' );
				$locked = add_option( $lock, time(), '', false );
			}
		}
	}
	if ( ! $locked ) { wp_send_json_error( array( 'message' => 'ذخیره همین پکیج در حال انجام است؛ کمی بعد دوباره تلاش کنید.' ), 409 ); }
	$transaction = false;
	try {
		if ( ! $id ) {
			$existing = get_posts( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'private', 'draft', 'pending' ), 'numberposts' => 1, 'fields' => 'ids', 'meta_query' => array( 'relation' => 'AND', array( 'key' => '_genosys_package_request_token', 'value' => $token ), array( 'key' => '_genosys_package_request_user', 'value' => get_current_user_id() ) ) ) );
			if ( $existing ) {
				$recovered = wc_get_product( reset( $existing ) );
				if ( ! $recovered || ! genosys_is_package_product( $recovered ) || ! current_user_can( 'edit_post', $recovered->get_id() ) ) { throw new Exception( 'پکیج برای ویرایش در دسترس نیست.' ); }
				$response = genosys_package_save_response( $recovered, true );
				delete_option( $lock ); wp_send_json_success( $response );
			}
		}
		$name = sanitize_text_field( $string( 'name' ) );
		if ( '' === trim( $name ) || strlen( $name ) > 800 || count( preg_split( '//u', $name, -1, PREG_SPLIT_NO_EMPTY ) ) > 200 ) { throw new Exception( 'نام پکیج را وارد کنید (حداکثر ۲۰۰ نویسه).' ); }
		$status = $string( 'status' ); $visibility = $string( 'visibility' );
		if ( ! in_array( $status, array( 'draft', 'pending', 'publish' ), true ) || ! in_array( $visibility, array( 'public', 'private', 'hidden' ), true ) ) { throw new Exception( 'وضعیت یا نمایان بودن پکیج معتبر نیست.' ); }
		if ( 'publish' === $status && ! current_user_can( 'publish_products' ) ) { throw new Exception( 'اجازه انتشار محصول را ندارید.' ); }
		if ( $id && in_array( $product->get_status(), array( 'publish', 'private' ), true ) && ! current_user_can( 'edit_published_products' ) ) { throw new Exception( 'اجازه ویرایش پکیج منتشرشده را ندارید.' ); }
		$submitted = json_decode( $string( 'items' ), true );
		if ( ! is_array( $submitted ) || count( $submitted ) > 24 ) { throw new Exception( 'فهرست محصولات پکیج معتبر نیست.' ); }
		$items = array(); $seen = array(); $regular = 0;
		foreach ( $submitted as $item ) {
			if ( ! is_array( $item ) || ! isset( $item['product_id'], $item['quantity'] ) || ! is_numeric( $item['product_id'] ) || (float) $item['product_id'] !== (float) (int) $item['product_id'] || (int) $item['product_id'] < 1 || ! is_numeric( $item['quantity'] ) || (float) $item['quantity'] !== (float) (int) $item['quantity'] || (int) $item['quantity'] < 1 || (int) $item['quantity'] > 9999 ) { throw new Exception( 'تعداد یا محصول انتخاب‌شده معتبر نیست.' ); }
			$component = wc_get_product( absint( $item['product_id'] ) );
			if ( ! genosys_package_component_is_eligible( $component ) || isset( $seen[ $component->get_id() ] ) ) { throw new Exception( 'محصولات باید معتبر و متفاوت باشند؛ پکیج دیگری را نمی‌توان در پکیج قرار داد.' ); }
			$seen[ $component->get_id() ] = true; $items[] = array( 'product_id' => $component->get_id(), 'quantity' => (int) $item['quantity'] );
			if ( '' === $component->get_price() && 'draft' !== $status ) { throw new Exception( 'یکی از محصولات قیمت ندارد؛ ابتدا قیمت آن را در ووکامرس تعیین کنید.' ); }
			$regular += (float) genosys_package_display_price( $component->get_price( 'edit' ), $component ) * (int) $item['quantity'];
		}
		$image_id = absint( $string( 'image_id' ) );
		if ( $image_id && ( ! current_user_can( 'upload_files' ) || 'attachment' !== get_post_type( $image_id ) || ! current_user_can( 'read_post', $image_id ) || ! in_array( get_post_mime_type( $image_id ), array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) ) { throw new Exception( 'تصویر انتخاب‌شده از رسانه معتبر نیست.' ); }
		if ( $image_id ) {
			$metadata = wp_get_attachment_metadata( $image_id ); $file = get_attached_file( $image_id );
			$size = isset( $metadata['filesize'] ) ? (int) $metadata['filesize'] : ( $file && is_file( $file ) ? filesize( $file ) : 0 );
			if ( $size > 5 * MB_IN_BYTES ) { throw new Exception( 'حجم تصویر باید حداکثر ۵ مگابایت باشد.' ); }
		}
		$raw_price = genosys_shop_ascii_digits( trim( $string( 'price' ) ) );
		if ( '' !== $raw_price && ( ! preg_match( '/^\d{1,15}(?:\.\d{1,6})?$/D', $raw_price ) || ! is_finite( (float) $raw_price ) ) ) { throw new Exception( 'قیمت پکیج معتبر نیست.' ); }
		$price = '' === $raw_price ? '' : wc_format_decimal( $raw_price, wc_get_price_decimals() );
		if ( 'draft' !== $status && ( count( $items ) < 2 || ! $image_id || '' === $price || (float) $price <= 0 ) ) { throw new Exception( 'برای انتشار، نام، حداقل دو محصول متفاوت، تصویر و قیمت پکیج را کامل کنید.' ); }
		$sku = sanitize_text_field( $string( 'sku' ) );
		if ( strlen( $sku ) > 100 || ( '' !== $sku && ! wc_product_has_unique_sku( $id, $sku ) ) ) { throw new Exception( 'شناسه SKU تکراری یا نامعتبر است.' ); }
		$term = genosys_package_special_category( true );
		if ( ! $term || is_wp_error( $term ) ) { throw new Exception( 'دسته‌بندی پکیج‌های ویژه در دسترس نیست.' ); }
		if ( ! $product ) { $product = new WC_Product_Genosys_Package(); }
		$product->set_name( $name );
		$product->set_description( wp_kses_post( $string( 'description' ) ) );
		$product->set_short_description( wp_trim_words( wp_strip_all_tags( $string( 'description' ) ), 40, '…' ) );
		$product->set_status( 'publish' === $status && 'private' === $visibility ? 'private' : $status );
		$product->set_catalog_visibility( 'hidden' === $visibility ? 'hidden' : 'visible' );
		$product->update_meta_data( '_genosys_package_visibility', $visibility );
		$product->set_category_ids( array( (int) $term->term_id ) );
		$product->set_image_id( $image_id ); $product->set_sku( $sku );
		$product->set_manage_stock( false ); $product->set_backorders( 'no' );
		$product->set_virtual( false ); $product->set_downloadable( false );
		$entered_price = '' === $price ? '' : genosys_package_entered_price( $price, $product );
		$entered_regular = '' === $price ? '' : genosys_package_entered_price( max( $regular, (float) $price ), $product );
		$product->set_regular_price( $entered_regular );
		$product->set_sale_price( '' !== $entered_price && (float) $entered_regular > (float) $entered_price ? $entered_price : '' );
		$product->set_price( $entered_price );
		$product->update_meta_data( '_genosys_package_items', $items );
		$product->delete_meta_data( '_genosys_package_component_id' );
		foreach ( $items as $item ) {
			$component = wc_get_product( $item['product_id'] );
			foreach ( array_unique( array_filter( array( $item['product_id'], $component->get_parent_id(), $component->get_stock_managed_by_id() ) ) ) as $dependency ) { $product->add_meta_data( '_genosys_package_component_id', (int) $dependency ); }
		}
		if ( ! $id ) { $product->update_meta_data( '_genosys_package_request_token', $token ); $product->update_meta_data( '_genosys_package_request_user', get_current_user_id() ); }
		global $wpdb;
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { throw new Exception( 'ذخیره پکیج آغاز نشد؛ دوباره تلاش کنید.' ); }
		$transaction = true;
		$product->save();
		if ( function_exists( 'genosys_package_sync_stock' ) ) { genosys_package_sync_stock( $product->get_id() ); }
		if ( false === $wpdb->query( 'COMMIT' ) ) { throw new Exception( 'ذخیره پکیج کامل نشد؛ دوباره تلاش کنید.' ); }
		$transaction = false;
		$response = genosys_package_save_response( wc_get_product( $product->get_id() ) );
		delete_option( $lock ); wp_send_json_success( $response );
	} catch ( Throwable $error ) {
		if ( $transaction ) { $wpdb->query( 'ROLLBACK' ); if ( $product && $product->get_id() ) { clean_post_cache( $product->get_id() ); wc_delete_product_transients( $product->get_id() ); } }
		delete_option( $lock ); wp_send_json_error( array( 'message' => wp_strip_all_tags( $error->getMessage() ) ), 400 );
	}
}
add_action( 'wp_ajax_genosys_package_save', 'genosys_package_save_ajax' );
add_action( 'wp_ajax_nopriv_genosys_package_save', 'genosys_package_save_ajax' );
