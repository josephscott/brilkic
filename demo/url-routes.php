<?php
declare( strict_types = 1 );

Router::get( '/', 'home.php' );
Router::get( '/hello/{name}', 'hello.php' );

// The same route file renders the form (GET) and handles the submission
// (POST); it branches on the request method.
Router::get( '/csrf', 'csrf.php' );
Router::post( '/csrf', 'csrf.php' );
