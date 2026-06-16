<?php
declare( strict_types = 1 );

// A reusable partial. It is rendered in its own isolated scope and sees only the
// $data passed to this template() call -- nothing leaks in from the caller.
$title = is_string( $data['title'] ?? null ) ? $data['title'] : '';
$body = is_string( $data['body'] ?? null ) ? $data['body'] : '';
?>
<div class="card" style="transform:none;cursor:default">
	<h3><?= esc_html( $title ) ?></h3>
	<p><?= esc_html( $body ) ?></p>
</div>
