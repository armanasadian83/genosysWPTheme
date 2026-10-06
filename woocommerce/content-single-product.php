<?php
/**
 * Single product layout. WooCommerce owns the current product, gallery, cart form,
 * variation controls, descriptions and reviews through its normal hooks.
 *
 * @package Genosys
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'genosys-product', $product ); ?>>
	<div class="genosys-product__hero">
		<div class="genosys-product__gallery">
			<?php
			/** The real responsive WooCommerce gallery and our dynamic sale/stock badge. */
			do_action( 'woocommerce_before_single_product_summary' );
			?>
		</div>

		<div class="genosys-product__info summary entry-summary">
			<?php
			/** Native WooCommerce title, excerpt, price, cart form and structured data. */
			do_action( 'woocommerce_single_product_summary' );
			?>
		</div>
	</div>

	<?php
	/** Native WooCommerce description, attributes and review tabs. */
	do_action( 'woocommerce_after_single_product_summary' );
	?>
</div>
<?php do_action( 'woocommerce_after_single_product' ); ?>
