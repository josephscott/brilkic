<?php
declare( strict_types = 1 );

// A route that throws, to exercise run_app()'s uncaught-exception handling.
throw new RuntimeException( 'route boom' );
