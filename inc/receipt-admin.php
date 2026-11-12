<?php
/** Private payment receipts in the WordPress dashboard. */
defined( 'ABSPATH' ) || exit;

/** Both administrators and WooCommerce store managers may review receipts. */
function genosys_receipts_admin_capability() {
	return function_exists( 'WC' ) && current_user_can( 'manage_woocommerce' ) ? 'manage_woocommerce' : 'manage_options';
}

function genosys_receipts_add_admin_page() {
	add_menu_page(
		'فیش های واریزی',
		'فیش های واریزی',
		genosys_receipts_admin_capability(),
		'genosys-payment-receipts',
		'genosys_receipts_render_admin_page',
		'dashicons-media-document',
		62
	);
}
add_action( 'admin_menu', 'genosys_receipts_add_admin_page' );

/** The receipts dashboard presentation is loaded only on its existing admin page. */
function genosys_receipts_admin_assets( $hook ) {
	if ( 'toplevel_page_genosys-payment-receipts' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'genosys-receipts-dashboard', get_theme_file_uri( '/assets/css/receipt-admin.css' ), array(), '1.5.51' );
}
add_action( 'admin_enqueue_scripts', 'genosys_receipts_admin_assets' );

/** Fixed, presentation-only icons shared by dashboard controls. */
function genosys_receipts_admin_icon( $name ) {
	$paths = array(
		'document' => '<path d="M6 2h8l5 5v15H6Z"/><path d="M14 2v6h5M9 12h7M9 16h7M9 19h5"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/>',
		'check'    => '<circle cx="12" cy="12" r="10" fill="currentColor" stroke="none"/><path d="m7 12 3 3 7-7" stroke="white" stroke-width="2.2"/>',
		'rejected' => '<path d="M6 2h8l5 5v15H6Z"/><path d="M14 2v6h5M9 11h7m-6 4 5 5m0-5-5 5"/>',
		'search'   => '<circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/>',
		'account'  => '<circle cx="12" cy="8" r="4"/><path d="M4 22v-2a8 8 0 0 1 16 0v2"/>',
		'help'     => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 4 2c-1 .6-1.5 1-1.5 2M12 17h.01"/>',
		'eye'      => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
		'download' => '<path d="M12 3v12m-4-4 4 4 4-4M5 17v4h14v-4"/>',
		'trash'    => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
		'external' => '<path d="M14 3h7v7m0-7L10 14M10 5H4v16h16v-6"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $name ] ?? $paths['document'] ) . '</svg>';
}

/** Read-only totals for the same receipts/filter; the listing and stored data are untouched. */
function genosys_receipts_admin_summary( $args, $total ) {
	$counts = array( 'total' => max( 0, (int) $total ), 'approved' => 0, 'pending' => 0, 'rejected' => 0 );
	foreach ( array( 'approved', 'rejected' ) as $status ) {
		$count_args = $args;
		$count_args['posts_per_page'] = 1;
		$count_args['paged'] = 1;
		$count_args['fields'] = 'ids';
		$count_args['no_found_rows'] = false;
		$count_args['update_post_meta_cache'] = false;
		$count_args['update_post_term_cache'] = false;
		$status_clause = array( 'key' => '_genosys_receipt_review_status', 'value' => $status, 'compare' => '=', 'type' => 'BINARY' );
		$count_args['meta_query'] = empty( $args['meta_query'] ) ? array( $status_clause ) : array( 'relation' => 'AND', $args['meta_query'], $status_clause );
		$count_query = new WP_Query( $count_args );
		$counts[ $status ] = max( 0, (int) $count_query->found_posts );
	}
	// The existing review helper treats missing or unknown review metadata as pending.
	$counts['pending'] = max( 0, $counts['total'] - $counts['approved'] - $counts['rejected'] );
	return $counts;
}

/** Save a review without changing the order's payment, totals, or fulfillment. */
function genosys_receipts_admin_review() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || ! genosys_receipt_can_manage() ) {
		wp_die( 'شما اجازه تغییر وضعیت فیش را ندارید.', '', array( 'response' => 403 ) );
	}
	$receipt_id = absint( genosys_receipt_request_string( $_POST, 'receipt_id' ) );
	$nonce      = genosys_receipt_request_string( $_POST, '_wpnonce' );
	if ( ! $receipt_id || ! wp_verify_nonce( $nonce, 'genosys_receipt_review_' . $receipt_id ) ) {
		wp_die( 'درخواست معتبر نیست. صفحه را تازه‌سازی کنید.', '', array( 'response' => 403 ) );
	}
	$result = genosys_receipt_update_review_status(
		$receipt_id,
		absint( genosys_receipt_request_string( $_POST, 'order_id' ) ),
		genosys_receipt_request_string( $_POST, 'review_status' ),
		genosys_receipt_request_string( $_POST, 'previous_status' )
	);
	$notice = 'saved';
	if ( is_wp_error( $result ) ) {
		$notices = array( 'receipt_stale' => 'stale', 'receipt_busy' => 'busy', 'receipt_invalid' => 'invalid' );
		$notice  = isset( $notices[ $result->get_error_code() ] ) ? $notices[ $result->get_error_code() ] : 'error';
	}
	$args = array( 'page' => 'genosys-payment-receipts', 'receipt_notice' => $notice );
	$filter_order = absint( genosys_receipt_request_string( $_POST, 'filter_order_id' ) );
	$page_number  = max( 1, absint( genosys_receipt_request_string( $_POST, 'receipts_page' ) ) );
	if ( $filter_order ) {
		$args['order_id'] = $filter_order;
	}
	if ( $page_number > 1 ) {
		$args['paged'] = $page_number;
	}
	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_genosys_receipt_review', 'genosys_receipts_admin_review' );
