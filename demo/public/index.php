<?php
declare( strict_types = 1 );

require __DIR__ . '/../init.php';
require __DIR__ . '/../url-routes.php';

// The CSRF helpers store their token in $_SESSION, which has to be started
// before run_app() sends headers. Once headers are committed a session can no
// longer begin, so this is the place to do it.
if ( session_status() === PHP_SESSION_NONE ) {
	session_start();
}

run_app();
