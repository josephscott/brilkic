<?php
declare( strict_types = 1 );

// Report exactly which variables are in scope for the template, so a test
// can confirm that only $data leaks in and nothing else (e.g. $file).
echo implode( ',', array_keys( get_defined_vars() ) );
