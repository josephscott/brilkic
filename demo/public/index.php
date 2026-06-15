<?php
declare( strict_types = 1 );

require __DIR__ . '/../init.php';
require __DIR__ . '/../url-routes.php';

// No eager session_start() here. run_app() resumes an existing session when the
// client presents one (session_resume_if_present()) and buffers the response so
// the csrf_* helpers can otherwise start a session lazily, only on the pages
// that need one. A visitor with no session never touches the session subsystem,
// so they avoid its per-request file lock and I/O.
run_app();
