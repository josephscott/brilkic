<?php
declare( strict_types = 1 );

// Explains and exercises HTTP-method routing. The buttons below fetch
// /methods/action with different verbs. PUT/PATCH/DELETE are registered there
// and return JSON; GET is not, so the router answers 405 Method Not Allowed and
// sends an Allow header listing the verbs that are accepted.

template( 'header.php', [ 'title' => 'HTTP methods' ] );
?>
<p class="lead">brilkic routes per HTTP method. <code>/methods/action</code> accepts
<code>PUT</code>, <code>PATCH</code>, and <code>DELETE</code> &mdash; anything else gets a
<code>405</code> with an <code>Allow</code> header.</p>

<div class="panel">
	<p>
		<button type="button" data-method="PUT">PUT</button>
		<button type="button" data-method="PATCH">PATCH</button>
		<button type="button" data-method="DELETE">DELETE</button>
		<button type="button" data-method="GET">GET (405)</button>
	</p>
	<pre><code id="out">Click a button to send a request to /methods/action.</code></pre>
</div>
<a class="back" href="/">&larr; Back home</a>

<script>
	document.querySelectorAll('button[data-method]').forEach(function (btn) {
		btn.addEventListener('click', async function () {
			const method = btn.dataset.method;
			const out = document.getElementById('out');
			const res = await fetch('/methods/action', { method });
			const allow = res.headers.get('Allow');
			const body = await res.text();
			out.textContent =
				method + ' /methods/action -> ' + res.status +
				(allow ? '\nAllow: ' + allow : '') +
				'\n\n' + body;
		});
	});
</script>
<?php
template( 'footer.php' );
