<?php
/** A real WooCommerce package list, using the existing shared component inventory. */
defined( 'ABSPATH' ) || exit;

function genosys_package_list_menu() {
	if ( ! function_exists( 'wc_get_product' ) ) { return; }
	global $submenu;
	$position = null;
	foreach ( (array) ( $submenu['woocommerce'] ?? array() ) as $index => $item ) {
		if ( 'genosys-add-package' === ( $item[2] ?? '' ) ) { $position = $index + 1; break; }
	}
	add_submenu_page( 'woocommerce', 'لیست پکیج ها', 'لیست پکیج ها', 'manage_woocommerce', 'genosys-package-list', 'genosys_package_list_page', $position );
}
add_action( 'admin_menu', 'genosys_package_list_menu', 21 );

function genosys_package_list_assets( $hook ) {
	if ( 'woocommerce_page_genosys-package-list' !== $hook ) { return; }
	wp_enqueue_style( 'genosys-package-list', get_theme_file_uri( '/assets/css/package-list.css' ), array(), '1.5.57' );
	wp_enqueue_script( 'genosys-package-list', get_theme_file_uri( '/assets/js/package-list.js' ), array(), '1.5.57', true );
}
add_action( 'admin_enqueue_scripts', 'genosys_package_list_assets' );

function genosys_package_list_icon( $name ) {
	$paths = array(
		'package' => '<path d="m12 3 9 5v8l-9 5-9-5V8l9-5Zm-9 5 9 5 9-5M12 13v8M7.5 5.5l9 5"/>',
		'check' => '<path d="m5 12 4 4L19 6"/>',
		'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/>',
		'close' => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6m0-6-6 6"/>',
		'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
		'chevron' => '<path d="m6 9 6 6 6-6"/>',
		'edit' => '<path d="m15 4 5 5M4 20l4-1L20 7a2.1 2.1 0 0 0-3-3L5 16l-1 4Z"/>',
		'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
		'copy' => '<rect x="8" y="8" width="12" height="13" rx="2"/><path d="M15 4H5a2 2 0 0 0-2 2v11"/>',
		'trash' => '<path d="M4 6h16M9 6V3h6v3M6 6l1 15h10l1-15M10 10v7m4-7v7"/>',
		'restore' => '<path d="M3 11a9 9 0 1 1 2 7M3 4v7h7M12 8v5l3 2"/>',
		'plus' => '<path d="M12 5v14M5 12h14"/>',
		'image' => '<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="2"/><path d="m3 17 5-5 4 4 4-7 5 8"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $name ] ?? $paths['package'] ) . '</svg>';
}

function genosys_package_list_price( $value ) {
	if ( '' === $value || null === $value ) { return '—'; }
	return wp_kses_post( genosys_checkout_localize_html( wc_price( $value ) ) );
}

function genosys_package_list_date( $date ) {
	if ( ! $date ) { return '—'; }
	try {
		$parts = explode( '/', explode( ' ', genosys_shop_ascii_digits( genosys_sms_shamsi_date( $date->getTimestamp() ) ) )[0] );
		$months = array( '', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
		return genosys_persian_number( (int) $parts[2] . ' ' . $months[ (int) $parts[1] ] . ' ' . $parts[0] );
	} catch ( Throwable $error ) {
		return genosys_persian_number( wp_date( 'j F Y', $date->getTimestamp(), wp_timezone() ) );
	}
}

/** Keep referenced products visible as unavailable if they were removed or unpublished. */
function genosys_package_list_components_data( $package ) {
	$rows = array();
	foreach ( genosys_package_components( $package ) as $item ) {
		$product = wc_get_product( $item['product_id'] );
		if ( $product && ( ! genosys_package_list_can_read( $product->get_id() ) || ( $product->is_type( 'variation' ) && ! genosys_package_list_can_read( $product->get_parent_id() ) ) ) ) { $product = null; }
		$data = $product ? genosys_package_catalog_payload( $product ) : null;
		if ( ! $data ) {
			$data = array( 'id' => $item['product_id'], 'name' => $product ? $product->get_name() : 'محصول #' . genosys_persian_number( $item['product_id'] ),
				'price' => $product && '' !== $product->get_price( 'edit' ) ? genosys_package_display_price( $product->get_price( 'edit' ), $product ) : null,
				'image' => $product && $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '',
				'stock_quantity' => 0, 'stock_state' => 'outofstock', 'stock_label' => 'در دسترس نیست' );
		}
		$data['quantity'] = $item['quantity'];
		$rows[] = $data;
	}
	return $rows;
}

function genosys_package_list_return_fields( $context ) {
	foreach ( array( 'gpl_status' => 'status', 'gpl_search' => 'search', 'gpl_order' => 'order', 'gpl_page' => 'page' ) as $field => $key ) {
		echo '<input type="hidden" name="' . esc_attr( $field ) . '" value="' . esc_attr( $context[ $key ] ) . '">';
	}
}

function genosys_package_list_action_form( $operation, $product, $context, $label, $icon, $confirm = '' ) {
	$id = $product->get_id();
	?>
	<form class="gpl__action-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"<?php if ( $confirm ) : ?> data-package-confirm="<?php echo esc_attr( $confirm ); ?>"<?php endif; ?>>
		<input type="hidden" name="action" value="genosys_package_list_action"><input type="hidden" name="operation" value="<?php echo esc_attr( $operation ); ?>"><input type="hidden" name="package_id" value="<?php echo esc_attr( $id ); ?>">
		<?php wp_nonce_field( 'genosys_package_list_action_' . $operation . '_' . $id, '_wpnonce', false ); genosys_package_list_return_fields( $context ); ?>
		<button class="gpl__action" type="submit" title="<?php echo esc_attr( $label ); ?>" aria-label="<?php echo esc_attr( $label . ': ' . $product->get_name() ); ?>" data-package-operation="<?php echo esc_attr( $operation ); ?>"><?php echo genosys_package_list_icon( $icon ); ?></button>
	</form>
	<?php
}

function genosys_package_list_details( $product, $components, $stock ) {
	$total = 0; $has_price = true;
	?>
	<div class="gpl__details">
		<h2>محصولات داخل پکیج</h2>
		<?php if ( $components ) : ?>
			<div class="gpl__components">
				<?php foreach ( $components as $component ) : ?>
					<?php if ( null === $component['price'] ) { $has_price = false; } else { $total += (float) $component['price'] * $component['quantity']; } ?>
					<div class="gpl__component">
						<span class="gpl__component-image"><?php if ( $component['image'] ) : ?><img src="<?php echo esc_url( $component['image'] ); ?>" alt="" loading="lazy"><?php else : ?><?php echo genosys_package_builder_icon( 'bottle' ); ?><?php endif; ?></span>
						<div class="gpl__component-copy"><strong class="gpl__component-name" title="<?php echo esc_attr( $component['name'] ); ?>"><?php echo esc_html( $component['name'] ); ?></strong><span class="gpl__component-price"><?php echo genosys_package_list_price( $component['price'] ); ?></span></div>
						<div class="gpl__component-quantity"><strong>×<?php echo esc_html( genosys_persian_number( $component['quantity'] ) ); ?></strong><span>تعداد</span></div>
						<div class="gpl__component-stock" data-state="<?php echo esc_attr( $component['stock_state'] ); ?>"><strong><?php echo esc_html( null === $component['stock_quantity'] ? ( 'outofstock' === $component['stock_state'] ? 'ناموجود' : '—' ) : genosys_persian_number( $component['stock_quantity'] ) ); ?></strong><span><?php echo esc_html( 'outofstock' === $component['stock_state'] ? $component['stock_label'] : ( null === $component['stock_quantity'] ? 'بدون مدیریت تعداد' : 'موجودی' ) ); ?></span></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?><p class="gpl__empty-components">محصولی برای این پکیج انتخاب نشده است.</p><?php endif; ?>
		<div class="gpl__details-summary"><span>مجموع قیمت محصولات: <strong><?php echo genosys_package_list_price( $has_price && $components ? $total : null ); ?></strong></span><span>موجودی پکیج: <strong><?php echo esc_html( null === $stock ? 'بدون مدیریت تعداد' : genosys_persian_number( $stock ) . ' عدد' ); ?></strong> (بر اساس کمترین موجودی)</span></div>
	</div>
	<?php
}

function genosys_package_list_row( $product, $context ) {
	$id = $product->get_id(); $components = genosys_package_list_components_data( $product );
	$stock = genosys_package_stock( $product ); $threshold = max( 0, (float) get_option( 'woocommerce_notify_low_stock_amount', 2 ) );
	$state = 0 === $stock ? 'outofstock' : ( null === $stock ? 'unmanaged' : ( $stock <= $threshold ? 'low' : 'available' ) );
	$status = $product->get_status(); $trashed = 'trash' === $status;
	$can_edit = ! $trashed && current_user_can( 'edit_products' ) && current_user_can( 'edit_post', $id );
	$can_delete = current_user_can( 'delete_post', $id );
	$price = '' === $product->get_price( 'edit' ) ? null : genosys_package_display_price( $product->get_price( 'edit' ), $product );
	$regular = '' === $product->get_regular_price( 'edit' ) ? null : genosys_package_display_price( $product->get_regular_price( 'edit' ), $product );
	$discount = $price !== null && $regular > 0 && $price < $regular ? (int) round( 100 * ( 1 - $price / $regular ) ) : 0;
	$date = $product->get_date_created(); $image = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '';
	$labels = array( 'publish' => 'منتشر شده', 'private' => 'خصوصی', 'draft' => 'پیش‌نویس', 'auto-draft' => 'پیش‌نویس', 'pending' => 'در انتظار بررسی', 'trash' => 'زباله‌دان' );
	?>
	<tr class="gpl__row" data-package-row data-package-id="<?php echo esc_attr( $id ); ?>">
		<td class="gpl__select-col"><input type="checkbox" class="gpl__checkbox" data-package-select value="<?php echo esc_attr( $id ); ?>" aria-label="<?php echo esc_attr( 'انتخاب ' . $product->get_name() ); ?>"<?php disabled( $trashed || ! $can_delete ); ?>></td>
		<td class="gpl__package-col"><div class="gpl__package"><span class="gpl__image"><?php if ( $image ) : ?><img src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy"><?php else : ?><?php echo genosys_package_list_icon( 'image' ); ?><?php endif; ?></span><div class="gpl__package-copy"><strong class="gpl__name"><?php if ( $can_edit ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=genosys-add-package&package_id=' . $id ) ); ?>"><?php endif; ?><?php echo esc_html( $product->get_name() ); ?><?php if ( $can_edit ) : ?></a><?php endif; ?></strong><p class="gpl__meta">شناسه: <bdi><?php echo esc_html( $product->get_sku() ?: '#' . genosys_persian_number( $id ) ); ?></bdi> · <?php if ( $date ) : ?><time datetime="<?php echo esc_attr( gmdate( DATE_ATOM, $date->getTimestamp() ) ); ?>"><?php endif; ?><?php echo esc_html( genosys_package_list_date( $date ) ); ?><?php if ( $date ) : ?></time><?php endif; ?></p><?php if ( 'publish' !== $status ) : ?><span class="gpl__status" data-status="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $labels[ $status ] ?? $status ); ?></span><?php endif; ?></div></div></td>
		<td class="gpl__contents-col"><div class="gpl__chips"><?php foreach ( array_slice( $components, 0, 2 ) as $component ) : ?><span class="gpl__chip"><b>×<?php echo esc_html( genosys_persian_number( $component['quantity'] ) ); ?></b><span class="gpl__chip-name" title="<?php echo esc_attr( $component['name'] ); ?>"><?php echo esc_html( $component['name'] ); ?></span></span><?php endforeach; ?><?php if ( count( $components ) > 2 ) : ?><span class="gpl__more-chip"><?php echo esc_html( genosys_persian_number( count( $components ) - 2 ) ); ?>+ محصول</span><?php endif; ?></div><button type="button" class="gpl__contents-toggle" data-package-toggle aria-expanded="false" aria-controls="gpl-details-<?php echo esc_attr( $id ); ?>"><span data-package-toggle-label>مشاهده‌ی محتوا</span><?php echo genosys_package_list_icon( 'chevron' ); ?></button></td>
		<td class="gpl__price-col"><strong class="gpl__price"><?php echo genosys_package_list_price( $price ); ?></strong><?php if ( $discount ) : ?><del class="gpl__regular-price"><?php echo genosys_package_list_price( $regular ); ?></del><span class="gpl__discount"><?php echo esc_html( genosys_persian_number( $discount ) ); ?>٪ تخفیف</span><?php endif; ?></td>
		<td class="gpl__stock-col"><div class="gpl__stock" data-state="<?php echo esc_attr( $state ); ?>"><strong><?php echo esc_html( null === $stock ? '—' : genosys_persian_number( $stock ) ); ?></strong><span><?php echo esc_html( 'outofstock' === $state ? 'ناموجود' : ( 'low' === $state ? 'موجودی کم' : ( 'unmanaged' === $state ? 'موجود · بدون مدیریت تعداد' : 'موجود' ) ) ); ?></span></div></td>
		<td class="gpl__actions-col"><div class="gpl__actions">
			<?php if ( $can_edit ) : ?><a class="gpl__action" href="<?php echo esc_url( admin_url( 'admin.php?page=genosys-add-package&package_id=' . $id ) ); ?>" title="ویرایش" aria-label="<?php echo esc_attr( 'ویرایش ' . $product->get_name() ); ?>" data-package-operation="edit"><?php echo genosys_package_list_icon( 'edit' ); ?></a><?php endif; ?>
			<?php if ( ! $trashed && current_user_can( 'read_post', $id ) ) : ?><a class="gpl__action" href="<?php echo esc_url( in_array( $status, array( 'publish', 'private' ), true ) ? $product->get_permalink() : get_preview_post_link( $id ) ); ?>" target="_blank" rel="noopener noreferrer" title="مشاهده" aria-label="<?php echo esc_attr( 'مشاهده ' . $product->get_name() ); ?>" data-package-operation="view"><?php echo genosys_package_list_icon( 'eye' ); ?></a><?php endif; ?>
			<?php if ( $can_edit ) { genosys_package_list_action_form( 'duplicate', $product, $context, 'کپی پکیج', 'copy' ); } ?>
			<?php if ( $can_delete ) { genosys_package_list_action_form( $trashed ? 'restore' : 'trash', $product, $context, $trashed ? 'بازگردانی' : 'انتقال به زباله‌دان', $trashed ? 'restore' : 'trash', $trashed ? '' : 'این پکیج به زباله‌دان منتقل شود؟' ); } ?>
		</div></td>
	</tr>
	<tr class="gpl__detail-row" id="gpl-details-<?php echo esc_attr( $id ); ?>" hidden><td colspan="6"><?php genosys_package_list_details( $product, $components, $stock ); ?></td></tr>
	<?php
}

function genosys_package_list_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'wc_get_product' ) ) { wp_die( 'اجازه دسترسی به این صفحه را ندارید.', '', array( 'response' => 403 ) ); }
	$context = genosys_package_list_context();
	$labels = array( 'all' => 'همه', 'published' => 'منتشر شده', 'draft' => 'پیش‌نویس', 'pending' => 'در انتظار بررسی', 'outofstock' => 'ناموجود', 'trash' => 'زباله‌دان' );
	$notice = isset( $_GET['gpl_notice'] ) && is_scalar( $_GET['gpl_notice'] ) ? sanitize_key( wp_unslash( $_GET['gpl_notice'] ) ) : '';
	$notices = array( 'duplicated' => 'یک نسخه پیش‌نویس از پکیج ساخته شد.', 'trashed' => 'پکیج به زباله‌دان منتقل شد.', 'restored' => 'پکیج از زباله‌دان بازگردانی شد.', 'bulk_trashed' => 'پکیج‌های انتخاب‌شده به زباله‌دان منتقل شدند.', 'error' => 'عملیات انجام نشد. صفحه را تازه کنید و دوباره تلاش کنید.' );
	?>
	<div class="wrap gpl" dir="rtl" data-package-list>
		<header class="gpl__header"><div class="gpl__header-title"><h1>پکیج‌ها</h1><span class="gpl__heading-count"><?php echo esc_html( genosys_persian_number( $context['total'] ) ); ?> پکیج</span></div><?php if ( current_user_can( 'edit_products' ) ) : ?><a class="gpl__add" href="<?php echo esc_url( admin_url( 'admin.php?page=genosys-add-package' ) ); ?>"><?php echo genosys_package_list_icon( 'plus' ); ?>افزودن پکیج</a><?php endif; ?></header>
		<?php if ( isset( $notices[ $notice ] ) ) : ?><div class="gpl__notice<?php echo 'error' === $notice ? ' gpl__notice--error' : ''; ?>" role="status"><?php echo esc_html( $notices[ $notice ] ); ?></div><?php endif; ?>
		<section class="gpl__stats" aria-label="خلاصه پکیج‌ها">
			<?php foreach ( array( 'all' => array( 'total', 'کل پکیج‌ها', 'package' ), 'published' => array( 'published', 'منتشر شده', 'check' ), 'low' => array( 'low', 'موجودی کم', 'alert' ), 'outofstock' => array( 'outofstock', 'ناموجود', 'close' ) ) as $key => $stat ) : ?>
				<div class="gpl__stat gpl__stat--<?php echo esc_attr( $stat[0] ); ?>"><span class="gpl__stat-icon"><?php echo genosys_package_list_icon( $stat[2] ); ?></span><span class="gpl__stat-label"><?php echo esc_html( $stat[1] ); ?></span><strong class="gpl__stat-value"><?php echo esc_html( genosys_persian_number( $context['stats'][ $key ] ) ); ?></strong></div>
			<?php endforeach; ?>
		</section>
		<section class="gpl__panel" aria-label="لیست پکیج ها">
			<nav class="gpl__tabs" aria-label="وضعیت پکیج‌ها"><?php foreach ( $labels as $key => $label ) : ?><a class="gpl__tab<?php echo $key === $context['status'] ? ' is-active' : ''; ?>" href="<?php echo esc_url( genosys_package_list_url( array( 'gpl_status' => $key, 'gpl_page' => 1 ) ) ); ?>"<?php if ( $key === $context['status'] ) : ?> aria-current="page"<?php endif; ?>><?php echo esc_html( $label ); ?><span class="gpl__tab-count"><?php echo esc_html( genosys_persian_number( $context['counts'][ $key ] ) ); ?></span></a><?php endforeach; ?></nav>
			<div class="gpl__toolbar">
				<form class="gpl__search-form" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>"><input type="hidden" name="page" value="genosys-package-list"><input type="hidden" name="gpl_status" value="<?php echo esc_attr( $context['status'] ); ?>"><label class="gpl__search-field"><?php echo genosys_package_list_icon( 'search' ); ?><span class="screen-reader-text">جستجو در پکیج‌ها با نام، شناسه یا محصولات داخل آن</span><input type="search" name="gpl_search" value="<?php echo esc_attr( $context['search'] ); ?>" placeholder="جستجو در نام پکیج، شناسه یا محصولات داخل آن..." autocomplete="off"></label><label class="gpl__sort"><span class="screen-reader-text">مرتب‌سازی پکیج‌ها</span><select name="gpl_order"><?php foreach ( array( 'latest' => 'جدیدترین', 'oldest' => 'قدیمی‌ترین', 'price_asc' => 'ارزان‌ترین', 'price_desc' => 'گران‌ترین', 'name' => 'نام پکیج' ) as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"<?php selected( $context['order'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><?php echo genosys_package_list_icon( 'chevron' ); ?></label><button type="submit" class="gpl__search-submit screen-reader-text">جستجو</button></form>
				<p class="gpl__results"><?php echo esc_html( 'نمایش ' . genosys_persian_number( count( $context['products'] ) ) . ' از ' . genosys_persian_number( $context['filtered_total'] ) . ' پکیج' ); ?></p>
			</div>
			<form class="gpl__bulk-bar" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-package-bulk data-package-confirm="پکیج‌های انتخاب‌شده به زباله‌دان منتقل شوند؟" hidden><input type="hidden" name="action" value="genosys_package_list_action"><input type="hidden" name="operation" value="bulk_trash"><?php wp_nonce_field( 'genosys_package_list_action_bulk_trash_0', '_wpnonce', false ); genosys_package_list_return_fields( $context ); ?><span><b data-package-selected-count>۰</b> پکیج انتخاب شده</span><span data-package-selected-inputs></span><button type="submit"><?php echo genosys_package_list_icon( 'trash' ); ?>انتقال به زباله‌دان</button></form>
			<div class="gpl__table-wrap" role="region" aria-label="جدول پکیج‌ها" tabindex="0"><table class="gpl__table"><thead><tr><th class="gpl__select-col"><input type="checkbox" class="gpl__checkbox" data-package-select-all aria-label="انتخاب همه پکیج‌های این صفحه"></th><th class="gpl__package-col" scope="col">پکیج</th><th class="gpl__contents-col" scope="col">محتوای پکیج</th><th class="gpl__price-col" scope="col">قیمت</th><th class="gpl__stock-col" scope="col">موجودی</th><th class="gpl__actions-col" scope="col">عملیات</th></tr></thead><tbody>
				<?php foreach ( $context['products'] as $product ) { genosys_package_list_row( $product, $context ); } ?>
				<?php if ( ! $context['products'] ) : ?><tr><td class="gpl__empty" colspan="6"><?php echo esc_html( 'trash' === $context['status'] ? 'پکیجی در زباله‌دان نیست.' : ( $context['search'] ? 'پکیجی با این جستجو پیدا نشد.' : 'پکیجی با این وضعیت پیدا نشد.' ) ); ?></td></tr><?php endif; ?>
			</tbody></table></div>
			<?php if ( $context['pages'] > 1 ) : ?><nav class="gpl__pagination" aria-label="صفحه‌بندی پکیج‌ها">
				<?php if ( $context['page'] > 1 ) : ?><a class="gpl__page" href="<?php echo esc_url( genosys_package_list_url( array( 'gpl_page' => $context['page'] - 1 ) ) ); ?>">قبلی</a><?php else : ?><span class="gpl__page is-disabled" aria-disabled="true">قبلی</span><?php endif; ?>
				<?php $visible_pages = array_unique( array_merge( array( 1 ), range( max( 1, $context['page'] - 2 ), min( $context['pages'], $context['page'] + 2 ) ), array( $context['pages'] ) ) ); $last = 0; foreach ( $visible_pages as $page ) : ?><?php if ( $last && $page > $last + 1 ) : ?><span class="gpl__page-gap" aria-hidden="true">…</span><?php endif; ?><a class="gpl__page<?php echo $page === $context['page'] ? ' is-current' : ''; ?>" href="<?php echo esc_url( genosys_package_list_url( array( 'gpl_page' => $page ) ) ); ?>"<?php if ( $page === $context['page'] ) : ?> aria-current="page"<?php endif; ?>><?php echo esc_html( genosys_persian_number( $page ) ); ?></a><?php $last = $page; endforeach; ?>
				<?php if ( $context['page'] < $context['pages'] ) : ?><a class="gpl__page" href="<?php echo esc_url( genosys_package_list_url( array( 'gpl_page' => $context['page'] + 1 ) ) ); ?>">بعدی</a><?php else : ?><span class="gpl__page is-disabled" aria-disabled="true">بعدی</span><?php endif; ?>
			</nav><?php endif; ?>
		</section>
	</div>
	<?php
}
