<?php
declare( strict_types = 1 );

Router::get( '/', 'home.php' );

// Custom pages for error conditions. Without these, run_app() sends the bare
// status with no body. The 500 handler covers both an unreadable route file and
// any uncaught exception a route throws.
Router::error( 404, 'error-404.php' );
Router::error( 500, 'error-500.php' );
