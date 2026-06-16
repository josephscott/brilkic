<?php
declare( strict_types = 1 );

// Demonstrates template composition. The page stitches together the shared
// header and footer plus a card partial rendered once per item. Each template()
// call renders in its own isolated scope, so the partial sees only the $data it
// is handed -- the loop variable below never leaks into it.
$items = [
	[ 'title' => 'Isolated scope', 'body' => 'A template sees only its own $data, never the caller\'s variables.' ],
	[ 'title' => 'Reusable', 'body' => 'The same partial renders here once per item with different data.' ],
	[ 'title' => 'Confined to root', 'body' => 'Paths resolve under TEMPLATE_PATH; ../ traversal is rejected.' ],
];

template( 'header.php', [ 'title' => 'Nested templates' ] );
?>
<p class="lead">One page composed from several <code>template()</code> calls: a shared header and
footer, plus a card partial rendered once per item.</p>

<div class="cards">
<?php foreach ( $items as $item ) : ?>
	<?php template( 'partial-card.php', $item ); ?>
<?php endforeach; ?>
</div>
<a class="back" href="/">&larr; Back home</a>
<?php
template( 'footer.php' );
