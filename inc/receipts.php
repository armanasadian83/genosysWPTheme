<?php
/**
 * Private, order-linked payment receipts. WooCommerce remains responsible for payment.
 *
 * @package Genosys
 */

defined( 'ABSPATH' ) || exit;

/** Receipts have no public URLs, REST route, or default editing screen. */
function genosys_receipt_register_post_type() {
	register_post_type( 'genosys_receipt', array(
		'labels'              => array( 'name' => 'فیش های واریزی', 'singular_name' => 'فیش واریزی' ),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => false,
		'show_in_menu'        => false,
		'show_in_rest'        => false,
		'rewrite'             => false,
		'query_var'           => false,
		'supports'            => false,
		'capability_type'     => 'genosys_receipt',
		'map_meta_cap'        => false,
		// Only the dedicated, explicitly authorized UI/endpoints may access receipts.
		'capabilities'        => array(
			'edit_post'              => 'do_not_allow',
			'read_post'              => 'do_not_allow',
			'delete_post'            => 'do_not_allow',
			'edit_posts'             => 'do_not_allow',
			'edit_others_posts'       => 'do_not_allow',
			'publish_posts'          => 'do_not_allow',
			'read_private_posts'     => 'do_not_allow',
			'delete_posts'           => 'do_not_allow',
			'delete_private_posts'   => 'do_not_allow',
			'delete_published_posts' => 'do_not_allow',
			'delete_others_posts'    => 'do_not_allow',
			'edit_private_posts'     => 'do_not_allow',
			'edit_published_posts'   => 'do_not_allow',
			'create_posts'           => 'do_not_allow',
		),
	) );
}
add_action( 'init', 'genosys_receipt_register_post_type' );

/** Administrators and WooCommerce shop managers can view the private receipts. */
function genosys_receipt_can_manage() {
	return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
}

/** Receipt review is separate from WooCommerce payment and order status. */
function genosys_receipt_status_labels() {
	return array(
		'pending'  => 'در حال بررسی',
		'approved' => 'تایید شده',
		'rejected' => 'رد شده',
	);
}

/** Existing receipts without review metadata start in the review queue. */
function genosys_receipt_review_status( $receipt_id ) {
	$status = get_post_meta( absint( $receipt_id ), '_genosys_receipt_review_status', true );
	return is_string( $status ) && array_key_exists( $status, genosys_receipt_status_labels() ) ? $status : 'pending';
}

/** Review the current receipt under the same lock used by upload and replacement. */
function genosys_receipt_update_review_status( $receipt_id, $order_id, $status, $previous_status ) {
	if ( ! genosys_receipt_can_manage() ) {
		return new WP_Error( 'receipt_forbidden', 'شما اجازه تغییر وضعیت فیش را ندارید.' );
	}
	$labels = genosys_receipt_status_labels();
	if ( ! is_string( $status ) || ! is_string( $previous_status ) || ! isset( $labels[ $status ], $labels[ $previous_status ] ) ) {
		return new WP_Error( 'receipt_invalid', 'وضعیت انتخاب‌شده معتبر نیست.' );
	}
	$receipt_id = absint( $receipt_id );
	$order_id   = absint( $order_id );
	if ( ! $receipt_id || ! $order_id || ! function_exists( 'wc_get_order' ) ) {
		return new WP_Error( 'receipt_stale', 'فیش یا سفارش در دسترس نیست. صفحه را تازه‌سازی کنید.' );
	}
	$lock = genosys_receipt_lock( $order_id );
	if ( is_wp_error( $lock ) ) {
		return $lock;
	}
	try {
		// Refresh both sides after locking so a replaced receipt cannot be reviewed.
		clean_post_cache( $receipt_id );
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$order->read_meta_data( true );
		}
		$current = genosys_receipt_get_current( $order );
		if ( ! $current || (int) $current['id'] !== $receipt_id ) {
			return new WP_Error( 'receipt_stale', 'فیش این سفارش تغییر کرده است. صفحه را تازه‌سازی کنید.' );
		}
		if ( $previous_status !== $current['reviewStatus'] ) {
			return new WP_Error( 'receipt_stale', 'وضعیت فیش در صفحه دیگری تغییر کرده است. صفحه را تازه‌سازی کنید.' );
		}
		if ( $status !== $current['reviewStatus'] ) {
			$saved = update_post_meta( $receipt_id, '_genosys_receipt_review_status', $status );
			if ( false === $saved || $status !== genosys_receipt_review_status( $receipt_id ) ) {
				return new WP_Error( 'receipt_save', 'ذخیره وضعیت فیش انجام نشد. دوباره تلاش کنید.' );
			}
			// The decision is committed; notification failures must not undo its success.
			try {
				do_action( 'genosys_receipt_review_status_changed', $receipt_id, $order_id, $current['reviewStatus'], $status, wp_generate_uuid4() );
			} catch ( Throwable $notification_error ) {
				if ( function_exists( 'genosys_receipt_sms_log' ) ) {
					genosys_receipt_sms_log( $order_id, 'notification_unavailable' );
				}
			}
		}
		return $status;
	} catch ( Throwable $exception ) {
		return new WP_Error( 'receipt_save', 'ذخیره وضعیت فیش انجام نشد. دوباره تلاش کنید.' );
	} finally {
		genosys_receipt_unlock( $lock );
	}
}

