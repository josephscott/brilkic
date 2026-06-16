<?php
declare( strict_types = 1 );

// Registered via Router::error( 500, 'error-500.php' ). Reached when a route
// file cannot be read or a route throws; the cause is already logged via
// log_error(), so this page stays deliberately generic and leaks no detail.
template( 'header.php', [ 'title' => 'Something went wrong' ] );
?>
<div class="panel">
	<p>Something went wrong on our end. The error has been logged.</p>
</div>
<a class="back" href="/">&larr; Back home</a>
<?php
template( 'footer.php' );
