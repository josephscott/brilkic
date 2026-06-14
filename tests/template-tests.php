<?php
declare( strict_types = 1 );

// template() reads Config::TEMPLATE_PATH. The real Config lives in the demo
// app and is not autoloaded for the suite, so define a test stand-in that
// points at the fixture templates beside this file.
if ( ! class_exists( 'Config', false ) ) {
	final class Config {
		const string TEMPLATE_PATH = __DIR__ . '/templates/';

		const string ROUTE_PATH = __DIR__ . '/routes/';
	}
}

/**
 * Run template() and return whatever it rendered to the output buffer.
 *
 * @param mixed $data
 */
function render_template( string $file, mixed $data = null ) : string {
	ob_start();
	if ( $data === null ) {
		template( $file );
	} else {
		template( $file, $data );
	}

	return (string) ob_get_clean();
}

test( 'renders a readable template', function() : void {
	expect( render_template( 'hello.php' ) )
		->toBe( 'Hello from the template' );
} );

test( 'exposes $data to the template', function() : void {
	$data = [ 'name' => 'Ada', 'role' => 'engineer' ];

	expect( render_template( 'echo-data.php', $data ) )
		->toBe( 'Name: Ada, Role: engineer' );
} );

test( '$data defaults to an empty array when omitted', function() : void {
	expect( render_template( 'dump-data.php' ) )
		->toBe( 'array (' . "\n" . ')' );
} );

test( 'accepts non-array data', function() : void {
	expect( render_template( 'dump-data.php', 'just a string' ) )
		->toBe( "'just a string'" );
} );

test( 'leaks only $data into the template scope', function() : void {
	// The path is handed off positionally via func_get_arg(), so $file must
	// never appear as a variable in the template's scope; only $data should.
	expect( render_template( 'scope.php', [ 'x' => 1 ] ) )
		->toBe( 'data' );
} );

test( 'renders nothing for a missing template and returns void', function() : void {
	$result = null;
	$output = null;

	ob_start();
	$result = template( 'does-not-exist.php' );
	$output = ob_get_clean();

	expect( $result )->toBeNull();
	expect( $output )->toBe( '' );
} );

test( 'blocks "../" traversal outside the template root', function() : void {
	// Resolves to repo/src/template.php, a real readable file outside the
	// template root. It must be refused, not required.
	expect( render_template( '../../src/template.php' ) )
		->toBe( '' );
} );

test( 'blocks traversal to a real file at the repo root', function() : void {
	expect( render_template( '../../LICENSE' ) )
		->toBe( '' );
} );