/** Managers may remove a current receipt without changing the WooCommerce order. */
function genosys_receipt_admin_delete_current( $receipt_id, $order_id ) {
	if ( ! genosys_receipt_can_manage() ) {
		return new WP_Error( 'receipt_forbidden', 'شما اجازه حذف فیش را ندارید.' );
	}
	$receipt_id = absint( $receipt_id );
	$order_id   = absint( $order_id );
	if ( ! $receipt_id || ! $order_id || ! function_exists( 'wc_get_order' ) ) {
		return new WP_Error( 'receipt_stale', 'فیش یا سفارش در دسترس نیست. صفحه را تازه‌سازی کنید.' );
	}
	$lock = genosys_receipt_lock( $order_id );
	if ( is_wp_error( $lock ) ) {
		return $lock;
	}
	$order    = false;
	$unlinked = false;
	try {
		clean_post_cache( $receipt_id );
		$order = wc_get_order( $order_id );
		if ( $order && $order->get_data_store() ) {
			$order->get_data_store()->read( $order );
			$order->read_meta_data( true );
		}
		$current = genosys_receipt_get_current( $order );
		if ( ! $current || (int) $current['id'] !== $receipt_id ) {
			return new WP_Error( 'receipt_stale', 'فیش این سفارش تغییر کرده است. صفحه را تازه‌سازی کنید.' );
		}
		$path = genosys_receipt_storage_path( (string) get_post_meta( $receipt_id, '_genosys_receipt_file', true ) );
		$order->delete_meta_data( '_genosys_receipt_id' );
		$unlinked = true;
		if ( ! $order->save() ) {
			throw new RuntimeException( 'receipt_unlink' );
		}
		$order->read_meta_data( true );
		if ( 0 !== absint( $order->get_meta( '_genosys_receipt_id', true ) ) ) {
			throw new RuntimeException( 'receipt_unlink' );
		}
		genosys_receipt_delete_record( $receipt_id );
		clean_post_cache( $receipt_id );
		if ( get_post( $receipt_id ) || ( $path && is_file( $path ) ) ) {
			throw new RuntimeException( 'receipt_cleanup' );
		}
		return true;
	} catch ( Throwable $exception ) {
		// Restore the link only when a failed deletion left the original record intact.
		$post = get_post( $receipt_id );
		if ( $unlinked && $order && $post && 'genosys_receipt' === $post->post_type && 'private' === $post->post_status && (int) get_post_meta( $receipt_id, '_genosys_order_id', true ) === $order_id ) {
			try {
				$order->read_meta_data( true );
				if ( 0 === absint( $order->get_meta( '_genosys_receipt_id', true ) ) ) {
					$order->update_meta_data( '_genosys_receipt_id', $receipt_id );
					$order->save();
				}
			} catch ( Throwable $restore_error ) {
				// Keep the private record and file available during a database failure.
			}
		}
		return new WP_Error( 'receipt_delete', 'حذف فیش کامل نشد. صفحه را تازه‌سازی و دوباره تلاش کنید.' );
	} finally {
		genosys_receipt_unlock( $lock );
	}
}

