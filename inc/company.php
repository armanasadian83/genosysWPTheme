<?php
/** Serve the company introduction immediately, with or without a saved WordPress Page. */
defined( 'ABSPATH' ) || exit;

function genosys_company_request_path() {
	if ( is_admin() || empty( $_SERVER['REQUEST_URI'] ) || ! is_string( $_SERVER['REQUEST_URI'] ) ) { return false; }
	$request = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	$company = wp_parse_url( home_url( '/company/' ), PHP_URL_PATH );
	return is_string( $request ) && is_string( $company ) && untrailingslashit( $request ) === untrailingslashit( $company );
}

function genosys_register_company_route() {
	add_rewrite_rule( '^company/?$', 'index.php?genosys_company=1', 'top' );
	if ( '1' !== get_option( 'genosys_company_route_v1' ) ) {
		flush_rewrite_rules( false );
		update_option( 'genosys_company_route_v1', '1', false );
	}
}
add_action( 'init', 'genosys_register_company_route', 20 );

function genosys_company_query_vars( $vars ) {
	$vars[] = 'genosys_company';
	return $vars;
}
add_filter( 'query_vars', 'genosys_company_query_vars' );

/** Match the physical URL too, including plain and subdirectory permalink installations. */
function genosys_company_parse_request( $wp ) {
	if ( ! genosys_company_request_path() ) { return; }
	foreach ( array( 'error', 'p', 'page_id', 'attachment_id', 'name', 'pagename', 's' ) as $key ) { unset( $wp->query_vars[ $key ] ); }
	$wp->query_vars['genosys_company'] = '1';
}
add_action( 'parse_request', 'genosys_company_parse_request', 99 );

function genosys_is_company() {
	return genosys_company_request_path() && '1' === (string) get_query_var( 'genosys_company' );
}

function genosys_company_main_query( $query ) {
	if ( ! $query->is_main_query() || ! genosys_company_request_path() || '1' !== (string) $query->get( 'genosys_company' ) ) { return; }
	$query->is_home = false;
	$query->is_404 = false;
	$query->set( 'post__in', array( 0 ) );
	$query->set( 'posts_per_page', 1 );
	$query->set( 'ignore_sticky_posts', true );
}
add_action( 'parse_query', 'genosys_company_main_query' );

function genosys_company_pre_handle_404( $preempt, $query ) {
	if ( $query->is_main_query() && genosys_company_request_path() && '1' === (string) $query->get( 'genosys_company' ) ) {
		$query->is_404 = false;
		status_header( 200 );
		return true;
	}
	return $preempt;
}
add_filter( 'pre_handle_404', 'genosys_company_pre_handle_404', 10, 2 );

function genosys_company_template( $template ) {
	return genosys_is_company() ? get_theme_file_path( '/page-company.php' ) : $template;
}
add_filter( 'template_include', 'genosys_company_template', 99 );

function genosys_company_document_title( $title ) {
	return genosys_is_company() ? 'شرکت ژنوسیس | ' . get_bloginfo( 'name' ) : $title;
}
add_filter( 'pre_get_document_title', 'genosys_company_document_title' );

function genosys_company_canonical_redirect( $redirect ) {
	return genosys_is_company() ? false : $redirect;
}
add_filter( 'redirect_canonical', 'genosys_company_canonical_redirect' );

function genosys_company_body_class( $classes ) {
	if ( genosys_is_company() ) { $classes[] = 'genosys-company-page'; }
	return $classes;
}
add_filter( 'body_class', 'genosys_company_body_class' );
