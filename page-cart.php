<?php
/**
 * Cart page. WooCommerce owns the cart session, prices, coupons and checkout.
 *
 * @package Genosys
 */

defined( 'ABSPATH' ) || exit;

$cart      = function_exists( 'WC' ) && WC() ? WC()->cart : null;
$cart_url  = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
$shop_url  = function_exists( 'wc_get_page_permalink' ) && wc_get_page_id( 'shop' ) > 0 ? wc_get_page_permalink( 'shop' ) : home_url( '/products/' );
$cart_count = $cart ? $cart->get_cart_contents_count() : 0;
$localize_digits = static function ( $html ) {
	if ( ! function_exists( 'genosys_persian_number' ) ) {
		return $html;
	}
	$parts = function_exists( 'wp_html_split' ) ? wp_html_split( $html ) : array( $html );
	foreach ( $parts as &$part ) {
		if ( '' !== $part && '<' !== $part[0] ) {
			$part = esc_html( genosys_persian_number( html_entity_decode( $part, ENT_QUOTES, 'UTF-8' ) ) );
		}
	}
	return implode( '', $parts );
};

get_header();
?>
<main id="main" class="genosys-cart-page" dir="rtl" tabindex="-1">
	<div class="genosys-cart-container">
		<nav class="genosys-cart-breadcrumb" aria-label="مسیر صفحه">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a>
			<span aria-hidden="true">/</span>
			<span aria-current="page">سبد خرید</span>
		</nav>
		<header class="genosys-cart-heading">
			<h1>سبد خرید</h1>
			<span class="genosys-cart-count"><?php echo esc_html( $localize_digits( number_format_i18n( $cart_count ) ) ); ?> کالا</span>
		</header>

		<?php if ( function_exists( 'wc_print_notices' ) ) { wc_print_notices(); } ?>

		<?php if ( ! $cart || $cart->is_empty() ) : ?>
			<div class="genosys-cart-empty">
				<h2>سبد خرید شما خالی است</h2>
				<p>محصولات مورد علاقه‌تان را انتخاب کنید و به سبد خرید اضافه کنید.</p>
				<a href="<?php echo esc_url( $shop_url ); ?>">مشاهده محصولات</a>
			</div>
		<?php else : ?>
			<?php
			$show_tax_in_prices = method_exists( $cart, 'display_prices_including_tax' ) && $cart->display_prices_including_tax();
			$sale_savings       = 0.0;
			foreach ( $cart->get_cart() as $saved_item ) {
				$saved_product = isset( $saved_item['data'] ) ? $saved_item['data'] : null;
				if ( ! $saved_product || ! $saved_product->exists() ) {
					continue;
				}
				$regular_price = $saved_product->get_regular_price();
				if ( '' !== $regular_price && (float) $regular_price > (float) $saved_product->get_price() ) {
					$sale_savings += max( 0, wc_get_price_to_display( $saved_product, array( 'price' => (float) $regular_price, 'display_context' => 'cart' ) ) - wc_get_price_to_display( $saved_product, array( 'display_context' => 'cart' ) ) ) * (int) $saved_item['quantity'];
				}
			}
			$cart_subtotal = (float) $cart->get_subtotal() + ( $show_tax_in_prices ? (float) $cart->get_subtotal_tax() : 0 );
			$coupon_savings = (float) $cart->get_discount_total() + ( $show_tax_in_prices ? (float) $cart->get_discount_tax() : 0 );
			// Cart preview excludes delivery; the selected rate remains payable at checkout.
			$cart_total_without_shipping = max( 0, (float) $cart->get_total( 'edit' ) - (float) $cart->get_shipping_total() - (float) $cart->get_shipping_tax() );
			?>
			<div class="genosys-cart-layout">
				<div class="genosys-cart-items">
					<form id="genosys-cart-form" class="genosys-cart-items__form" action="<?php echo esc_url( $cart_url ); ?>" method="post" data-genosys-cart-form>
						<?php foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) : ?>
							<?php
							$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
							if ( ! $product || ! $product->exists() || (int) $cart_item['quantity'] < 1 ) {
								continue;
							}
							$quantity       = (int) $cart_item['quantity'];
							$product_url    = $product->is_visible() ? $product->get_permalink( $cart_item ) : '';
							$regular_price  = $product->get_regular_price();
							$current_price  = (float) $product->get_price();
							$on_sale        = '' !== $regular_price && (float) $regular_price > $current_price;
							$discount       = $on_sale ? round( ( (float) $regular_price - $current_price ) / (float) $regular_price * 100 ) : 0;
							$category_terms = get_the_terms( $product->get_parent_id() ?: $product->get_id(), 'product_cat' );
							$category       = is_array( $category_terms ) && ! empty( $category_terms ) ? $category_terms[0]->name : '';
							?>
							<article class="genosys-cart-item">
								<div class="genosys-cart-item__thumb">
									<?php if ( $product_url ) : ?><a href="<?php echo esc_url( $product_url ); ?>" aria-label="<?php echo esc_attr( $product->get_name() ); ?>"><?php endif; ?>
									<?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ) ); ?>
									<?php if ( $product_url ) : ?></a><?php endif; ?>
								</div>
								<div class="genosys-cart-item__details">
									<?php if ( $category ) : ?><span class="genosys-cart-item__category"><?php echo esc_html( $category ); ?></span><?php endif; ?>
									<h2 class="genosys-cart-item__title">
										<?php if ( $product_url ) : ?><a href="<?php echo esc_url( $product_url ); ?>"><?php endif; ?>
										<?php echo esc_html( $product->get_name() ); ?>
										<?php if ( $product_url ) : ?></a><?php endif; ?>
									</h2>
									<?php echo wp_kses_post( wc_get_formatted_cart_item_data( $cart_item ) ); ?>
									<div class="genosys-cart-item__price">
										<?php echo wp_kses_post( $localize_digits( wc_price( wc_get_price_to_display( $product, array( 'display_context' => 'cart' ) ) ) ) ); ?>
										<?php if ( $on_sale ) : ?>
											<del class="genosys-cart-item__old-price"><?php echo wp_kses_post( $localize_digits( wc_price( wc_get_price_to_display( $product, array( 'price' => (float) $regular_price, 'display_context' => 'cart' ) ) ) ) ); ?></del>
											<span class="genosys-cart-item__discount"><?php echo esc_html( $localize_digits( number_format_i18n( $discount ) ) ); ?>٪ تخفیف</span>
										<?php endif; ?>
									</div>
								</div>
								<div class="genosys-cart-item__actions">
									<strong class="genosys-cart-item__subtotal"><?php echo wp_kses_post( $localize_digits( $cart->get_product_subtotal( $product, $quantity ) ) ); ?></strong>
									<div class="genosys-cart-item__quantity">
										<?php
										if ( $product->is_sold_individually() ) {
											echo '<span>۱</span><input type="hidden" name="cart[' . esc_attr( $cart_item_key ) . '][qty]" value="1">';
										} else {
											?><button class="genosys-cart-item__step genosys-cart-item__step--minus" type="button" data-cart-quantity-change="-1" aria-label="کاهش تعداد <?php echo esc_attr( $product->get_name() ); ?>">−</button><?php
											echo woocommerce_quantity_input( array(
											'input_name'  => "cart[{$cart_item_key}][qty]",
											'input_value' => $quantity,
											'max_value'   => $product->get_max_purchase_quantity(),
											'min_value'   => 0,
											'product_name' => $product->get_name(),
										), $product, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce's quantity template escapes the generated fields.
											?><button class="genosys-cart-item__step genosys-cart-item__step--plus" type="button" data-cart-quantity-change="1" aria-label="افزایش تعداد <?php echo esc_attr( $product->get_name() ); ?>">+</button><?php
										}
										?>
									</div>
									<a class="genosys-cart-item__remove" href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" aria-label="حذف <?php echo esc_attr( $product->get_name() ); ?> از سبد خرید"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5"/></svg> حذف</a>
								</div>
							</article>
						<?php endforeach; ?>
						<button class="genosys-cart-update" type="submit" name="update_cart" value="1">به‌روزرسانی سبد خرید</button>
						<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
					</form>
					<a class="genosys-cart-continue" href="<?php echo esc_url( $shop_url ); ?>"><span aria-hidden="true">‹</span> ادامه‌ی خرید</a>
				</div>

				<aside class="genosys-cart-summary" aria-labelledby="genosys-cart-summary-title">
					<h2 class="genosys-cart-summary__title" id="genosys-cart-summary-title">خلاصه‌ی سفارش</h2>
					<div class="genosys-cart-summary__row"><span>جمع کالاها</span><strong><?php echo wp_kses_post( $localize_digits( wc_price( $cart_subtotal + $sale_savings ) ) ); ?></strong></div>
					<?php if ( $sale_savings + $coupon_savings > 0 ) : ?>
						<div class="genosys-cart-summary__row genosys-cart-summary__row--discount"><span>سود شما از تخفیف</span><strong><?php echo wp_kses_post( $localize_digits( wc_price( $sale_savings + $coupon_savings ) ) ); ?></strong></div>
					<?php endif; ?>
					<?php foreach ( $cart->get_fees() as $fee ) : ?>
						<div class="genosys-cart-summary__row"><span><?php echo esc_html( $fee->name ); ?></span><strong><?php echo wp_kses_post( $localize_digits( wc_price( $fee->total + ( $show_tax_in_prices ? $fee->tax : 0 ) ) ) ); ?></strong></div>
					<?php endforeach; ?>
					<?php if ( ! $show_tax_in_prices && $cart->get_total_tax() - $cart->get_shipping_tax() > 0 ) : ?>
						<div class="genosys-cart-summary__row"><span>مالیات</span><strong><?php echo wp_kses_post( $localize_digits( wc_price( $cart->get_total_tax() - $cart->get_shipping_tax() ) ) ); ?></strong></div>
					<?php endif; ?>
					<?php if ( function_exists( 'wc_coupons_enabled' ) && wc_coupons_enabled() ) : ?>
						<div class="genosys-cart-summary__coupon">
							<label class="screen-reader-text" for="genosys-cart-coupon">کد تخفیف</label>
							<input id="genosys-cart-coupon" name="coupon_code" type="text" placeholder="کد تخفیف دارید؟" form="genosys-cart-form">
							<button type="submit" name="apply_coupon" value="1" form="genosys-cart-form">اعمال</button>
						</div>
						<?php foreach ( $cart->get_applied_coupons() as $code ) : ?>
							<p class="genosys-cart-summary__applied-coupon"><?php echo esc_html( $code ); ?> <a href="<?php echo esc_url( add_query_arg( 'remove_coupon', rawurlencode( $code ), $cart_url ) ); ?>" aria-label="حذف کد تخفیف <?php echo esc_attr( $code ); ?>">حذف</a></p>
						<?php endforeach; ?>
					<?php endif; ?>
					<div class="genosys-cart-summary__total"><span>مبلغ قابل پرداخت</span><strong><?php echo wp_kses_post( $localize_digits( wc_price( $cart_total_without_shipping ) ) ); ?></strong></div>
					<button class="genosys-cart-summary__checkout" type="submit" name="proceed" value="1" form="genosys-cart-form">ادامه جهت تسویه حساب</button>
					<div class="genosys-cart-summary__trust"><span>✓ ضمانت اصالت کالا</span><span>✓ پرداخت امن</span><span>✓ ارسال سریع</span></div>
				</aside>
			</div>
		<?php endif; ?>
	</div>
</main>
<?php get_footer(); ?>
