<?php
declare( strict_types = 1 );

// A minimal CSRF-protected form. On GET we render the form; the template embeds
// the token with csrf_field(). On POST we confirm the submitted token matches
// the one in the session with csrf_verify() before trusting the input.

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$result = null;

if ( $method === 'POST' ) {
	if ( csrf_verify() ) {
		$name = $_POST['name'] ?? '';
		$name = is_string( $name ) ? trim( $name ) : '';

		$result = [
			'ok'  => true,
			'msg' => $name === ''
				? 'Token valid. The form was accepted.'
				: "Token valid. Hello, $name!",
		];
	} else {
		// Reject the submission: the token was missing, wrong, or expired.
		http_response_code( 400 );
		$result = [
			'ok'  => false,
			'msg' => 'Token check failed. The form was rejected.',
		];
	}
}

template( 'header.php', [ 'title' => 'CSRF Demo' ] );
template( 'csrf-form.php', [ 'result' => $result ] );
template( 'footer.php' );
