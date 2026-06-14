<?php
declare( strict_types = 1 );

// template() and run_route() read Config::TEMPLATE_PATH / Config::ROUTE_PATH.
// The real Config lives in the demo app and is not autoloaded for the suite,
// so define a shared test stand-in that points at the fixture directories
// beside this file.
if ( ! class_exists( 'Config', false ) ) {
	final class Config {
		const string TEMPLATE_PATH = __DIR__ . '/templates/';

		const string ROUTE_PATH = __DIR__ . '/routes/';

		const string CHAR_SET = 'utf-8';
	}
}

// Tests redirect error_log() and other transient output here. The directory
// is gitignored, so it is absent on a fresh checkout (e.g. CI); create it up
// front rather than letting writes to it fail silently.
if ( ! is_dir( __DIR__ . '/tmp' ) ) {
	mkdir( __DIR__ . '/tmp', 0o777, true );
}
