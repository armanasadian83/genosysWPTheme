<?php
/** Classic WooCommerce checkout form with the Genosys layout. */
defined( 'ABSPATH' ) || exit;

// The coupon control is integrated into the order summary below.
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

?>
<ol class="gco-steps" aria-label="مراحل ثبت سفارش">
	<li class="gco-step gco-step--complete"><span class="gco-step__number" aria-hidden="true">✓</span><span>سبد خرید</span></li>
	<li class="gco-steps__line gco-steps__line--complete" aria-hidden="true"></li>
	<li class="gco-step gco-step--current" aria-current="step"><span class="gco-step__number" aria-hidden="true">۲</span><span>اطلاعات و ارسال</span></li>
	<li class="gco-steps__line" aria-hidden="true"></li>
	<li class="gco-step"><span class="gco-step__number" aria-hidden="true">۳</span><span>تکمیل سفارش</span></li>
</ol>

<form name="checkout" method="post" class="checkout woocommerce-checkout gco-layout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="تسویه‌حساب">
	<input type="hidden" name="genosys_checkout_layout" value="1">
	<div class="gco-main" id="customer_details">
		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
		<section class="gco-panel gco-contact" aria-labelledby="gco-contact-heading">
			<h2 class="gco-panel__heading" id="gco-contact-heading"><span class="gco-panel__number">۱</span>اطلاعات تماس</h2>
			<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>
			<div class="gco-fields">
				<?php genosys_checkout_render_field( $checkout, 'billing_first_name', array( 'label' => 'نام', 'class' => array( 'form-row', 'gco-field' ) ) ); ?>
				<?php genosys_checkout_render_field( $checkout, 'billing_last_name', array( 'label' => 'نام خانوادگی', 'class' => array( 'form-row', 'gco-field' ) ) ); ?>
				<?php genosys_checkout_render_field( $checkout, 'billing_phone', array( 'label' => 'شماره موبایل', 'class' => array( 'form-row', 'gco-field' ), 'placeholder' => '09123456789' ) ); ?>
				<?php genosys_checkout_render_field( $checkout, 'billing_email', array( 'label' => 'ایمیل', 'class' => array( 'form-row', 'gco-field' ), 'placeholder' => 'name@example.com' ) ); ?>
			</div>
			<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
				<div class="gco-account-fields woocommerce-account-fields">
					<?php if ( ! $checkout->is_registration_required() ) : ?>
						<p class="form-row create-account"><label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox"><input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( true === $checkout->get_value( 'createaccount' ) || true === apply_filters( 'woocommerce_create_account_default_checked', false ) ); ?> type="checkbox" name="createaccount" value="1"><span>ساخت حساب کاربری برای این سفارش</span></label></p>
					<?php endif; ?>
					<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>
					<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?><div class="create-account gco-account-fields__details"><?php genosys_checkout_render_extra_fields( $checkout, 'account' ); ?></div><?php endif; ?>
					<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
				</div>
			<?php endif; ?>
		</section>

		<section class="gco-panel gco-address" aria-labelledby="gco-address-heading">
			<h2 class="gco-panel__heading" id="gco-address-heading"><span class="gco-panel__number">۲</span>آدرس تحویل</h2>
			<input type="hidden" name="billing_country" id="billing_country" value="IR">
			<div class="gco-fields gco-fields--address">
				<?php genosys_checkout_render_field( $checkout, 'billing_state', array( 'label' => 'استان', 'country' => 'IR', 'class' => array( 'form-row', 'gco-field' ) ) ); ?>
				<?php genosys_checkout_render_field( $checkout, 'billing_city', array( 'label' => 'شهر', 'class' => array( 'form-row', 'gco-field' ) ) ); ?>
				<?php genosys_checkout_render_field( $checkout, 'billing_address_1', array( 'label' => 'آدرس کامل', 'type' => 'textarea', 'placeholder' => 'خیابان، کوچه، پلاک، واحد', 'class' => array( 'form-row', 'gco-field', 'gco-field--wide' ) ) ); ?>
				<?php genosys_checkout_render_field( $checkout, 'billing_postcode', array( 'label' => 'کد پستی', 'placeholder' => '۱۰ رقم', 'class' => array( 'form-row', 'gco-field', 'gco-field--postal' ) ) ); ?>
			</div>
			<?php genosys_checkout_render_extra_fields( $checkout, 'billing', array( 'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_email', 'billing_country', 'billing_state', 'billing_city', 'billing_address_1', 'billing_postcode', 'billing_company', 'billing_address_2' ) ); ?>
			<?php foreach ( array( 'billing_company', 'billing_address_2' ) as $preserved_key ) : ?>
				<?php if ( $checkout->get_value( $preserved_key ) ) : ?><input type="hidden" name="<?php echo esc_attr( $preserved_key ); ?>" value="<?php echo esc_attr( $checkout->get_value( $preserved_key ) ); ?>"><?php endif; ?>
			<?php endforeach; ?>
			<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
		</section>
		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

		<section class="gco-panel gco-shipping" aria-labelledby="gco-shipping-heading">
			<h2 class="gco-panel__heading" id="gco-shipping-heading"><span class="gco-panel__number">۳</span>روش ارسال</h2>
			<div class="gco-shipping-options"><?php genosys_checkout_render_shipping_methods(); ?></div>
			<div class="gco-order-notes">
				<?php genosys_checkout_render_field( $checkout, 'order_comments', array( 'label' => 'توضیحات سفارش', 'type' => 'textarea', 'placeholder' => 'مثلاً: ساعت تحویل، یادداشت برای ارسال...', 'class' => array( 'form-row', 'gco-field', 'gco-field--wide' ) ) ); ?>
				<?php genosys_checkout_render_extra_fields( $checkout, 'order', array( 'order_comments' ) ); ?>
			</div>
		</section>
		<?php // Keep Woo's payment field in the form for checkout AJAX and order processing. ?>
		<?php woocommerce_checkout_payment(); ?>
	</div>

	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
	<aside id="order_review" class="gco-summary woocommerce-checkout-review-order" aria-labelledby="gco-summary-heading">
		<div class="gco-summary-inner woocommerce-checkout-review-order-table"><?php genosys_checkout_render_summary(); ?></div>
		<div class="gco-summary__terms"><?php wc_get_template( 'checkout/terms.php' ); ?></div>
		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>
		<?php $order_button_text = apply_filters( 'woocommerce_order_button_text', 'ثبت سفارش' ); ?>
		<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="gco-submit button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce gateway integrations filter this button. ?>
		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>
		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
		<a class="gco-summary__back" href="<?php echo esc_url( wc_get_cart_url() ); ?>">بازگشت به سبد خرید</a>
	</aside>
	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
