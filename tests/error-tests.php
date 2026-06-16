<?php
declare( strict_types = 1 );

// Error-condition route handlers. Router holds routes and error handlers in
// private static arrays; each case registers its own and both are reset between
// cases.

/**
 * Run a request through run_app() with error_log redirected to a throwaway file,
 * returning the rendered body and whatever was logged. Mirrors capture_log_error
 * but at the run_app() level, so a route's logged failure is asserted rather
 * than leaking to stderr.
 *
 * @return array{ body: string, logged: string }
 */
function dispatch_capturing( string $method, string $uri ) : array {
	$_SERVER['REQUEST_METHOD'] = $method;
	$_SERVER['REQUEST_URI'] = $uri;

	$tmp = __DIR__ . '/tmp/run_app_' . uniqid( '', true ) . '.log';
	$previous = ini_set( 'error_log', $tmp );

	ob_start();
	try {
		run_app();
	} finally {
		if ( is_string( $previous ) ) {
			ini_set( 'error_log', $previous );
		}
	}
	$body = (string) ob_get_clean();

	$logged = is_readable( $tmp ) ? (string) file_get_contents( $tmp ) : '';
	if ( file_exists( $tmp ) ) {
		unlink( $tmp );
	}

	return [ 'body' => $body, 'logged' => $logged ];
}

describe( 'error handlers', function() : void {
	beforeEach( function() : void {
		( new ReflectionProperty( Router::class, 'routes' ) )->setValue( null, [] );
		( new ReflectionProperty( Router::class, 'errors' ) )->setValue( null, [] );

		$this->server = $_SERVER;
		http_response_code( 200 );
	} );

	afterEach( function() : void {
		// Leave Router clean so a lingering handler cannot affect other files.
		( new ReflectionProperty( Router::class, 'routes' ) )->setValue( null, [] );
		( new ReflectionProperty( Router::class, 'errors' ) )->setValue( null, [] );

		$_SERVER = $this->server;
	} );

	test( 'an unmatched path with no handler sends a bare 404', function() : void {
		Router::get( '/', 'hello.php' );

		$result = dispatch_capturing( 'GET', '/missing' );

		expect( $result['body'] )->toBe( '' );
		expect( http_response_code() )->toBe( 404 );
	} );

	test( 'an unmatched path renders the registered 404 handler with context', function() : void {
		Router::get( '/', 'hello.php' );
		Router::error( 404, 'error-handler.php' );

		$result = dispatch_capturing( 'GET', '/missing' );

		expect( http_response_code() )->toBe( 404 );
		// The handler ran and received the request path as $vars.
		expect( $result['body'] )->toBe( 'ERROR-HANDLER:/missing' );
	} );

	test( 'a method mismatch renders the 405 handler and still sends Allow', function() : void {
		Router::get( '/only-get', 'hello.php' );
		Router::error( 405, 'error-handler.php' );

		$result = dispatch_capturing( 'POST', '/only-get' );

		expect( http_response_code() )->toBe( 405 );
		expect( $result['body'] )->toBe( 'ERROR-HANDLER' );
	} );

	test( 'an unreadable route renders the 500 handler and logs the failure', function() : void {
		Router::get( '/broken', 'does-not-exist.php' );
		Router::error( 500, 'error-handler.php' );

		$result = dispatch_capturing( 'GET', '/broken' );

		expect( http_response_code() )->toBe( 500 );
		expect( $result['body'] )->toBe( 'ERROR-HANDLER' );
		expect( $result['logged'] )->toContain( 'Route not readable' );
	} );

	test( 'a thrown exception is logged and replaced by the 500 handler', function() : void {
		Router::get( '/boom', 'boom.php' );
		Router::error( 500, 'error-handler.php' );

		$result = dispatch_capturing( 'GET', '/boom' );

		expect( http_response_code() )->toBe( 500 );
		expect( $result['body'] )->toBe( 'ERROR-HANDLER' );
		expect( $result['logged'] )->toContain( 'route boom' );
	} );

	test( 'a thrown exception discards already-rendered output', function() : void {
		Router::get( '/partial', 'partial-boom.php' );
		Router::error( 500, 'error-handler.php' );

		$result = dispatch_capturing( 'GET', '/partial' );

		// The half-rendered 'PARTIAL' is cleared; only the 500 page remains.
		expect( $result['body'] )->not->toContain( 'PARTIAL' );
		expect( $result['body'] )->toBe( 'ERROR-HANDLER' );
	} );

	test( 'a thrown exception with no handler sends a bare 500', function() : void {
		Router::get( '/boom', 'boom.php' );

		$result = dispatch_capturing( 'GET', '/boom' );

		expect( $result['body'] )->toBe( '' );
		expect( http_response_code() )->toBe( 500 );
		expect( $result['logged'] )->toContain( 'route boom' );
	} );

	test( 'a missing handler file logs and falls back to the bare status', function() : void {
		Router::get( '/', 'hello.php' );
		Router::error( 404, 'no-such-handler.php' );

		$result = dispatch_capturing( 'GET', '/missing' );

		expect( http_response_code() )->toBe( 404 );
		expect( $result['body'] )->toBe( '' );
		expect( $result['logged'] )->toContain( 'Route not readable' );
	} );
} );
