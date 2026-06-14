<?php
declare( strict_types = 1 );

Router::get( '/', 'home.php' );
Router::get( '/hello/{name}', 'hello.php' );
