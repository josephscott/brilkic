<?php
declare( strict_types = 1 );

// The shared Config test stand-in (pointing at the fixture dirs) is defined
// in tests/Pest.php.

/**
 * Run run_route() and return whatever it rendered to the output buffer.
 *
 * @param ?array<array-key, mixed> $vars
 */
function render_route( string $file, ?array $vars = null ) : string {
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

test( 'rejects non-array vars with a TypeError', function() : void {
	// $vars is typed array, so a scalar is refused at the call boundary under
	// strict_types rather than reaching the route as a bad offset target.
	expect( fn () => run_route( 'dump-vars.php', 'just a string' ) )
		->toThrow( TypeError::class );
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
		// Router holds its routes and error handlers in private static arrays;
		// clear both so each case dispatches against only what it registers.
		( new ReflectionProperty( Router::class, 'routes' ) )->setValue( null, [] );
		( new ReflectionProperty( Router::class, 'errors' ) )->setValue( null, [] );

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

	test( 'redirects a trailing-slash miss to the registered path', function() : void {
		Router::get( '/csrf', 'hello.php' );

		// "/csrf/" does not match, but stripping the slash reaches "/csrf";
		// redirect there (302 by default) rather than 404, rendering nothing.
		expect( dispatch( 'GET', '/csrf/' ) )->toBe( '' );
		expect( http_response_code() )->toBe( 302 );
	} );

	test( 'carries the query string through a trailing-slash redirect', function() : void {
		if ( ! function_exists( 'xdebug_get_headers' ) ) {
			$this->markTestSkipped( 'xdebug_get_headers() is required to inspect sent headers.' );
		}

		Router::get( '/search', 'hello.php' );

		dispatch( 'GET', '/search/?q=test&page=2' );

		expect( xdebug_get_headers() )->toContain( 'Location: /search?q=test&page=2' );
	} );

	test( 'does not redirect when the trailing-slash variant is unregistered', function() : void {
		Router::get( '/csrf', 'hello.php' );

		// "/nope/" stripped is "/nope", which is not a route either: 404, no
		// redirect.
		expect( dispatch( 'GET', '/nope/' ) )->toBe( '' );
		expect( http_response_code() )->toBe( 404 );
	} );

	test( 'never strips the root path to an empty redirect target', function() : void {
		Router::get( '/only', 'hello.php' );

		// "/" has a trailing slash but no route; stripping it would leave "",
		// so it must 404 rather than redirect.
		expect( dispatch( 'GET', '/' ) )->toBe( '' );
		expect( http_response_code() )->toBe( 404 );
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

describe( 'trailing-slash redirect config', function() : void {
	// The test Config stand-in declares none of the trailing-slash constants, so
	// each helper sees its default. (Their override branches read by name, the
	// same shape as the session/csrf helpers, so the defaults are what matter.)
	test( 'redirect is on by default when Config omits TRAILING_SLASH_REDIRECT', function() : void {
		expect( defined( 'Config::TRAILING_SLASH_REDIRECT' ) )->toBeFalse();
		expect( trailing_slash_redirect() )->toBeTrue();
	} );

	test( 'direction strips by default when Config omits TRAILING_SLASH_ADD', function() : void {
		expect( defined( 'Config::TRAILING_SLASH_ADD' ) )->toBeFalse();
		expect( trailing_slash_add() )->toBeFalse();
	} );

	test( 'redirect code defaults to 302 when Config omits TRAILING_SLASH_REDIRECT_CODE', function() : void {
		expect( defined( 'Config::TRAILING_SLASH_REDIRECT_CODE' ) )->toBeFalse();
		expect( trailing_slash_redirect_code() )->toBe( 302 );
	} );
} );

describe( 'trailing_slash_alternate()', function() : void {
	test( 'strips a single trailing slash when adding is off', function() : void {
		expect( trailing_slash_alternate( '/csrf/', false ) )->toBe( '/csrf' );
	} );

	test( 'returns null for a slashless path when adding is off', function() : void {
		expect( trailing_slash_alternate( '/csrf', false ) )->toBeNull();
	} );

	test( 'leaves the root path alone when adding is off', function() : void {
		// Stripping "/" would yield an empty target, so there is nothing to try.
		expect( trailing_slash_alternate( '/', false ) )->toBeNull();
	} );

	test( 'appends a trailing slash when adding is on', function() : void {
		expect( trailing_slash_alternate( '/csrf', true ) )->toBe( '/csrf/' );
	} );

	test( 'returns null for an already-slashed path when adding is on', function() : void {
		expect( trailing_slash_alternate( '/csrf/', true ) )->toBeNull();
		expect( trailing_slash_alternate( '/', true ) )->toBeNull();
	} );
} );
