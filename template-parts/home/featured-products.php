<?php
/** Featured homepage tabs backed by real WooCommerce product categories. */
defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'wc_get_products' ) || ! taxonomy_exists( 'product_cat' ) ) { return; }

$featured_tabs = genosys_site_setting( 'featured_tabs' );
if ( ! $featured_tabs ) { return; }
$catalog_terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
if ( is_wp_error( $catalog_terms ) ) { $catalog_terms = array(); }
$normalize_name = static function ( $name ) {
    $name = str_replace( array( 'ي', 'ك' ), array( 'ی', 'ک' ), (string) $name );
    return preg_replace( '/[\s\x{200C}\x{200D}\x{064B}-\x{065F}\x{0670}ـ-]+/u', '', $name );
};

foreach ( $featured_tabs as &$tab ) {
    $tab['term'] = null;
    $tab['url'] = '';
    $tab['products'] = array();
    $selected_id = absint( $tab['term_id'] ?? 0 );
    if ( $selected_id ) {
        foreach ( $catalog_terms as $term ) { if ( $selected_id === (int) $term->term_id ) { $tab['term'] = $term; break; } }
    }
    foreach ( $selected_id ? array() : ( $tab['names'] ?? array() ) as $name ) {
        foreach ( $catalog_terms as $term ) {
            if ( $normalize_name( $term->name ) === $normalize_name( $name ) ) {
                $tab['term'] = $term;
                break 2;
            }
        }
    }
    if ( ! $tab['term'] ) { continue; }

    $term_url = get_term_link( $tab['term'] );
    if ( ! is_wp_error( $term_url ) ) { $tab['url'] = $term_url; }
    $query = array(
        'status' => 'publish',
        'limit' => max( 1, min( 24, (int) genosys_site_setting( 'featured_count', 6 ) ) ),
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
        'visibility' => 'catalog',
        'category' => array( $tab['term']->slug ),
    );
    if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
        $query['stock_status'] = array( 'instock', 'onbackorder' );
    }
    $tab['products'] = wc_get_products( $query );
}
unset( $tab );
?>
<section class="genosys-featured-products" aria-labelledby="genosys-featured-products-title" data-featured-products>
    <div class="genosys-featured-products__layout">
        <div class="genosys-featured-products__sidebar">
            <span class="genosys-featured-products__eyebrow"><?php echo esc_html( genosys_site_setting( 'featured_eyebrow' ) ); ?></span>
            <h2 id="genosys-featured-products-title" class="genosys-featured-products__heading"><?php echo esc_html( genosys_site_setting( 'featured_title' ) ); ?> <em><?php echo esc_html( genosys_site_setting( 'featured_highlight' ) ); ?></em> <?php echo esc_html( genosys_site_setting( 'featured_suffix' ) ); ?></h2>
            <p class="genosys-featured-products__description"><?php echo esc_html( genosys_site_setting( 'featured_description' ) ); ?></p>
            <div class="genosys-featured-products__tabs" role="tablist" aria-label="دسته‌بندی محصولات منتخب" aria-orientation="vertical">
                <?php foreach ( $featured_tabs as $index => $tab ) : ?>
                    <button class="genosys-featured-products__tab<?php echo 0 === $index ? ' is-active' : ''; ?>" type="button" role="tab" id="genosys-featured-tab-<?php echo esc_attr( $index ); ?>" aria-controls="genosys-featured-panel-<?php echo esc_attr( $index ); ?>" aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>" data-featured-tab>
                        <span><?php echo esc_html( $tab['label'] ); ?></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m15 5-7 7 7 7"/></svg>
                    </button>
                <?php endforeach; ?>
            </div>
            <a class="genosys-featured-products__categories-link" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">
                <span><?php echo esc_html( genosys_site_setting( 'featured_link_text' ) ); ?></span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 12H4m6-6-6 6 6 6"/></svg>
            </a>
        </div>

        <div class="genosys-featured-products__panels">
            <?php foreach ( $featured_tabs as $index => $tab ) : ?>
                <div class="genosys-featured-products__panel" id="genosys-featured-panel-<?php echo esc_attr( $index ); ?>" role="tabpanel" aria-labelledby="genosys-featured-tab-<?php echo esc_attr( $index ); ?>" tabindex="0" data-featured-panel<?php echo 0 === $index ? '' : ' hidden'; ?>>
                    <?php if ( $tab['products'] ) : ?>
                        <div class="genosys-featured-products__grid" data-featured-rail tabindex="0" role="region" aria-label="<?php echo esc_attr( 'محصولات ' . $tab['label'] ); ?>">
                            <?php foreach ( $tab['products'] as $product ) : ?>
                                <?php get_template_part( 'template-parts/product/card', null, array( 'product' => $product ) ); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p class="genosys-featured-products__empty"><?php echo esc_html( $tab['term'] ? 'هنوز محصولی در این دسته‌بندی ثبت نشده است.' : 'این دسته‌بندی هنوز در فروشگاه ثبت نشده است.' ); ?></p>
                    <?php endif; ?>
                    <?php if ( $tab['url'] ) : ?>
                        <a class="genosys-featured-products__view-all" href="<?php echo esc_url( $tab['url'] ); ?>">
                            <span>مشاهده همه</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 12H4m6-6-6 6 6 6"/></svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
