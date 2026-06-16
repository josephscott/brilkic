<?php
declare( strict_types = 1 );

// $data['result'] is null on first load, or [ 'ok' => bool, 'msg' => string ]
// after a POST. csrf_field() outputs the hidden token input for the form.
$result = $data['result'] ?? null;
?>
<p class="lead">A single-use CSRF token is minted on render and verified on submit.</p>
<?php if ( is_array( $result ) ) : ?>
	<p class="flash <?= ( $result['ok'] ?? false ) ? 'flash-ok' : 'flash-err' ?>">
		<?= esc_html( is_string( $result['msg'] ?? null ) ? $result['msg'] : '' ) ?>
	</p>
<?php endif; ?>
<form class="panel" method="post" action="/csrf">
	<?= csrf_field() ?>
	<label>Your name
		<input type="text" name="name" placeholder="optional">
	</label>
	<button type="submit">Submit</button>
</form>
<a class="back" href="/">&larr; Back home</a>