add_action( 'admin_post_nopriv_genosys_receipt_review', 'genosys_receipts_admin_review' );

/** Protected, current-receipt deletion from the dashboard's trash control. */
function genosys_receipts_admin_delete() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) || ! genosys_receipt_can_manage() ) {
		wp_die( 'شما اجازه حذف فیش را ندارید.', '', array( 'response' => 403 ) );
	}
	$receipt_id = absint( genosys_receipt_request_string( $_POST, 'receipt_id' ) );
	$nonce      = genosys_receipt_request_string( $_POST, '_wpnonce' );
	if ( ! $receipt_id || ! wp_verify_nonce( $nonce, 'genosys_receipt_delete_' . $receipt_id ) ) {
		wp_die( 'درخواست معتبر نیست. صفحه را تازه‌سازی کنید.', '', array( 'response' => 403 ) );
	}
	$result = genosys_receipt_admin_delete_current( $receipt_id, absint( genosys_receipt_request_string( $_POST, 'order_id' ) ) );
	$notice = 'deleted';
	if ( is_wp_error( $result ) ) {
		$notices = array( 'receipt_stale' => 'stale', 'receipt_busy' => 'busy' );
		$notice  = isset( $notices[ $result->get_error_code() ] ) ? $notices[ $result->get_error_code() ] : 'delete_error';
	}
	$args         = array( 'page' => 'genosys-payment-receipts', 'receipt_notice' => $notice );
	$filter_order = absint( genosys_receipt_request_string( $_POST, 'filter_order_id' ) );
	$page_number  = max( 1, absint( genosys_receipt_request_string( $_POST, 'receipts_page' ) ) );
	if ( $filter_order ) {
		$args['order_id'] = $filter_order;
	}
	if ( $page_number > 1 ) {
		$args['paged'] = $page_number;
	}
	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_genosys_receipt_delete', 'genosys_receipts_admin_delete' );
add_action( 'admin_post_nopriv_genosys_receipt_delete', 'genosys_receipts_admin_delete' );

