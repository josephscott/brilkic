<?php
declare( strict_types = 1 );

/**
 * Start a session with session-fixation protection forced on.
 *
 * session.use_strict_mode makes PHP reject a session ID it did not issue --
 * generating a fresh one -- instead of adopting an attacker-supplied ID. PHP's
 * built-in default is off and only the distributed production php.ini turns it
 * on, so it is pinned here, immediately before every start, rather than left to
 * deployment config. This is the single place brilkic starts a session, so the
 * setting cannot be forgotten.
 *
 * The guards mirror the rest of the framework: an already-active session is
 * left alone, and no session is begun once headers are committed (or under the
 * CLI/test harness where output is already flushed) since session_start()
 * cannot run then -- callers still operate safely on the $_SESSION superglobal.
 */
function session_start_safe() : void {
	if ( session_status() !== PHP_SESSION_NONE || headers_sent() ) {
		return;
	}

	// Must be set before session_start(); ini_set() on a session.* setting only
	// takes effect while no session is active, which the guard above ensures.
	ini_set( 'session.use_strict_mode', '1' );
	session_start();
}

/**
 * Resume a session only when the client already presents one.
 *
 * The session cookie is the only evidence that a client has a session, so a
 * visitor without one pays nothing here -- no lock, no session I/O. A returning
 * visitor's $_SESSION is hooked up before any route runs, so even a direct
 * $_SESSION read sees the right data. run_app() calls this automatically.
 *
 * On by default. An app opts out with `const bool SESSION_AUTO_RESUME = false`
 * on its Config class; omitting it, or any value other than false, leaves
 * resume on.
 */
function session_resume_if_present() : void {
	if ( ! session_auto_resume() ) {
		return;
	}

	if ( ! isset( $_COOKIE[session_name()] ) ) {
		return;
	}

	session_start_safe();
}

/**
 * Destroy the current session completely: empty its data, expire its cookie, and
 * discard the server-side record. This is the logout primitive -- it undoes what
 * session_start_safe()/session_resume_if_present() set up.
 *
 * The session is started first if one is not already active, since there is
 * nothing to destroy (and no cookie to clear) otherwise; session_start_safe()'s
 * own guards still apply, so this is a no-op once headers are committed or under
 * the CLI/test harness. $_SESSION is cleared in-process so a later read in the
 * same request sees no stale data, and the session cookie is expired in the
 * browser so the client stops presenting the now-dead ID -- without it,
 * session_resume_if_present() would keep trying to resume a destroyed session.
 */
function session_destroy_safe() : void {
	session_start_safe();

	if ( session_status() !== PHP_SESSION_ACTIVE ) {
		return;
	}

	$_SESSION = [];

	// Expire the cookie using the same attributes it was set with, so the
	// browser actually matches and removes it. Guard headers_sent() rather than
	// suppress: a genuine "headers already sent" case should surface at its
	// origin, and session_destroy() below still tears down the server side.
	$name = session_name();
	if ( is_string( $name ) && ini_get( 'session.use_cookies' ) && ! headers_sent() ) {
		$params = session_get_cookie_params();
		setcookie( $name, '', [
			'expires'  => time() - 42000,
			'path'     => $params['path'],
			'domain'   => $params['domain'],
			'secure'   => $params['secure'],
			'httponly' => $params['httponly'],
			'samesite' => $params['samesite'],
		] );
	}

	session_destroy();
}

/**
 * Whether run_app() auto-resumes an existing session. Defaults to true and is
 * read by name so the constant stays optional: an app that omits it still gets
 * resume on. Only an explicit `false` turns it off; any other value is ignored.
 */
function session_auto_resume() : bool {
	$constant = 'Config::SESSION_AUTO_RESUME';

	return ! ( defined( $constant ) && constant( $constant ) === false );
}
