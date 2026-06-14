<?php
declare( strict_types = 1 );

function template( string $file, mixed $data = [] ) : void {
	$file = Config::TEMPLATE_PATH . $file;

	if ( ! is_readable( $file ) ) {
		log_error( "Template not readable: $file" );
		return;
	}

	// Render in an isolated scope. A required file inherits the local
	// scope of wherever the require runs, so we hand off to a static
	// closure whose only named variable is $data. The path is passed
	// positionally and read via func_get_arg(), so it is never a
	// variable in scope. The Config class stays available as it is
	// global. Nothing else leaks into the template.
	( static function ( mixed $data ) : void {
		require func_get_arg( 1 );
	} )( $data, $file );
}
