<?php
declare( strict_types = 1 );

// A JSON endpoint. A route is free to override the default Content-Type with
// header() -- the response is buffered, so headers are not committed yet. This
// shows brilkic serving an API, not just HTML pages.
header( 'Content-Type: application/json; charset=' . Config::CHAR_SET );

echo json_encode( [
	'framework' => 'brilkic',
	'method'    => $_SERVER['REQUEST_METHOD'] ?? 'GET',
	'time'      => date( 'c' ),
	'message'   => 'Any route can return JSON by setting its own Content-Type.',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
