<?php
declare( strict_types = 1 );

// The CSRF helpers store their tokens in $_SESSION. Under the CLI test harness
// csrf_session_start() operates on the $_SESSION superglobal directly. Reset it
// before each case so tokens from one test never bleed into the next.
describe( 'csrf', function() : void {
	beforeEach( function() : void {
		$_SESSION = [];
		$_POST = [];
	} );

	afterEach( function() : void {
		$_SESSION = [];
		$_POST = [];
	} );

	test( 'falls back to the default field and session-key names', function() : void {
		// The test Config (tests/Pest.php) defines no CSRF_* constants, so both
		// accessors must return the built-in 'csrf_token' default. An app sets
		// const string CSRF_TOKEN_FIELD / CSRF_SESSION_KEY on its Config to
		// override these.
		expect( csrf_token_field() )->toBe( 'csrf_token' );
		expect( csrf_session_key() )->toBe( 'csrf_token' );
	} );

	test( 'csrf_config() reads a defined Config constant over its default', function() : void {
		// Config::CHAR_SET exists in the test stand-in, so csrf_config() returns
		// its value rather than the default; an undefined name returns default.
		expect( csrf_config( 'CHAR_SET', 'fallback' ) )->toBe( Config::CHAR_SET );
		expect( csrf_config( 'DOES_NOT_EXIST', 'fallback' ) )->toBe( 'fallback' );
	} );

	test( 'csrf_token_ttl() defaults to 1800 seconds', function() : void {
		// No CSRF_TOKEN_TTL on the test Config, so the 30-minute default holds.
		expect( defined( 'Config::CSRF_TOKEN_TTL' ) )->toBeFalse();
		expect( csrf_token_ttl() )->toBe( 1800 );
	} );

	test( 'csrf_token() mints a 64-character hex token', function() : void {
		// 32 random bytes hex-encode to 64 lowercase hex characters.
		expect( csrf_token() )->toMatch( '/^[0-9a-f]{64}$/' );
	} );

	test( 'csrf_token() mints a new token on every call', function() : void {
		// Single use: each call must produce its own token, not reuse one.
		expect( csrf_token() )->not->toBe( csrf_token() );
	} );

	test( 'csrf_token() stores the token in the pool with a future expiry', function() : void {
		$before = time();
		$token = csrf_token();

		$pool = $_SESSION[csrf_session_key()];

		expect( $pool )->toBeArray();
		expect( $pool )->toHaveKey( $token );
		// Default TTL is 1800s; allow a second of slack for clock movement.
		expect( $pool[$token] )->toBeGreaterThanOrEqual( $before + 1800 );
		expect( $pool[$token] )->toBeLessThanOrEqual( time() + 1800 );
	} );

	test( 'csrf_token() honours a per-call TTL', function() : void {
		$before = time();
		$token = csrf_token( 60 );

		$expiry = $_SESSION[csrf_session_key()][$token];

		expect( $expiry )->toBeGreaterThanOrEqual( $before + 60 );
		expect( $expiry )->toBeLessThanOrEqual( time() + 60 );
	} );

	test( 'csrf_token() recovers from a non-array pool value', function() : void {
		// A leftover pre-pool single-string token (or any junk) is ignored and
		// replaced rather than causing an error.
		$_SESSION[csrf_session_key()] = 'legacy-single-token';

		$token = csrf_token();

		expect( $_SESSION[csrf_session_key()] )->toBe( [ $token => $_SESSION[csrf_session_key()][$token] ] );
	} );

	test( 'csrf_token() caps the pool at 100 tokens', function() : void {
		for ( $i = 0; $i < 105; $i++ ) {
			csrf_token();
		}

		expect( $_SESSION[csrf_session_key()] )->toHaveCount( 100 );
	} );

	test( 'csrf_field() embeds a fresh, valid token in a hidden input', function() : void {
		$field = csrf_field();

		expect( $field )->toStartWith( '<input type="hidden" name="csrf_token" value="' );

		// The embedded token validates exactly once.
		preg_match( '/value="([0-9a-f]{64})"/', $field, $m );
		expect( $m[1] ?? '' )->toMatch( '/^[0-9a-f]{64}$/' );
		expect( csrf_validate( $m[1] ) )->toBeTrue();
	} );

	test( 'csrf_validate() accepts a minted token', function() : void {
		expect( csrf_validate( csrf_token() ) )->toBeTrue();
	} );

	test( 'csrf_validate() consumes the token so it cannot be reused', function() : void {
		$token = csrf_token();

		expect( csrf_validate( $token ) )->toBeTrue();
		// Single use: the replay fails.
		expect( csrf_validate( $token ) )->toBeFalse();
	} );

	test( 'csrf_validate() consumes only the matching token', function() : void {
		// Two forms / tabs open at once: spending one leaves the other usable.
		$a = csrf_token();
		$b = csrf_token();

		expect( csrf_validate( $a ) )->toBeTrue();
		expect( csrf_validate( $b ) )->toBeTrue();
	} );

	test( 'csrf_validate() rejects an expired token', function() : void {
		// Inject a token whose expiry is already in the past.
		$_SESSION[csrf_session_key()] = [ str_repeat( 'a', 64 ) => time() - 1 ];

		expect( csrf_validate( str_repeat( 'a', 64 ) ) )->toBeFalse();
	} );

	test( 'csrf_validate() rejects a wrong token', function() : void {
		csrf_token();

		expect( csrf_validate( 'nope' ) )->toBeFalse();
	} );

	test( 'csrf_validate() rejects when no token is stored', function() : void {
		expect( csrf_validate( 'anything' ) )->toBeFalse();
	} );

	test( 'csrf_validate() rejects non-string and empty input', function() : void {
		csrf_token();

		expect( csrf_validate( null ) )->toBeFalse();
		expect( csrf_validate( '' ) )->toBeFalse();
		expect( csrf_validate( [ 'a' ] ) )->toBeFalse();
		expect( csrf_validate( 123 ) )->toBeFalse();
	} );

	test( 'csrf_verify() validates the token submitted in $_POST', function() : void {
		$_POST[csrf_token_field()] = csrf_token();

		expect( csrf_verify() )->toBeTrue();
	} );

	test( 'csrf_verify() rejects a replayed token', function() : void {
		$_POST[csrf_token_field()] = csrf_token();

		expect( csrf_verify() )->toBeTrue();
		// The same posted token cannot be verified twice.
		expect( csrf_verify() )->toBeFalse();
	} );

	test( 'csrf_verify() rejects a missing or wrong posted token', function() : void {
		csrf_token();

		expect( csrf_verify() )->toBeFalse();

		$_POST[csrf_token_field()] = 'wrong';
		expect( csrf_verify() )->toBeFalse();
	} );
} );
