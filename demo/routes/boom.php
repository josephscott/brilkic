<?php
declare( strict_types = 1 );

// Deliberately throws to demonstrate run_app()'s error handling: the exception
// is logged via log_error(), the half-rendered output is discarded, and the
// registered 500 handler (error-500.php) is rendered in its place.
throw new RuntimeException( 'Boom! This route throws on purpose to show the 500 handler.' );
