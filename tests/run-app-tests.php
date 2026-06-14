<?php
declare( strict_types = 1 );

// The shared Config test stand-in (pointing at the fixture dirs) is defined
// in tests/Pest.php.

/**
 * Run run_route() and return whatever it rendered to the output buffer.
 *
 * @param mixed $vars
 */
function render_route( string $file, mixed $vars = null ) : string {
	ob_start();
	if ( $vars === null ) {
		run_route( $file );
	} else {
		run_route( $file, $vars );
	}

	return (string) ob_get_clean();
}

test( 'renders a readable route', function() : void {
	expect( render_route( 'hello.php' ) )
		->toBe( 'Hello from the route' );
} );

test( 'exposes $vars to the route', function() : void {
	$vars = [ 'id' => '42', 'slug' => 'hello-world' ];

	expect( render_route( 'echo-vars.php', $vars ) )
		->toBe( 'Id: 42, Slug: hello-world' );
} );

test( '$vars defaults to an empty array when omitted', function() : void {
	expect( render_route( 'dump-vars.php' ) )
		->toBe( 'array (' . "\n" . ')' );
} );

test( 'leaks only $vars into the route scope', function() : void {
	// The path is handed off positionally via func_get_arg(), so $file must
	// never appear as a variable in the route's scope; only $vars should.
	expect( render_route( 'scope.php', [ 'id' => '1' ] ) )
		->toBe( 'vars' );
} );

test( 'renders nothing and responds 500 for a missing route', function() : void {
	http_response_code( 200 );

	$result = null;
	ob_start();
	$result = run_route( 'does-not-exist.php' );
	$output = ob_get_clean();

	expect( $result )->toBeNull();
	expect( $output )->toBe( '' );
	expect( http_response_code() )->toBe( 500 );
} );

test( 'blocks "../" traversal and responds 500', function() : void {
	http_response_code( 200 );

	// Resolves to repo/src/run-app.php, a real readable file outside the
	// route root. It must be refused, not required.
	$output = render_route( '../../src/run-app.php' );

	expect( $output )->toBe( '' );
	expect( http_response_code() )->toBe( 500 );
} );

test( 'blocks traversal to a real file at the repo root', function() : void {
	http_response_code( 200 );

	$output = render_route( '../../LICENSE' );

	expect( $output )->toBe( '' );
	expect( http_response_code() )->toBe( 500 );
} );

/**
 * Dispatch the given method/URI through run_app() and return the rendered
 * output. The route table and superglobals are reset per test by the
 * describe() hooks below.
 */
function dispatch( string $method, string $uri ) : string {
	$_SERVER['REQUEST_METHOD'] = $method;
	$_SERVER['REQUEST_URI'] = $uri;

	ob_start();
	run_app();

	return (string) ob_get_clean();
}

describe( 'run_app()', function() : void {
	beforeEach( function() : void {
		// Router holds its routes in a private static array; clear it so each
		// case dispatches against only the routes it registers.
		( new ReflectionProperty( Router::class, 'routes' ) )->setValue( null, [] );

		$this->server = $_SERVER;
		http_response_code( 200 );
	} );

	afterEach( function() : void {
		$_SERVER = $this->server;
	} );

	test( 'sends a charset-pinned content type and nosniff by default', function() : void {
		if ( ! function_exists( 'xdebug_get_headers' ) ) {
			$this->markTestSkipped( 'xdebug_get_headers() is required to inspect sent headers.' );
		}

		Router::get( '/', 'hello.php' );
		dispatch( 'GET', '/' );

		$headers = xdebug_get_headers();

		expect( $headers )->toContain(
			'Content-Type: ' . Config::DEFAULT_CONTENT_TYPE . '; charset=' . Config::CHAR_SET
		);
		expect( $headers )->toContain( 'X-Content-Type-Options: nosniff' );
	} );

	test( 'dispatches a matched route to its file', function() : void {
		Router::get( '/hello', 'hello.php' );

		expect( dispatch( 'GET', '/hello' ) )
			->toBe( 'Hello from the route' );
	} );

	test( 'passes matched path parameters to the route', function() : void {
		Router::get( '/item/{id}/{slug}', 'echo-vars.php' );

		expect( dispatch( 'GET', '/item/42/hello-world' ) )
			->toBe( 'Id: 42, Slug: hello-world' );
	} );

	test( 'responds 404 for an unmatched path', function() : void {
		Router::get( '/hello', 'hello.php' );

		expect( dispatch( 'GET', '/nope' ) )->toBe( '' );
		expect( http_response_code() )->toBe( 404 );
	} );

	test( 'responds 405 when the path exists but the method does not', function() : void {
		Router::get( '/only-get', 'hello.php' );

		expect( dispatch( 'POST', '/only-get' ) )->toBe( '' );
		expect( http_response_code() )->toBe( 405 );
	} );

	test( 'strips the query string before matching', function() : void {
		Router::get( '/search', 'hello.php' );

		expect( dispatch( 'GET', '/search?q=test&page=2' ) )
			->toBe( 'Hello from the route' );
	} );

	test( 'rawurldecodes the path before matching', function() : void {
		Router::get( "/caf\u{00e9}", 'hello.php' );

		expect( dispatch( 'GET', '/caf%C3%A9' ) )
			->toBe( 'Hello from the route' );
	} );

	test( 'falls back to GET / when $_SERVER is missing', function() : void {
		Router::get( '/', 'hello.php' );

		unset( $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] );

		ob_start();
		run_app();
		$output = (string) ob_get_clean();

		expect( $output )->toBe( 'Hello from the route' );
	} );

	test( 'falls back to GET when the request method is not a string', function() : void {
		Router::get( '/', 'hello.php' );

		$_SERVER['REQUEST_METHOD'] = [ 'POST' ];
		$_SERVER['REQUEST_URI'] = '/';

		ob_start();
		run_app();
		$output = (string) ob_get_clean();

		expect( $output )->toBe( 'Hello from the route' );
	} );

	test( 'falls back to / when the request URI is not a string', function() : void {
		Router::get( '/', 'hello.php' );

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = 123;

		ob_start();
		run_app();
		$output = (string) ob_get_clean();

		expect( $output )->toBe( 'Hello from the route' );
	} );
} );
