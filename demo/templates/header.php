<?php
declare( strict_types = 1 );

$title = $data['title'] ?? 'Brilkic Demo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="<?= esc_attr( Config::CHAR_SET ) ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= esc_html( $title ) ?></title>
</head>
<body>
	<header>
		<h1><?= esc_html( $title ) ?></h1>
	</header>
	<main>
