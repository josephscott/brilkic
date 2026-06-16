<?php
declare( strict_types = 1 );

// Emits output and then throws, so a test can confirm the half-rendered body is
// discarded before the 500 handler runs.
echo 'PARTIAL';
throw new RuntimeException( 'boom after partial output' );
