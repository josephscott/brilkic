<?php
declare( strict_types = 1 );

function template( string $file, mixed $data = [] ) : void {
	$requested = Config::TEMPLATE_PATH . $file;

	// Canonicalize both the template root and the requested path, then
	// require that the resolved file lives strictly inside the root. This
	// neutralizes "../" traversal and symlinks that would otherwise let a
	// caller escape Config::TEMPLATE_PATH. realpath() also returns false
	// for paths that do not exist, which covers the missing-file case.
	$base = realpath( Config::TEMPLATE_PATH );
	$file = realpath( $requested );

	if (
		$base === false
		|| $file === false
		|| ! str_starts_with( $file, $base . DIRECTORY_SEPARATOR )
		|| ! is_readable( $file )
	) {
		log_error( "Template not readable: $requested" );
		return;
	}

	// Render in an isolated scope. A required file inherits the local
	// scope of wherever the require runs, so we hand off to a static
	// closure whose only named variable is $data. The path is passed
	// positionally and read via func_get_arg(), so it is never a
	// variable in scope. The Config class stays available as it is
	// global. Nothing else leaks into the template.
	// @phpstan-ignore arguments.count (extra arg read via func_get_arg)
	( static function( mixed $data ) : void {
		require func_get_arg( 1 );
	} )( $data, $file );
}
