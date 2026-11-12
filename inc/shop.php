<?php
/** Shop presentation filters layered onto WooCommerce's native main query. */
defined( 'ABSPATH' ) || exit;

function genosys_shop_ascii_digits( $value ) {
	return strtr( (string) $value, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
		'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
		'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		'٫' => '.',
	) );
}

/** Blank or malformed prices never become an accidental zero-price filter. */
function genosys_shop_price( $value ) {
	if ( ! is_scalar( $value ) || is_bool( $value ) ) {
		return '';
	}
	$value = genosys_shop_ascii_digits( trim( (string) $value ) );
	$value = preg_replace( '/[\s\x{00A0}\x{200C}\x{202F},٬،]+/u', '', $value );
	if ( ! is_string( $value ) || ! preg_match( '/^\d+(?:\.\d+)?$/D', $value ) ) {
		return '';
	}
	list( $whole, $fraction ) = array_pad( explode( '.', $value, 2 ), 2, '' );
	$whole = ltrim( $whole, '0' );
	$whole = '' === $whole ? '0' : $whole;
	$fraction = rtrim( $fraction, '0' );
	$decimals = function_exists( 'wc_get_price_decimals' ) ? max( 0, (int) wc_get_price_decimals() ) : 2;
	if ( strlen( $whole ) > 15 || strlen( $fraction ) > $decimals ) {
		return '';
	}
	return $whole . ( '' === $fraction ? '' : '.' . $fraction );
}

function genosys_shop_category_ids( $values ) {
	if ( ! is_array( $values ) || ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}
	$ids = array();
	foreach ( $values as $value ) {
		if ( ! is_scalar( $value ) || is_bool( $value ) ) {
			continue;
		}
		$value = genosys_shop_ascii_digits( trim( (string) $value ) );
		if ( preg_match( '/^[1-9][0-9]{0,9}$/D', $value ) && (float) $value <= 2147483647 ) {
			$ids[] = (int) $value;
		}
	}
	$ids = array_values( array_unique( $ids ) );
	if ( ! $ids ) {
		return array();
	}
	$found = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'include' => $ids, 'fields' => 'ids' ) );
	if ( is_wp_error( $found ) ) {
		return array();
	}
	// Keep the customer's selection order while rejecting IDs from other taxonomies.
	return array_values( array_intersect( $ids, array_map( 'intval', $found ) ) );
}

function genosys_shop_switch( $value ) {
	return is_scalar( $value ) && in_array( strtolower( trim( (string) $value ) ), array( '1', 'on', 'yes', 'true' ), true );
}

/** Public template contract: IDs, decimal strings, booleans and a native sort key. */
function genosys_shop_normalize_filters( $values ) {
	$values = is_array( $values ) ? $values : array();
	$orderby = isset( $values['orderby'] ) && is_string( $values['orderby'] ) ? trim( $values['orderby'] ) : 'date';
	if ( ! in_array( $orderby, array( 'date', 'popularity', 'rating', 'price', 'price-desc', 'menu_order' ), true ) ) {
		$orderby = 'date';
	}
	$minimum = genosys_shop_price( $values['min_price'] ?? '' );
	$maximum = genosys_shop_price( $values['max_price'] ?? '' );
	if ( '' !== $minimum && '' !== $maximum && (float) $minimum > (float) $maximum ) {
		list( $minimum, $maximum ) = array( $maximum, $minimum );
	}
	return array(
		'categories' => genosys_shop_category_ids( $values['categories'] ?? ( $values['shop_cat'] ?? array() ) ),
		'min_price' => $minimum,
		'max_price' => $maximum,
		'only_in_stock' => genosys_shop_switch( $values['only_in_stock'] ?? false ),
		'only_on_sale' => genosys_shop_switch( $values['only_on_sale'] ?? false ),
		'orderby' => $orderby,
	);
}

