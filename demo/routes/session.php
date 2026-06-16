<?php
declare( strict_types = 1 );

// Demonstrates sessions. session_start_safe() starts a session (with fixation
// protection forced on) only when we actually need to write one -- the response
// is buffered, so this works mid-render. On later requests run_app() resumes the
// same session automatically because the client now presents its cookie.
//
// run_app() strips the query string before dispatch, but $_GET is still
// populated, so the route can branch on ?destroy / ?reset.

// ?destroy tears the whole session down: data cleared, cookie expired, and the
// server-side record discarded. After this the counter starts over from scratch.
if ( isset( $_GET['destroy'] ) ) {
	session_destroy_safe();

	template( 'header.php', [ 'title' => 'Sessions' ] );
	?>
	<p class="flash flash-ok">Session deleted. The counter and cookie are gone.</p>
	<div class="panel">
		<p style="margin:0"><a href="/session">Start a fresh session &rarr;</a></p>
	</div>
	<a class="back" href="/">&larr; Back home</a>
	<?php
	template( 'footer.php' );
	return;
}

session_start_safe();

// ?reset clears just the counter but keeps the session itself alive.
if ( isset( $_GET['reset'] ) ) {
	unset( $_SESSION['count'] );
}

$count = $_SESSION['count'] ?? 0;
$count = is_int( $count ) ? $count + 1 : 1;
$_SESSION['count'] = $count;

template( 'header.php', [ 'title' => 'Sessions' ] );
?>
<p class="lead">A per-visit counter kept in <code>$_SESSION</code>. Reload to watch it climb;
the value survives because run_app() resumes the session on each request.</p>

<div class="panel">
	<p style="font-size:1.4rem;margin:0">You have viewed this page
		<strong><?= esc_html( (string) $count ) ?></strong>
		<?= $count === 1 ? 'time' : 'times' ?> this session.</p>
</div>
<p>
	<a href="/session">Reload</a> &middot;
	<a href="/session?reset=1">Reset counter</a> &middot;
	<a href="/session?destroy=1">Delete session</a>
</p>
<a class="back" href="/">&larr; Back home</a>
<?php
template( 'footer.php' );
