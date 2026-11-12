<?php
/** Standalone phone-login route. */
defined( 'ABSPATH' ) || exit;

function genosys_register_login_route() {
	add_rewrite_rule( '^login/?$', 'index.php?genosys_login=1', 'top' );
	if ( '1' !== get_option( 'genosys_login_route_v1' ) ) {
		flush_rewrite_rules( false );
		update_option( 'genosys_login_route_v1', '1', false );
	}
}
add_action( 'init', 'genosys_register_login_route', 20 );

function genosys_login_query_var( $vars ) {
	$vars[] = 'genosys_login';
	return $vars;
}
add_filter( 'query_vars', 'genosys_login_query_var' );

function genosys_login_request_path() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return false;
	}
	$request_path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	$login_path   = wp_parse_url( home_url( '/login/' ), PHP_URL_PATH );
	return is_string( $request_path ) && is_string( $login_path ) && untrailingslashit( $request_path ) === untrailingslashit( $login_path );
}

function genosys_is_login() {
	return genosys_login_request_path() && '1' === (string) get_query_var( 'genosys_login' );
}

function genosys_login_main_query( $query ) {
	if ( $query->is_main_query() && genosys_login_request_path() && $query->get( 'genosys_login' ) ) {
		$query->is_home = false;
		$query->is_404  = false;
	}
}
add_action( 'parse_query', 'genosys_login_main_query' );

function genosys_login_status( $preempt, $query ) {
	if ( genosys_login_request_path() && $query->get( 'genosys_login' ) ) {
		$query->is_404 = false;
		status_header( 200 );
		return true;
	}
	return $preempt;
}
add_filter( 'pre_handle_404', 'genosys_login_status', 10, 2 );

/** Only this explicit, same-site return route is accepted by the public SMS flow. */
function genosys_login_return_url( $value ) {
	$club = home_url( '/fans-club/' );
	$path = wp_parse_url( $club, PHP_URL_PATH );
	if ( is_string( $value ) && ( $value === $club || $value === untrailingslashit( $club ) || $value === $path || $value === untrailingslashit( $path ) ) ) {
		return $club;
	}
	return home_url( '/' );
}

function genosys_login_redirect_signed_in() {
	if ( genosys_login_request_path() && is_user_logged_in() ) {
		wp_safe_redirect( genosys_login_return_url( isset( $_GET['redirect_to'] ) && is_string( $_GET['redirect_to'] ) ? wp_unslash( $_GET['redirect_to'] ) : '' ) );
		exit;
	}
}
add_action( 'template_redirect', 'genosys_login_redirect_signed_in', 0 );

function genosys_login_no_cache() {
	if ( genosys_is_login() ) {
		nocache_headers();
	}
}
add_action( 'template_redirect', 'genosys_login_no_cache', 1 );

function genosys_login_template( $template ) {
	return genosys_is_login() ? get_theme_file_path( '/page-login.php' ) : $template;
}
add_filter( 'template_include', 'genosys_login_template', 99 );

function genosys_login_title( $title ) {
	return genosys_is_login() ? 'ورود به حساب کاربری | ' . get_bloginfo( 'name' ) : $title;
}
add_filter( 'pre_get_document_title', 'genosys_login_title' );

function genosys_login_canonical( $redirect ) {
	return genosys_is_login() ? false : $redirect;
}
add_filter( 'redirect_canonical', 'genosys_login_canonical' );

function genosys_login_body_class( $classes ) {
	if ( genosys_is_login() ) {
		$classes[] = 'genosys-login-page';
	}
	return $classes;
}
add_filter( 'body_class', 'genosys_login_body_class' );
