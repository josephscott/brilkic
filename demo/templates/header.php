<?php
declare( strict_types = 1 );

$title = $data['title'] ?? 'Brilkic Demo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="<?= esc_attr( Config::CHAR_SET ) ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= esc_html( $title ) ?> &middot; Brilkic Demo</title>
	<style>
		:root {
			--bg: #0f1115;
			--panel: #181b22;
			--panel-2: #1f232c;
			--border: #2a2f3a;
			--text: #e6e9ef;
			--muted: #9aa3b2;
			--accent: #6ea8fe;
			--ok: #4ade80;
			--err: #f87171;
			--radius: 12px;
		}

		* { box-sizing: border-box; }

		body {
			margin: 0;
			font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
			line-height: 1.6;
			color: var(--text);
			background: radial-gradient(1200px 600px at 80% -10%, #1b2030 0%, var(--bg) 60%);
			min-height: 100vh;
		}

		a { color: var(--accent); text-decoration: none; }
		a:hover { text-decoration: underline; }

		.site-header {
			border-bottom: 1px solid var(--border);
			background: rgba(15, 17, 21, 0.7);
			backdrop-filter: blur(8px);
			position: sticky;
			top: 0;
			z-index: 10;
		}

		.site-header .bar {
			max-width: 860px;
			margin: 0 auto;
			padding: 0.9rem 1.25rem;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 1rem;
		}

		.brand { font-weight: 700; font-size: 1.15rem; color: var(--text); letter-spacing: 0.02em; }
		.brand:hover { text-decoration: none; color: var(--accent); }

		.site-header nav { display: flex; gap: 1.1rem; font-size: 0.95rem; }

		main {
			max-width: 860px;
			margin: 0 auto;
			padding: 2rem 1.25rem 3rem;
		}

		main > h2:first-child { margin-top: 0; }

		.lead { color: var(--muted); font-size: 1.05rem; }

		.cards {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
			gap: 1rem;
			margin: 1.5rem 0;
			padding: 0;
			list-style: none;
		}

		.card {
			display: block;
			padding: 1.1rem 1.2rem;
			background: var(--panel);
			border: 1px solid var(--border);
			border-radius: var(--radius);
			color: var(--text);
			transition: border-color 0.15s ease, transform 0.15s ease;
		}

		.card:hover { text-decoration: none; border-color: var(--accent); transform: translateY(-2px); }
		.card h3 { margin: 0 0 0.35rem; font-size: 1.05rem; }
		.card p { margin: 0; color: var(--muted); font-size: 0.9rem; }

		.panel {
			background: var(--panel);
			border: 1px solid var(--border);
			border-radius: var(--radius);
			padding: 1.25rem 1.4rem;
			margin: 1.25rem 0;
		}

		code {
			background: var(--panel-2);
			border: 1px solid var(--border);
			padding: 0.1rem 0.4rem;
			border-radius: 6px;
			font-size: 0.9em;
		}

		pre {
			background: var(--panel-2);
			border: 1px solid var(--border);
			border-radius: 8px;
			padding: 0.9rem 1rem;
			overflow-x: auto;
		}

		pre code { background: none; border: none; padding: 0; }

		label { display: block; margin-bottom: 0.75rem; color: var(--muted); }

		input[type="text"] {
			display: block;
			margin-top: 0.3rem;
			width: 100%;
			max-width: 360px;
			padding: 0.55rem 0.7rem;
			background: var(--panel-2);
			border: 1px solid var(--border);
			border-radius: 8px;
			color: var(--text);
			font-size: 1rem;
		}

		button {
			padding: 0.55rem 1.1rem;
			background: var(--accent);
			color: #0b1020;
			border: none;
			border-radius: 8px;
			font-size: 1rem;
			font-weight: 600;
			cursor: pointer;
		}

		button:hover { background: #8bbcff; }

		.flash { padding: 0.7rem 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--border); }
		.flash-ok { color: var(--ok); border-color: rgba(74, 222, 128, 0.4); background: rgba(74, 222, 128, 0.08); }
		.flash-err { color: var(--err); border-color: rgba(248, 113, 113, 0.4); background: rgba(248, 113, 113, 0.08); }

		.back { display: inline-block; margin-top: 1.5rem; color: var(--muted); }

		.site-footer {
			max-width: 860px;
			margin: 0 auto;
			padding: 1.5rem 1.25rem 2.5rem;
			color: var(--muted);
			font-size: 0.85rem;
			border-top: 1px solid var(--border);
		}
	</style>
</head>
<body>
	<header class="site-header">
		<div class="bar">
			<a class="brand" href="/">brilkic</a>
			<nav>
				<a href="/">Home</a>
				<a href="/hello/world">Routing</a>
				<a href="/csrf">CSRF</a>
				<a href="/escape">Escaping</a>
			</nav>
		</div>
	</header>
	<main>
		<h2><?= esc_html( $title ) ?></h2>
