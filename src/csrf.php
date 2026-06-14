<?php
declare( strict_types = 1 );

/**
 * Name of the hidden form field (and the matching $_POST key) that carries the
 * token between the request that renders a form and the one that submits it.
 *
 * An app may override it with `const string CSRF_TOKEN_FIELD` on its Config
 * class; absent that, this default is used.
 */
function csrf_token_field() : string {
	return csrf_config( 'CSRF_TOKEN_FIELD', 'csrf_token' );
}

/**
 * Key the token is stored under in $_SESSION. Overridable via
 * `const string CSRF_SESSION_KEY` on the app's Config class.
 */
function csrf_session_key() : string {
	return csrf_config( 'CSRF_SESSION_KEY', 'csrf_token' );
}

/**
 * Read a CSRF setting from the app's Config class, falling back to a default
 * when Config does not define it. The lookup is by name so these constants stay
 * optional: Config is the single place to override them, but an app that omits
 * them still gets working defaults. A non-string Config value is ignored.
 */
function csrf_config( string $name, string $default ) : string {
	$constant = 'Config::' . $name;

	if ( defined( $constant ) ) {
		$value = constant( $constant );
		if ( is_string( $value ) ) {
			return $value;
		}
	}

	return $default;
}

/**
 * Return the session's CSRF token, minting one on first use.
 *
 * A single token is kept per session (the synchronizer-token pattern) and
 * reused across requests, so calling this from a template to render a form and
 * again on submit to validate yields the same value.
 */
function csrf_token() : string {
	csrf_session_start();

	$key = csrf_session_key();
	$token = $_SESSION[$key] ?? null;

	// random_bytes() is cryptographically secure; 32 bytes (256 bits) is well
	// beyond guessing range and hex-encodes to an attribute-safe string.
	if ( ! is_string( $token ) || $token === '' ) {
		$token = bin2hex( random_bytes( 32 ) );
		$_SESSION[$key] = $token;
	}

	return $token;
}

/**
 * Return a ready-to-embed hidden input carrying the current token, for use in
 * a template's <form>. The value is attribute-escaped, so the token is safe to
 * drop straight into markup.
 */
function csrf_field() : string {
	return '<input type="hidden" name="' . esc_attr( csrf_token_field() )
		. '" value="' . esc_attr( csrf_token() ) . '">';
}

/**
 * Validate a submitted token against the one stored in the session.
 *
 * Accepts mixed so a raw value pulled straight from request input can be handed
 * in without the caller having to narrow it first; anything that is not a
 * non-empty string fails. The comparison is constant-time to avoid leaking how
 * much of the token matched.
 */
function csrf_validate( #[\SensitiveParameter] mixed $token ) : bool {
	csrf_session_start();

	$stored = $_SESSION[csrf_session_key()] ?? null;

	if (
		! is_string( $stored ) || $stored === ''
		|| ! is_string( $token ) || $token === ''
	) {
		return false;
	}

	return hash_equals( $stored, $token );
}

/**
 * Validate the token submitted in $_POST under the configured field name. This
 * is the one-liner for a route callback handling a form post: bail unless it
 * returns true.
 */
function csrf_verify() : bool {
	return csrf_validate( $_POST[csrf_token_field()] ?? null );
}

/**
 * Ensure a session is active so the token has somewhere to live across the
 * form-render and form-submit requests.
 *
 * An already-active session is reused. Otherwise a session is started only when
 * that can still succeed: once headers are committed (or under the CLI/test
 * harness where output is already flushed) session_start() cannot run, so fall
 * back to using $_SESSION as-is rather than emitting a "headers already sent"
 * warning -- mirroring send_default_headers(). When no session can be started,
 * the token helpers still operate safely on the $_SESSION superglobal: reads
 * are guarded with `?? null` and a write autovivifies it as an array.
 */
function csrf_session_start() : void {
	if ( session_status() === PHP_SESSION_NONE && ! headers_sent() ) {
		session_start();
	}
}
