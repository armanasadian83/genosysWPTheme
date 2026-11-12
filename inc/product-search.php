<?php
/** Product-name and SKU search layered onto WooCommerce's native main query. */
defined( 'ABSPATH' ) || exit;

/** Generic WordPress searches, secondary queries and feeds are left alone. */
function genosys_is_product_search( $query = null ) {
	if ( null === $query ) {
		global $wp_query;
		$query = $wp_query;
	}
	if ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() || ! $query->is_search() || $query->is_feed() || ! function_exists( 'wc_get_product' ) ) {
		return false;
	}
	$post_type = $query->get( 'post_type' );
	return 'product' === $post_type || array( 'product' ) === $post_type;
}

/** Normalize the same Persian/Arabic letters and digits in input and database values. */
function genosys_product_search_character_map() {
	return array(
		'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', '‌' => ' ',
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
		'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
		'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	);
}

/** Public template contract: a safe, bounded query string, including the value "0". */
function genosys_product_search_term( $query = null ) {
	if ( null === $query ) {
		global $wp_query;
		$query = $wp_query;
	}
	$value = $query instanceof WP_Query ? $query->get( 's' ) : '';
	if ( ! is_scalar( $value ) || is_bool( $value ) ) {
		return '';
	}
	$value = strtr( sanitize_text_field( (string) $value ), genosys_product_search_character_map() );
	$value = trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
	return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 160, 'UTF-8' ) : wp_html_excerpt( $value, 160, '' );
}

function genosys_product_search_prepare_query( $query ) {
	if ( ! genosys_is_product_search( $query ) ) {
		return;
	}
	$query->set( 's', genosys_product_search_term( $query ) );
	// Even an administrator's public search must not expose private or draft products.
	$query->set( 'post_status', 'publish' );
	$query->set( 'genosys_product_search', true );
}
add_action( 'pre_get_posts', 'genosys_product_search_prepare_query', 2 );

/** The expression and aliases are internal constants; every replacement is prepared. */
function genosys_product_search_normalized_sql( $expression, $needle ) {
	global $wpdb;
	foreach ( genosys_product_search_character_map() as $from => $to ) {
		// Avoid extra SQL functions for characters that cannot affect this search.
		if ( false !== strpos( $needle, (string) $to ) ) {
			$expression = $wpdb->prepare( "REPLACE({$expression}, %s, %s)", $from, $to ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Internal SQL expression only.
		}
	}
	return $expression;
}

/** EXISTS keeps one result per parent and lets Woo retain its native pagination. */
function genosys_product_search_sql( $query ) {
	global $wpdb;
	$term = genosys_product_search_term( $query );
	if ( '' === $term ) {
		return '0 = 1';
	}
	$title_terms = preg_split( '/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY );
	$title_sql = array();
	foreach ( $title_terms as $word ) {
		$title = genosys_product_search_normalized_sql( "{$wpdb->posts}.post_title", $word );
		$title_sql[] = $wpdb->prepare( "{$title} LIKE %s", '%' . $wpdb->esc_like( $word ) . '%' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Prepared expression.
	}
	$sku_like = '%' . $wpdb->esc_like( $term ) . '%';
	$product_sku = genosys_product_search_normalized_sql( 'genosys_product_sku.sku', $term );
	$variation_sku = genosys_product_search_normalized_sql( 'genosys_variation_sku.sku', $term );
	$variation_stock = 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ? " AND genosys_variation_sku.stock_status <> 'outofstock'" : '';
	$sku_sql = $wpdb->prepare(
		"EXISTS (SELECT 1 FROM {$wpdb->wc_product_meta_lookup} AS genosys_product_sku
			WHERE genosys_product_sku.product_id = {$wpdb->posts}.ID AND {$product_sku} LIKE %s)
		OR EXISTS (SELECT 1 FROM {$wpdb->posts} AS genosys_variation
			INNER JOIN {$wpdb->wc_product_meta_lookup} AS genosys_variation_sku ON genosys_variation_sku.product_id = genosys_variation.ID
			WHERE genosys_variation.post_parent = {$wpdb->posts}.ID
			AND genosys_variation.post_type = 'product_variation' AND genosys_variation.post_status = 'publish'
			AND {$variation_sku} LIKE %s{$variation_stock})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Internal identifiers and prepared expressions.
		$sku_like,
		$sku_like
	);
	$sql = '((' . implode( ' AND ', $title_sql ) . ') OR (' . $sku_sql . '))';
	if ( ! is_user_logged_in() ) {
		$sql .= " AND ({$wpdb->posts}.post_password = '')";
	}
	return $sql;
}

function genosys_product_search_posts_search( $search, $query ) {
	if ( ! genosys_is_product_search( $query ) || ! $query->get( 'genosys_product_search' ) ) {
		return $search;
	}
	return ' AND (' . genosys_product_search_sql( $query ) . ') ';
}
add_filter( 'posts_search', 'genosys_product_search_posts_search', 20, 2 );

/** Woo's price widget rebuilds text search; keep its aggregate consistent with SKU results. */
function genosys_product_search_price_filter_sql( $sql ) {
	global $wpdb;
	if ( ! class_exists( 'WC_Query' ) ) {
		return $sql;
	}
	$query = WC_Query::get_main_query();
	if ( ! $query instanceof WP_Query || ! genosys_is_product_search( $query ) || ! $query->get( 'genosys_product_search' ) ) {
		return $sql;
	}
	$native_search = WC_Query::get_main_search_query_sql();
	if ( '' !== $native_search ) {
		return str_replace( ' AND ' . $native_search, ' AND (' . genosys_product_search_sql( $query ) . ')', $sql );
	}
	// Empty searches have no native fragment; insert the restriction into the parent subquery.
	$publish_clause = "AND {$wpdb->posts}.post_status = 'publish'";
	return str_replace( $publish_clause, $publish_clause . ' AND (' . genosys_product_search_sql( $query ) . ')', $sql );
}
add_filter( 'woocommerce_price_filter_sql', 'genosys_product_search_price_filter_sql', 20, 1 );

/** Keep a single match on the same search-results page as larger result sets. */
function genosys_product_search_single_result_redirect( $redirect ) {
	return genosys_is_product_search() ? false : $redirect;
}
add_filter( 'woocommerce_redirect_single_search_result', 'genosys_product_search_single_result_redirect', 20, 1 );
