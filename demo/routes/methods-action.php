<?php
declare( strict_types = 1 );

// Registered for PUT, PATCH, and DELETE on /methods/action. A request with any
// other verb never reaches this file: the router returns 405 with an Allow
// header listing these three. Returns JSON describing what it handled.
header( 'Content-Type: application/json; charset=' . Config::CHAR_SET );

echo json_encode( [
	'handled' => $_SERVER['REQUEST_METHOD'] ?? '',
	'message' => 'This handler runs for PUT, PATCH, and DELETE.',
], JSON_PRETTY_PRINT );
