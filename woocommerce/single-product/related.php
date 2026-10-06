<?php
/**
 * Related products retain WooCommerce's selected products and use the shared card.
 *
 * @package Genosys
 * @version 9.6.0
 */

defined( 'ABSPATH' ) || exit;

if ( $related_products ) :
	$heading = apply_filters( 'woocommerce_product_related_products_heading', __( 'Related products', 'woocommerce' ) );
	?>
	<section class="related products genosys-related-products">
		<?php if ( $heading ) : ?>
			<h2><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>
		<div class="latest-products__row" role="region" aria-label="<?php echo esc_attr( 'محصولات مرتبط، فهرست افقی' ); ?>" tabindex="0">
			<?php foreach ( $related_products as $related_product ) : ?>
				<?php get_template_part( 'template-parts/product/card', null, array( 'product' => $related_product ) ); ?>
			<?php endforeach; ?>
		</div>
		<p class="screen-reader-text" role="status" aria-live="polite" data-favorites-status></p>
	</section>
	<?php
endif;

wp_reset_postdata();
