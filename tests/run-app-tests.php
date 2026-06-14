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
