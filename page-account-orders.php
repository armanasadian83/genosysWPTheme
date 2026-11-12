<?php
/** Authorized WooCommerce orders; only the orders endpoint uses this view. */
defined( 'ABSPATH' ) || exit;

$context = genosys_customer_orders_context();
$has_access = genosys_order_access_has_proof();
$show_lookup = ! is_user_logged_in() || ! genosys_sms_normalize_phone( (string) get_user_meta( get_current_user_id(), 'genosys_phone', true ) );
$access_config = $show_lookup ? genosys_order_access_client_config() : null;
$digits  = 'genosys_customer_orders_digits';
$account_url = wc_get_page_permalink( 'myaccount' );
$shop_url = wc_get_page_id( 'shop' ) > 0 ? wc_get_page_permalink( 'shop' ) : home_url( '/products/' );
$icon = static function ( $name ) {
	$paths = array(
		'bag' => '<path d="M5 8h14l-1.2 12H6.2L5 8Z"/><path d="M9 8V5a3 3 0 0 1 6 0v3"/>',
		'check' => '<path d="m5 12 4 4 10-10"/>',
		'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 3h.01"/>',
		'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
		'chevron' => '<path d="m5 9 7 7 7-7"/>',
		'upload' => '<path d="M12 16V3m-5 5 5-5 5 5M4 15v5a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-5"/>',
	);
	echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( isset( $paths[ $name ] ) ? $paths[ $name ] : '' ) . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG paths only.
};
get_header();
?>
<main id="main" class="gorders" dir="rtl" tabindex="-1" data-time-zone="<?php echo esc_attr( wp_timezone_string() ); ?>" data-time-offset="<?php echo esc_attr( wp_timezone()->getOffset( new DateTimeImmutable( 'now' ) ) / 60 ); ?>">
	<div class="gorders__container">
		<nav class="gorders__breadcrumb" aria-label="مسیر صفحه"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a><span aria-hidden="true">/</span><a href="<?php echo esc_url( $account_url ); ?>">حساب کاربری</a><span aria-hidden="true">/</span><span aria-current="page">سفارشات</span></nav>
		<header class="gorders__title-row"><h1 class="gorders__title"><?php echo esc_html( genosys_site_setting( 'orders_title', 'سفارشات من' ) ); ?></h1><?php if ( $has_access ) : ?><span class="gorders__count"><?php echo esc_html( $digits( number_format_i18n( $context['total'] ) ) ); ?> سفارش</span><?php endif; ?></header>
		<?php if ( $show_lookup ) : ?>
			<details class="gorders-access" data-orders-access data-orders-url="<?php echo esc_url( genosys_customer_orders_url() ); ?>"<?php if ( ! $has_access || ! $context['total'] ) : ?> open<?php endif; ?>>
				<summary><span class="gorders-access__icon"><?php $icon( 'bag' ); ?></span><span><?php echo esc_html( $has_access && $context['total'] ? 'مشاهده سفارش‌های دیگر' : 'مشاهده سفارش‌های مهمان' ); ?></span><?php $icon( 'chevron' ); ?></summary>
				<div class="gorders-access__body">
					<p>شماره موبایلی را که هنگام ثبت سفارش وارد کرده‌اید بنویسید. با تأیید کد پیامکی، سفارش‌های مهمان شما نمایش داده می‌شوند.</p>
					<form class="gorders-access__form" data-orders-access-form novalidate>
						<div data-access-phone-step><label for="gorders-access-phone">شماره موبایل</label><input id="gorders-access-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="09123456789" required data-access-phone></div>
						<div data-access-code-step hidden><label for="gorders-access-code">کد تأیید پیامکی</label><input id="gorders-access-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" dir="ltr" maxlength="6" data-access-code disabled><small data-access-sent></small></div>
						<button type="submit" class="gorders-access__submit" data-access-submit>دریافت کد تأیید</button>
						<div class="gorders-access__actions" data-access-actions hidden><button type="button" data-access-change>تغییر شماره</button><button type="button" data-access-resend disabled>ارسال دوباره کد</button></div>
						<p class="gorders-access__message" role="status" aria-live="polite" data-access-message hidden></p>
					</form>
					<?php if ( ! is_user_logged_in() && $has_access ) : ?><button type="button" class="gorders-access__clear" data-access-clear>پایان مشاهده سفارش‌ها</button><?php endif; ?>
					<noscript><p>برای دریافت کد تأیید، جاوااسکریپت مرورگر را فعال کنید.</p></noscript>
					<script type="application/json" data-orders-access-config><?php echo wp_json_encode( $access_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe script-context JSON. ?></script>
				</div>
			</details>
		<?php endif; ?>
		<?php if ( $has_access ) : ?>
			<?php if ( $context['missingCount'] ) : ?>
				<div class="gorders__notice" role="status">
					<div class="gorders__notice-copy"><?php $icon( 'alert' ); ?><strong><?php echo esc_html( $digits( $context['missingCount'] ) ); ?> سفارش شما منتظر بارگذاری فیش واریزی است.</strong></div>
					<a class="gorders__notice-button" href="<?php echo esc_url( $context['missingOrderURL'] ); ?>" data-missing-receipts>مشاهده</a>
				</div>
			<?php endif; ?>
			<nav class="gorders__filters" aria-label="فیلتر وضعیت سفارشات">
				<?php foreach ( $context['filters'] as $key => $filter ) : ?>
					<a class="gorders__filter<?php echo $context['filter'] === $key ? ' is-active' : ''; ?>" href="<?php echo esc_url( $filter['url'] ); ?>"<?php if ( $context['filter'] === $key ) : ?> aria-current="page"<?php endif; ?>><span><?php echo esc_html( $filter['label'] ); ?></span><span class="gorders__filter-count"><?php echo esc_html( $digits( number_format_i18n( $filter['count'] ) ) ); ?></span></a>
				<?php endforeach; ?>
			</nav>
			<div class="gorders__list">
				<?php foreach ( $context['orders'] as $order ) : ?>
					<?php
					$view = genosys_customer_order_presentation( $order );
					if ( ! $view ) { continue; }
					$order_id = $order->get_id();
					$details_id = 'gorder-details-' . $order_id;
					$receipt = $view['receipt'];
					$receipt_review = $receipt ? $receipt['reviewStatus'] : '';
					$receipt_rejected = $receipt && 'rejected' === $receipt_review;
					$receipt_state = $receipt ? ( $receipt_rejected ? 'rejected' : 'saved' ) : ( $view['receiptEligible'] ? 'missing' : 'none' );
					$receipt_can_upload = $view['receiptEligible'] && ( ! $receipt || $receipt_rejected );
					$receipt_locked = $receipt && ! $receipt_rejected;
					$receipt_icon = $receipt ? ( 'approved' === $receipt_review ? 'check' : ( 'rejected' === $receipt_review ? 'close' : 'clock' ) ) : 'alert';
					$payment_card = '';
					$payment_holder = '';
					if ( $receipt_can_upload && 'genosys_card_to_card' === $order->get_payment_method() ) {
						$has_snapshot = $order->meta_exists( '_genosys_payment_card' ) || $order->meta_exists( '_genosys_payment_holder' );
						$payment_card = genosys_checkout_card_digits( $has_snapshot ? $order->get_meta( '_genosys_payment_card', true ) : get_option( 'genosys_checkout_card_number', '' ) );
						$payment_holder = trim( (string) ( $has_snapshot ? $order->get_meta( '_genosys_payment_holder', true ) : get_option( 'genosys_checkout_card_holder', '' ) ) );
					}
					$tax_included = 'incl' === get_option( 'woocommerce_tax_display_cart', 'excl' );
					$recipient = trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() );
					if ( '' === $recipient ) { $recipient = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); }
					$phone = method_exists( $order, 'get_shipping_phone' ) ? $order->get_shipping_phone() : '';
					if ( '' === $phone ) { $phone = $order->get_billing_phone(); }
					$address_type = $order->get_shipping_address_1() ? 'shipping' : 'billing';
					$address_data = $order->get_address( $address_type );
					$city = isset( $address_data['city'] ) ? $address_data['city'] : '';
					$state = isset( $address_data['state'] ) ? $address_data['state'] : '';
					$states = WC()->countries->get_states( isset( $address_data['country'] ) ? $address_data['country'] : '' );
					if ( is_array( $states ) && isset( $states[ $state ] ) ) { $state = $states[ $state ]; }
					$address = implode( '، ', array_filter( array( $city, $state !== $city ? $state : '', isset( $address_data['address_1'] ) ? $address_data['address_1'] : '', isset( $address_data['address_2'] ) ? $address_data['address_2'] : '' ) ) );
					$shipping_name = $order->get_shipping_method();
					$tipax = false;
					foreach ( $order->get_shipping_methods() as $shipping_item ) {
						$tipax = $tipax || 'genosys_tipax' === $shipping_item->get_method_id();
					}
					if ( $tipax ) { $shipping_name .= ' (پس‌کرایه)'; }
					?>
					<article class="gorder" id="gco-order-<?php echo esc_attr( $order_id ); ?>" data-order-id="<?php echo esc_attr( $order_id ); ?>" data-status="<?php echo esc_attr( $view['group'] ); ?>" data-receipt-state="<?php echo esc_attr( $receipt_state ); ?>">
						<div class="gorder__summary">
							<div class="gorder__heading"><span class="gorder__icon"><?php $icon( 'bag' ); ?></span><div class="gorder__identity"><h2>سفارش <bdi>#<?php echo esc_html( $digits( $order->get_order_number() ) ); ?></bdi></h2><time datetime="<?php echo esc_attr( $view['dateISO'] ); ?>" data-orders-date><?php echo esc_html( $view['dateText'] ); ?></time></div></div>
							<div class="gorder__total"><span>مبلغ کل</span><strong><?php echo wp_kses_post( genosys_customer_orders_price( $order->get_total(), $order->get_currency() ) ); ?></strong></div>
						</div>
						<div class="gorder__toolbar">
							<div class="gorder__badges"><span class="gorder__status gorder__status--<?php echo esc_attr( in_array( $view['group'], array( 'pending', 'review', 'preparing', 'shipped', 'delivered', 'cancelled' ), true ) ? $view['group'] : 'other' ); ?>"><span class="gorder__status-dot" aria-hidden="true"></span><?php echo esc_html( $view['label'] ); ?></span><span class="gorder__receipt-badge gorder__receipt-badge--<?php echo esc_attr( $receipt_state ); ?><?php if ( $receipt ) { echo ' gorder__receipt-badge--' . esc_attr( $receipt_review ); } ?>"<?php if ( $receipt ) : ?> data-receipt-review-status="<?php echo esc_attr( $receipt_review ); ?>"<?php endif; ?>><?php if ( 'none' !== $receipt_state ) { $icon( $receipt_icon ); } ?><?php echo esc_html( $receipt ? 'فیش: ' . $receipt['reviewLabel'] : ( $view['receiptEligible'] ? 'فیش بارگذاری نشده' : 'بدون فیش' ) ); ?></span></div>
							<button type="button" class="gorder__toggle" aria-expanded="true" aria-controls="<?php echo esc_attr( $details_id ); ?>"><span data-toggle-label>بستن جزئیات</span><?php $icon( 'chevron' ); ?></button>
						</div>
						<div class="gorder__details" id="<?php echo esc_attr( $details_id ); ?>">
							<section class="gorder__section"><h3>وضعیت سفارش</h3>
								<?php if ( 0 === $view['stage'] ) : ?><p class="gorder__status-note"><?php echo esc_html( $view['label'] ); ?></p><?php endif; ?>
								<ol class="gorder__timeline" aria-label="مراحل سفارش">
									<?php foreach ( array( 1 => 'ثبت سفارش', 2 => 'تأیید پرداخت', 3 => 'آماده‌سازی', 4 => 'ارسال', 5 => 'تحویل' ) as $stage => $label ) : ?>
										<?php $done = $stage < $view['stage'] || ( 5 === $view['stage'] && 5 === $stage ); ?>
										<li class="gorder__step<?php echo $done ? ' gorder__step--done' : ( $stage === $view['stage'] ? ' gorder__step--current' : '' ); ?>"<?php if ( $stage === $view['stage'] ) : ?> aria-current="step"<?php endif; ?>><span class="gorder__step-number"><?php if ( $done ) { $icon( 'check' ); } else { echo esc_html( $digits( $stage ) ); } ?></span><span class="gorder__step-label"><?php echo esc_html( $label ); ?></span><?php if ( $stage === $view['stage'] && $view['stageNote'] ) : ?><small class="gorder__step-note"><?php echo esc_html( $view['stageNote'] ); ?></small><?php endif; ?></li>
									<?php endforeach; ?>
								</ol>
							</section>
							<section class="gorder__section"><h3>کالاهای سفارش</h3><div class="gorder__products">
								<?php foreach ( $order->get_items() as $item ) : ?>
									<?php $product = $item->get_product(); $product_url = $product && $product->is_visible() ? $product->get_permalink() : ''; ?>
									<div class="gorder__product"><div class="gorder__product-image"><?php echo wp_kses_post( $product ? $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy', 'alt' => $item->get_name() ) ) : wc_placeholder_img( 'woocommerce_thumbnail', array( 'alt' => $item->get_name() ) ) ); ?><span class="gorder__product-quantity"><?php echo esc_html( $digits( $item->get_quantity() ) ); ?></span></div><div class="gorder__product-copy"><h4><?php if ( $product_url ) : ?><a href="<?php echo esc_url( $product_url ); ?>"><?php endif; ?><?php echo esc_html( $item->get_name() ); ?><?php if ( $product_url ) : ?></a><?php endif; ?></h4><span class="gorder__product-unit"><?php echo wp_kses_post( genosys_customer_orders_price( $order->get_item_total( $item, $tax_included ), $order->get_currency() ) ); ?></span><?php wc_display_item_meta( $item ); ?></div><strong class="gorder__product-total"><?php echo wp_kses_post( genosys_customer_orders_price( $order->get_line_total( $item, $tax_included ), $order->get_currency() ) ); ?></strong></div>
								<?php endforeach; ?>
							</div></section>
							<section class="gorder__section"><h3>اطلاعات ارسال</h3><div class="gorder__info-grid">
								<div class="gorder__info"><span class="gorder__info-label">گیرنده</span><span class="gorder__info-value"><?php echo esc_html( $recipient ? $recipient : '—' ); ?></span></div>
								<div class="gorder__info"><span class="gorder__info-label">شماره تماس</span><span class="gorder__info-value"><bdi dir="ltr"><?php echo esc_html( $phone ? $digits( $phone ) : '—' ); ?></bdi></span></div>
								<div class="gorder__info gorder__info--wide"><span class="gorder__info-label">آدرس</span><span class="gorder__info-value"><?php echo esc_html( $address ? $digits( $address ) : '—' ); ?></span></div>
								<div class="gorder__info"><span class="gorder__info-label">روش ارسال</span><span class="gorder__info-value"><?php echo esc_html( $shipping_name ? $shipping_name : '—' ); ?></span></div>
								<div class="gorder__info"><span class="gorder__info-label">روش پرداخت</span><span class="gorder__info-value"><?php echo esc_html( $order->get_payment_method_title() ? $order->get_payment_method_title() : '—' ); ?></span></div>
							</div></section>
							<section class="gorder__section"><h3>فیش واریزی</h3>
								<?php if ( $receipt_rejected ) : ?>
									<div class="gorder__receipt-rejected-notice" role="status" data-orders-rejected-notice>
										<span class="gorder__receipt-rejected-icon"><?php $icon( 'alert' ); ?></span><div><strong>فیش قبلی رد شده است؛ لطفاً فیش جدید را بارگذاری کنید.</strong><p><a href="<?php echo esc_url( $receipt['downloadUrl'] ); ?>" dir="auto"><?php echo esc_html( $receipt['name'] ); ?></a> · <time datetime="<?php echo esc_attr( $receipt['uploadedAt'] ); ?>" data-orders-date><?php echo esc_html( $digits( wp_date( 'j F Y، ساعت H:i', strtotime( $receipt['uploadedAt'] ) ) ) ); ?></time></p></div>
									</div>
								<?php endif; ?>
								<?php if ( 16 === strlen( $payment_card ) && '' !== $payment_holder ) : ?>
									<div class="gorder__deposit-card" aria-label="اطلاعات کارت برای واریز">
										<p class="gorder__deposit-card-note">مبلغ سفارش را به کارت زیر واریز کنید و سپس فیش را بارگذاری کنید.</p>
										<div class="gorder__deposit-card-fields"><div><span class="gorder__deposit-card-label">شماره کارت</span><bdi class="gorder__deposit-card-number" dir="ltr"><?php echo esc_html( trim( chunk_split( $payment_card, 4, ' ' ) ) ); ?></bdi></div><div><span class="gorder__deposit-card-label">نام و نام خانوادگی صاحب کارت</span><strong class="gorder__deposit-card-holder"><?php echo esc_html( $payment_holder ); ?></strong></div></div>
									</div>
								<?php endif; ?>
								<div class="gorder__receipt" data-orders-receipt data-state="<?php echo $receipt_locked ? 'saved' : 'idle'; ?>"<?php if ( $receipt ) : ?> data-review-status="<?php echo esc_attr( $receipt_review ); ?>"<?php endif; ?>>
									<span class="gorder__receipt-icon"><?php $icon( $receipt_locked ? $receipt_icon : 'upload' ); ?></span>
									<div class="gorder__receipt-copy"><h4><?php echo esc_html( $receipt_locked ? 'وضعیت فیش: ' . $receipt['reviewLabel'] : ( $receipt_can_upload ? ( $receipt_rejected ? 'فیش جدید را بارگذاری کنید' : 'فیش واریزی را بارگذاری کنید' ) : 'فیش واریزی ثبت نشده است' ) ); ?></h4>
										<?php if ( $receipt_locked ) : ?><p><a href="<?php echo esc_url( $receipt['downloadUrl'] ); ?>" dir="auto"><?php echo esc_html( $receipt['name'] ); ?></a> · <time datetime="<?php echo esc_attr( $receipt['uploadedAt'] ); ?>" data-orders-date><?php echo esc_html( $digits( wp_date( 'j F Y، ساعت H:i', strtotime( $receipt['uploadedAt'] ) ) ) ); ?></time></p><?php else : ?><p><?php echo esc_html( $receipt_can_upload ? 'JPG · PNG · WebP · PDF — حداکثر ۵ مگابایت' : ( 'genosys_card_to_card' === $order->get_payment_method() ? 'امکان بارگذاری فیش برای این سفارش وجود ندارد.' : 'برای این روش پرداخت نیازی به بارگذاری فیش نیست.' ) ); ?></p><?php endif; ?>
									</div>
									<?php if ( $receipt_can_upload && $view['receiptConfig'] ) : ?>
										<button type="button" class="gorder__receipt-picker" data-orders-picker>انتخاب فایل</button><input type="file" accept="image/jpeg,image/png,image/webp,application/pdf,.jpg,.jpeg,.png,.webp,.pdf" data-orders-file hidden aria-label="انتخاب فیش واریزی">
										<script type="application/json" data-orders-receipt-config><?php echo wp_json_encode( $view['receiptConfig'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX safely encodes script context. ?></script>
									<?php endif; ?>
									<p class="gorder__receipt-error" role="alert" data-orders-error hidden></p>
								</div>
							</section>
							<section class="gorder__section"><h3>خلاصه‌ی مبلغ</h3><div class="gorder__amount-summary">
								<?php foreach ( $order->get_order_item_totals() as $key => $row ) : ?>
									<?php if ( 'payment_method' === $key ) { continue; } ?>
									<div class="gorder__amount-row<?php echo 'order_total' === $key ? ' gorder__amount-row--total' : ''; ?>"><span><?php echo esc_html( 'cart_subtotal' === $key ? 'جمع کالاها' : ( 'shipping' === $key ? 'هزینه‌ی ارسال' : ( 'order_total' === $key ? 'مبلغ کل' : rtrim( wp_strip_all_tags( $row['label'] ), ':：' ) ) ) ); ?></span><strong><?php echo 'shipping' === $key && $tipax && 0.0 === (float) $order->get_shipping_total() ? 'پس‌کرایه' : wp_kses_post( genosys_checkout_localize_html( $row['value'] ) ); ?></strong></div>
								<?php endforeach; ?>
							</div></section>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if ( ! $context['orders'] ) : ?><div class="gorders__empty"><span class="gorders__empty-icon"><?php $icon( 'bag' ); ?></span><h2><?php echo esc_html( $context['total'] ? 'سفارشی با این وضعیت پیدا نشد' : 'هنوز سفارشی ثبت نکرده‌اید' ); ?></h2><a class="gorders__primary-button" href="<?php echo esc_url( $context['total'] ? genosys_customer_orders_url() : $shop_url ); ?>"><?php echo esc_html( $context['total'] ? 'مشاهده همه سفارشات' : 'مشاهده محصولات' ); ?></a></div><?php endif; ?>
			<?php if ( $context['pages'] > 1 ) : ?><nav class="gorders__pagination" aria-label="صفحه‌بندی سفارشات"><?php for ( $page_number = 1; $page_number <= $context['pages']; $page_number++ ) : ?><a href="<?php echo esc_url( genosys_customer_orders_url( $context['filter'], $page_number ) ); ?>"<?php if ( $context['currentPage'] === $page_number ) : ?> aria-current="page"<?php endif; ?>><?php echo esc_html( $digits( $page_number ) ); ?></a><?php endfor; ?></nav><?php endif; ?>
		<?php endif; ?>
	</div>
</main>
<?php get_footer(); ?>