/** Read a single request value without allowing array inputs into string functions. */
function genosys_receipt_request_string( $source, $key ) {
	return isset( $source[ $key ] ) && is_scalar( $source[ $key ] ) ? sanitize_text_field( wp_unslash( (string) $source[ $key ] ) ) : '';
}

/** The same authorized history grants receipt access; guest keys remain supported. */
function genosys_receipt_customer_authorized( $order, $order_key = '' ) {
	if ( ! genosys_order_access_valid_order( $order ) ) {
		return false;
	}
	if ( genosys_order_access_can_view( $order ) ) {
		return true;
	}
	// A registered customer's orders require that account's identity. A guest's
	// private received-page link remains usable on another browser.
	if ( 0 !== (int) $order->get_customer_id() ) {
		return false;
	}
	$saved_key = (string) $order->get_order_key();
	return '' !== $saved_key && '' !== $order_key && hash_equals( $saved_key, (string) $order_key );
}

/** Receipt display belongs to card-transfer orders, independent of review state. */
function genosys_receipt_order_supports_receipts( $order ) {
	return $order && 'genosys_card_to_card' === $order->get_payment_method() && ! $order->has_status( 'trash' );
}

/** Only missing or rejected receipts on non-cancelled orders may be changed. */
function genosys_receipt_order_is_eligible( $order ) {
	if ( ! genosys_receipt_order_supports_receipts( $order ) || $order->has_status( 'cancelled' ) ) {
		return false;
	}
	$receipt = genosys_receipt_get_current( $order );
	return ! $receipt || 'rejected' === $receipt['reviewStatus'];
}

/** Resolve the dedicated storage directory, optionally creating its protection files. */
function genosys_receipt_storage_directory( $create = false ) {
	$uploads = wp_upload_dir( null, false );
	if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
		return new WP_Error( 'receipt_storage', 'ذخیره فایل در دسترس نیست. لطفاً دوباره تلاش کنید.' );
	}
	$directory = trailingslashit( $uploads['basedir'] ) . 'genosys-receipts';
	if ( ! $create ) {
		return $directory;
	}
	if ( ! wp_mkdir_p( $directory ) ) {
		return new WP_Error( 'receipt_storage', 'ذخیره فایل در دسترس نیست. لطفاً دوباره تلاش کنید.' );
	}
	$protections = array(
		'index.php'   => "<?php exit; ?>\n",
		'.htaccess'   => "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n",
		'web.config'  => '<?xml version="1.0" encoding="UTF-8"?><configuration><system.webServer><security><authorization><clear/><add accessType="Deny" users="*"/></authorization></security></system.webServer></configuration>',
	);
	foreach ( $protections as $name => $contents ) {
		$path = trailingslashit( $directory ) . $name;
		if ( ! file_exists( $path ) && false === file_put_contents( $path, $contents, LOCK_EX ) ) {
			return new WP_Error( 'receipt_storage', 'ذخیره فایل در دسترس نیست. لطفاً دوباره تلاش کنید.' );
		}
	}
	return $directory;
}

/** Stored metadata contains a generated basename, never a client-provided path. */
function genosys_receipt_storage_path( $filename ) {
	if ( ! is_string( $filename ) || ! preg_match( '/^[a-f0-9]{32}\.php$/D', $filename ) ) {
		return false;
	}
	$directory = genosys_receipt_storage_directory();
	return is_wp_error( $directory ) ? false : trailingslashit( $directory ) . $filename;
}

