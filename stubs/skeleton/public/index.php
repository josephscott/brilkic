<?php
declare( strict_types = 1 );

// The single entry point. Point your web server's document root here; every
// request that is not a real file is routed through this file.
require __DIR__ . '/../init.php';
require __DIR__ . '/../url-routes.php';

run_app();
