<?php
/** Homepage category showcase, using the saved cards and native WooCommerce terms. */
defined( 'ABSPATH' ) || exit;
$category_cards = genosys_site_setting( 'category_cards' );
$default_cards = genosys_site_defaults()['category_cards'];
$products_url = genosys_home_card_url( 'products' );
?>
<section class="genosys-categories" aria-labelledby="genosys-categories-title">
	<div class="genosys-categories__intro">
		<h2 id="genosys-categories-title"><?php echo esc_html( genosys_site_setting( 'categories_title' ) ); ?> <span><?php echo esc_html( genosys_site_setting( 'categories_highlight' ) ); ?></span></h2>
		<p><?php echo esc_html( genosys_site_setting( 'categories_description' ) ); ?></p>
		<a class="genosys-categories__button" href="<?php echo esc_url( home_url( '/shop/' ) ); ?>"><?php echo esc_html( genosys_site_setting( 'categories_button' ) ); ?></a>
		<a class="genosys-categories__brand" href="<?php echo esc_url( genosys_site_url( 'categories_brand_url', home_url( '/brand/' ) ) ); ?>"><?php echo esc_html( genosys_site_setting( 'categories_brand_text' ) ); ?></a>
	</div>
	<div class="genosys-categories__stage">
		<div id="genosys-category-row" class="genosys-categories__row latest-products__row" role="region" aria-label="دسته‌بندی محصولات ژنوسیس، فهرست افقی" tabindex="0">
			<?php foreach ( $category_cards as $index => $card ) : ?>
				<?php
                if ( ! is_array( $card ) ) { continue; }
				$url = $products_url;
                $term_id = absint( $card['term_id'] ?? 0 );
                if ( $term_id ) {
                    $term = get_term( $term_id, 'product_cat' );
                    $term_url = $term && ! is_wp_error( $term ) ? get_term_link( $term ) : null;
                    if ( is_string( $term_url ) ) { $url = $term_url; }
                } else {
					foreach ( ( $card['terms'] ?? array() ) as $term_name ) {
						$term = get_term_by( 'name', $term_name, 'product_cat' );
						if ( ! $term || is_wp_error( $term ) ) { continue; }
						$term_url = get_term_link( $term );
						if ( ! is_wp_error( $term_url ) ) { $url = $term_url; break; }
					}
                }
                $size = isset( $card['size'] ) && 'tall' === $card['size'] ? 'tall' : 'short';
				?>
				<a class="genosys-categories__card genosys-categories__card--<?php echo esc_attr( $size ); ?>" href="<?php echo esc_url( $url ); ?>">
					<img src="<?php echo esc_url( ! empty( $card['image'] ) ? $card['image'] : ( $default_cards[ $index ]['image'] ?? '' ) ); ?>" alt="" width="536" height="536" loading="lazy" decoding="async" draggable="false">
					<span class="genosys-categories__card-title"><?php echo esc_html( $card['title'] ?? '' ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php get_template_part( 'template-parts/home/category-stats' ); ?>
	</div>
</section>