/** Only the current receipt ID, linked to this order in both directions, is valid. */
function genosys_receipt_get_current( $order ) {
	if ( ! $order || ! method_exists( $order, 'get_meta' ) ) {
		return null;
	}
	$id   = absint( $order->get_meta( '_genosys_receipt_id', true ) );
	$post = $id ? get_post( $id ) : null;
	if ( ! $post || 'genosys_receipt' !== $post->post_type || 'private' !== $post->post_status || (int) get_post_meta( $id, '_genosys_order_id', true ) !== (int) $order->get_id() ) {
		return null;
	}
	$review_status = genosys_receipt_review_status( $id );
	$review_labels = genosys_receipt_status_labels();
	return array(
		'id'          => $id,
		'name'        => (string) get_post_meta( $id, '_genosys_receipt_name', true ),
		'mime'        => (string) get_post_meta( $id, '_genosys_receipt_mime', true ),
		'size'        => (int) get_post_meta( $id, '_genosys_receipt_size', true ),
		'uploadedAt'  => get_post_time( 'c', true, $post ),
		'previewUrl'  => genosys_receipt_file_url( $id, false ),
		'downloadUrl' => genosys_receipt_file_url( $id, true ),
		'reviewStatus' => $review_status,
		'reviewLabel'  => $review_labels[ $review_status ],
	);
}

/** Every preview and download goes through an authorized handler. */
function genosys_receipt_file_url( $receipt_id, $download = true, $order_key = '' ) {
	$order_id = absint( get_post_meta( $receipt_id, '_genosys_order_id', true ) );
	$args     = array(
		'action'     => 'genosys_receipt_file',
		'receipt_id' => absint( $receipt_id ),
		'download'   => $download ? '1' : '0',
	);
	if ( '' !== $order_key ) {
		$args['order_key'] = (string) $order_key;
		$args['_wpnonce'] = wp_create_nonce( 'genosys_receipt_order_' . $order_id );
	} else {
		$args['_wpnonce'] = wp_create_nonce( 'genosys_receipt_file_' . absint( $receipt_id ) );
	}
	return add_query_arg( $args, admin_url( 'admin-post.php' ) );
}

/** Add customer-authorized URLs to the safe receipt descriptor. */
function genosys_receipt_customer_descriptor( $order, $receipt ) {
	if ( ! $receipt ) {
		return null;
	}
	$key                    = (string) $order->get_order_key();
	$receipt['previewUrl']   = genosys_receipt_file_url( $receipt['id'], false, $key );
	$receipt['downloadUrl']  = genosys_receipt_file_url( $receipt['id'], true, $key );
	return $receipt;
}

/** Configuration is emitted only on an authorized customer's order page. */
function genosys_receipt_customer_context( $order ) {
	$key = genosys_receipt_request_string( $_GET, 'key' );
	if ( ! genosys_receipt_order_supports_receipts( $order ) || ! genosys_receipt_customer_authorized( $order, $key ) ) {
		return null;
	}
	$can_change = genosys_receipt_order_is_eligible( $order );
	return array(
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'orderId'  => (int) $order->get_id(),
		'orderKey' => (string) $order->get_order_key(),
		'nonce'    => wp_create_nonce( 'genosys_receipt_order_' . $order->get_id() ),
		'receipt'  => genosys_receipt_customer_descriptor( $order, genosys_receipt_get_current( $order ) ),
		'canUpload' => $can_change,
		'canRemove' => $can_change,
	);
}

/** Serialize receipt changes, including requests sent from two browser tabs. */
function genosys_receipt_lock( $order_id ) {
	global $wpdb;
	$name  = 'genosys_receipt_lock_' . absint( $order_id );
	$value = wp_json_encode( array( 'time' => time(), 'token' => wp_generate_uuid4() ) );
	if ( add_option( $name, $value, '', false ) ) {
		return array( $name, $value );
	}
	$old = get_option( $name, '' );
	$row = is_string( $old ) ? json_decode( $old, true ) : null;
	if ( is_array( $row ) && isset( $row['time'] ) && time() - (int) $row['time'] > 120 ) {
		// Compare and delete only the expired lock we observed, never another request's lock.
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, $old ) );
		if ( $deleted ) {
			wp_cache_delete( $name, 'options' );
			if ( add_option( $name, $value, '', false ) ) {
				return array( $name, $value );
			}
		}
	}
	return new WP_Error( 'receipt_busy', 'فیش در حال ذخیره شدن است. چند لحظه دیگر دوباره تلاش کنید.' );
}

function genosys_receipt_unlock( $lock ) {
	global $wpdb;
	if ( is_array( $lock ) && 2 === count( $lock ) ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $lock[0], $lock[1] ) );
		wp_cache_delete( $lock[0], 'options' );
	}
}

