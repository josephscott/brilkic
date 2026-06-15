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
 * Time-to-live, in seconds, for a freshly minted token. Read by name so the
 * constant stays optional: an app overrides it with `const int CSRF_TOKEN_TTL`
 * on its Config class, otherwise the default is used. A non-int or non-positive
 * value is ignored.
 */
function csrf_token_ttl() : int {
	$constant = 'Config::CSRF_TOKEN_TTL';

	if ( defined( $constant ) ) {
		$value = constant( $constant );
		if ( is_int( $value ) && $value > 0 ) {
			return $value;
		}
	}

	// 30 minutes: long enough to fill in a form, short enough to bound the
	// window in which a captured token stays live.
	return 1800;
}

/**
 * Mint a fresh single-use token and return it.
 *
 * Tokens are one-time: every call produces a new value, added to a per-session
 * pool, and a token is removed the first time it validates. Two calls therefore
 * never return the same value -- callers that render a form just call this (via
 * csrf_field()) once per form. $ttl overrides csrf_token_ttl() for this token
 * only; a null or non-positive value uses the default.
 */
function csrf_token( ?int $ttl = null ) : string {
	csrf_session_start();

	if ( $ttl === null || $ttl <= 0 ) {
		$ttl = csrf_token_ttl();
	}

	// random_bytes() is cryptographically secure; 32 bytes (256 bits) is well
	// beyond guessing range and hex-encodes to an attribute-safe string.
	$token = bin2hex( random_bytes( 32 ) );

	$pool = csrf_pool_read();
	$pool[$token] = time() + $ttl;

	// Prune after adding so the new token counts toward the cap and an expired
	// neighbour cannot survive longer than a render.
	csrf_pool_write( csrf_pool_prune( $pool ) );

	return $token;
}

/**
 * Return a ready-to-embed hidden input carrying a fresh token, for use in a
 * template's <form>. The value is attribute-escaped, so the token is safe to
 * drop straight into markup. $ttl is forwarded to csrf_token().
 */
function csrf_field( ?int $ttl = null ) : string {
	return '<input type="hidden" name="' . esc_attr( csrf_token_field() )
		. '" value="' . esc_attr( csrf_token( $ttl ) ) . '">';
}

/**
 * Validate a submitted token and consume it.
 *
 * Accepts mixed so a raw value pulled straight from request input can be handed
 * in without the caller narrowing it first; anything that is not a non-empty
 * string fails. On a match the token is removed from the pool, so it can never
 * validate a second time (single use). The comparison is constant-time to avoid
 * leaking how much of the token matched.
 */
function csrf_validate( #[\SensitiveParameter] mixed $token ) : bool {
	csrf_session_start();

	if ( ! is_string( $token ) || $token === '' ) {
		return false;
	}

	// Pruned first, so an expired token is simply absent and cannot match.
	$pool = csrf_pool_prune( csrf_pool_read() );

	foreach ( $pool as $stored => $expires ) {
		if ( hash_equals( (string) $stored, $token ) ) {
			unset( $pool[$stored] );
			csrf_pool_write( $pool );
			return true;
		}
	}

	return false;
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
 * Read the token pool from the session as a clean token => expiry-timestamp map,
 * dropping anything malformed. Returns an empty array when no usable pool is
 * stored (including the pre-pool single-string format, which is simply replaced
 * on the next mint).
 *
 * @return array<string, int>
 */
function csrf_pool_read() : array {
	$stored = $_SESSION[csrf_session_key()] ?? null;
	if ( ! is_array( $stored ) ) {
		return [];
	}

	$pool = [];
	foreach ( $stored as $token => $expires ) {
		if ( is_int( $expires ) ) {
			$pool[(string) $token] = $expires;
		}
	}

	return $pool;
}

/**
 * @param array<string, int> $pool
 */
function csrf_pool_write( array $pool ) : void {
	$_SESSION[csrf_session_key()] = $pool;
}

/**
 * Drop expired tokens, then bound the pool size so a flood of rendered-but-
 * unsubmitted forms cannot grow the session without limit. The most recently
 * minted tokens are kept (PHP arrays preserve insertion order).
 *
 * @param array<string, int> $pool
 * @return array<string, int>
 */
function csrf_pool_prune( array $pool ) : array {
	$now = time();
	foreach ( $pool as $token => $expires ) {
		if ( $expires <= $now ) {
			unset( $pool[$token] );
		}
	}

	$max = 100;
	if ( count( $pool ) > $max ) {
		$pool = array_slice( $pool, -$max, null, true );
	}

	return $pool;
}

/**
 * Ensure a session is active so the token has somewhere to live across the
 * form-render and form-submit requests. This is the creation trigger: it starts
 * a session because the caller is about to write one, regardless of whether the
 * client already had one.
 *
 * Delegates to session_start_safe(), so the same fixation protection and guards
 * apply. When no session can be started (headers committed, or the CLI/test
 * harness), the token helpers still operate safely on the $_SESSION
 * superglobal: reads are guarded with `?? null` and a write autovivifies it as
 * an array.
 */
function csrf_session_start() : void {
	session_start_safe();
}
