<?php
declare( strict_types = 1 );

// Stand-in error handler. Echoes a marker so tests can confirm it ran, plus the
// request path when the error context carries one (404).
echo 'ERROR-HANDLER';

if ( is_string( $vars['uri'] ?? null ) ) {
	echo ':' . $vars['uri'];
}
