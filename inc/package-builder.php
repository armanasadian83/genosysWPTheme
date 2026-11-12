<?php
/** Package editing with WordPress media and a protected WooCommerce component catalog. */
defined( 'ABSPATH' ) || exit;

function genosys_package_builder_menu() {
	if ( function_exists( 'wc_get_product' ) ) {
		add_submenu_page( 'woocommerce', 'افزودن پکیج', 'افزودن پکیج', 'manage_woocommerce', 'genosys-add-package', 'genosys_package_builder_page' );
	}
}
add_action( 'admin_menu', 'genosys_package_builder_menu', 20 );

function genosys_package_builder_decimals() {
	return max( 0, min( 6, (int) wc_get_price_decimals() ) );
}

function genosys_package_builder_current() {
	$id = isset( $_GET['package_id'] ) && is_scalar( $_GET['package_id'] ) ? absint( $_GET['package_id'] ) : 0; // Read-only authenticated edit selection.
	$product = $id ? wc_get_product( $id ) : null;
	return $product && genosys_is_package_product( $product ) && current_user_can( 'edit_post', $id ) ? $product : null;
}

function genosys_package_builder_assets( $hook ) {
	if ( 'woocommerce_page_genosys-add-package' !== $hook ) { return; }
	wp_enqueue_media();
	$editing = genosys_package_builder_current();
	wp_enqueue_style( 'genosys-package-builder', get_theme_file_uri( '/assets/css/package-builder.css' ), array(), '1.5.55' );
	wp_enqueue_script( 'genosys-package-builder', get_theme_file_uri( '/assets/js/package-builder.js' ), array( 'media-views' ), '1.5.55', true );
	wp_localize_script( 'genosys-package-builder', 'genosysPackageBuilder', array(
		'endpoint' => admin_url( 'admin-ajax.php' ), 'action' => 'genosys_package_products',
		'nonce' => wp_create_nonce( 'genosys_package_catalog' ),
		'saveAction' => 'genosys_package_save', 'saveNonce' => wp_create_nonce( 'genosys_package_save' ),
		'packageId' => $editing ? $editing->get_id() : 0, 'package' => $editing ? genosys_package_builder_data( $editing ) : null,
		'readOnly' => ! current_user_can( 'edit_products' ),
		'currencySymbol' => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
		'priceDecimals' => genosys_package_builder_decimals(),
		'decimalSeparator' => wc_get_price_decimal_separator(), 'thousandSeparator' => wc_get_price_thousand_separator(),
		'slugBase' => home_url( '/' . trim( wc_get_permalink_structure()['product_rewrite_slug'], '/' ) . '/' ), 'maxItems' => 24,
	) );
}
add_action( 'admin_enqueue_scripts', 'genosys_package_builder_assets' );

function genosys_package_builder_icon( $name ) {
	$paths = array(
		'chevron' => '<path d="m6 9 6 6 6-6"/>',
		'upload' => '<path d="M12 16V3m-5 5 5-5 5 5M4 15v5a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-5"/>',
		'image' => '<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8" cy="8" r="2"/><path d="m3 17 5-5 4 4 4-7 5 8"/>',
		'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>',
		'check' => '<path d="m6 12 4 4 8-8"/>',
		'bottle' => '<path d="M9 3h6m-5 0v5l-4 5v7a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-7l-4-5V3"/>',
		'list' => '<path d="M10 6h11M10 12h11M10 18h11M4 6h.01M4 12h.01M4 18h.01"/>',
		'ordered' => '<path d="M10 6h11M10 12h11M10 18h11M3 4h2v5M3 12h3v2l-3 3h3M3 20h3"/>',
	);
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $name ] ?? $paths['image'] ) . '</svg>';
}

