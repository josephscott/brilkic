<?php
declare( strict_types = 1 );

function log_error( mixed $data ) : void {
	$msg = '';

	if ( ! is_string( $data ) ) {
		$msg = print_r( $data, true );
	}

	echo $msg;
	error_log( $msg );
}
