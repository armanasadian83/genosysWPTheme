<?php
/** Keep WooCommerce's payment selection in the checkout form without a payment panel. */
defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}

$needs_payment = WC()->cart && WC()->cart->needs_payment();
$card_gateway = $needs_payment && isset( $available_gateways['genosys_card_to_card'] );
?>
<div id="payment" class="woocommerce-checkout-payment gco-payment-processing<?php echo $needs_payment && ! $card_gateway ? ' gco-payment-processing--unavailable' : ''; ?>">
	<?php if ( $card_gateway ) : ?>
		<ul class="wc_payment_methods payment_methods methods">
			<li class="wc_payment_method payment_method_genosys_card_to_card">
				<input id="payment_method_genosys_card_to_card" type="radio" class="input-radio" name="payment_method" value="genosys_card_to_card" checked="checked" aria-hidden="true" tabindex="-1">
			</li>
		</ul>
	<?php elseif ( $needs_payment ) : ?>
		<p class="woocommerce-error gco-payment-unavailable" role="alert">پرداخت کارت به کارت هنوز برای این فروشگاه تنظیم نشده است. لطفاً با پشتیبانی تماس بگیرید.</p>
	<?php endif; ?>
</div>
<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