/** Validate the authenticated request before reading or moving its file. */
function genosys_receipt_ajax_order() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || ! function_exists( 'wc_get_order' ) ) {
		return new WP_Error( 'receipt_forbidden', 'دسترسی به این سفارش مجاز نیست.' );
	}
	$order_id = absint( genosys_receipt_request_string( $_POST, 'order_id' ) );
	$order    = $order_id ? wc_get_order( $order_id ) : false;
	$nonce    = genosys_receipt_request_string( $_POST, 'nonce' );
	$key      = genosys_receipt_request_string( $_POST, 'order_key' );
	if ( ! $order || ! wp_verify_nonce( $nonce, 'genosys_receipt_order_' . $order_id ) || ! genosys_receipt_customer_authorized( $order, $key ) ) {
		return new WP_Error( 'receipt_forbidden', 'دسترسی به این سفارش مجاز نیست. صفحه را تازه‌سازی کنید.' );
	}
	if ( ! genosys_receipt_order_is_eligible( $order ) ) {
		return new WP_Error( 'receipt_ineligible', 'امکان تغییر فیش این سفارش وجود ندارد.' );
	}
	return $order;
}

/** Never trust the submitted MIME type, extension, or byte count. */
function genosys_receipt_validate_file( $file ) {
	$types = array( 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf' );
	if ( ! is_array( $file ) || ! isset( $file['error'], $file['tmp_name'], $file['name'] ) || ! is_scalar( $file['tmp_name'] ) || ! is_scalar( $file['name'] ) || is_array( $file['error'] ) ) {
		return new WP_Error( 'receipt_missing', 'لطفاً یک فایل فیش واریزی انتخاب کنید.' );
	}
	if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
		$message = in_array( (int) $file['error'], array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ? 'حجم فایل باید حداکثر ۵ مگابایت باشد.' : 'بارگذاری فایل انجام نشد. دوباره تلاش کنید.';
		return new WP_Error( 'receipt_upload', $message );
	}
	$path = (string) $file['tmp_name'];
	if ( ! is_uploaded_file( $path ) ) {
		return new WP_Error( 'receipt_upload', 'بارگذاری فایل معتبر نیست.' );
	}
	$size = filesize( $path );
	if ( false === $size || $size < 1 || $size > 5 * 1024 * 1024 ) {
		return new WP_Error( 'receipt_size', 'حجم فایل باید بیشتر از صفر و حداکثر ۵ مگابایت باشد.' );
	}
	$name      = sanitize_file_name( wp_basename( (string) $file['name'] ) );
	$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	if ( ! isset( $types[ $extension ] ) ) {
		return new WP_Error( 'receipt_type', 'فقط فایل‌های JPG، PNG، WebP و PDF مجاز هستند.' );
	}
	$expected = $types[ $extension ];
	if ( 'application/pdf' === $expected ) {
		$header = file_get_contents( $path, false, null, 0, 5 );
		$handle = function_exists( 'finfo_open' ) ? finfo_open( FILEINFO_MIME_TYPE ) : false;
		$mime   = $handle ? finfo_file( $handle, $path ) : '';
		if ( $handle ) {
			finfo_close( $handle );
		}
		if ( '%PDF-' !== $header || 'application/pdf' !== $mime ) {
			return new WP_Error( 'receipt_type', 'محتوای فایل با نوع PDF مطابقت ندارد.' );
		}
	} else {
		$info = @getimagesize( $path );
		if ( ! is_array( $info ) || empty( $info['mime'] ) || $expected !== $info['mime'] ) {
			return new WP_Error( 'receipt_type', 'محتوای فایل با نوع تصویر انتخاب‌شده مطابقت ندارد.' );
		}
	}
	$bytes = file_get_contents( $path );
	if ( false === $bytes || strlen( $bytes ) !== $size ) {
		return new WP_Error( 'receipt_read', 'خواندن فایل انجام نشد. دوباره تلاش کنید.' );
	}
	$name = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 200 ) : substr( $name, 0, 200 );
	return array( 'name' => $name, 'mime' => $expected, 'size' => $size, 'bytes' => $bytes );
}

