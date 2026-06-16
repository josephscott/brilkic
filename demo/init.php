<?php
declare( strict_types = 1 );

require __DIR__ . '/../vendor/autoload.php';

final class Config {
	const string ROUTE_PATH = __DIR__ . '/routes/';

	const string TEMPLATE_PATH = __DIR__ . '/templates/';

	const string CHAR_SET = 'utf-8';

	const string DEFAULT_CONTENT_TYPE = 'text/html';

	// run_app() resumes an existing session when the client presents its cookie,
	// so a session is started only when needed (a returning visitor, or a page
	// that mints one). It is on by default; uncomment to turn the auto-resume
	// off, e.g. for a stateless API that never uses sessions.
	// const bool SESSION_AUTO_RESUME = false;

	// Per-app session overrides, passed straight to session_start(); any session.*
	// directive is fair game (cookie_lifetime, gc_maxlifetime, cookie_path, ...).
	// use_strict_mode and HttpOnly are always forced on regardless. The cookie is
	// Secure (HTTPS only) by default, but this demo is served over plain HTTP via
	// `php -S`, where a Secure cookie would never be returned by the browser, so
	// it opts out here. A real HTTPS deployment should delete this override.
	const array SESSION_OPTIONS = [ 'cookie_secure' => false ];

	// When a path does not match, run_app() tries its trailing-slash variant and
	// redirects to it if that is a registered route -- so "/csrf/" lands on
	// "/csrf" rather than 404. On by default; uncomment to turn it off, flip the
	// direction (add a slash instead of stripping one), or make the redirect
	// permanent (301) rather than the default temporary (302).
	// const bool TRAILING_SLASH_REDIRECT = false;
	// const bool TRAILING_SLASH_ADD = true;
	// const int TRAILING_SLASH_REDIRECT_CODE = 301;
}