/** Published physical products and variations; no package, attachment, stock, or option writes. */
function genosys_package_catalog_payload( $product ) {
	if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_type( array( 'simple', 'variation' ) ) ) { return null; }
	$parent = $product->is_type( 'variation' ) ? wc_get_product( $product->get_parent_id() ) : null;
	if ( $product->is_type( 'variation' ) && ( ! $parent || ! $parent->is_type( 'variable' ) || 'publish' !== $parent->get_status() ) ) { return null; }
	$name = $product->get_name();
	if ( $parent ) { $name = $parent->get_name() . ' — ' . wc_get_formatted_variation( $product, true, true, false ); }
	$name = html_entity_decode( wp_strip_all_tags( $name ), ENT_QUOTES, 'UTF-8' );
	$pool_id = (int) $product->get_stock_managed_by_id();
	$pool = $pool_id === $product->get_id() ? $product : wc_get_product( $pool_id );
	$managed = $pool && $pool->managing_stock();
	$quantity = $managed ? $pool->get_stock_quantity() : null;
	$quantity = is_numeric( $quantity ) ? max( 0, (float) $quantity ) : null;
	$status = $product->get_stock_status();
	$state = 'outofstock' === $status ? 'outofstock' : ( null === $quantity ? 'unmanaged' : ( $quantity <= 5 ? 'low' : 'available' ) );
	$stock_label = null === $quantity ? ( $managed ? 'تعداد نامشخص' : 'بدون مدیریت تعداد' ) : genosys_persian_number( (string) $quantity ) . ' عدد';
	if ( 'outofstock' === $status ) { $stock_label = 'ناموجود'; }
	elseif ( 'onbackorder' === $status || ( $product->backorders_allowed() && null !== $quantity && $quantity <= 0 ) ) {
		$state = 'backorder'; $stock_label = ( null === $quantity ? '' : genosys_persian_number( (string) $quantity ) . ' عدد · ' ) . 'پیش‌فروش';
	}
	$price = '' === $product->get_price() ? null : (float) genosys_package_display_price( $product->get_price( 'edit' ), $product );
	$image = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : '';
	return array(
		'id' => $product->get_id(), 'name' => $name, 'sku' => $product->get_sku(),
		'price' => $price, 'price_minor' => null === $price ? null : (int) round( $price * ( 10 ** genosys_package_builder_decimals() ) ),
		'image' => $image ?: '', 'stock_quantity' => $quantity, 'stock_pool_id' => $pool_id,
		'stock_status' => $status, 'stock_label' => $stock_label, 'stock_state' => $state, 'backorders' => $product->backorders_allowed(),
	);
}

function genosys_package_catalog_ajax() {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'wc_get_product' ) ) {
		wp_send_json_error( array( 'message' => 'اجازه مشاهده این فهرست را ندارید.' ), 403 );
	}
	if ( ! check_ajax_referer( 'genosys_package_catalog', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'صفحه را تازه کنید و دوباره تلاش کنید.' ), 403 );
	}
	global $wpdb;
	$raw = isset( $_GET['q'] ) && is_string( $_GET['q'] ) ? wp_unslash( $_GET['q'] ) : '';
	$query = trim( preg_replace( '/\s+/u', ' ', strtr( sanitize_text_field( $raw ), genosys_product_search_character_map() ) ) );
	$query = function_exists( 'mb_substr' ) ? mb_substr( $query, 0, 160, 'UTF-8' ) : wp_html_excerpt( $query, 160, '' );
	$page = isset( $_GET['page'] ) && is_scalar( $_GET['page'] ) ? max( 1, min( 10000, absint( $_GET['page'] ) ) ) : 1;
	$lookup = $wpdb->prefix . 'wc_product_meta_lookup';
	$where = "p.post_status = 'publish' AND ((p.post_type = 'product' AND NOT EXISTS (
		SELECT 1 FROM {$wpdb->term_relationships} rel INNER JOIN {$wpdb->term_taxonomy} tax ON tax.term_taxonomy_id = rel.term_taxonomy_id
		INNER JOIN {$wpdb->terms} term ON term.term_id = tax.term_id
		WHERE rel.object_id = p.ID AND tax.taxonomy = 'product_type' AND term.slug <> 'simple'
	)) OR (p.post_type = 'product_variation' AND parent.post_type = 'product' AND parent.post_status = 'publish'))";
	if ( '' !== $query ) {
		$words = preg_split( '/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY ); $titles = array();
		foreach ( $words as $word ) {
			$title = genosys_product_search_normalized_sql( 'p.post_title', $word );
			$parent_title = genosys_product_search_normalized_sql( 'parent.post_title', $word );
			$like = '%' . $wpdb->esc_like( $word ) . '%';
			$titles[] = $wpdb->prepare( "({$title} LIKE %s OR {$parent_title} LIKE %s)", $like, $like );
		}
		$sku = genosys_product_search_normalized_sql( 'catalog.sku', $query );
		$where .= ' AND ((' . implode( ' AND ', $titles ) . ') OR ' . $wpdb->prepare( "{$sku} LIKE %s", '%' . $wpdb->esc_like( $query ) . '%' ) . ')';
	}
	$sql = $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->posts} parent ON parent.ID = p.post_parent
		LEFT JOIN {$lookup} catalog ON catalog.product_id = p.ID WHERE {$where} ORDER BY p.post_title ASC, p.ID ASC LIMIT %d OFFSET %d", 21, ( $page - 1 ) * 20 );
	$ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query clauses and pagination are prepared; identifiers are internal.
	$products = array();
	foreach ( array_slice( $ids, 0, 20 ) as $id ) {
		$product = genosys_package_catalog_payload( wc_get_product( $id ) );
		if ( $product ) { $products[] = $product; }
	}
	wp_send_json_success( array( 'products' => $products, 'page' => $page, 'has_more' => count( $ids ) > 20 ) );
}
add_action( 'wp_ajax_genosys_package_products', 'genosys_package_catalog_ajax' );
add_action( 'wp_ajax_nopriv_genosys_package_products', 'genosys_package_catalog_ajax' );

