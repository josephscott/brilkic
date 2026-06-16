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

	/** @var array<int, string> */
	private static array $errors = [];

	/**
	 * Register a route file to render for an HTTP error status (404, 405, 500,
	 * ...). run_app()/run_error() invoke it when that condition occurs; with no
	 * handler registered the bare status is sent and nothing is rendered.
	 */
	public static function error( int $status, string $file ) : void {
		self::$errors[$status] = $file;
	}

	/** @return array<int, string> */
	public static function errors() : array {
		return self::$errors;
	}
}
