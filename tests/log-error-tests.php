<?php
declare( strict_types = 1 );

/**
 * Run log_error() while capturing both what it echoes and what it sends to
 * error_log(). The error log is redirected to a throwaway file under
 * tests/tmp so the message is asserted rather than leaking to stderr as test
 * noise.
 *
 * error_log() writes each entry as "[timestamp] <message>\n"; the normalized
 * 'message' field strips that prefix and the trailing newline so callers can
 * assert on the exact text log_error() handed off.
 *
 * @param mixed $data
 *
 * @return array{ echoed: string, logged: string, message: string }
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

	$message = preg_replace( '/^\[[^\]]*\] /', '', $logged ) ?? '';
	if ( str_ends_with( $message, "\n" ) ) {
		$message = substr( $message, 0, -1 );
	}

	return [ 'echoed' => $echoed, 'logged' => $logged, 'message' => $message ];
}

test( 'never echoes, even for sensitive data', function() : void {
	// log_error() must not write potentially sensitive, unescaped data to the
	// output; everything goes to the error log instead.
	expect( capture_log_error( 'boom' )['echoed'] )->toBe( '' );
	expect( capture_log_error( [ 'token' => 'secret' ] )['echoed'] )->toBe( '' );
} );

test( 'logs a string argument verbatim', function() : void {
	expect( capture_log_error( 'boom' )['message'] )->toBe( 'boom' );
} );

test( 'logs an array argument with print_r', function() : void {
	$data = [ 'code' => 500, 'msg' => 'nope' ];

	expect( capture_log_error( $data )['message'] )
		->toBe( print_r( $data, true ) );
} );

test( 'logs non-string scalars with print_r', function() : void {
	expect( capture_log_error( 42 )['message'] )->toBe( '42' );
	expect( capture_log_error( 3.5 )['message'] )->toBe( '3.5' );
	expect( capture_log_error( true )['message'] )->toBe( '1' );
} );

test( 'logs null and false as empty strings', function() : void {
	expect( capture_log_error( null )['message'] )->toBe( '' );
	expect( capture_log_error( false )['message'] )->toBe( '' );
} );

test( 'sends the rendered message to the error log', function() : void {
	$data = [ 'where' => 'router', 'why' => 'missing' ];

	$result = capture_log_error( $data );

	// The error log entry carries the rendered text.
	expect( $result['message'] )->toBe( print_r( $data, true ) );
	expect( $result['logged'] )->toContain( '[where] => router' );
} );
