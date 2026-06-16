<?php
declare( strict_types = 1 );

require __DIR__ . '/vendor/autoload.php';

// Pick the config for the current environment. Production is the default, so a
// deployment that sets nothing still gets the hardened settings; export
// BRILKIC_ENV=dev for local, plain-HTTP development. Each init-*.php defines its
// own Config class -- only the one loaded here ever exists.
$brilkic_env = getenv( 'BRILKIC_ENV' ) === 'dev' ? 'dev' : 'prod';
require __DIR__ . '/init-' . $brilkic_env . '.php';
