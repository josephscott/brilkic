<?php
declare( strict_types = 1 );

use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;

function run_app() : void {
	send_default_headers();

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

function send_default_headers() : void {
	// Under the CLI/test harness output may already be flushed; once headers
	// are committed there is nothing to send, and emitting would warn. Bail
	// rather than suppress, so genuine "headers already sent" cases surface
	// at their real origin.
	if ( headers_sent() ) {
		return;
	}

	// Pin the wire charset to the one the escaper is built with so the two
	// cannot drift; a utf-8-correct escaper served as another charset is
	// bypassable. An empty DEFAULT_CONTENT_TYPE opts out (e.g. an API that
	// sets its own type per route). nosniff is always sent to stop the
	// browser second-guessing the declared type.
	// @phpstan-ignore notIdentical.alwaysTrue (DEFAULT_CONTENT_TYPE is project-configurable; '' opts out)
	if ( Config::DEFAULT_CONTENT_TYPE !== '' ) {
		header( 'Content-Type: ' . Config::DEFAULT_CONTENT_TYPE . '; charset=' . Config::CHAR_SET );
	}

	header( 'X-Content-Type-Options: nosniff' );
}

function run_route( string $file, mixed $vars = [] ) : void {
	$requested = Config::ROUTE_PATH . $file;

	// Canonicalize both the route root and the requested path, then require
	// that the resolved file lives strictly inside the root. This mirrors
	// template() and neutralizes "../" traversal and symlinks that would
	// otherwise let a route escape Config::ROUTE_PATH. realpath() also
	// returns false for paths that do not exist, covering the missing-file
	// case.
	$base = realpath( Config::ROUTE_PATH );
	$file = realpath( $requested );

	if (
		$base === false
		|| $file === false
		|| ! str_starts_with( $file, $base . DIRECTORY_SEPARATOR )
		|| ! is_readable( $file )
	) {
		log_error( "Route not readable: $requested" );
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
