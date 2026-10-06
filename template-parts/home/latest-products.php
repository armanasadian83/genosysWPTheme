<?php
/** Latest catalog products in a native RTL scroll row. */
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'wc_get_products' ) ) { return; }
$latest_products = genosys_latest_products();
?>
<section class="latest-products" aria-labelledby="latest-products-title">
    <h2 id="latest-products-title" class="latest-products__title">جدیدترین محصولات</h2>
    <?php if ( $latest_products ) : ?>
        <div class="latest-products__row" role="region" aria-label="جدیدترین محصولات، فهرست افقی" tabindex="0">
            <?php foreach ( $latest_products as $latest_product ) : ?>
                <?php get_template_part( 'template-parts/product/card', null, array( 'product' => $latest_product ) ); ?>
            <?php endforeach; ?>
            <?php if ( count( $latest_products ) >= 8 ) : ?>
                <a class="products-view-all" href="<?php echo esc_url( home_url( '/products' ) ); ?>">
                    <span>مشاهده همه</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M20 12H4m6-6-6 6 6 6"/></svg>
                </a>
            <?php endif; ?>
        </div>
        <p class="screen-reader-text" role="status" aria-live="polite" data-favorites-status></p>
    <?php else : ?>
        <p class="latest-products__empty">هنوز محصولی برای نمایش موجود نیست.</p>
    <?php endif; ?>
</section>
