<?php
/** Shop only: the native WooCommerce catalog query and shared homepage cards. */
defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'genosys_shop_filters' ) || ! function_exists( 'wc_get_product' ) ) {
	get_header();
	echo '<main id="main" class="site-main" tabindex="-1"><h1>فروشگاه</h1></main>';
	get_footer();
	return;
}

global $wp_query;
$filters = genosys_shop_filters();
$categories = genosys_shop_categories();
$bounds = genosys_shop_price_bounds();
// Keep an active selection visible even when another filter narrows the catalog.
foreach ( array( 'min_price', 'max_price' ) as $price_key ) {
	if ( '' !== $filters[ $price_key ] ) {
		$bounds['min'] = min( $bounds['min'], (float) $filters[ $price_key ] );
		$bounds['max'] = max( $bounds['max'], (float) $filters[ $price_key ] );
	}
}
$shop_url = wc_get_page_permalink( 'shop' );
$is_product_search = function_exists( 'genosys_is_product_search' ) && genosys_is_product_search();
$search_term = $is_product_search ? genosys_product_search_term() : '';
$filter_url = $is_product_search ? home_url( '/' ) : $shop_url;
$reset_url = $is_product_search ? add_query_arg( array( 's' => $search_term, 'post_type' => 'product' ), $filter_url ) : $shop_url;
$page = max( 1, (int) get_query_var( 'paged', 1 ) );
$per_page = max( 1, (int) $wp_query->get( 'posts_per_page' ) );
$total = (int) $wp_query->found_posts;
$first = $total ? ( $page - 1 ) * $per_page + 1 : 0;
$last = min( $total, $page * $per_page );
$currency = wp_strip_all_tags( html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) );
$price_text = static function ( $number ) {
	return genosys_persian_number( number_format_i18n( (float) $number, wc_get_price_decimals() ) );
};
$icon = static function ( $name ) {
	$paths = array(
		'chevron' => '<path d="m5 9 7 7 7-7"/>',
		'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
		'filter' => '<path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="3" fill="white"/><circle cx="15" cy="17" r="3" fill="white"/>',
	);
	echo '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $name ] ?? '' ) . '</svg>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed SVG only.
};
$order_labels = array(
	'date' => 'جدیدترین',
	'popularity' => 'پرفروش‌ترین',
	'rating' => 'بالاترین امتیاز',
	'price' => 'ارزان‌ترین',
	'price-desc' => 'گران‌ترین',
	'menu_order' => 'مرتب‌سازی پیش‌فرض',
);
$base_args = array();
parse_str( (string) wp_parse_url( $shop_url, PHP_URL_QUERY ), $base_args );
if ( $is_product_search ) {
	$base_args = array( 's' => $search_term, 'post_type' => 'product' );
}
// Preserve WooCommerce's validated attribute/rating filters alongside our controls.
foreach ( WC_Query::get_layered_nav_chosen_attributes() as $taxonomy => $selection ) {
	$attribute = wc_attribute_taxonomy_slug( $taxonomy );
	$base_args[ 'filter_' . $attribute ] = implode( ',', $selection['terms'] );
	if ( in_array( $selection['query_type'], array( 'and', 'or' ), true ) ) {
		$base_args[ 'query_type_' . $attribute ] = $selection['query_type'];
	}
}
if ( isset( $_GET['rating_filter'] ) && is_scalar( $_GET['rating_filter'] ) && ! is_bool( $_GET['rating_filter'] ) ) {
	$ratings = array_values( array_unique( array_intersect( array_map( 'absint', explode( ',', wp_unslash( (string) $_GET['rating_filter'] ) ) ), array( 1, 2, 3, 4, 5 ) ) ) );
	if ( $ratings ) {
		$base_args['rating_filter'] = implode( ',', $ratings );
	}
}
get_header();
?>
<main id="main" class="genosys-shop" dir="rtl" tabindex="-1" data-shop>
	<div class="genosys-shop__container">
		<nav class="genosys-shop__breadcrumb" aria-label="مسیر صفحه"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a><span aria-hidden="true">/</span><?php if ( $is_product_search ) : ?><a href="<?php echo esc_url( $shop_url ); ?>">فروشگاه</a><span aria-hidden="true">/</span><span aria-current="page">جستجوی محصولات</span><?php else : ?><span aria-current="page">فروشگاه</span><?php endif; ?></nav>
		<?php if ( $is_product_search ) : ?>
			<div class="genosys-shop__heading"><h1 class="genosys-shop__title">نتایج <em>جستجو</em></h1><p class="genosys-shop__description">محصولات مطابق با «<bdi><?php echo esc_html( $search_term ); ?></bdi>»</p></div>
		<?php else : ?>
			<h1 class="genosys-shop__heading genosys-shop__title"><?php echo esc_html( genosys_site_setting( 'shop_title' ) ); ?> <em><?php echo esc_html( genosys_site_setting( 'shop_highlight' ) ); ?></em></h1>
		<?php endif; ?>
		<div class="genosys-shop__layout">
			<button class="genosys-shop__backdrop" type="button" data-shop-backdrop aria-label="بستن فیلترها" tabindex="-1" hidden></button>
			<aside class="genosys-shop__sidebar" id="genosys-shop-filters" data-shop-sidebar aria-label="فیلترهای فروشگاه" tabindex="-1">
				<div class="genosys-shop__drawer-header"><h2 id="genosys-shop-filter-title">فیلتر محصولات</h2><button type="button" data-shop-close aria-label="بستن فیلترها"><?php $icon( 'close' ); ?></button></div>
				<form id="genosys-shop-filter-form" class="genosys-shop__filter-form" data-shop-filter-form method="get" action="<?php echo esc_url( $filter_url ); ?>">
					<?php foreach ( $base_args as $key => $value ) : ?>
						<?php if ( is_scalar( $value ) ) : ?><input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( (string) $value ); ?>"><?php endif; ?>
					<?php endforeach; ?>
					<details class="genosys-shop__panel" open>
						<summary class="genosys-shop__panel-heading"><span>دسته‌بندی محصولات</span><?php $icon( 'chevron' ); ?></summary>
						<div class="genosys-shop__panel-body genosys-shop__categories">
							<?php if ( $categories ) : ?>
								<?php foreach ( $categories as $category ) : ?>
									<label class="genosys-shop__category-row" style="--category-depth:<?php echo esc_attr( min( 4, count( get_ancestors( $category->term_id, 'product_cat', 'taxonomy' ) ) ) ); ?>">
										<input type="checkbox" name="shop_cat[]" value="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( in_array( (int) $category->term_id, $filters['categories'], true ) ); ?> data-shop-autosubmit>
										<span class="genosys-shop__category-name"><?php echo esc_html( $category->name ); ?></span>
										<span class="genosys-shop__category-count"><?php echo esc_html( genosys_persian_number( number_format_i18n( $category->count ) ) ); ?></span>
									</label>
								<?php endforeach; ?>
							<?php else : ?><p class="genosys-shop__muted">هنوز دسته‌بندی محصولی وجود ندارد.</p><?php endif; ?>
						</div>
					</details>
					<details class="genosys-shop__panel" open>
						<summary class="genosys-shop__panel-heading"><span>محدوده‌ی قیمت</span><?php $icon( 'chevron' ); ?></summary>
						<div class="genosys-shop__panel-body">
							<div class="genosys-shop__price-controls" data-shop-price data-min="<?php echo esc_attr( $bounds['min'] ); ?>" data-max="<?php echo esc_attr( $bounds['max'] ); ?>">
								<div class="genosys-shop__price-range">
								<div class="genosys-shop__range-track">
									<span class="genosys-shop__price-fill" data-price-fill aria-hidden="true"></span>
								</div>
									<input type="range" min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>" step="<?php echo esc_attr( $bounds['step'] ); ?>" value="<?php echo esc_attr( '' !== $filters['min_price'] ? max( $bounds['min'], min( $bounds['max'], (float) $filters['min_price'] ) ) : $bounds['min'] ); ?>" data-price-range="min" aria-label="حداقل قیمت">
									<input type="range" min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>" step="<?php echo esc_attr( $bounds['step'] ); ?>" value="<?php echo esc_attr( '' !== $filters['max_price'] ? max( $bounds['min'], min( $bounds['max'], (float) $filters['max_price'] ) ) : $bounds['max'] ); ?>" data-price-range="max" aria-label="حداکثر قیمت">
								</div>
								<div class="genosys-shop__price-values">
									<label class="genosys-shop__price-box"><span>از</span><input type="text" inputmode="decimal" name="min_price" data-price-input="min" value="<?php echo esc_attr( '' !== $filters['min_price'] ? $price_text( $filters['min_price'] ) : '' ); ?>" placeholder="<?php echo esc_attr( $price_text( $bounds['min'] ) ); ?>" autocomplete="off" aria-label="قیمت از"></label>
									<label class="genosys-shop__price-box"><span>تا</span><input type="text" inputmode="decimal" name="max_price" data-price-input="max" value="<?php echo esc_attr( '' !== $filters['max_price'] ? $price_text( $filters['max_price'] ) : '' ); ?>" placeholder="<?php echo esc_attr( $price_text( $bounds['max'] ) ); ?>" autocomplete="off" aria-label="قیمت تا"></label>
								</div>
								<p class="genosys-shop__price-currency"><?php echo esc_html( 'قیمت‌ها به ' . $currency ); ?></p>
							</div>
						</div>
					</details>
					<details class="genosys-shop__panel" open>
						<summary class="genosys-shop__panel-heading"><span>وضعیت</span><?php $icon( 'chevron' ); ?></summary>
						<div class="genosys-shop__panel-body genosys-shop__status">
							<label class="genosys-shop__status-row"><span>فقط کالاهای موجود</span><input type="checkbox" name="only_in_stock" value="1" <?php checked( $filters['only_in_stock'] ); ?> data-shop-autosubmit><span class="genosys-shop__switch" aria-hidden="true"></span></label>
							<label class="genosys-shop__status-row"><span>فقط کالاهای تخفیف‌دار</span><input type="checkbox" name="only_on_sale" value="1" <?php checked( $filters['only_on_sale'] ); ?> data-shop-autosubmit><span class="genosys-shop__switch" aria-hidden="true"></span></label>
						</div>
					</details>
					<button class="genosys-shop__apply" type="submit" data-shop-apply>اعمال فیلترها</button>
					<a class="genosys-shop__reset" href="<?php echo esc_url( $reset_url ); ?>">حذف همه‌ی فیلترها</a>
				</form>
			</aside>
			<section class="genosys-shop__main" aria-label="محصولات فروشگاه">
				<div class="genosys-shop__toolbar">
					<p class="genosys-shop__count" aria-live="polite">نمایش <strong><?php echo esc_html( genosys_persian_number( $first ) ); ?></strong> تا <strong><?php echo esc_html( genosys_persian_number( $last ) ); ?></strong> از <strong><?php echo esc_html( genosys_persian_number( $total ) ); ?></strong> محصول</p>
					<div class="genosys-shop__toolbar-actions">
						<button class="genosys-shop__filter-toggle" type="button" data-shop-open aria-expanded="false" aria-controls="genosys-shop-filters"><?php $icon( 'filter' ); ?><span>فیلترها</span></button>
						<label class="genosys-shop__sort"><span class="screen-reader-text">مرتب‌سازی محصولات</span><select name="orderby" form="genosys-shop-filter-form" data-shop-autosubmit aria-label="مرتب‌سازی محصولات"><?php foreach ( $order_labels as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['orderby'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><?php $icon( 'chevron' ); ?></label>
					</div>
				</div>
				<?php if ( have_posts() ) : ?>
					<div class="genosys-shop__grid">
						<?php while ( have_posts() ) : the_post(); ?>
							<?php get_template_part( 'template-parts/product/card', null, array( 'product' => wc_get_product( get_the_ID() ) ) ); ?>
						<?php endwhile; ?>
					</div>
					<?php $pagination = genosys_shop_pagination(); ?>
					<?php if ( $pagination ) : ?>
						<nav class="genosys-shop__pagination" aria-label="صفحه‌های محصولات">
							<?php if ( 1 === $page ) : ?><span class="page-numbers prev is-disabled" aria-disabled="true">قبلی</span><?php endif; ?>
							<?php foreach ( $pagination as $link ) : ?>
								<?php
								$parts = wp_html_split( $link );
								foreach ( $parts as $part_index => $part ) {
									if ( '' !== $part && '<' !== $part[0] ) {
										$parts[ $part_index ] = genosys_persian_number( $part );
									}
								}
								echo wp_kses_post( implode( '', $parts ) );
								?>
							<?php endforeach; ?>
							<?php if ( $page >= (int) $wp_query->max_num_pages ) : ?><span class="page-numbers next is-disabled" aria-disabled="true">بعدی</span><?php endif; ?>
						</nav>
					<?php endif; ?>
				<?php else : ?>
					<div class="genosys-shop__empty"><h2>محصولی یافت نشد</h2><p><?php echo $is_product_search ? 'نام محصول یا کد SKU دیگری را جستجو کنید، یا فیلترها را تغییر دهید.' : 'برای دیدن محصولات، فیلترهای انتخاب‌شده را تغییر دهید.'; ?></p><a class="genosys-shop__reset" href="<?php echo esc_url( $is_product_search ? $shop_url : $reset_url ); ?>"><?php echo $is_product_search ? 'مشاهده همه محصولات' : 'حذف همه‌ی فیلترها'; ?></a></div>
				<?php endif; ?>
				<p class="screen-reader-text" role="status" aria-live="polite" data-favorites-status></p>
			</section>
		</div>
	</div>
</main>
<?php get_footer(); ?>
