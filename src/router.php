<?php
declare( strict_types = 1 );

final class Router {
	/** @var list<array{method: string, path: string, file: string}> */
	private static array $routes = [];

	public static function get( string $path, string $file ) : void {
		self::add( 'GET', $path, $file );
	}

	public static function head( string $path, string $file ) : void {
		self::add( 'HEAD', $path, $file );
	}

	public static function post( string $path, string $file ) : void {
		self::add( 'POST', $path, $file );
	}

	public static function put( string $path, string $file ) : void {
		self::add( 'PUT', $path, $file );
	}

	public static function patch( string $path, string $file ) : void {
		self::add( 'PATCH', $path, $file );
	}

	public static function delete( string $path, string $file ) : void {
		self::add( 'DELETE', $path, $file );
	}

	public static function options( string $path, string $file ) : void {
		self::add( 'OPTIONS', $path, $file );
	}

	public static function add( string $method, string $path, string $file ) : void {
		self::$routes[] = [
			'method' => strtoupper( $method ),
			'path'   => $path,
			'file'   => $file,
		];
	}

	/** @return list<array{method: string, path: string, file: string}> */
	public static function routes() : array {
		return self::$routes;
	}
}
