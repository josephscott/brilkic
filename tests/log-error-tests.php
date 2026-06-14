<?php
declare( strict_types = 1 );

/**
 * Run log_error() while capturing both what it echoes and what it sends to
 * error_log(). The error log is redirected to a throwaway file under
 * tests/tmp so the message is asserted rather than leaking to stderr as test
 * noise.
 *
 * @param mixed $data
 *
 * @return array{ echoed: string, logged: string }
 */
function capture_log_error( mixed $data ) : array {
	$tmp = __DIR__ . '/tmp/log_error_' . uniqid( '', true ) . '.log';
	$previous = ini_set( 'error_log', $tmp );

	ob_start();
	log_error( $data );
	$echoed = (string) ob_get_clean();

	if ( is_string( $previous ) ) {
		ini_set( 'error_log', $previous );
	}

	$logged = is_readable( $tmp ) ? (string) file_get_contents( $tmp ) : '';
	if ( file_exists( $tmp ) ) {
		unlink( $tmp );
	}

	return [ 'echoed' => $echoed, 'logged' => $logged ];
}

test( 'does not render a string argument', function() : void {
	// Only non-strings are passed through print_r(); a plain string yields an
	// empty rendering.
	expect( capture_log_error( 'boom' )['echoed'] )
		->toBe( '' );
} );

test( 'renders an array argument with print_r', function() : void {
	$data = [ 'code' => 500, 'msg' => 'nope' ];

	expect( capture_log_error( $data )['echoed'] )
		->toBe( print_r( $data, true ) );
} );

test( 'renders non-string scalars with print_r', function() : void {
	expect( capture_log_error( 42 )['echoed'] )->toBe( '42' );
	expect( capture_log_error( 3.5 )['echoed'] )->toBe( '3.5' );
	expect( capture_log_error( true )['echoed'] )->toBe( '1' );
} );

test( 'renders null and false as empty strings', function() : void {
	expect( capture_log_error( null )['echoed'] )->toBe( '' );
	expect( capture_log_error( false )['echoed'] )->toBe( '' );
} );

test( 'sends the rendered message to the error log', function() : void {
	$data = [ 'where' => 'router', 'why' => 'missing' ];

	$result = capture_log_error( $data );

	// The error log entry carries the same rendered text that was echoed.
	expect( $result['logged'] )->toContain( $result['echoed'] );
	expect( $result['logged'] )->toContain( '[where] => router' );
} );
