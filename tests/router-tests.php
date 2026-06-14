<?php
declare( strict_types = 1 );

// Router keeps its registered routes in a private static array. Reset it
// before each test so cases stay isolated from one another.
beforeEach( function() : void {
	( new ReflectionProperty( Router::class, 'routes' ) )->setValue( null, [] );
} );

test( 'starts with no routes', function() : void {
	expect( Router::routes() )->toBe( [] );
} );

test( 'records a route via add()', function() : void {
	Router::add( 'GET', '/home', 'home.php' );

	expect( Router::routes() )->toBe( [
		[ 'method' => 'GET', 'path' => '/home', 'file' => 'home.php' ],
	] );
} );

test( 'uppercases the method in add()', function() : void {
	Router::add( 'get', '/x', 'x.php' );

	expect( Router::routes()[0]['method'] )->toBe( 'GET' );
} );

test( 'appends routes in registration order', function() : void {
	Router::get( '/a', 'a.php' );
	Router::post( '/b', 'b.php' );
	Router::get( '/c', 'c.php' );

	expect( array_column( Router::routes(), 'path' ) )
		->toBe( [ '/a', '/b', '/c' ] );
} );

test( 'verb helper registers the matching HTTP method', function( string $verb, string $method ) : void {
	call_user_func( [ Router::class, $verb ], '/path', 'file.php' );

	expect( Router::routes() )->toBe( [
		[ 'method' => $method, 'path' => '/path', 'file' => 'file.php' ],
	] );
} )->with( [
	'get'     => [ 'get', 'GET' ],
	'head'    => [ 'head', 'HEAD' ],
	'post'    => [ 'post', 'POST' ],
	'put'     => [ 'put', 'PUT' ],
	'patch'   => [ 'patch', 'PATCH' ],
	'delete'  => [ 'delete', 'DELETE' ],
	'options' => [ 'options', 'OPTIONS' ],
] );
