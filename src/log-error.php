<?php
declare( strict_types = 1 );

function log_error( mixed $data ) : void {
	$msg = $data;

	if ( ! is_string( $data ) ) {
		$msg = print_r( $data, true );
	}

	error_log( $msg );
}
