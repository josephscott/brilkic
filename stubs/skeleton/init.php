<?php
declare( strict_types = 1 );

require __DIR__ . '/vendor/autoload.php';

// App configuration. Assumes the site is served over HTTPS, so the session
// cookie is Secure by default -- nothing to set for that.
final class Config {
	const string ROUTE_PATH = __DIR__ . '/routes/';

	const string TEMPLATE_PATH = __DIR__ . '/templates/';

	const string CHAR_SET = 'utf-8';

	const string DEFAULT_CONTENT_TYPE = 'text/html';

	// For plain-HTTP local development the browser will not return a Secure
	// cookie, so the session would never resume. Uncomment to turn Secure off
	// while working locally (use_strict_mode and HttpOnly stay forced on):
	// const array SESSION_OPTIONS = [ 'cookie_secure' => false ];
}
