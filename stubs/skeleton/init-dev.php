<?php
declare( strict_types = 1 );

// Development config. Loaded when BRILKIC_ENV=dev. Tuned for local, plain-HTTP
// work; keep production-only hardening in init-prod.php.
final class Config {
	const string ROUTE_PATH = __DIR__ . '/routes/';

	const string TEMPLATE_PATH = __DIR__ . '/templates/';

	const string CHAR_SET = 'utf-8';

	const string DEFAULT_CONTENT_TYPE = 'text/html';

	// Local dev is plain HTTP, where the browser would never return a Secure
	// session cookie, so turn Secure off here. (HttpOnly and SameSite=Lax stay on
	// regardless.)
	const bool SESSION_COOKIE_SECURE = false;
}
