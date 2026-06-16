<?php
declare( strict_types = 1 );

$title = ( is_array( $data ) && isset( $data['title'] ) && is_string( $data['title'] ) )
	? $data['title']
	: 'My Site';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="<?= esc_attr( Config::CHAR_SET ) ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= esc_html( $title ) ?></title>
	<style>
		body {
			font-family: system-ui, -apple-system, sans-serif;
			line-height: 1.6;
			max-width: 42rem;
			margin: 2rem auto;
			padding: 0 1rem;
			color: #1a1a1a;
		}
		header h1 { margin-bottom: 0; }
		main { margin-top: 1rem; }
		footer {
			margin-top: 3rem;
			padding-top: 1rem;
			border-top: 1px solid #e5e5e5;
			color: #666;
			font-size: 0.9rem;
		}
		code { background: #f4f4f4; padding: 0.1rem 0.35rem; border-radius: 4px; }
	</style>
</head>
<body>
	<header>
		<h1><?= esc_html( $title ) ?></h1>
	</header>
	<main>