function genosys_shop_filters() {
	// These controls are read-only catalogue filters; no mutation or nonce is involved.
	return genosys_shop_normalize_filters( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/** Identify the shop before WC_Query's priority-10 pre_get_posts processing. */
function genosys_shop_is_main_query( $query ) {
	if ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() || $query->is_feed() || $query->is_tax() || ! function_exists( 'wc_get_page_id' ) ) {
		return false;
	}
	$shop_id = wc_get_page_id( 'shop' );
	return $query->is_post_type_archive( 'product' ) || ( $shop_id > 0 && $query->is_page( $shop_id ) ) || ( function_exists( 'genosys_is_product_search' ) && genosys_is_product_search( $query ) );
}

function genosys_shop_query_args( $filters ) {
	$args = array( 'orderby' => $filters['orderby'] );
	if ( function_exists( 'genosys_is_product_search' ) && genosys_is_product_search() ) {
		$args['s'] = genosys_product_search_term();
		$args['post_type'] = 'product';
	}
	if ( $filters['categories'] ) {
		$args['shop_cat'] = $filters['categories'];
	}
	foreach ( array( 'min_price', 'max_price' ) as $key ) {
		if ( '' !== $filters[ $key ] ) {
			$args[ $key ] = $filters[ $key ];
		}
	}
	foreach ( array( 'only_in_stock', 'only_on_sale' ) as $key ) {
		if ( $filters[ $key ] ) {
			$args[ $key ] = '1';
		}
	}
	return $args;
}

function genosys_shop_prepare_query( $query ) {
	if ( ! genosys_shop_is_main_query( $query ) ) {
		return;
	}
	$filters = genosys_shop_filters();
	$args = genosys_shop_query_args( $filters );
	// Woo reads these values directly from GET; sanitize before its native clauses run.
	foreach ( array( 'shop_cat', 'min_price', 'max_price', 'only_in_stock', 'only_on_sale', 'orderby' ) as $key ) {
		if ( isset( $args[ $key ] ) ) {
			$_GET[ $key ] = $args[ $key ];
		} else {
			unset( $_GET[ $key ] );
		}
	}
	$query->set( 'orderby', $filters['orderby'] );
	$query->set( 'posts_per_page', 12 );
	$query->set( 'genosys_shop_query', true );
}
add_action( 'pre_get_posts', 'genosys_shop_prepare_query', 1 );

/** Add restrictions without replacing Woo's visibility and extension clauses. */
function genosys_shop_product_query( $query ) {
	if ( ! genosys_shop_is_main_query( $query ) ) {
		return;
	}
	$filters = genosys_shop_filters();
	$query->set( 'posts_per_page', 12 );
	if ( $filters['categories'] ) {
		$clause = array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $filters['categories'], 'operator' => 'IN', 'include_children' => true );
		$existing = $query->get( 'tax_query' );
		$query->set( 'tax_query', $existing ? array( 'relation' => 'AND', $existing, $clause ) : array( $clause ) );
	}
	if ( $filters['only_in_stock'] ) {
		$clause = array( 'key' => '_stock_status', 'value' => 'instock', 'compare' => '=' );
		$existing = $query->get( 'meta_query' );
		$query->set( 'meta_query', $existing ? array( 'relation' => 'AND', $existing, $clause ) : array( $clause ) );
	}
	if ( $filters['only_on_sale'] ) {
		$sale_ids = array_map( 'intval', wc_get_product_ids_on_sale() );
		$existing = (array) $query->get( 'post__in' );
		$ids = $existing ? array_values( array_intersect( $existing, $sale_ids ) ) : $sale_ids;
		$query->set( 'post__in', $ids ? $ids : array( 0 ) );
	}
}
add_action( 'woocommerce_product_query', 'genosys_shop_product_query', 20, 1 );

/** Category names, hierarchy and padded counts remain database-backed. */
function genosys_shop_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC', 'pad_counts' => true ) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	// The database name sort orders siblings; traverse parents before their children.
	$children = array();
	foreach ( $terms as $term ) {
		$children[ (int) $term->parent ][] = $term;
	}
	$ordered = array();
	$seen = array();
	$walk = function ( $parent ) use ( &$walk, &$ordered, &$seen, $children ) {
		foreach ( $children[ $parent ] ?? array() as $term ) {
			if ( isset( $seen[ $term->term_id ] ) ) {
				continue;
			}
			$seen[ $term->term_id ] = true;
			$ordered[] = $term;
			$walk( $term->term_id );
		}
	};
	$walk( 0 );
	// Retain terms if an extension excluded their parent from this result set.
	foreach ( $terms as $term ) {
		if ( ! isset( $seen[ $term->term_id ] ) ) {
			$seen[ $term->term_id ] = true;
			$ordered[] = $term;
			$walk( $term->term_id );
		}
	}
	return $ordered;
}

/** Reuse Woo's lookup aggregate, tax adjustments and widget extension filters. */
function genosys_shop_price_bounds() {
	static $bounds = null;
	if ( null !== $bounds ) {
		return $bounds;
	}
	$bounds = array( 'min' => 0.0, 'max' => 0.0, 'step' => 1.0 );
	if ( ! function_exists( 'WC' ) || ! WC()->query || ! WC()->query->get_main_query() || ! class_exists( 'WC_Widget_Price_Filter' ) ) {
		return $bounds;
	}
	if ( ! class_exists( 'Genosys_Shop_Price_Lookup', false ) ) {
		class Genosys_Shop_Price_Lookup extends WC_Widget_Price_Filter {
			// No widget registration or unrelated slider assets are needed by this view.
			public function __construct() {}
			public function bounds() { return $this->get_filtered_price(); }
		}
	}
	$prices = ( new Genosys_Shop_Price_Lookup() )->bounds();
	if ( ! $prices || null === $prices->min_price || null === $prices->max_price ) {
		return $bounds;
	}
	$minimum = (float) $prices->min_price;
	$maximum = (float) $prices->max_price;
	if ( wc_tax_enabled() && ! wc_prices_include_tax() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
		$tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' );
		$rates = WC_Tax::get_rates( $tax_class );
		if ( $rates ) {
			$minimum += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $minimum, $rates ) );
			$maximum += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $maximum, $rates ) );
		}
	}
	$step = max( 1.0, (float) apply_filters( 'woocommerce_price_filter_widget_step', 10 ) );
	$minimum = (float) apply_filters( 'woocommerce_price_filter_widget_min_amount', floor( $minimum / $step ) * $step );
	$maximum = (float) apply_filters( 'woocommerce_price_filter_widget_max_amount', ceil( $maximum / $step ) * $step );
	$bounds = array( 'min' => max( 0.0, $minimum ), 'max' => max( $minimum, $maximum, 0.0 ), 'step' => $step );
	return $bounds;
}

