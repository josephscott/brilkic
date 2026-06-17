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

// The command-dispatch (brilkic_cli) and outcome-reporting (brilkic_init) layers
// write straight to the STDOUT/STDERR constants via fwrite(), which bypass PHP's
// output buffer and so cannot be captured in-process. Drive the real bin/brilkic
// launcher in a subprocess instead and read its pipes -- this also exercises the
// entry point and its exit codes end to end.
describe( 'cli command dispatch', function() : void {
	beforeEach( function() : void {
		$this->target = __DIR__ . '/tmp/cli-' . uniqid();
	} );

	afterEach( function() : void {
		rrmdir( $this->target );
	} );

	test( 'no command prints usage and exits 0', function() : void {
		// No command is a help request, so it succeeds.
		$result = run_cli( [] );

		expect( $result['code'] )->toBe( 0 );
		expect( $result['stdout'] )->toContain( 'brilkic - scaffold a minimal brilkic app' );
		expect( $result['stdout'] )->toContain( 'Usage:' );
	} );

	test( 'an unknown command prints usage and exits 1', function() : void {
		// An unrecognised command falls through to the usage text but is an error.
		$result = run_cli( [ 'bogus' ] );

		expect( $result['code'] )->toBe( 1 );
		expect( $result['stdout'] )->toContain( 'Usage:' );
	} );

	test( 'init scaffolds into the given dir, reports the created files, and exits 0', function() : void {
		$result = run_cli( [ 'init', $this->target ] );

		expect( $result['code'] )->toBe( 0 );
		expect( $result['stdout'] )->toContain( 'created public/index.php' );
		expect( $result['stdout'] )->toContain( 'created init.php' );
		// The closing guidance points at the freshly scaffolded public/ dir.
		expect( $result['stdout'] )->toContain( 'Done. Start the dev server with:' );
		expect( $result['stdout'] )->toContain( 'php -S localhost:8080 -t ' . $this->target . '/public' );
		expect( is_file( $this->target . '/init.php' ) )->toBeTrue();
	} );

	test( 'init without a dir scaffolds into the current working directory', function() : void {
		// No dir argument falls back to getcwd(); run the child with its cwd set to
		// the empty target and confirm the skeleton lands there.
		mkdir( $this->target, 0o755, true );

		$result = run_cli( [ 'init' ], $this->target );

		expect( $result['code'] )->toBe( 0 );
		expect( is_file( $this->target . '/init.php' ) )->toBeTrue();
	} );

	test( 'init refuses to overwrite, reports the conflict on stderr, and exits 1', function() : void {
		mkdir( $this->target, 0o755, true );
		file_put_contents( $this->target . '/init.php', '<?php // mine' );

		$result = run_cli( [ 'init', $this->target ] );

		expect( $result['code'] )->toBe( 1 );
		// The conflict report goes to stderr, naming the clashing path.
		expect( $result['stderr'] )->toContain( 'Refusing to overwrite existing files' );
		expect( $result['stderr'] )->toContain( 'init.php' );
		// Nothing else was written and the existing file is untouched.
		expect( file_get_contents( $this->target . '/init.php' ) )->toBe( '<?php // mine' );
		expect( is_file( $this->target . '/public/index.php' ) )->toBeFalse();
	} );
} );

/**
 * Launch the brilkic CLI in a subprocess and capture its result. $args is the
 * argument vector after the script name (e.g. [ 'init', $dir ]); $cwd sets the
 * child's working directory, which the no-dir `init` falls back to via getcwd().
 *
 * Invoked through PHP_BINARY rather than the bin shebang, so it runs regardless
 * of the launcher's execute bit. stdout and stderr are read separately so a case
 * can tell created-file output from a conflict report.
 *
 * @param list<string> $args
 *
 * @return array{ stdout: string, stderr: string, code: int }
 */
function run_cli( array $args, ?string $cwd = null ) : array {
	$command = array_merge( [ PHP_BINARY, dirname( __DIR__ ) . '/bin/brilkic' ], $args );

	$descriptors = [
		1 => [ 'pipe', 'w' ],
		2 => [ 'pipe', 'w' ],
	];

	$process = proc_open( $command, $descriptors, $pipes, $cwd );
	if ( ! is_resource( $process ) ) {
		throw new RuntimeException( 'could not launch the brilkic CLI' );
	}

	$stdout = (string) stream_get_contents( $pipes[1] );
	fclose( $pipes[1] );
	$stderr = (string) stream_get_contents( $pipes[2] );
	fclose( $pipes[2] );
	$code = proc_close( $process );

	return [ 'stdout' => $stdout, 'stderr' => $stderr, 'code' => $code ];
}

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
