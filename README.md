# brilkic

A tiny PHP micro-framework. File-based routing on top of [FastRoute](https://github.com/nikic/FastRoute), isolated-scope template rendering, and built-in escaping, CSRF, and session helpers. No controllers, no classes to extend — routes and templates are plain PHP files.

Requires PHP >= 8.4.

## Quick start

Install:

```sh
composer require josephscott/brilkic
```

Create a `Config` class, register routes, and call `run_app()`. A minimal app is four files:

```php
// public/index.php — the single entry point (point your web server here)
<?php
require __DIR__ . '/../vendor/autoload.php';

final class Config {
    const string ROUTE_PATH    = __DIR__ . '/../routes/';
    const string TEMPLATE_PATH = __DIR__ . '/../templates/';
}

Router::get( '/', 'home.php' );
Router::get( '/hello/{name}', 'hello.php' );

run_app();
```

```php
// routes/home.php — a route is just a PHP file in ROUTE_PATH
<?php
template( 'page.php', [ 'title' => 'Home', 'body' => 'Hello, world' ] );
```

```php
// routes/hello.php — matched URL params arrive in $vars
<?php
template( 'page.php', [ 'title' => 'Hi', 'body' => 'Hello, ' . esc_html( $vars['name'] ) ] );
```

```php
// templates/page.php — a template is a PHP file in TEMPLATE_PATH; gets $data
<?php
?>
<!DOCTYPE html>
<title><?= esc_html( $data['title'] ) ?></title>
<p><?= $data['body'] ?></p>
```

Run it locally with PHP's built-in server:

```sh
php -S localhost:8080 -t public
```

A complete working example lives in [`demo/`](demo/) (`php -S localhost:8080 -t demo/public`).

## Deploying with nginx + php-fpm

Point the document root at your `public/` directory and route every request that
isn't a real file to `index.php` — brilkic's single entry point handles the rest.

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/app/public;
    index index.php;

    # Serve real files directly; send everything else to the front controller.
    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_param DOCUMENT_ROOT $document_root;
    }
}
```

Only `public/` is web-served; keep `routes/`, `templates/`, `vendor/`, and your
`Config` outside the document root.

## Core API

| Call | Purpose |
| --- | --- |
| `Router::get/head/post/put/patch/delete/options( $path, $file )` | Register a route. `$path` is a FastRoute pattern (e.g. `/hello/{name}`); `$file` is a path relative to `ROUTE_PATH`. |
| `Router::add( $method, $path, $file )` | Register a route for any method. |
| `Router::error( $status, $file )` | Render `$file` for an HTTP error (e.g. `404`, `405`, `500`). Without one, the bare status is sent and nothing is rendered. |
| `run_app()` | Match the request, run the route, send the response. Call once from your entry point. |
| `template( $file, $data = [] )` | Render a template file (relative to `TEMPLATE_PATH`) in an isolated scope; it sees only `$data`. |
| `esc_html / esc_attr / esc_js / esc_url / esc_css( $string )` | Context-aware escaping (laminas-escaper). |
| `csrf_token( $ttl = null )` | Mint a fresh single-use token. |
| `csrf_field( $ttl = null )` | Return a ready-to-embed hidden `<input>` carrying a fresh token. |
| `csrf_verify()` | Validate and consume the token from `$_POST` (the one-liner for a form handler). |
| `csrf_validate( $token )` | Validate and consume an arbitrary token value. |
| `log_error( $data )` | Write to the PHP error log (arrays/objects are `print_r`'d). |

Inside a route file, matched URL parameters are available as `$vars`. Error routes receive context in `$vars` too (404: `method`, `uri`; 405: `allowed`). Templates receive `$data`. The `Config` class is available everywhere (it's global); nothing else leaks into route or template scope.

### Behavior worth knowing

- The whole response is buffered, so CSRF and session helpers can start a session lazily mid-render — only on pages that need one. Visitors without a session never touch the session subsystem.
- Sessions are started with `session.use_strict_mode` forced on (fixation protection). An existing session is auto-resumed when the client presents its cookie.
- Route and template paths are canonicalized and confined to their configured roots, neutralizing `../` traversal and symlink escapes.
- A route that throws is logged, the half-rendered output is discarded, and the `500` handler runs.
- `X-Content-Type-Options: nosniff` is always sent; `Content-Type` (with charset) is sent before dispatch unless opted out.

## Configuration options

All configuration lives as constants on your `Config` class. Only `ROUTE_PATH` and `TEMPLATE_PATH` are required; every other constant is read by name and falls back to a default when omitted.

| Constant | Type | Default | Description |
| --- | --- | --- | --- |
| `ROUTE_PATH` | `string` | — | Directory route files are resolved against. Routes must live inside it. |
| `TEMPLATE_PATH` | `string` | — | Directory template files are resolved against. Templates must live inside it. |
| `CHAR_SET` | `string` | `utf-8` | Charset for the escaper and the `Content-Type` header (pinned together so they can't drift). |
| `DEFAULT_CONTENT_TYPE` | `string` | `text/html` | `Content-Type` sent before dispatch (charset appended). Set to `''` to send none — e.g. an API that sets its own type per route. |
| `CSRF_TOKEN_FIELD` | `string` | `csrf_token` | Hidden form field / `$_POST` key the token travels in. |
| `CSRF_SESSION_KEY` | `string` | `csrf_token` | `$_SESSION` key the token pool is stored under. |
| `CSRF_TOKEN_TTL` | `int` | `1800` | Seconds a minted token stays valid. A per-call `$ttl` overrides it. |
| `SESSION_AUTO_RESUME` | `bool` | `true` | Resume an existing session when the client presents its cookie, before routes run. Set `false` for a stateless API. |
| `TRAILING_SLASH_REDIRECT` | `bool` | `true` | When a path doesn't match, try its trailing-slash variant and redirect to it if registered (rather than 404). Set `false` to disable. |
| `TRAILING_SLASH_ADD` | `bool` | `false` | Redirect direction. `false` strips a trailing slash (`/csrf/` → `/csrf`); `true` adds one (`/csrf` → `/csrf/`). |
| `TRAILING_SLASH_REDIRECT_CODE` | `int` | `302` | Status for the trailing-slash redirect. `302` (temporary) or `301` (permanent, cacheable). |

A ready-to-copy config stub with these documented inline is in [`stubs/config.stub`](stubs/config.stub).

## Development

```sh
make all      # style, lint, static analysis, tests
make tests    # Pest
make analyze  # PHPStan
make style    # php-cs-fixer
```

## License

MIT — see [LICENSE](LICENSE).
