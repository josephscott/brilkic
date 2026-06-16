<?php
declare( strict_types = 1 );

// Demonstrates the context-aware escaping helpers. A single hostile-looking
// string is rendered through each helper so you can see how the output differs
// per context (and that none of it executes).
$payload = '<script>alert("xss")</script> & "quotes" \'apostrophe\'';

template( 'header.php', [ 'title' => 'Escaping' ] );
?>
<p class="lead">The same untrusted string, escaped for each output context. View source to
confirm nothing executes.</p>

<div class="panel">
	<p><code>esc_html()</code> &mdash; element text:</p>
	<pre><code><?= esc_html( $payload ) ?></code></pre>

	<p><code>esc_attr()</code> &mdash; inside an attribute:</p>
	<pre><code>&lt;input value="<?= esc_html( esc_attr( $payload ) ) ?>"&gt;</code></pre>

	<p><code>esc_js()</code> &mdash; inside a JavaScript string:</p>
	<pre><code><?= esc_html( esc_js( $payload ) ) ?></code></pre>

	<p><code>esc_url()</code> &mdash; inside a URL:</p>
	<pre><code><?= esc_html( esc_url( $payload ) ) ?></code></pre>
</div>
<a class="back" href="/">&larr; Back home</a>
<?php
template( 'footer.php' );