function genosys_package_builder_row( $index ) {
	$id = 'gpk-product-' . sanitize_key( (string) $index );
	$number = is_numeric( $index ) ? genosys_persian_number( (string) ( (int) $index + 1 ) ) : '';
	?>
	<div class="gpk__item" data-package-row>
		<span class="gpk__number" data-row-number><?php echo esc_html( $number ); ?></span>
		<div class="gpk__picker" data-picker>
			<button type="button" class="gpk__picker-trigger" data-picker-trigger role="combobox" aria-haspopup="listbox" aria-expanded="false" aria-controls="<?php echo esc_attr( $id . '-options' ); ?>" aria-label="انتخاب محصول <?php echo esc_attr( $number ); ?>">
				<span class="gpk__picker-thumb" data-picked-thumb hidden></span>
				<span class="gpk__picker-copy"><span class="gpk__picker-name" data-picked-name>یک محصول انتخاب کنید...</span><span class="gpk__picker-price" data-picked-price hidden></span></span>
				<span class="gpk__chevron"><?php echo genosys_package_builder_icon( 'chevron' ); ?></span>
			</button>
			<div class="gpk__picker-popover" data-picker-popover hidden>
				<input type="search" class="gpk__search" data-product-search placeholder="جستجوی محصول..." aria-label="جستجوی محصول با نام یا SKU" autocomplete="off">
				<div class="gpk__options" id="<?php echo esc_attr( $id . '-options' ); ?>" data-product-options role="listbox" aria-label="محصولات فروشگاه"></div>
				<p class="gpk__picker-status" data-picker-status role="status" aria-live="polite"></p>
				<button type="button" class="gpk__load-more" data-load-more hidden>نمایش محصولات بیشتر</button>
			</div>
		</div>
		<div class="gpk__stock" data-row-stock><span>موجودی</span><strong data-stock-value>—</strong></div>
		<div class="gpk__stepper"><button type="button" data-qty-step="1" aria-label="افزایش تعداد">+</button><input class="gpk__qty" data-item-qty type="text" inputmode="numeric" value="1" aria-label="تعداد محصول" maxlength="4"><button type="button" data-qty-step="-1" aria-label="کاهش تعداد">−</button></div>
		<button type="button" class="gpk__row-remove" data-remove-product aria-label="حذف محصول از پکیج" hidden>×</button>
	</div>
	<?php
}