/** Delete only files belonging to the private receipt records. */
function genosys_receipt_delete_record( $receipt_id ) {
	$post = get_post( $receipt_id );
	if ( ! $post || 'genosys_receipt' !== $post->post_type ) {
		return;
	}
	$path = genosys_receipt_storage_path( (string) get_post_meta( $receipt_id, '_genosys_receipt_file', true ) );
	$deleted = wp_delete_post( $receipt_id, true );
	if ( $deleted && $path && is_file( $path ) ) {
		wp_delete_file( $path );
	}
}

/** Atomic logical replacement: a saved previous receipt survives failed new uploads. */
function genosys_receipt_ajax_upload() {
	$order = genosys_receipt_ajax_order();
	if ( is_wp_error( $order ) ) {
		wp_send_json_error( array( 'message' => $order->get_error_message() ), 403 );
	}
	$lock = genosys_receipt_lock( $order->get_id() );
	if ( is_wp_error( $lock ) ) {
		wp_send_json_error( array( 'message' => $lock->get_error_message() ), 409 );
	}
	$response  = null;
	$error     = null;
	$status    = 400;
	$new_id    = 0;
	$new_path  = false;
	$previous  = genosys_receipt_get_current( $order );
	$old_id    = $previous ? (int) $previous['id'] : 0;
	try {
		// Refresh under the lock, including payment status and changes from another tab.
		$order = wc_get_order( $order->get_id() );
		if ( $order && $order->get_data_store() ) {
			$order->get_data_store()->read( $order );
		}
		if ( $order ) {
			$order->read_meta_data( true );
			$fresh_receipt_id = absint( $order->get_meta( '_genosys_receipt_id', true ) );
			if ( $fresh_receipt_id ) {
				clean_post_cache( $fresh_receipt_id );
			}
		}
		if ( ! genosys_receipt_order_is_eligible( $order ) ) {
			$status = 403;
			throw new RuntimeException( 'امکان تغییر فیش این سفارش وجود ندارد.' );
		}
		$previous = genosys_receipt_get_current( $order );
		$old_id   = $previous ? (int) $previous['id'] : 0;
		if ( absint( genosys_receipt_request_string( $_POST, 'receipt_id' ) ) !== $old_id ) {
			$status = 409;
			throw new RuntimeException( 'فیش این سفارش در صفحه دیگری تغییر کرده است. صفحه را تازه‌سازی کنید.' );
		}
		$file = genosys_receipt_validate_file( isset( $_FILES['receipt'] ) ? $_FILES['receipt'] : null );
		if ( is_wp_error( $file ) ) {
			throw new RuntimeException( $file->get_error_message() );
		}
		$directory = genosys_receipt_storage_directory( true );
		if ( is_wp_error( $directory ) ) {
			$status = 500;
			throw new RuntimeException( $directory->get_error_message() );
		}
		$basename = bin2hex( random_bytes( 16 ) ) . '.php';
		$new_path = trailingslashit( $directory ) . $basename;
		$payload  = "<?php exit; ?>\n" . base64_encode( $file['bytes'] );
		if ( strlen( $payload ) !== file_put_contents( $new_path, $payload, LOCK_EX ) ) {
			$status = 500;
			throw new RuntimeException( 'ذخیره فایل انجام نشد. لطفاً دوباره تلاش کنید.' );
		}
		@chmod( $new_path, 0600 );
		$new_id = wp_insert_post( array(
			'post_type'   => 'genosys_receipt',
			'post_status' => 'private',
			'post_title'  => $file['name'],
			'post_author' => (int) $order->get_customer_id(),
			'meta_input'  => array(
				'_genosys_order_id'     => (int) $order->get_id(),
				'_genosys_receipt_name' => $file['name'],
				'_genosys_receipt_mime' => $file['mime'],
				'_genosys_receipt_size' => $file['size'],
				'_genosys_receipt_file' => $basename,
				'_genosys_receipt_review_status' => 'pending',
			),
		), true );
		if ( is_wp_error( $new_id ) || ! $new_id ) {
			$new_id = 0;
			$status = 500;
			throw new RuntimeException( 'ذخیره فیش انجام نشد. لطفاً دوباره تلاش کنید.' );
		}
		$order->update_meta_data( '_genosys_receipt_id', $new_id );
		if ( ! $order->save() ) {
			$status = 500;
			throw new RuntimeException( 'ذخیره فیش انجام نشد. لطفاً دوباره تلاش کنید.' );
		}
		$response = genosys_receipt_customer_descriptor( $order, genosys_receipt_get_current( $order ) );
		if ( ! $response ) {
			$status = 500;
			throw new RuntimeException( 'ذخیره فیش انجام نشد. لطفاً دوباره تلاش کنید.' );
		}
		if ( $old_id ) {
			try {
				genosys_receipt_delete_record( $old_id );
			} catch ( Throwable $cleanup_error ) {
				// The new receipt is committed; a cleanup hook must not undo a saved upload.
			}
		}
	} catch ( Throwable $exception ) {
		$error = $exception instanceof RuntimeException ? $exception->getMessage() : 'ذخیره فیش انجام نشد. لطفاً دوباره تلاش کنید.';
		if ( $new_id ) {
			try {
				$old_id ? $order->update_meta_data( '_genosys_receipt_id', $old_id ) : $order->delete_meta_data( '_genosys_receipt_id' );
				$order->save();
			} catch ( Throwable $restore_error ) {
				// Keep the prior file and record even if the database is temporarily unavailable.
			}
			genosys_receipt_delete_record( $new_id );
		} elseif ( $new_path && is_file( $new_path ) ) {
			wp_delete_file( $new_path );
		}
	} finally {
		genosys_receipt_unlock( $lock );
	}
	if ( null !== $error ) {
		wp_send_json_error( array( 'message' => $error ), $status );
	}
	wp_send_json_success( array( 'receipt' => $response ) );
}
add_action( 'wp_ajax_genosys_receipt_upload', 'genosys_receipt_ajax_upload' );
add_action( 'wp_ajax_nopriv_genosys_receipt_upload', 'genosys_receipt_ajax_upload' );

