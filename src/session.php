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
 * Whether run_app() auto-resumes an existing session. Defaults to true and is
 * read by name so the constant stays optional: an app that omits it still gets
 * resume on. Only an explicit `false` turns it off; any other value is ignored.
 */
function session_auto_resume() : bool {
	$constant = 'Config::SESSION_AUTO_RESUME';

	return ! ( defined( $constant ) && constant( $constant ) === false );
}