function genosys_package_builder_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'wc_get_product' ) ) { wp_die( 'اجازه دسترسی به این صفحه را ندارید.', '', array( 'response' => 403 ) ); }
	$editing = genosys_package_builder_current();
	if ( ! empty( $_GET['package_id'] ) && ! $editing ) { wp_die( 'پکیج برای ویرایش در دسترس نیست.', '', array( 'response' => 403 ) ); }
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) { $terms = array(); }
	$currency = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
	?>
	<div class="wrap gpk" dir="rtl" data-package-builder>
		<div class="gpk__header"><div><p class="gpk__breadcrumb"><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&product_type=genosys_package' ) ); ?>">پکیج‌ها</a> / <?php echo $editing ? 'ویرایش پکیج' : 'افزودن پکیج'; ?></p><h1><?php echo $editing ? 'ویرایش پکیج' : 'افزودن پکیج جدید'; ?></h1></div><span class="gpk__draft" data-package-status-label>پیش‌نویس</span></div>
		<div class="gpk__layout">
			<div class="gpk__main">
				<section class="gpk__card">
					<label class="gpk__label" for="gpk-name">نام پکیج <em>*</em></label><input id="gpk-name" class="gpk__name" data-package-name type="text" maxlength="200" placeholder="مثلاً: پکیج جوانسازی و درخشندگی پوست" autocomplete="off">
					<p class="gpk__permalink">پیوند یکتا: <span dir="ltr" data-package-slug><?php echo esc_html( home_url( '/package/…' ) ); ?></span></p>
					<label class="gpk__label" id="gpk-description-label">توضیحات پکیج</label><div class="gpk__editor"><div class="gpk__toolbar" role="toolbar" aria-label="قالب‌بندی توضیحات"><button type="button" data-format="bold" aria-label="پررنگ"><b>B</b></button><button type="button" data-format="italic" aria-label="مورب"><i>I</i></button><button type="button" data-format="insertUnorderedList" aria-label="فهرست نشانه‌دار"><?php echo genosys_package_builder_icon( 'list' ); ?></button><button type="button" data-format="insertOrderedList" aria-label="فهرست شماره‌دار"><?php echo genosys_package_builder_icon( 'ordered' ); ?></button></div><div class="gpk__editor-body" data-package-editor contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="gpk-description-label" data-placeholder="توضیح کوتاهی درباره‌ی پکیج و کاربرد محصولات داخل آن بنویسید..."></div></div>
				</section>
				<section class="gpk__card">
					<h2 class="gpk__card-heading">محتوای پکیج</h2><p>حداقل دو محصول متفاوت را از میان محصولات فعلی فروشگاه انتخاب کنید. موجودی هر محصول جلوی آن نمایش داده می‌شود.</p>
					<div class="gpk__items" data-package-items><?php genosys_package_builder_row( 0 ); genosys_package_builder_row( 1 ); ?></div>
					<template data-package-row-template><?php genosys_package_builder_row( '__INDEX__' ); ?></template>
					<button class="gpk__add" data-add-product type="button">+ افزودن محصول دیگر</button>
					<div class="gpk__metrics"><div class="gpk__metric"><span>مجموع قیمت محصولات</span><strong data-total-price>—</strong></div><div class="gpk__metric"><span>موجودی پکیج (محاسبه‌شده)</span><strong data-package-stock>—</strong></div><div class="gpk__metric"><span>تعداد محصولات</span><strong data-product-count>—</strong></div></div>
					<p>موجودی پکیج خودکار از موجودی محصولات محاسبه می‌شود. با هر فروش، از موجودی تک‌تک محصولات کم می‌شود.</p>
				</section>
				<section class="gpk__card">
					<h2 class="gpk__card-heading">قیمت‌گذاری</h2><p>قیمت نهایی پکیج را تعیین کنید؛ مجموع قیمت محصولات به‌عنوان قیمت قبل از تخفیف نمایش داده می‌شود.</p>
					<div class="gpk__pricing-grid"><div><label class="gpk__label" for="gpk-price">قیمت پکیج <em>*</em></label><div class="gpk__price-field"><input id="gpk-price" data-package-price type="text" inputmode="decimal" placeholder="0" autocomplete="off"><span><?php echo esc_html( $currency ); ?></span></div></div><div><label class="gpk__label" for="gpk-sku">شناسه‌ی پکیج (SKU)</label><input id="gpk-sku" data-package-sku type="text" dir="ltr" placeholder="PKG-001" maxlength="100" autocomplete="off"></div></div>
					<div class="gpk__discounts"><button type="button" data-discount="10">پیشنهاد قیمت با ۱۰٪ تخفیف</button><button type="button" data-discount="15">۱۵٪ تخفیف</button><button type="button" data-discount="20">۲۰٪ تخفیف</button></div>
				</section>
			</div>
			<aside class="gpk__aside" aria-label="تنظیمات و پیش‌نمایش پکیج">
				<section class="gpk__card"><h2 class="gpk__card-heading">انتشار</h2><ul class="gpk__publish-checks"><?php foreach ( array( 'name' => 'نام پکیج', 'products' => 'حداقل دو محصول متفاوت', 'image' => 'تصویر پکیج', 'price' => 'قیمت پکیج' ) as $key => $label ) : ?><li class="gpk__check" data-check="<?php echo esc_attr( $key ); ?>"><span class="gpk__check-marker" aria-hidden="true"><?php echo genosys_package_builder_icon( 'check' ); ?></span><span><?php echo esc_html( $label ); ?></span></li><?php endforeach; ?></ul>
					<div class="gpk__publish-fields"><label class="gpk__label" for="gpk-status">وضعیت</label><select id="gpk-status" data-package-status><option value="publish">منتشر شده</option><option value="draft">پیش‌نویس</option><option value="pending">در انتظار بررسی</option></select><label class="gpk__label" for="gpk-visibility">نمایان بودن</label><select id="gpk-visibility" data-package-visibility><option value="public">عمومی</option><option value="private">خصوصی</option><option value="hidden">پنهان</option></select></div>
					<div class="gpk__publish-actions"><button type="button" class="gpk__button gpk__button--outline" data-package-action="draft">ذخیره پیش‌نویس</button><button type="button" class="gpk__button gpk__button--primary" data-package-action="publish">انتشار پکیج</button></div>
				</section>
				<section class="gpk__card"><h2 class="gpk__card-heading">تصویر پکیج</h2><p>یک تصویر برای کل پکیج (نسبت پیشنهادی ۴:۵).</p><input type="hidden" data-package-image value="0"><div class="gpk__upload" data-package-upload role="button" tabindex="0" aria-label="انتخاب تصویر از رسانه وردپرس"><img class="gpk__upload-preview" data-upload-preview alt="پیش‌نمایش تصویر پکیج" hidden><div class="gpk__upload-copy" data-upload-copy><span class="gpk__upload-icon"><?php echo genosys_package_builder_icon( 'upload' ); ?></span><strong>انتخاب تصویر پکیج از رسانه</strong><p>برای انتخاب یا بارگذاری تصویر کلیک کنید</p><span class="gpk__formats"><b dir="ltr">JPG · PNG · WebP</b> — حداکثر ۵ مگابایت</span></div></div><p data-image-error role="alert" hidden></p><div class="gpk__image-actions"><button type="button" class="gpk__button gpk__button--outline" data-image-change hidden>تغییر تصویر</button><button type="button" class="gpk__button gpk__button--outline" data-image-remove hidden>حذف</button></div></section>
				<section class="gpk__card"><h2 class="gpk__card-heading">دسته‌بندی</h2><p>پکیج‌ها فقط در دسته‌بندی پکیج‌های ویژه نمایش داده می‌شوند.</p><div class="gpk__categories"><?php $special = genosys_package_special_category(); ?><label><input type="checkbox" data-package-category value="<?php echo esc_attr( $special ? $special->term_id : 0 ); ?>" checked disabled><span>پکیج‌های ویژه</span></label></div></section>
				<section class="gpk__card"><h2 class="gpk__card-heading">پیش‌نمایش کارت</h2><p>این‌گونه در فروشگاه دیده می‌شود.</p><div class="gpk__preview"><div class="gpk__preview-bg"><img data-preview-image alt="تصویر پکیج" hidden></div><span class="gpk__preview-icon" data-preview-icon><?php echo genosys_package_builder_icon( 'image' ); ?></span><button type="button" class="gpk__favorite" data-preview-favorite aria-label="پسندیدن پیش‌نمایش" aria-pressed="false"><?php echo genosys_package_builder_icon( 'heart' ); ?></button><span data-preview-discount hidden></span><div class="gpk__preview-info"><h3 class="gpk__preview-name" data-preview-name>نام پکیج</h3><p class="gpk__preview-category">پکیج <span data-preview-count>۰</span> محصول</p><div class="gpk__preview-price"><del data-preview-original hidden></del><span data-preview-price>—</span></div></div></div></section>
			</aside>
		</div>
		<template data-product-icon><?php echo genosys_package_builder_icon( 'bottle' ); ?></template>
		<div class="gpk__toast" data-package-message role="status" aria-live="polite" hidden></div>
	</div>
	<?php
}
