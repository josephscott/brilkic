<?php
declare( strict_types = 1 );

// The CSRF helpers store their token in $_SESSION. Under the CLI test harness a
// real session cannot be started, so csrf_session_start() falls back to using
// the $_SESSION superglobal directly. Reset it before each case so tokens from
// one test never bleed into the next.
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

	test( 'csrf_token() mints a token and stores it in the session', function() : void {
		$token = csrf_token();

		expect( $token )->toBeString();
		expect( $token )->not->toBe( '' );
		expect( $_SESSION[csrf_session_key()] )->toBe( $token );
	} );

	test( 'csrf_token() mints a 64-character hex token', function() : void {
		// 32 random bytes hex-encode to 64 lowercase hex characters.
		expect( csrf_token() )->toMatch( '/^[0-9a-f]{64}$/' );
	} );

	test( 'csrf_token() reuses the stored token on repeat calls', function() : void {
		expect( csrf_token() )->toBe( csrf_token() );
	} );

	test( 'csrf_token() adopts a token already in the session', function() : void {
		$_SESSION[csrf_session_key()] = 'preset-token';

		expect( csrf_token() )->toBe( 'preset-token' );
	} );

	test( 'csrf_token() replaces a non-string token in the session', function() : void {
		$_SESSION[csrf_session_key()] = [ 'not', 'a', 'string' ];

		expect( csrf_token() )->toMatch( '/^[0-9a-f]{64}$/' );
	} );

	test( 'csrf_field() embeds the current token in a hidden input', function() : void {
		$token = csrf_token();

		expect( csrf_field() )->toBe(
			'<input type="hidden" name="' . csrf_token_field() . '" value="' . $token . '">'
		);
	} );

	test( 'csrf_field() mints a token when called first', function() : void {
		$field = csrf_field();

		expect( $_SESSION[csrf_session_key()] )->toBeString();
		expect( $field )->toContain( 'value="' . $_SESSION[csrf_session_key()] . '"' );
	} );

	test( 'csrf_validate() accepts the stored token', function() : void {
		$token = csrf_token();

		expect( csrf_validate( $token ) )->toBeTrue();
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
		$token = csrf_token();
		$_POST[csrf_token_field()] = $token;

		expect( csrf_verify() )->toBeTrue();
	} );

	test( 'csrf_verify() rejects a missing or wrong posted token', function() : void {
		csrf_token();

		expect( csrf_verify() )->toBeFalse();

		$_POST[csrf_token_field()] = 'wrong';
		expect( csrf_verify() )->toBeFalse();
	} );
} );
