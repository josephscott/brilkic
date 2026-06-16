<?php
declare( strict_types = 1 );

/**
 * Start a session, applying brilkic's hardened defaults and any per-app overrides.
 *
 * PHP's session_start() accepts an options array that overrides any session.*
 * directive for this start -- cookie_lifetime, gc_maxlifetime, cookie_path,
 * cookie_domain, sid_length and the rest -- so the full session surface is
 * exposed through one Config knob (SESSION_OPTIONS) rather than a constant per
 * setting. The options are layered in three bands:
 *
 *   1. Overridable defaults -- the cookie is Secure (HTTPS-only) and SameSite=Lax
 *      out of the box, both off/empty in vanilla PHP. An app overrides either:
 *      plain-HTTP local dev sets cookie_secure => false, a cross-site embed sets
 *      cookie_samesite => 'None'.
 *   2. The app's SESSION_OPTIONS -- anything it sets wins over the defaults above.
 *   3. Hard floors -- use_strict_mode and cookie_httponly are merged LAST, so an
 *      app cannot weaken them. use_strict_mode makes PHP reject a session ID it
 *      did not issue (fixation protection) instead of adopting an attacker-
 *      supplied one; cookie_httponly keeps the id out of JavaScript's reach so an
 *      XSS cannot lift it. Both default off in PHP and are pure footguns to
 *      disable, so they are pinned here regardless of deployment php.ini.
 *
 * This is the single place brilkic starts a session, so the hardening cannot be
 * forgotten. The guards mirror the rest of the framework: an already-active
 * session is left alone, and no session is begun once headers are committed (or
 * under the CLI/test harness where output is already flushed) since
 * session_start() cannot run then -- callers still operate safely on the
 * $_SESSION superglobal.
 */
function session_start_safe() : void {
	if ( session_status() !== PHP_SESSION_NONE || headers_sent() ) {
		return;
	}

	$options = array_merge(
		// 1. Overridable secure-by-default cookie attributes.
		[
			'cookie_secure'   => true,
			'cookie_samesite' => 'Lax',
		],
		// 2. App overrides -- the full session.* surface plus read_and_close.
		session_options(),
		// 3. Hard floors -- merged last so SESSION_OPTIONS cannot turn them off.
		[
			'use_strict_mode' => true,
			'cookie_httponly' => true,
		],
	);

	session_start( $options );
}

/**
 * Per-app session overrides, passed straight to session_start(). An app sets
 * `const array SESSION_OPTIONS` on its Config class with any session.* directive
 * (without the `session.` prefix) -- e.g. `[ 'cookie_lifetime' => 86400,
 * 'gc_maxlifetime' => 86400 ]` for a day-long persistent session. Read by name
 * so the constant stays optional, and a non-array value is ignored. Note that
 * cookie_lifetime (how long the browser keeps the cookie) and gc_maxlifetime
 * (how long the server keeps the data) are independent: a persistent login wants
 * both, or the cookie outlives the data the GC reclaims.
 *
 * @return array<array-key, mixed>
 */
function session_options() : array {
	$constant = 'Config::SESSION_OPTIONS';

	if ( defined( $constant ) ) {
		$value = constant( $constant );
		if ( is_array( $value ) ) {
			return $value;
		}
	}

	return [];
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
