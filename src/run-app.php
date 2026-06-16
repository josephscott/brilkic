<?php
declare( strict_types = 1 );

use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;

function run_app() : void {
	// Buffer the whole response. Nothing reaches the SAPI until ob_end_flush()
	// in the finally below, so headers_sent() stays false for the entire route
	// and template render. That is what lets the csrf_* helpers start a session
	// lazily, mid-render, on just the pages that use one: the app no longer has
	// to start a session eagerly on every request to beat the point where
	// streamed output would commit the headers. Flushing in a finally keeps the
	// buffer balanced for callers that wrap run_app() in their own buffer (the
	// test harness does) and still emits whatever rendered if a route throws.
	ob_start();

	try {
		// Hook up an existing session before any route runs, so a request that
		// continues an earlier session sees its data -- without starting a
		// session for visitors who do not have one. On by default; an app opts
		// out via Config::SESSION_AUTO_RESUME.
		session_resume_if_present();

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
				$methods = [];
				$allowed = $result[1];
				if ( is_array( $allowed ) ) {
					foreach ( $allowed as $name ) {
						if ( is_string( $name ) ) {
							$methods[] = $name;
						}
					}
					header( 'Allow: ' . implode( ', ', $methods ) );
				}
				run_error( 405, [ 'allowed' => $methods ] );
				return;

			case Dispatcher::NOT_FOUND:
			default:
				run_error( 404, [ 'method' => $method, 'uri' => $uri ] );
				return;
		}
	} catch ( Throwable $e ) {
		// A route or its templates threw. Log the cause -- never swallow it --
		// then discard whatever half-rendered and put the 500 handler in its
		// place. Buffering makes the clean swap possible: nothing has reached
		// the client yet.
		log_error( (string) $e );
		ob_clean();
		run_error( 500 );
	} finally {
		// Commit the buffered response: headers first, then the body.
		ob_end_flush();
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
	// A route that cannot be resolved or read is an internal error: hand off to
	// the 500 handler (or the bare status when none is registered).
	if ( ! render_route_file( $file, $vars ) ) {
		run_error( 500 );
	}
}

/**
 * Send an HTTP error status and, when the app registered a handler for it via
 * Router::error(), render that handler. With no handler the bare status is sent
 * and nothing is rendered -- the framework default, so apps that register
 * nothing behave exactly as before.
 *
 * The handler is rendered with render_route_file() directly (not run_route), so
 * a missing handler logs and falls back to the bare status rather than recursing
 * back through the 500 path.
 *
 * @param mixed $vars
 */
function run_error( int $status, mixed $vars = [] ) : void {
	http_response_code( $status );

	$handler = Router::errors()[$status] ?? null;
	if ( $handler !== null ) {
		render_route_file( $handler, $vars );
	}
}

/**
 * Resolve a route file inside Config::ROUTE_PATH and require it in an isolated
 * scope, returning whether it ran.
 *
 * Canonicalizes both the route root and the requested path, then requires that
 * the resolved file lives strictly inside the root. This mirrors template() and
 * neutralizes "../" traversal and symlinks that would otherwise let a route
 * escape Config::ROUTE_PATH. realpath() also returns false for paths that do not
 * exist, covering the missing-file case. On any of those it logs and returns
 * false, leaving the caller to choose the status.
 *
 * The file runs in an isolated scope: the path is passed positionally and read
 * via func_get_arg(), so it is never a named variable in scope. Only $vars (the
 * matched route parameters, or error context) is exposed. The Config class stays
 * available as it is global. Nothing else leaks in.
 *
 * @param mixed $vars
 */
function render_route_file( string $file, mixed $vars = [] ) : bool {
	$requested = Config::ROUTE_PATH . $file;

	$base = realpath( Config::ROUTE_PATH );
	$file = realpath( $requested );

	if (
		$base === false
		|| $file === false
		|| ! str_starts_with( $file, $base . DIRECTORY_SEPARATOR )
		|| ! is_readable( $file )
	) {
		log_error( "Route not readable: $requested" );
		return false;
	}

	// @phpstan-ignore arguments.count (extra arg read via func_get_arg)
	( static function( mixed $vars ) : void {
		require func_get_arg( 1 );
	} )( $vars, $file );

	return true;
}
