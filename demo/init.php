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
}
