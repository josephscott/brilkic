<?php
declare( strict_types = 1 );

// Rendered for any unmatched path (registered via Router::error( 404, ... )).
template( 'header.php', [ 'title' => 'Not Found' ] );
?>
<p>Sorry, that page does not exist.</p>
<?php
template( 'footer.php' );
