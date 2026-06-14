<?php
declare( strict_types = 1 );

function log_error( mixed $data ) : void {
	$msg = $data;

	if ( ! is_string( $msg ) ) {
		$msg = print_r( $msg, true );
	}

	error_log( $msg );
}
