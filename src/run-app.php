<?php
declare( strict_types = 1 );

use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;

function run_app() : void {
	$dispatcher = simpleDispatcher( static function( FastRoute\RouteCollector $r ) : void {
		foreach ( Router::routes() as $route ) {
			$r->addRoute( $route['method'], $route['path'], $route['file'] );
		}
	} );

	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
	$uri = $_SERVER['REQUEST_URI'] ?? '/';

	if ( ! is_string( $method ) ) {
		$method = 'GET';
	}

	if ( ! is_string( $uri ) ) {
		$uri = '/';
	}

	// Only the path is matched, so drop any query string before dispatch.
	$pos = strpos( $uri, '?' );
	if ( $pos !== false ) {
		$uri = substr( $uri, 0, $pos );
	}
	$uri = rawurldecode( $uri );

	$result = $dispatcher->dispatch( $method, $uri );

	switch ( $result[0] ) {
		case Dispatcher::FOUND:
			$file = $result[1];
			if ( is_string( $file ) ) {
				run_route( $file, $result[2] );
			}
			return;

		case Dispatcher::METHOD_NOT_ALLOWED:
			http_response_code( 405 );
			$allowed = $result[1];
			if ( is_array( $allowed ) ) {
				$methods = [];
				foreach ( $allowed as $name ) {
					if ( is_string( $name ) ) {
						$methods[] = $name;
					}
				}
				header( 'Allow: ' . implode( ', ', $methods ) );
			}
			return;

		case Dispatcher::NOT_FOUND:
		default:
			http_response_code( 404 );
			return;
	}
}

function run_route( string $file, mixed $vars = [] ) : void {
	$file = Config::ROUTE_PATH . $file;

	if ( ! is_readable( $file ) ) {
		log_error( "Route not readable: $file" );
		http_response_code( 500 );
		return;
	}

	// Run the route file in an isolated scope, mirroring template(). The
	// path is passed positionally and read via func_get_arg(), so it is
	// never a named variable in scope. Only $vars, the matched route
	// parameters, is exposed to the route file. The Config class stays
	// available as it is global. Nothing else leaks in.
	// @phpstan-ignore arguments.count (extra arg read via func_get_arg)
	( static function( mixed $vars ) : void {
		require func_get_arg( 1 );
	} )( $vars, $file );
}