/** Remove only this order's current receipt, preserving payment and order data. */
function genosys_receipt_ajax_remove() {
	$order = genosys_receipt_ajax_order();
	if ( is_wp_error( $order ) ) {
		wp_send_json_error( array( 'message' => $order->get_error_message() ), 403 );
	}
	$lock = genosys_receipt_lock( $order->get_id() );
	if ( is_wp_error( $lock ) ) {
		wp_send_json_error( array( 'message' => $lock->get_error_message() ), 409 );
	}
	$error  = null;
	$status = 500;
	$current_id = 0;
	try {
		$order = wc_get_order( $order->get_id() );
		if ( $order && $order->get_data_store() ) {
			$order->get_data_store()->read( $order );
		}
		if ( $order ) {
			$order->read_meta_data( true );
			$fresh_receipt_id = absint( $order->get_meta( '_genosys_receipt_id', true ) );
			if ( $fresh_receipt_id ) {
				clean_post_cache( $fresh_receipt_id );
			}
		}
		if ( ! genosys_receipt_order_is_eligible( $order ) ) {
			$status = 403;
			throw new RuntimeException( 'امکان تغییر فیش این سفارش وجود ندارد.' );
		}
		$current    = genosys_receipt_get_current( $order );
		$current_id = $current ? (int) $current['id'] : 0;
		if ( absint( genosys_receipt_request_string( $_POST, 'receipt_id' ) ) !== $current_id ) {
			$status = 409;
			throw new RuntimeException( 'فیش این سفارش در صفحه دیگری تغییر کرده است. صفحه را تازه‌سازی کنید.' );
		}
		if ( $current_id ) {
			$order->delete_meta_data( '_genosys_receipt_id' );
			if ( ! $order->save() ) {
				throw new RuntimeException( 'حذف فیش انجام نشد. لطفاً دوباره تلاش کنید.' );
			}
			try {
				genosys_receipt_delete_record( $current_id );
			} catch ( Throwable $cleanup_error ) {
				// The order link is already removed; leave a protected orphan if cleanup fails.
			}
		}
	} catch ( Throwable $exception ) {
		$error = $exception instanceof RuntimeException ? $exception->getMessage() : 'حذف فیش انجام نشد. لطفاً دوباره تلاش کنید.';
		if ( $current_id && 409 !== $status ) {
			try {
				$order->update_meta_data( '_genosys_receipt_id', $current_id );
				$order->save();
			} catch ( Throwable $restore_error ) {
				// Preserve the existing receipt's file and record while storage is unavailable.
			}
		}
	} finally {
		genosys_receipt_unlock( $lock );
	}
	if ( null !== $error ) {
		wp_send_json_error( array( 'message' => $error ), $status );
	}
	wp_send_json_success( array( 'receipt' => null ) );
}
add_action( 'wp_ajax_genosys_receipt_remove', 'genosys_receipt_ajax_remove' );
add_action( 'wp_ajax_nopriv_genosys_receipt_remove', 'genosys_receipt_ajax_remove' );

