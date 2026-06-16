<?php
declare( strict_types = 1 );

// The landing page. Each card links to a route that demonstrates one feature of
// the framework on its own page.

template( 'header.php', [ 'title' => 'Brilkic Demo' ] );
?>
<p class="lead">A tiny PHP micro-framework. Each page below shows one feature in action.</p>

<ul class="cards">
	<li>
		<a class="card" href="/hello/world">
			<h3>Routing &amp; URL params &rarr;</h3>
			<p>Match <code>/hello/{name}</code> and read the captured value in the route.</p>
		</a>
	</li>
	<li>
		<a class="card" href="/csrf">
			<h3>CSRF-protected form &rarr;</h3>
			<p>Mint a single-use token with <code>csrf_field()</code>, verify it with <code>csrf_verify()</code>.</p>
		</a>
	</li>
	<li>
		<a class="card" href="/escape">
			<h3>Output escaping &rarr;</h3>
			<p>Context-aware helpers: <code>esc_html()</code>, <code>esc_attr()</code>, and friends.</p>
		</a>
	</li>
	<li>
		<a class="card" href="/session">
			<h3>Sessions &rarr;</h3>
			<p>A per-visit counter in <code>$_SESSION</code>, with lazy start and auto-resume.</p>
		</a>
	</li>
	<li>
		<a class="card" href="/methods">
			<h3>HTTP methods &rarr;</h3>
			<p>Per-verb routing, plus the <code>405</code> + <code>Allow</code> header path.</p>
		</a>
	</li>
	<li>
		<a class="card" href="/api/info">
			<h3>JSON endpoint &rarr;</h3>
			<p>A route that sets its own <code>Content-Type</code> and returns JSON.</p>
		</a>
	</li>
	<li>
		<a class="card" href="/partials">
			<h3>Nested templates &rarr;</h3>
			<p>Compose a page from a header, footer, and a repeated card partial.</p>
		</a>
	</li>
	<li>
		<a class="card" href="/csrf/">
			<h3>Trailing-slash redirect &rarr;</h3>
			<p>Visit <code>/csrf/</code> and get redirected to the canonical <code>/csrf</code>.</p>
		</a>
	</li>
	<li>
		<a class="card" href="/does-not-exist">
			<h3>Custom 404 &rarr;</h3>
			<p>An unmatched path renders the registered <code>404</code> handler.</p>
		</a>
	</li>
	<li>
		<a class="card" href="/boom">
			<h3>Custom 500 &rarr;</h3>
			<p>A route that throws is logged and renders the <code>500</code> handler.</p>
		</a>
	</li>
</ul>
<?php
template( 'footer.php' );
