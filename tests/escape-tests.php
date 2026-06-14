<?php
declare( strict_types = 1 );

// Each esc_* helper is a thin wrapper over one Laminas Escaper method. The
// assertions pin the concrete escaped output so a helper wired to the wrong
// method (e.g. esc_js delegating to escapeHtml) is caught immediately.

test( 'esc_html escapes element content', function() : void {
	expect( esc_html( '<b>Tom & Jerry</b>' ) )
		->toBe( '&lt;b&gt;Tom &amp; Jerry&lt;/b&gt;' );
} );

test( 'esc_attr escapes attribute values', function() : void {
	expect( esc_attr( 'a b"c' ) )
		->toBe( 'a&#x20;b&quot;c' );
} );

test( 'esc_js escapes a JavaScript string', function() : void {
	expect( esc_js( 'alert("x");' ) )
		->toBe( 'alert\\x28\\x22x\\x22\\x29\\x3B' );
} );

test( 'esc_url escapes a URL component', function() : void {
	expect( esc_url( 'a b&c=d/e' ) )
		->toBe( 'a%20b%26c%3Dd%2Fe' );
} );

test( 'esc_css escapes a CSS value', function() : void {
	expect( esc_css( 'color: red;' ) )
		->toBe( 'color\\3A \\20 red\\3B ' );
} );

test( 'escaper() reuses a single shared instance', function() : void {
	// The helpers memoize one Escaper rather than constructing a fresh one per
	// call; the same object must come back on repeated calls.
	expect( escaper() )->toBe( escaper() );
} );