/** Serve validated bytes only after order ownership/key and nonce checks. */
function genosys_receipt_file() {
	$id   = absint( genosys_receipt_request_string( $_GET, 'receipt_id' ) );
	$post = $id ? get_post( $id ) : null;
	if ( ! $post || 'genosys_receipt' !== $post->post_type || 'private' !== $post->post_status || ! function_exists( 'wc_get_order' ) ) {
		wp_die( 'فیش در دسترس نیست.', '', array( 'response' => 404 ) );
	}
	$order_id = absint( get_post_meta( $id, '_genosys_order_id', true ) );
	$order    = wc_get_order( $order_id );
	$nonce    = genosys_receipt_request_string( $_GET, '_wpnonce' );
	$key      = genosys_receipt_request_string( $_GET, 'order_key' );
	$admin    = genosys_receipt_can_manage() && wp_verify_nonce( $nonce, 'genosys_receipt_file_' . $id );
	$customer = $order && wp_verify_nonce( $nonce, 'genosys_receipt_order_' . $order_id ) && genosys_receipt_customer_authorized( $order, $key );
	if ( ! $admin && ( ! $customer || (int) $order->get_meta( '_genosys_receipt_id', true ) !== $id ) ) {
		wp_die( 'دسترسی به این فیش مجاز نیست.', '', array( 'response' => 403 ) );
	}
	$path = genosys_receipt_storage_path( (string) get_post_meta( $id, '_genosys_receipt_file', true ) );
	if ( ! $path || ! is_file( $path ) || filesize( $path ) > 7 * 1024 * 1024 ) {
		wp_die( 'فایل فیش در دسترس نیست.', '', array( 'response' => 404 ) );
	}
	$container = file_get_contents( $path );
	$prefix    = "<?php exit; ?>\n";
	$bytes     = is_string( $container ) && 0 === strpos( $container, $prefix ) ? base64_decode( substr( $container, strlen( $prefix ) ), true ) : false;
	$mime      = (string) get_post_meta( $id, '_genosys_receipt_mime', true );
	$size      = (int) get_post_meta( $id, '_genosys_receipt_size', true );
	$extensions = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf' );
	if ( false === $bytes || ! isset( $extensions[ $mime ] ) || strlen( $bytes ) !== $size || $size > 5 * 1024 * 1024 || headers_sent() ) {
		wp_die( 'فایل فیش در دسترس نیست.', '', array( 'response' => 404 ) );
	}
	$name        = (string) get_post_meta( $id, '_genosys_receipt_name', true );
	$disposition = '0' === genosys_receipt_request_string( $_GET, 'download' ) ? 'inline' : 'attachment';
	nocache_headers();
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: no-referrer' );
	header( 'Content-Type: ' . $mime );
	header( 'Content-Length: ' . strlen( $bytes ) );
	header( 'Content-Disposition: ' . $disposition . '; filename="receipt-' . $id . '.' . $extensions[ $mime ] . '"; filename*=UTF-8\'\'' . rawurlencode( $name ) );
	echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Authorized, MIME-validated file bytes.
	exit;
}
add_action( 'admin_post_genosys_receipt_file', 'genosys_receipt_file' );
add_action( 'admin_post_nopriv_genosys_receipt_file', 'genosys_receipt_file' );
