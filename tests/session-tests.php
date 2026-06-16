<?php
declare( strict_types = 1 );

// Session resume policy. The test Config (tests/Pest.php) defines no
// SESSION_AUTO_RESUME, so these cases see the default (on). Any session a case
// starts is kept under tests/tmp and closed between cases so state never leaks
// across tests (the suite runs in a single process).
describe( 'session', function() : void {
	beforeEach( function() : void {
		if ( session_status() === PHP_SESSION_ACTIVE ) {
			session_write_close();
		}

		$this->cookie = $_COOKIE;
		$_COOKIE = [];

		// Keep any session the helper starts inside the test tmp dir rather than
		// the system session path.
		ini_set( 'session.save_path', __DIR__ . '/tmp' );
	} );

	afterEach( function() : void {
		if ( session_status() === PHP_SESSION_ACTIVE ) {
			session_write_close();
		}

		$_COOKIE = $this->cookie;
	} );

	test( 'auto-resume is on by default when Config omits SESSION_AUTO_RESUME', function() : void {
		expect( defined( 'Config::SESSION_AUTO_RESUME' ) )->toBeFalse();
		expect( session_auto_resume() )->toBeTrue();
	} );

	test( 'does not start a session when the client has no cookie', function() : void {
		// The cheap path: no cookie means it returns before any session call, so
		// a visitor with no session never starts one.
		session_resume_if_present();

		expect( session_status() )->toBe( PHP_SESSION_NONE );
	} );

	test( 'resumes and forces strict mode when the client presents a cookie', function() : void {
		if ( headers_sent() ) {
			$this->markTestSkipped( 'A session cannot be started once headers are sent.' );
		}

		$_COOKIE[session_name()] = 'client-supplied-id';

		session_resume_if_present();

		expect( session_status() )->toBe( PHP_SESSION_ACTIVE );
		// Fixation protection is pinned on regardless of php.ini.
		expect( ini_get( 'session.use_strict_mode' ) )->toBe( '1' );
	} );

	test( 'pins HttpOnly and SameSite=Lax on the session cookie', function() : void {
		if ( headers_sent() ) {
			$this->markTestSkipped( 'A session cannot be started once headers are sent.' );
		}

		session_start_safe();

		// Both default off/empty in vanilla PHP; the helper pins them every start.
		expect( ini_get( 'session.cookie_httponly' ) )->toBe( '1' );
		expect( ini_get( 'session.cookie_samesite' ) )->toBe( 'Lax' );
	} );

	test( 'Secure defaults on when Config omits SESSION_OPTIONS', function() : void {
		if ( headers_sent() ) {
			$this->markTestSkipped( 'A session cannot be started once headers are sent.' );
		}

		// The test Config (tests/Pest.php) defines no SESSION_OPTIONS, so the
		// default applies -- Secure on, so production is secure out of the box.
		expect( defined( 'Config::SESSION_OPTIONS' ) )->toBeFalse();
		expect( session_options() )->toBe( [] );

		session_start_safe();

		expect( ini_get( 'session.cookie_secure' ) )->toBe( '1' );
	} );

	test( 'destroy clears the data and tears the session down', function() : void {
		if ( headers_sent() ) {
			$this->markTestSkipped( 'A session cannot be started once headers are sent.' );
		}

		session_start_safe();
		$_SESSION['count'] = 7;
		expect( session_status() )->toBe( PHP_SESSION_ACTIVE );

		session_destroy_safe();

		// Data is emptied in-process and the server-side session is gone.
		expect( $_SESSION )->toBe( [] );
		expect( session_status() )->toBe( PHP_SESSION_NONE );
	} );

	test( 'destroy starts a session first so there is something to tear down', function() : void {
		if ( headers_sent() ) {
			$this->markTestSkipped( 'A session cannot be started once headers are sent.' );
		}

		// No active session and no client cookie: destroy still leaves a clean,
		// inactive state rather than erroring.
		expect( session_status() )->toBe( PHP_SESSION_NONE );

		session_destroy_safe();

		expect( $_SESSION )->toBe( [] );
		expect( session_status() )->toBe( PHP_SESSION_NONE );
	} );
} );
