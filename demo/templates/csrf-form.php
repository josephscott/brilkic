<?php
declare( strict_types = 1 );

// $data['result'] is null on first load, or [ 'ok' => bool, 'msg' => string ]
// after a POST. csrf_field() outputs the hidden token input for the form.
$result = $data['result'] ?? null;
?>
<?php if ( is_array( $result ) ) : ?>
	<p style="color: <?= ( $result['ok'] ?? false ) ? 'green' : 'red' ?>">
		<?= esc_html( is_string( $result['msg'] ?? null ) ? $result['msg'] : '' ) ?>
	</p>
<?php endif; ?>
<form method="post" action="/csrf">
	<?= csrf_field() ?>
	<label>Your name: <input type="text" name="name"></label>
	<button type="submit">Submit</button>
</form>
