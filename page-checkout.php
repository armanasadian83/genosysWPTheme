<?php
/** The WooCommerce shortcode retains checkout, order-pay, and order-received flows. */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="genosys-checkout" dir="rtl" tabindex="-1">
	<div class="gco-container">
		<?php if ( function_exists( 'WC' ) ) : ?>
			<?php echo do_shortcode( '[woocommerce_checkout]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce owns checkout rendering. ?>
		<?php else : ?>
			<p class="woocommerce-info">تسویه‌حساب در حال حاضر در دسترس نیست.</p>
		<?php endif; ?>
	</div>
</main>
<?php get_footer(); ?>
