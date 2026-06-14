<?php
declare( strict_types = 1 );

require __DIR__ . '/../vendor/autoload.php';

final class Config {
	const string ROUTE_PATH = __DIR__ . '/routes/';

	const string TEMPLATE_PATH = __DIR__ . '/templates/';
}

require __DIR__ . '/url-routes.php';
