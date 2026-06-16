<?php
declare( strict_types = 1 );

// Production config. Loaded by default (whenever BRILKIC_ENV is not "dev").
// Assumes the site is served over HTTPS.
final class Config {
	const string ROUTE_PATH = __DIR__ . '/routes/';

	const string TEMPLATE_PATH = __DIR__ . '/templates/';

	const string CHAR_SET = 'utf-8';

	const string DEFAULT_CONTENT_TYPE = 'text/html';

	// Sessions use a Secure cookie (HTTPS only) by default -- nothing to set here.
	// See init-dev.php for the local, plain-HTTP override.
}