/** Only the current, bidirectionally linked receipt may have an editable review. */
function genosys_receipts_render_admin_review( $receipt_id, $order, $filter_order_id, $page_number ) {
	$status  = genosys_receipt_review_status( $receipt_id );
	$labels  = genosys_receipt_status_labels();
	$current = $order ? genosys_receipt_get_current( $order ) : null;
	?>
	<td class="genosys-receipts__review">
		<span class="genosys-receipts__review-label genosys-receipts__review-label--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $labels[ $status ] ); ?></span>
		<?php if ( $current && (int) $current['id'] === (int) $receipt_id ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="genosys-receipts__review-form">
				<input type="hidden" name="action" value="genosys_receipt_review">
				<input type="hidden" name="receipt_id" value="<?php echo esc_attr( $receipt_id ); ?>">
				<input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>">
				<input type="hidden" name="previous_status" value="<?php echo esc_attr( $status ); ?>">
				<input type="hidden" name="filter_order_id" value="<?php echo esc_attr( $filter_order_id ); ?>">
				<input type="hidden" name="receipts_page" value="<?php echo esc_attr( $page_number ); ?>">
				<?php wp_nonce_field( 'genosys_receipt_review_' . $receipt_id ); ?>
				<label class="screen-reader-text" for="genosys-receipt-review-<?php echo esc_attr( $receipt_id ); ?>">وضعیت فیش</label>
				<select name="review_status" id="genosys-receipt-review-<?php echo esc_attr( $receipt_id ); ?>">
					<?php foreach ( $labels as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="submit" class="button genosys-receipts__review-save">ذخیره</button>
			</form>
		<?php else : ?>
			<p class="description">فیش فعلی این سفارش نیست.</p>
		<?php endif; ?>
	</td>
	<?php
}

/** Accept an exact numeric order ID, including Persian and Arabic digits. */
function genosys_receipts_admin_order_filter() {
	if ( ! isset( $_GET['order_id'] ) || ! is_scalar( $_GET['order_id'] ) ) {
		return 0;
	}
	$value = strtr(
		trim( wp_unslash( (string) $_GET['order_id'] ) ),
		array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
			'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
			'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		)
	);
	return preg_match( '/^[0-9]+$/D', $value ) ? absint( $value ) : 0;
}

/** Read order details through WooCommerce CRUD so HPOS remains supported. */
function genosys_receipts_render_admin_order( $order, $order_id ) {
	if ( ! $order ) {
		?>
		<td class="genosys-receipts__order"><strong><?php echo esc_html( '#' . $order_id ); ?></strong><p class="description">سفارش در دسترس نیست.</p></td>
		<td class="genosys-receipts__customer"><span class="genosys-receipts__muted">—</span></td>
		<td class="genosys-receipts__details"><span class="genosys-receipts__muted">—</span></td>
		<td class="genosys-receipts__amount"><span class="genosys-receipts__muted">—</span></td>
		<td class="genosys-receipts__payment"><span class="genosys-receipts__muted">—</span></td>
		<td class="genosys-receipts__order-status"><span class="genosys-receipts__status">نامشخص</span></td>
		<?php
		return;
	}
	$edit_url      = method_exists( $order, 'get_edit_order_url' ) ? $order->get_edit_order_url() : '';
	$customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	$email         = $order->get_billing_email();
	$phone         = $order->get_billing_phone();
	$shipping      = $order->get_shipping_method();
	$payment       = $order->get_payment_method_title();
	$status        = $order->get_status();
	?>
	<td class="genosys-receipts__order">
		<?php if ( $edit_url ) : ?>
			<a class="genosys-receipts__order-link" href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( '#' . $order->get_order_number() ); ?></a>
		<?php else : ?>
			<strong><?php echo esc_html( '#' . $order->get_order_number() ); ?></strong>
		<?php endif; ?>
		<?php if ( (string) $order->get_order_number() !== (string) $order_id ) : ?>
			<p class="description"><?php echo esc_html( 'شناسه: ' . $order_id ); ?></p>
		<?php endif; ?>
	</td>
	<td class="genosys-receipts__customer">
		<strong><?php echo esc_html( $customer_name ? $customer_name : 'مشتری مهمان' ); ?></strong>
		<?php if ( $phone ) : ?><p dir="ltr"><?php echo esc_html( $phone ); ?></p><?php endif; ?>
		<?php if ( $email ) : ?><p dir="ltr"><?php echo esc_html( $email ); ?></p><?php endif; ?>
	</td>
	<td class="genosys-receipts__details">
		<strong><?php echo esc_html( 'سفارش #' . $order->get_order_number() ); ?></strong>
		<?php if ( $shipping ) : ?><p class="description"><?php echo esc_html( 'ارسال: ' . $shipping ); ?></p><?php endif; ?>
		<?php if ( $edit_url ) : ?><a class="genosys-receipts__details-link" href="<?php echo esc_url( $edit_url ); ?>"><?php echo genosys_receipts_admin_icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG. ?>مشاهده جزئیات</a><?php endif; ?>
	</td>
	<td class="genosys-receipts__amount"><div class="genosys-receipts__total"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></div></td>
	<td class="genosys-receipts__payment"><?php echo esc_html( $payment ? $payment : '—' ); ?></td>
	<td class="genosys-receipts__order-status"><span class="genosys-receipts__status genosys-receipts__status--<?php echo esc_attr( sanitize_html_class( $status ) ); ?>"><?php echo esc_html( wc_get_order_status_name( $status ) ); ?></span></td>
	<?php
}

function genosys_receipts_render_admin_file( $receipt_id, $order = null, $filter_order_id = 0, $page_number = 1 ) {
	$name         = (string) get_post_meta( $receipt_id, '_genosys_receipt_name', true );
	$mime         = (string) get_post_meta( $receipt_id, '_genosys_receipt_mime', true );
	$size         = max( 0, (int) get_post_meta( $receipt_id, '_genosys_receipt_size', true ) );
	$preview_url  = function_exists( 'genosys_receipt_file_url' ) ? genosys_receipt_file_url( $receipt_id, false ) : '';
	$image        = in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true );
	?>
	<div class="genosys-receipts__file">
		<?php if ( $image && $preview_url ) : ?>
			<a class="genosys-receipts__preview" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="مشاهده فیش">
				<img src="<?php echo esc_url( $preview_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="88" height="88" loading="lazy">
				<span class="genosys-receipts__preview-zoom"><?php echo genosys_receipts_admin_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG. ?></span>
			</a>
		<?php else : ?>
			<span class="genosys-receipts__document" aria-hidden="true"><?php echo esc_html( 'application/pdf' === $mime ? 'PDF' : 'فیش' ); ?></span>
		<?php endif; ?>
		<div class="genosys-receipts__file-info">
			<span class="genosys-receipts__filename" dir="auto" title="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ? $name : 'فیش واریزی' ); ?></span>
			<?php if ( $size ) : ?><p class="description"><?php echo esc_html( size_format( $size, 1 ) ); ?></p><?php endif; ?>
		</div>
	</div>
	<?php
}

/** Existing preview/download/delete controls, presented in their own action column. */
function genosys_receipts_render_admin_actions( $receipt_id, $order = null, $filter_order_id = 0, $page_number = 1 ) {
	$download_url = function_exists( 'genosys_receipt_file_url' ) ? genosys_receipt_file_url( $receipt_id, true ) : '';
	$preview_url  = function_exists( 'genosys_receipt_file_url' ) ? genosys_receipt_file_url( $receipt_id, false ) : '';
	$current      = $order ? genosys_receipt_get_current( $order ) : null;
	$can_delete   = $current && (int) $current['id'] === (int) $receipt_id;
	$delete_label = $order ? 'حذف فیش واریزی سفارش #' . $order->get_order_number() : 'حذف فیش واریزی';
	?>
	<div class="genosys-receipts__actions">
		<?php if ( $preview_url ) : ?><a class="button genosys-receipts__view" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo genosys_receipts_admin_icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG. ?>مشاهده</a><?php endif; ?>
		<?php if ( $download_url ) : ?>
			<a class="button genosys-receipts__download" href="<?php echo esc_url( $download_url ); ?>"><?php echo genosys_receipts_admin_icon( 'download' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG. ?>دانلود رسید</a>
		<?php else : ?><p class="description">فایل در دسترس نیست.</p><?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="genosys-receipts__delete-form">
				<?php if ( $can_delete ) : ?>
					<input type="hidden" name="action" value="genosys_receipt_delete">
					<input type="hidden" name="receipt_id" value="<?php echo esc_attr( $receipt_id ); ?>">
					<input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>">
					<input type="hidden" name="filter_order_id" value="<?php echo esc_attr( $filter_order_id ); ?>">
					<input type="hidden" name="receipts_page" value="<?php echo esc_attr( $page_number ); ?>">
					<?php wp_nonce_field( 'genosys_receipt_delete_' . $receipt_id ); ?>
				<?php endif; ?>
			<button type="submit" class="genosys-receipts__delete" aria-label="<?php echo esc_attr( $delete_label ); ?>" title="<?php echo esc_attr( $can_delete ? $delete_label : 'فیش فعلی این سفارش نیست.' ); ?>" <?php disabled( ! $can_delete ); ?>><?php echo genosys_receipts_admin_icon( 'trash' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG. ?>حذف</button>
		</form>
	</div>
	<?php
}

function genosys_receipts_render_admin_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'شما اجازه مشاهده فیش‌های واریزی را ندارید.', 'genosys' ), '', array( 'response' => 403 ) );
	}
	$page_number = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$order_id    = genosys_receipts_admin_order_filter();
	$notice      = genosys_receipt_request_string( $_GET, 'receipt_notice' );
	$notices     = array(
		'saved'   => 'وضعیت فیش ذخیره شد.',
		'deleted' => 'فیش واریزی حذف شد.',
		'stale'   => 'فیش یا وضعیت آن در صفحه دیگری تغییر کرده است. صفحه را تازه‌سازی و دوباره تلاش کنید.',
		'busy'    => 'فیش در حال ذخیره شدن است. چند لحظه دیگر دوباره تلاش کنید.',
		'invalid' => 'وضعیت انتخاب‌شده معتبر نیست.',
		'error'   => 'ذخیره وضعیت فیش انجام نشد. دوباره تلاش کنید.',
		'delete_error' => 'حذف فیش کامل نشد. صفحه را تازه‌سازی و دوباره تلاش کنید.',
	);
	$args        = array(
		'post_type'      => 'genosys_receipt',
		'post_status'    => 'private',
		'posts_per_page' => 20,
		'paged'          => $page_number,
		'orderby'        => array( 'date' => 'DESC', 'ID' => 'DESC' ),
	);
	if ( $order_id ) {
		$args['meta_query'] = array(
			array( 'key' => '_genosys_order_id', 'value' => $order_id, 'compare' => '=', 'type' => 'NUMERIC' ),
		);
	}
	$receipts   = new WP_Query( $args );
	$page_url   = admin_url( 'admin.php?page=genosys-payment-receipts' );
	$pagination = paginate_links(
		array(
			'base'      => add_query_arg( 'paged', '%#%', $order_id ? add_query_arg( 'order_id', $order_id, $page_url ) : $page_url ),
			'format'    => '',
			'current'   => $page_number,
			'total'     => $receipts->max_num_pages,
			'prev_text' => 'قبلی',
			'next_text' => 'بعدی',
			'type'      => 'list',
		)
	);
	$summary = genosys_receipts_admin_summary( $args, $receipts->found_posts );
	$user = wp_get_current_user();
	$range_start = $receipts->post_count ? ( $page_number - 1 ) * 20 + 1 : 0;
	$range_end = $receipts->post_count ? min( (int) $receipts->found_posts, $range_start + $receipts->post_count - 1 ) : 0;
	$summary_labels = array( 'total' => 'کل رسیدها', 'approved' => 'تایید شده', 'pending' => 'در انتظار بررسی', 'rejected' => 'رد شده' );
	$summary_icons = array( 'total' => 'document', 'approved' => 'check', 'pending' => 'clock', 'rejected' => 'rejected' );
	?>
	<div class="wrap genosys-receipts" dir="rtl">
		<header class="genosys-receipts__topbar">
			<div class="genosys-receipts__identity">
				<span class="genosys-receipts__avatar"><?php echo genosys_receipts_admin_icon( 'account' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG. ?></span>
				<div class="genosys-receipts__identity-text"><strong><?php echo esc_html( $user->display_name ); ?></strong><span><?php echo esc_html( current_user_can( 'manage_options' ) ? 'مدیر کل' : 'مدیر فروشگاه' ); ?></span></div>
			</div>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="genosys-receipts__search genosys-receipts__topbar-search">
				<input type="hidden" name="page" value="genosys-payment-receipts">
				<div class="genosys-receipts__search-field">
					<label class="screen-reader-text" for="genosys-receipt-topbar-order-id">شناسه سفارش</label>
					<input type="text" inputmode="numeric" name="order_id" id="genosys-receipt-topbar-order-id" value="<?php echo $order_id ? esc_attr( $order_id ) : ''; ?>" placeholder="جستجو در رسیدها با شماره سفارش ...">
					<button type="submit" class="genosys-receipts__topbar-submit" aria-label="جستجوی رسید با شناسه سفارش"><?php echo genosys_receipts_admin_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG. ?></button>
				</div>
			</form>
			<div class="genosys-receipts__brand"><img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/genosys-logo-1.1.1.svg' ) ); ?>" alt="GENOSYS" width="215" height="47"><small>SKIN SCIENCE FOR A BETTER YOU</small></div>
		</header>
		<?php if ( isset( $notices[ $notice ] ) ) : ?>
			<div class="notice <?php echo in_array( $notice, array( 'saved', 'deleted' ), true ) ? 'notice-success' : 'notice-error'; ?> is-dismissible"><p><?php echo esc_html( $notices[ $notice ] ); ?></p></div>
		<?php endif; ?>
		<section class="genosys-receipts__hero" aria-labelledby="genosys-receipts-title">
			<img class="genosys-receipts__hero-art" src="<?php echo esc_url( get_theme_file_uri( '/assets/images/receipt-dashboard-art.png' ) ); ?>" alt="" aria-hidden="true" width="1672" height="941">
			<p class="genosys-receipts__hero-slogan" lang="en" dir="ltr" aria-hidden="true">SCIENCE<br>FOR HEALTHY<br>BEAUTIFUL SKIN</p>
			<div class="genosys-receipts__hero-copy">
				<h1 id="genosys-receipts-title">مدیریت رسیدهای پرداخت</h1>
				<p class="genosys-receipts__intro">رسیدهای پرداخت ارسال‌شده توسط کاربران را بررسی، تأیید یا رد کنید.</p>
				<details class="genosys-receipts__help"><summary><?php echo genosys_receipts_admin_icon( 'help' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG. ?>راهنمای این بخش</summary><p>از «مشاهده» برای بررسی فایل و از «دانلود رسید» برای دریافت آن استفاده کنید. وضعیت فیش را انتخاب و ذخیره کنید. حذف فیش، امکان بارگذاری دوباره برای مشتری را فعال می‌کند.</p></details>
			</div>
		</section>
		<section class="genosys-receipts__stats" aria-label="خلاصه وضعیت رسیدها">
			<?php foreach ( $summary_labels as $key => $label ) : ?>
				<div class="genosys-receipts__stat genosys-receipts__stat--<?php echo esc_attr( $key ); ?>"><div class="genosys-receipts__stat-inner"><p class="genosys-receipts__stat-label"><?php echo esc_html( $label ); ?></p><strong class="genosys-receipts__stat-value"><?php echo esc_html( number_format_i18n( $summary[ $key ] ) ); ?></strong><span class="genosys-receipts__stat-icon"><?php echo genosys_receipts_admin_icon( $summary_icons[ $key ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG. ?></span></div></div>
			<?php endforeach; ?>
		</section>
		<div class="genosys-receipts__toolbar">
			<div class="genosys-receipts__toolbar-heading"><strong>جستجو با شناسه سفارش</strong><p><?php echo esc_html( $order_id ? 'رسیدهای سفارش #' . $order_id : 'رسیدها به ترتیب جدیدترین بارگذاری نمایش داده می‌شوند.' ); ?></p></div>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="genosys-receipts__search">
				<input type="hidden" name="page" value="genosys-payment-receipts">
				<div class="genosys-receipts__search-field">
					<?php echo genosys_receipts_admin_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG. ?>
					<label class="screen-reader-text" for="genosys-receipt-order-id">شناسه سفارش</label>
					<input type="text" inputmode="numeric" name="order_id" id="genosys-receipt-order-id" value="<?php echo $order_id ? esc_attr( $order_id ) : ''; ?>" placeholder="شماره سفارش، مثلاً ۱۷۴">
				</div>
				<button type="submit" class="button genosys-receipts__search-button">جستجو</button>
				<?php if ( $order_id ) : ?><a class="genosys-receipts__reset" href="<?php echo esc_url( $page_url ); ?>">نمایش همه</a><?php endif; ?>
			</form>
		</div>
		<div class="genosys-receipts__table-wrap" role="region" aria-label="جدول رسیدهای پرداخت" tabindex="0">
			<table class="widefat genosys-receipts__table">
				<thead><tr><th scope="col">#</th><th scope="col">مشتری</th><th scope="col">جزئیات سفارش</th><th scope="col">مبلغ سفارش</th><th scope="col">روش پرداخت</th><th scope="col">وضعیت سفارش</th><th scope="col">وضعیت فیش</th><th scope="col">تصویر رسید</th><th scope="col">عملیات</th><th scope="col">تاریخ بارگذاری</th></tr></thead>
				<tbody>
				<?php if ( $receipts->posts ) : ?>
					<?php foreach ( $receipts->posts as $receipt ) : ?>
						<?php
						$receipt_order_id = absint( get_post_meta( $receipt->ID, '_genosys_order_id', true ) );
						$order            = function_exists( 'wc_get_order' ) && $receipt_order_id ? wc_get_order( $receipt_order_id ) : false;
						?>
						<tr data-receipt-id="<?php echo esc_attr( $receipt->ID ); ?>">
							<?php genosys_receipts_render_admin_order( $order, $receipt_order_id ); ?>
							<?php genosys_receipts_render_admin_review( $receipt->ID, $order, $order_id, $page_number ); ?>
							<td class="genosys-receipts__receipt-image"><?php genosys_receipts_render_admin_file( $receipt->ID, $order, $order_id, $page_number ); ?></td>
							<td><?php genosys_receipts_render_admin_actions( $receipt->ID, $order, $order_id, $page_number ); ?></td>
							<td class="genosys-receipts__date"><time datetime="<?php echo esc_attr( get_post_time( 'c', true, $receipt ) ); ?>"><?php echo esc_html( get_the_date( get_option( 'date_format' ), $receipt ) ); ?><br><span><?php echo esc_html( get_the_time( get_option( 'time_format' ), $receipt ) ); ?></span></time></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="10" class="genosys-receipts__empty"><span class="dashicons dashicons-media-document" aria-hidden="true"></span><strong><?php echo esc_html( $order_id ? 'برای این سفارش فیشی پیدا نشد.' : 'هنوز فیش واریزی ثبت نشده است.' ); ?></strong></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<footer class="genosys-receipts__pagination">
			<p class="genosys-receipts__range"><?php echo esc_html( sprintf( 'نمایش %1$s تا %2$s از %3$s مورد', number_format_i18n( $range_start ), number_format_i18n( $range_end ), number_format_i18n( $receipts->found_posts ) ) ); ?></p>
			<nav class="genosys-receipts__page-links" aria-label="صفحه‌بندی فیش‌ها">
				<?php if ( $pagination ) : ?><?php echo wp_kses_post( $pagination ); ?><?php else : ?><ul class="page-numbers"><li><span aria-disabled="true" aria-label="قبلی"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></span></li><li><span class="page-numbers current" aria-current="page">۱</span></li><li><span aria-disabled="true" aria-label="بعدی"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg></span></li></ul><?php endif; ?>
			</nav>
			<p class="genosys-receipts__page-size"><span>تعداد در هر صفحه:</span><strong>۲۰</strong></p>
		</footer>
	</div>
	<?php
}
