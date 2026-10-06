<?php
/** Reusable WooCommerce card. Pass a WC_Product in the template's product argument. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$card_product = isset( $args['product'] ) ? $args['product'] : null;
if ( ! $card_product instanceof WC_Product ) { return; }
$name = $card_product->get_name();
$permalink = $card_product->get_permalink();
$category_names = array();
$categories = get_the_terms( $card_product->get_id(), 'product_cat' );
if ( $categories && ! is_wp_error( $categories ) ) {
    foreach ( $categories as $category ) { $category_names[] = $category->name; }
}
$category_text = implode( '، ', $category_names );
$price_html = genosys_product_price_html( $card_product );
$discount = genosys_product_discount( $card_product );
?>
<article class="product-card<?php echo $card_product->get_image_id() ? '' : ' product-card--placeholder'; ?>" data-product-id="<?php echo esc_attr( $card_product->get_id() ); ?>">
    <a class="product-card__photo" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $name ); ?>" tabindex="-1" aria-hidden="true">
        <?php echo wp_kses_post( $card_product->get_image( 'large', array( 'class' => 'product-card__image', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'draggable' => 'false' ), true ) ); ?>
    </a>
    <?php if ( ! $card_product->is_in_stock() ) : ?>
        <span class="product-card__badge product-card__badge--stock">ناموجود</span>
    <?php elseif ( $card_product->is_on_sale() ) : ?>
        <span class="product-card__badge"><?php echo esc_html( $discount ? genosys_persian_number( $discount ) . '٪ تخفیف' : 'تخفیف' ); ?></span>
    <?php endif; ?>
    <button class="product-favorite" type="button" data-favorite-id="<?php echo esc_attr( $card_product->get_id() ); ?>" aria-pressed="false" aria-label="<?php echo esc_attr( 'افزودن ' . $name . ' به علاقه‌مندی‌ها' ); ?>" data-product-name="<?php echo esc_attr( $name ); ?>" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg>
    </button>
    <div class="product-card__info">
        <h3 class="product-card__name"><a href="<?php echo esc_url( $permalink ); ?>" title="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></a></h3>
        <?php if ( $category_text ) : ?><p class="product-card__category" title="<?php echo esc_attr( $category_text ); ?>"><?php echo esc_html( $category_text ); ?></p><?php endif; ?>
        <div class="product-card__price<?php echo ! $card_product->is_in_stock() ? ' product-card__price--unavailable' : ( $price_html ? '' : ' product-card__price--empty' ); ?>">
            <span class="product-card__price-value"><?php echo ! $card_product->is_in_stock() ? esc_html( 'تماس بگیرید' ) : ( $price_html ? wp_kses_post( $price_html ) : esc_html( 'اطلاعات قیمت موجود نیست' ) ); ?></span>
        </div>
    </div>
</article>