/** Clean native pagination URLs so malformed original GET data cannot reappear. */
function genosys_shop_pagenum_link( $url ) {
	if ( is_admin() || ! function_exists( 'is_shop' ) || ( ! is_shop() && ! ( function_exists( 'genosys_is_product_search' ) && genosys_is_product_search() ) ) ) {
		return $url;
	}
	$url = remove_query_arg( array( 'shop_cat', 'min_price', 'max_price', 'only_in_stock', 'only_on_sale', 'orderby', 'add-to-cart' ), $url );
	return add_query_arg( genosys_shop_query_args( genosys_shop_filters() ), $url );
}
add_filter( 'get_pagenum_link', 'genosys_shop_pagenum_link', 20 );

/** Preserve native permalink pagination and any independent extension filters. */
function genosys_shop_url( $overrides = array(), $page = 1 ) {
	$values = genosys_shop_filters();
	foreach ( is_array( $overrides ) ? $overrides : array() as $key => $value ) {
		$key = 'shop_cat' === $key ? 'categories' : $key;
		if ( array_key_exists( $key, $values ) ) {
			$values[ $key ] = $value;
		}
	}
	$filters = genosys_shop_normalize_filters( $values );
	$page = max( 1, absint( $page ) );
	if ( function_exists( 'is_shop' ) && ( is_shop() || ( function_exists( 'genosys_is_product_search' ) && genosys_is_product_search() ) ) ) {
		$url = get_pagenum_link( $page, false );
	} else {
		$url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
		if ( $page > 1 ) {
			$url = add_query_arg( 'paged', $page, $url );
		}
	}
	$url = remove_query_arg( array( 'shop_cat', 'min_price', 'max_price', 'only_in_stock', 'only_on_sale', 'orderby', 'add-to-cart' ), $url );
	return add_query_arg( genosys_shop_query_args( $filters ), $url );
}

function genosys_shop_pagination() {
	global $wp_query;
	$total = $wp_query instanceof WP_Query ? (int) $wp_query->max_num_pages : 0;
	if ( $total <= 1 ) {
		return array();
	}
	$big = 999999999;
	$args = apply_filters( 'woocommerce_pagination_args', array(
		'base' => esc_url_raw( str_replace( (string) $big, '%#%', genosys_shop_url( array(), $big ) ) ),
		'format' => '',
		'add_args' => false,
		'current' => max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ),
		'total' => $total,
		'prev_text' => 'قبلی',
		'next_text' => 'بعدی',
		'type' => 'array',
		'end_size' => 1,
		'mid_size' => 2,
	) );
	$args['type'] = 'array';
	$links = paginate_links( $args );
	return is_array( $links ) ? $links : array();
}

function genosys_shop_template( $template ) {
	if ( ! is_admin() && function_exists( 'is_shop' ) && ( is_shop() || ( function_exists( 'genosys_is_product_search' ) && genosys_is_product_search() ) ) ) {
		$shop_template = get_theme_file_path( '/page-shop.php' );
		if ( is_readable( $shop_template ) ) {
			return $shop_template;
		}
	}
	return $template;
}
add_filter( 'template_include', 'genosys_shop_template', 99 );

function genosys_shop_assets() {
	if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! ( function_exists( 'genosys_is_product_search' ) && genosys_is_product_search() ) ) ) {
		return;
	}
	wp_enqueue_style( 'genosys-products', get_theme_file_uri( '/assets/css/products.css' ), array( 'genosys-style' ), '1.5.80' );
	wp_enqueue_script( 'genosys-favorites', get_theme_file_uri( '/assets/js/favorites.js' ), array(), '1.4.0', true );
	wp_enqueue_style( 'genosys-shop', get_theme_file_uri( '/assets/css/shop.css' ), array( 'genosys-products' ), '1.5.49' );
	wp_enqueue_script( 'genosys-shop', get_theme_file_uri( '/assets/js/shop.js' ), array(), '1.5.46', true );
}
add_action( 'wp_enqueue_scripts', 'genosys_shop_assets', 20 );
