<?php
/** Serve the brand story immediately, with or without a saved WordPress Page. */
defined( 'ABSPATH' ) || exit;

/** Resolve only playable videos selected from this site's media library. */
function genosys_brand_video_attachment( $id ) {
	if ( ! ( is_int( $id ) || is_string( $id ) ) || ! preg_match( '/^[1-9][0-9]*$/D', (string) $id ) ) { return array(); }
	$id = (int) $id;
	if ( 'attachment' !== get_post_type( $id ) || ! in_array( get_post_status( $id ), array( 'inherit', 'publish' ), true ) ) { return array(); }
	$type = get_post_mime_type( $id );
	if ( ! in_array( $type, array( 'video/mp4', 'video/webm', 'video/ogg' ), true ) ) { return array(); }
	$url = wp_get_attachment_url( $id );
	if ( ! is_string( $url ) || ! in_array( strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ), array( 'http', 'https' ), true ) ) { return array(); }
	$url = esc_url_raw( $url, array( 'http', 'https' ) );
	return $url ? array( 'id' => $id, 'url' => $url, 'type' => $type ) : array();
}

function genosys_brand_uploaded_video() {
	return genosys_brand_video_attachment( genosys_site_setting( 'brand_video_id', 0 ) );
}

function genosys_brand_request_path() {
	if ( is_admin() || empty( $_SERVER['REQUEST_URI'] ) || ! is_string( $_SERVER['REQUEST_URI'] ) ) { return false; }
	$request = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	$brand = wp_parse_url( home_url( '/brand/' ), PHP_URL_PATH );
	return is_string( $request ) && is_string( $brand ) && untrailingslashit( $request ) === untrailingslashit( $brand );
}

function genosys_register_brand_route() {
	add_rewrite_rule( '^brand/?$', 'index.php?genosys_brand=1', 'top' );
	if ( '1' !== get_option( 'genosys_brand_route_v1' ) ) {
		flush_rewrite_rules( false );
		update_option( 'genosys_brand_route_v1', '1', false );
	}
}
add_action( 'init', 'genosys_register_brand_route', 20 );

function genosys_brand_query_vars( $vars ) {
	$vars[] = 'genosys_brand';
	return $vars;
}
add_filter( 'query_vars', 'genosys_brand_query_vars' );

/** Also match the physical URL so plain permalink installations can render it. */
function genosys_brand_parse_request( $wp ) {
	if ( ! genosys_brand_request_path() ) { return; }
	foreach ( array( 'error', 'p', 'page_id', 'attachment_id', 'name', 'pagename', 's' ) as $key ) { unset( $wp->query_vars[ $key ] ); }
	$wp->query_vars['genosys_brand'] = '1';
}
add_action( 'parse_request', 'genosys_brand_parse_request', 99 );

function genosys_is_brand() {
	return genosys_brand_request_path() && '1' === (string) get_query_var( 'genosys_brand' );
}

function genosys_brand_main_query( $query ) {
	if ( ! $query->is_main_query() || ! genosys_brand_request_path() || '1' !== (string) $query->get( 'genosys_brand' ) ) { return; }
	$query->is_home = false;
	$query->is_404 = false;
	$query->set( 'post__in', array( 0 ) );
	$query->set( 'posts_per_page', 1 );
	$query->set( 'ignore_sticky_posts', true );
}
add_action( 'parse_query', 'genosys_brand_main_query' );

function genosys_brand_pre_handle_404( $preempt, $query ) {
	if ( $query->is_main_query() && genosys_brand_request_path() && '1' === (string) $query->get( 'genosys_brand' ) ) {
		$query->is_404 = false;
		status_header( 200 );
		return true;
	}
	return $preempt;
}
add_filter( 'pre_handle_404', 'genosys_brand_pre_handle_404', 10, 2 );

function genosys_brand_template( $template ) {
	return genosys_is_brand() ? get_theme_file_path( '/page-brand.php' ) : $template;
}
add_filter( 'template_include', 'genosys_brand_template', 99 );

function genosys_brand_document_title( $title ) {
	return genosys_is_brand() ? 'برند ژنوسیس | ' . get_bloginfo( 'name' ) : $title;
}
add_filter( 'pre_get_document_title', 'genosys_brand_document_title' );

function genosys_brand_canonical_redirect( $redirect ) {
	return genosys_is_brand() ? false : $redirect;
}
add_filter( 'redirect_canonical', 'genosys_brand_canonical_redirect' );

function genosys_brand_body_class( $classes ) {
	if ( genosys_is_brand() ) { $classes[] = 'genosys-brand-page'; }
	return $classes;
}
add_filter( 'body_class', 'genosys_brand_body_class' );
