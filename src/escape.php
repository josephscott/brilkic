<?php
declare( strict_types = 1 );

function escaper() : Laminas\Escaper\Escaper {
	static $escaper = null;
	if ( $escaper === null ) {
		$escaper = new Laminas\Escaper\Escaper( Config::CHAR_SET );
	}
	return $escaper;
}

function esc_html( string $input ) : string {
	return escaper()->escapeHtml( $input );
}

function esc_attr( string $input ) : string {
	return escaper()->escapeHtmlAttr( $input );
}

function esc_js( string $input ) : string {
	return escaper()->escapeJs( $input );
}

function esc_url( string $input ) : string {
	return escaper()->escapeUrl( $input );
}

function esc_css( string $input ) : string {
	return escaper()->escapeCss( $input );
}
