<?php
declare( strict_types = 1 );

// Registered via Router::get( '/hello/{name}', 'hello.php' ). The matched URL
// segment arrives in $vars; it is untrusted input, so escape it on output.
$name = is_string( $vars['name'] ?? null ) ? $vars['name'] : '';

template( 'header.php', [ 'title' => 'Routing' ] );
?>
<div class="panel">
	<p style="font-size:1.4rem;margin:0">Hello, <strong><?= esc_html( $name ) ?></strong> &#128075;</p>
</div>
<p class="lead">This page matched <code>/hello/{name}</code>. The <code>{name}</code> segment was
captured by the router and handed to the route file in <code>$vars</code>.</p>
<p>Try a different name by editing the URL, e.g.
	<a href="/hello/brilkic">/hello/brilkic</a> or
	<a href="/hello/PHP%208.4">/hello/PHP 8.4</a>.</p>
<a class="back" href="/">&larr; Back home</a>
<?php
template( 'footer.php' );
