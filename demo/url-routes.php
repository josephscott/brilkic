<?php
declare( strict_types = 1 );

Router::get( '/', 'home.php' );
Router::get( '/hello/{name}', 'hello.php' );

// Output-escaping showcase.
Router::get( '/escape', 'escape.php' );

// Sessions: a per-visit counter kept in $_SESSION.
Router::get( '/session', 'session.php' );

// Template composition: header + footer + a card partial per item.
Router::get( '/partials', 'partials.php' );

// JSON endpoint -- a route that sets its own Content-Type.
Router::get( '/api/info', 'api.php' );

// HTTP-method routing. The explainer page is GET; the action endpoint accepts
// only PUT/PATCH/DELETE, so any other verb gets 405 + an Allow header.
Router::get( '/methods', 'methods.php' );
Router::put( '/methods/action', 'methods-action.php' );
Router::patch( '/methods/action', 'methods-action.php' );
Router::delete( '/methods/action', 'methods-action.php' );

// Throws on purpose to demonstrate the 500 handler.
Router::get( '/boom', 'boom.php' );

// The same route file renders the form (GET) and handles the submission
// (POST); it branches on the request method.
Router::get( '/csrf', 'csrf.php' );
Router::post( '/csrf', 'csrf.php' );

// Custom pages for error conditions. Without these, run_app() sends the bare
// status with no body. The 500 handler covers both an unreadable route file and
// any uncaught exception a route throws.
Router::error( 404, 'error-404.php' );
Router::error( 500, 'error-500.php' );
