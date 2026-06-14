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
	}
}
