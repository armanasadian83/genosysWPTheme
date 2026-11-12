<?php
/** Packages use the existing product cards, only in an explicitly selected package category. */
defined( 'ABSPATH' ) || exit;

function genosys_package_special_category( $create = false ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) { return null; }
	$stored = (int) get_option( 'genosys_package_category_id', 0 );
	$term = $stored ? get_term( $stored, 'product_cat' ) : null;
	if ( $term && ! is_wp_error( $term ) ) { return $term; }
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC' ) );
	foreach ( is_wp_error( $terms ) ? array() : $terms as $candidate ) {
		$name = str_replace( array( 'ي', 'ك' ), array( 'ی', 'ک' ), $candidate->name );
		$name = preg_replace( '/[\s\x{200C}\x{200D}\x{064B}-\x{065F}\x{0670}ـ-]+/u', '', $name );
		if ( in_array( $name, array( 'پکیجهایویژه', 'پکیجویژه' ), true ) ) {
			if ( $create ) { update_option( 'genosys_package_category_id', (int) $candidate->term_id, false ); }
			return $candidate;
		}
	}
	if ( ! $create ) { return null; }
	$result = wp_insert_term( 'پکیج‌های ویژه', 'product_cat', array( 'slug' => 'special-packages' ) );
	if ( is_wp_error( $result ) ) { return $result; }
	update_option( 'genosys_package_category_id', (int) $result['term_id'], false );
	return get_term( $result['term_id'], 'product_cat' );
}

function genosys_package_category_clause( $clause, $term ) {
	if ( ! is_array( $clause ) || ! $term ) { return false; }
	if ( 'product_cat' === ( $clause['taxonomy'] ?? '' ) && in_array( strtoupper( $clause['operator'] ?? 'IN' ), array( 'IN', 'AND' ), true ) ) {
		$field = $clause['field'] ?? 'term_id';
		foreach ( (array) ( $clause['terms'] ?? array() ) as $value ) {
			if ( 'slug' === $field && (string) $value === $term->slug ) { return true; }
			if ( 'name' === $field && (string) $value === $term->name ) { return true; }
			if ( 'term_taxonomy_id' === $field && (int) $value === (int) $term->term_taxonomy_id ) { return true; }
			if ( in_array( $field, array( 'term_id', 'id' ), true ) && (int) $value === (int) $term->term_id ) { return true; }
		}
	}
	foreach ( $clause as $child ) { if ( is_array( $child ) && genosys_package_category_clause( $child, $term ) ) { return true; } }
	return false;
}

function genosys_package_query_has_category( $query ) {
	$term = genosys_package_special_category();
	if ( ! $term || is_wp_error( $term ) ) { return false; }
	if ( genosys_package_category_clause( $query->get( 'tax_query' ), $term ) ) { return true; }
	$slug = $query->get( 'product_cat' );
	if ( is_string( $slug ) && in_array( $term->slug, explode( '/', $slug ), true ) ) { return true; }
	if ( 'product_cat' === $query->get( 'taxonomy' ) && in_array( (string) $query->get( 'term' ), array( $term->slug, (string) $term->term_id ), true ) ) { return true; }
	if ( $query->is_main_query() && function_exists( 'genosys_shop_is_main_query' ) && genosys_shop_is_main_query( $query ) ) {
		return in_array( (int) $term->term_id, genosys_shop_filters()['categories'], true );
	}
	return false;
}

function genosys_package_catalog_visibility( $query ) {
	if ( is_admin() || ! $query instanceof WP_Query || $query->is_singular() ) { return; }
	$type = $query->get( 'post_type' );
	$is_products = in_array( $type, array( 'product', 'any' ), true ) || ( is_array( $type ) && ( in_array( 'product', $type, true ) || in_array( 'any', $type, true ) ) ) || $query->is_search() || $query->is_tax( array( 'product_cat', 'product_tag' ) );
	if ( ! $is_products || genosys_package_query_has_category( $query ) ) { return; }
	$exclude = array( 'taxonomy' => 'product_type', 'field' => 'slug', 'terms' => array( 'genosys_package' ), 'operator' => 'NOT IN' );
	$existing = $query->get( 'tax_query' );
	$query->set( 'tax_query', $existing ? array( 'relation' => 'AND', $existing, $exclude ) : array( $exclude ) );
}
add_action( 'pre_get_posts', 'genosys_package_catalog_visibility', 99 );

function genosys_package_edit_link( $link, $id ) {
	if ( 'product' === get_post_type( $id ) && has_term( 'genosys_package', 'product_type', $id ) && current_user_can( 'edit_post', $id ) ) {
		return admin_url( 'admin.php?page=genosys-add-package&package_id=' . (int) $id );
	}
	return $link;
}
add_filter( 'get_edit_post_link', 'genosys_package_edit_link', 10, 2 );

/** Category links arrive at the existing shop grid, with its normal product-card template. */
function genosys_package_category_shop_redirect() {
	if ( is_admin() || ! function_exists( 'is_product_category' ) || ! is_product_category() ) { return; }
	$special = genosys_package_special_category(); $current = get_queried_object();
	if ( ! $special || is_wp_error( $special ) || ! $current || (int) $current->term_id !== (int) $special->term_id ) { return; }
	$args = genosys_shop_query_args( genosys_shop_filters() );
	$args['shop_cat'] = array_values( array_unique( array_merge( (array) ( $args['shop_cat'] ?? array() ), array( (int) $special->term_id ) ) ) );
	$page = max( 1, (int) get_query_var( 'paged' ) ); if ( $page > 1 ) { $args['paged'] = $page; }
	wp_safe_redirect( add_query_arg( $args, wc_get_page_permalink( 'shop' ) ), 302 ); exit;
}
add_action( 'template_redirect', 'genosys_package_category_shop_redirect', 5 );

/** Woo's related-product SQL does not run WP_Query; enforce the same catalog rule there. */
function genosys_package_hide_related( $ids ) {
	if ( is_admin() ) { return $ids; }
	return array_values( array_filter( (array) $ids, static function ( $id ) { return ! has_term( 'genosys_package', 'product_type', (int) $id ); } ) );
}
add_filter( 'woocommerce_related_products', 'genosys_package_hide_related', 20 );
add_filter( 'woocommerce_product_get_upsell_ids', 'genosys_package_hide_related', 20 );
add_filter( 'woocommerce_product_get_cross_sell_ids', 'genosys_package_hide_related', 20 );
