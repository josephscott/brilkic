<?php
declare( strict_types = 1 );

// brilkic_scaffold() and friends are not autoloaded (the bin launcher and these
// tests require src/cli.php directly), so pull them in here.
require_once dirname( __DIR__ ) . '/src/cli.php';

describe( 'cli scaffold', function() : void {
	beforeEach( function() : void {
		// A fresh, empty target under the test tmp dir for each case.
		$this->target = __DIR__ . '/tmp/scaffold-' . uniqid();
		$this->skeleton = dirname( __DIR__ ) . '/stubs/skeleton';
	} );

	afterEach( function() : void {
		rrmdir( $this->target );
	} );

	test( 'writes the full skeleton into an empty target', function() : void {
		$result = brilkic_scaffold( $this->skeleton, $this->target );

		expect( $result['error'] )->toBeNull();
		expect( $result['conflicts'] )->toBe( [] );

		// Every file the scaffold reports as created exists on disk.
		foreach ( $result['created'] as $rel ) {
			expect( is_file( $this->target . '/' . $rel ) )->toBeTrue();
		}

		// The pieces a runnable site needs are all present.
		expect( $result['created'] )->toContain( 'public/index.php' );
		expect( $result['created'] )->toContain( 'init.php' );
		expect( $result['created'] )->toContain( 'url-routes.php' );
		expect( $result['created'] )->toContain( 'routes/home.php' );
		expect( $result['created'] )->toContain( 'routes/error-404.php' );
		expect( $result['created'] )->toContain( 'routes/error-500.php' );
		expect( $result['created'] )->toContain( 'templates/header.php' );
		expect( $result['created'] )->toContain( 'templates/footer.php' );
	} );

	test( 'the single init config is Secure (HTTPS) by default', function() : void {
		brilkic_scaffold( $this->skeleton, $this->target );

		$init = (string) file_get_contents( $this->target . '/init.php' );

		// One config, HTTPS-ready: the Secure-off override ships commented out, so
		// the cookie is Secure out of the box.
		expect( $init )->toContain( 'final class Config' );
		expect( $init )->toContain( "// const array SESSION_OPTIONS = [ 'cookie_secure' => false ];" );
		expect( $init )->not->toContain( "\n\tconst array SESSION_OPTIONS" );
	} );

	test( 'refuses to overwrite and writes nothing when a file already exists', function() : void {
		// Pre-create one of the files the scaffold would write.
		mkdir( $this->target, 0o755, true );
		file_put_contents( $this->target . '/init.php', '<?php // mine' );

		$result = brilkic_scaffold( $this->skeleton, $this->target );

		expect( $result['error'] )->toBeNull();
		expect( $result['conflicts'] )->toContain( 'init.php' );
		expect( $result['created'] )->toBe( [] );

		// The existing file is untouched and nothing else was written alongside it.
		expect( file_get_contents( $this->target . '/init.php' ) )->toBe( '<?php // mine' );
		expect( is_file( $this->target . '/public/index.php' ) )->toBeFalse();
	} );

	test( 'reports an error for a missing skeleton', function() : void {
		$result = brilkic_scaffold( $this->skeleton . '/nope', $this->target );

		expect( $result['error'] )->not->toBeNull();
		expect( $result['created'] )->toBe( [] );
	} );
} );

/**
 * Recursively remove a directory tree. No-op when the path does not exist, so
 * afterEach can call it unconditionally.
 */
function rrmdir( string $dir ) : void {
	if ( ! is_dir( $dir ) ) {
		return;
	}

	$entries = scandir( $dir );
	if ( $entries === false ) {
		return;
	}

	foreach ( $entries as $entry ) {
		if ( $entry === '.' || $entry === '..' ) {
			continue;
		}

		$path = $dir . '/' . $entry;
		if ( is_dir( $path ) ) {
			rrmdir( $path );
		} else {
			unlink( $path );
		}
	}

	rmdir( $dir );
}
