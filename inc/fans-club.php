<?php
/** Self-contained route for the visual-only customer club page. */
defined( 'ABSPATH' ) || exit;

function genosys_register_fans_club_route() {
	add_rewrite_rule( '^fans-club/?$', 'index.php?genosys_fans_club=1', 'top' );
	if ( '1' !== get_option( 'genosys_fans_club_route_v1' ) ) {
		flush_rewrite_rules( false );
		update_option( 'genosys_fans_club_route_v1', '1', false );
	}
}
add_action( 'init', 'genosys_register_fans_club_route', 20 );

function genosys_fans_club_query_var( $vars ) {
	$vars[] = 'genosys_fans_club';
	return $vars;
}
add_filter( 'query_vars', 'genosys_fans_club_query_var' );

function genosys_fans_club_request_path() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return false;
	}
	$request_path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	$club_path    = wp_parse_url( home_url( '/fans-club/' ), PHP_URL_PATH );
	return is_string( $request_path ) && is_string( $club_path ) && untrailingslashit( $request_path ) === untrailingslashit( $club_path );
}

function genosys_is_fans_club() {
	return genosys_fans_club_request_path() && '1' === (string) get_query_var( 'genosys_fans_club' );
}

function genosys_fans_club_main_query( $query ) {
	if ( $query->is_main_query() && genosys_fans_club_request_path() && $query->get( 'genosys_fans_club' ) ) {
		$query->is_home = false;
		$query->is_404  = false;
	}
}
add_action( 'parse_query', 'genosys_fans_club_main_query' );

function genosys_fans_club_status( $preempt, $query ) {
	if ( genosys_fans_club_request_path() && $query->get( 'genosys_fans_club' ) ) {
		$query->is_404 = false;
		status_header( 200 );
		return true;
	}
	return $preempt;
}
add_filter( 'pre_handle_404', 'genosys_fans_club_status', 10, 2 );

function genosys_fans_club_template( $template ) {
	return genosys_is_fans_club() ? get_theme_file_path( '/page-fans-club.php' ) : $template;
}
add_filter( 'template_include', 'genosys_fans_club_template', 99 );

function genosys_fans_club_title( $title ) {
	return genosys_is_fans_club() ? 'باشگاه مشتریان | ' . get_bloginfo( 'name' ) : $title;
}
add_filter( 'pre_get_document_title', 'genosys_fans_club_title' );

function genosys_fans_club_canonical( $redirect ) {
	return genosys_is_fans_club() ? false : $redirect;
}
add_filter( 'redirect_canonical', 'genosys_fans_club_canonical' );

function genosys_fans_club_body_class( $classes ) {
	if ( genosys_is_fans_club() ) {
		$classes[] = 'genosys-fans-club';
	}
	return $classes;
}
add_filter( 'body_class', 'genosys_fans_club_body_class' );
