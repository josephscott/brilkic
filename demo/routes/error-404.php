<?php
declare( strict_types = 1 );

// Registered via Router::error( 404, 'error-404.php' ). $vars carries the
// request method and path that missed, so the page can show what was not found.
$uri = ( is_array( $vars ) && is_string( $vars['uri'] ?? null ) ) ? $vars['uri'] : '';

template( 'header.php', [ 'title' => 'Not Found' ] );
?>
<div class="panel">
	<p>Sorry, nothing lives at <code><?= esc_html( $uri ) ?></code>.</p>
</div>
<a class="back" href="/">&larr; Back home</a>
<?php
template( 'footer.php' );
