<?php
/** The contact page is available immediately, including without a saved Page. */
defined( 'ABSPATH' ) || exit;

function genosys_contact_request_path() {
	if ( is_admin() || empty( $_SERVER['REQUEST_URI'] ) || ! is_string( $_SERVER['REQUEST_URI'] ) ) { return false; }
	$request = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	$contact = wp_parse_url( home_url( '/contact/' ), PHP_URL_PATH );
	return is_string( $request ) && is_string( $contact ) && untrailingslashit( $request ) === untrailingslashit( $contact );
}

function genosys_register_contact_route() {
	add_rewrite_rule( '^contact/?$', 'index.php?genosys_contact=1', 'top' );
	if ( '1' !== get_option( 'genosys_contact_route_v1' ) ) {
		flush_rewrite_rules( false );
		update_option( 'genosys_contact_route_v1', '1', false );
	}
}
add_action( 'init', 'genosys_register_contact_route', 20 );

function genosys_contact_query_vars( $vars ) {
	$vars[] = 'genosys_contact';
	return $vars;
}
add_filter( 'query_vars', 'genosys_contact_query_vars' );

/** Match the physical URL as well as the rewrite, including plain permalinks. */
function genosys_contact_parse_request( $wp ) {
	if ( ! genosys_contact_request_path() ) { return; }
	foreach ( array( 'error', 'p', 'page_id', 'attachment_id', 'name', 'pagename', 's' ) as $key ) { unset( $wp->query_vars[ $key ] ); }
	$wp->query_vars['genosys_contact'] = '1';
}
add_action( 'parse_request', 'genosys_contact_parse_request', 99 );

function genosys_is_contact() {
	return genosys_contact_request_path() && '1' === (string) get_query_var( 'genosys_contact' );
}

function genosys_contact_main_query( $query ) {
	if ( ! $query->is_main_query() || ! genosys_contact_request_path() || '1' !== (string) $query->get( 'genosys_contact' ) ) { return; }
	$query->is_home = false;
	$query->is_404 = false;
	$query->set( 'post__in', array( 0 ) );
	$query->set( 'posts_per_page', 1 );
	$query->set( 'ignore_sticky_posts', true );
}
add_action( 'parse_query', 'genosys_contact_main_query' );

function genosys_contact_pre_handle_404( $preempt, $query ) {
	if ( $query->is_main_query() && genosys_contact_request_path() && '1' === (string) $query->get( 'genosys_contact' ) ) {
		$query->is_404 = false;
		status_header( 200 );
		return true;
	}
	return $preempt;
}
add_filter( 'pre_handle_404', 'genosys_contact_pre_handle_404', 10, 2 );

function genosys_contact_template( $template ) {
	return genosys_is_contact() ? get_theme_file_path( '/page-contact.php' ) : $template;
}
add_filter( 'template_include', 'genosys_contact_template', 99 );

function genosys_contact_document_title( $title ) {
	return genosys_is_contact() ? genosys_site_setting( 'contact_page_title', 'تماس با ما' ) . ' | ' . get_bloginfo( 'name' ) : $title;
}
add_filter( 'pre_get_document_title', 'genosys_contact_document_title' );

function genosys_contact_canonical_redirect( $redirect ) {
	return genosys_is_contact() ? false : $redirect;
}
add_filter( 'redirect_canonical', 'genosys_contact_canonical_redirect' );

function genosys_contact_body_class( $classes ) {
	if ( genosys_is_contact() ) { $classes[] = 'genosys-contact-page'; }
	return $classes;
}
add_filter( 'body_class', 'genosys_contact_body_class' );

function genosys_contact_no_cache() {
	if ( genosys_is_contact() ) { nocache_headers(); }
}
add_action( 'template_redirect', 'genosys_contact_no_cache', 1 );
