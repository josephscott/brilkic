<?php
declare( strict_types = 1 );

// The brilkic command-line tool. This file holds only functions and is neither
// classmap- nor files-autoloaded, so a consuming app never loads it: the bin
// launcher (bin/brilkic) and the test suite require it directly. Living under
// src/ keeps it covered by phpstan and the lint step like the rest of the code.

/**
 * Dispatch a CLI invocation. $argv is the raw argument vector (mixed, since it
 * comes from $_SERVER); anything unexpected falls through to the usage text.
 * Returns the process exit code.
 */
function brilkic_cli( mixed $argv ) : int {
	$args = is_array( $argv ) ? array_values( $argv ) : [];
	$command = ( isset( $args[1] ) && is_string( $args[1] ) ) ? $args[1] : '';

	if ( $command === 'init' ) {
		$dir = ( isset( $args[2] ) && is_string( $args[2] ) && $args[2] !== '' ) ? $args[2] : null;
		if ( $dir === null ) {
			$cwd = getcwd();
			$dir = is_string( $cwd ) ? $cwd : '.';
		}

		return brilkic_init( $dir );
	}

	brilkic_cli_usage();

	// No command is a help request (success); an unknown command is an error.
	return $command === '' ? 0 : 1;
}

function brilkic_cli_usage() : void {
	fwrite( STDOUT, "brilkic - scaffold a minimal brilkic app\n\n" );
	fwrite( STDOUT, "Usage:\n" );
	fwrite( STDOUT, "  brilkic init [dir]   create a starter site in dir (default: current directory)\n" );
}

/**
 * Scaffold a starter app into $target and report the outcome to the terminal.
 * All human-facing output lives here so brilkic_scaffold() stays quiet and
 * testable. Returns the process exit code.
 */
function brilkic_init( string $target ) : int {
	// The skeleton ships beside the package: src/ -> package root -> stubs.
	$skeleton = dirname( __DIR__ ) . '/stubs/skeleton';

	$result = brilkic_scaffold( $skeleton, $target );

	if ( $result['error'] !== null ) {
		fwrite( STDERR, 'Error: ' . $result['error'] . "\n" );
		return 1;
	}

	if ( $result['conflicts'] !== [] ) {
		fwrite( STDERR, "Refusing to overwrite existing files in $target:\n" );
		foreach ( $result['conflicts'] as $rel ) {
			fwrite( STDERR, "  $rel\n" );
		}
		return 1;
	}

	foreach ( $result['created'] as $rel ) {
		fwrite( STDOUT, "  created $rel\n" );
	}

	$serve = rtrim( $target, '/' ) . '/public';
	fwrite( STDOUT, "\nDone. Start the dev server with:\n" );
	fwrite( STDOUT, "  php -S localhost:8080 -t $serve\n" );
	fwrite( STDOUT, "\n(Local dev is plain HTTP -- uncomment SESSION_OPTIONS in init.php to use sessions.)\n" );

	return 0;
}

/**
 * Copy the skeleton tree at $skeleton into $target.
 *
 * Every destination is checked first; if any already exists, nothing is written
 * and the clashing paths come back in 'conflicts' -- so the command can never
 * clobber existing work. 'error' carries a setup failure (missing skeleton, a
 * directory that cannot be created or written); 'created' lists the relative
 * paths written on success.
 *
 * @return array{created: list<string>, conflicts: list<string>, error: ?string}
 */
function brilkic_scaffold( string $skeleton, string $target ) : array {
	if ( ! is_dir( $skeleton ) ) {
		return [ 'created' => [], 'conflicts' => [], 'error' => "skeleton not found at $skeleton" ];
	}

	$target = rtrim( $target, '/' );
	if ( $target === '' ) {
		$target = '.';
	}

	$files = brilkic_scaffold_files( $skeleton );

	$conflicts = [];
	foreach ( $files as $rel ) {
		if ( file_exists( "$target/$rel" ) ) {
			$conflicts[] = $rel;
		}
	}
	if ( $conflicts !== [] ) {
		return [ 'created' => [], 'conflicts' => $conflicts, 'error' => null ];
	}

	$created = [];
	foreach ( $files as $rel ) {
		$dest = "$target/$rel";
		$dir = dirname( $dest );

		if ( ! is_dir( $dir ) && ! mkdir( $dir, 0o755, true ) && ! is_dir( $dir ) ) {
			return [ 'created' => $created, 'conflicts' => [], 'error' => "could not create directory $dir" ];
		}

		if ( ! copy( "$skeleton/$rel", $dest ) ) {
			return [ 'created' => $created, 'conflicts' => [], 'error' => "could not write $dest" ];
		}

		$created[] = $rel;
	}

	return [ 'created' => $created, 'conflicts' => [], 'error' => null ];
}

/**
 * List every file under $root as a path relative to it, sorted so output and
 * conflict reporting are deterministic.
 *
 * @return list<string>
 */
function brilkic_scaffold_files( string $root ) : array {
	$files = [];

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
	);
	foreach ( $iterator as $info ) {
		if ( $info instanceof SplFileInfo && $info->isFile() ) {
			$files[] = substr( $info->getPathname(), strlen( $root ) + 1 );
		}
	}

	sort( $files );

	return $files;
}
