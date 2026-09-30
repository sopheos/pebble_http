# pebble_http — API cheat sheet

Quick lookup by intent. This is not exhaustive — read the source in `vendor/sopheos/pebble_http/src/` for exact signatures/edge cases not covered here.

## Request (`Pebble\Http\Request`)

Build with `Request::createFromServer()` (reads superglobals) or `Request::create()` + manual setters (useful in tests).

| Intent | Method |
|---|---|
| Current path (no query string) | `uri(): string` |
| Scheme + host, e.g. `https://example.com` | `baseUrl(): string` |
| Full current URL, optionally with query string | `currentUrl(bool $withQueryString = true): string` |
| Raw `$_SERVER` array / single value | `servers(): array` / `server(string $name): mixed` |
| Request timestamp (int / float with µs) | `time(): int` / `mtime(): float` |
| HTTP method, `'CLI'` if none | `method(): string` |
| Method checks | `isGet()`, `isPost()`, `isPut()`, `isPatch()`, `isDelete()`, `isClient()` |
| `X-Requested-With: XMLHttpRequest` check | `isAjax(): bool` |
| HTTPS check (see gotchas re: proxies) | `isSecure(): bool` |
| `User-Agent` header | `userAgent(): ?string` |
| Client IP, validated, `'0.0.0.0'` if unknown | `ip(): string` |
| Query params (array / single) | `queryParams(): array` / `queryParam(string $name): mixed` |
| Body params — form or JSON, array / single | `bodyParams(): array` / `bodyParam(string $name): mixed` |
| Uploaded files — all / one field | `attachements(): array` / `attachement(string $name): ?array` |
| Cookies — all / one | `cookies(): array` / `cookie(string $name): mixed` |

## Response (`Pebble\Http\Response`)

Build with `Response::createFromServer()` (picks up protocol version, HTTPS, and CORS request headers) or `Response::create()`.

**Config** (call before building the response body, all fluent):
`setStreamFactory()`, `setBuffer(int)`, `setCookiePrefix()`, `setCookieDomain()`, `setCookiePath()`, `setCookieSecure()`, `setCookieHttponly()`, `setCookieSamesite()`, `setCorsOrigin()`, `setCorsMethods()`, `setCorsHeaders()`.

**Status / protocol:**
`getProtocolVersion()` / `setProtocolVersion(string $version)` (accepts `"1.1"` or `"HTTP/1.1"`), `getStatusCode()` / `setStatusCode(int $code, ?string $reason = null)`.

**Headers:**
`getHeaders(): array`, `getHeader(string $name): string[]`, `hasHeader(string $name): bool`, `addHeader(string $name, string $value, bool $replace = false)`, `removeHeader(string $name)`, `setContentType(string $mime, string $charset = "UTF-8")` (accepts a mime-map key like `'json'` or a literal mime string).

**Cookies:**
`addCookie(string $name, $value, int $expire = 0, array $settings = [])`, `removeCookie(string $name)` (sets expiry in the past).

**Redirect / cache:**
`redirect(string $url = "/", bool $temporary = true)` (302 or 301; resets headers/body but keeps cookies), `cache(int $age = 86400)`, `noCache()`.

**CORS:**
`cors(?string $origin = null, ?string $methods = null, ?string $headers = null)` — writes the `Access-Control-Allow-*` headers using the config set via `setCorsOrigin()`/etc. or `createFromServer()`, unless overridden by the arguments.

**Body:**
`getBody(): StreamInterface`, `setBody($body = "")` (accepts `StreamInterface`, resource, scalar, `Stringable`, or `null`), `setBodyFile(string $filename, string $mode = 'r')` (streamed, not loaded in memory), `setText(string $data = '')`, `setJson(mixed $data = null, int $flags = 0)` (throws `\JsonException` on failure), `setJsonException(ResponseException $ex)`.

**Rendering (call exactly once, at the end):**
`emit(?int $bufferLength = null)`, `emitHeaders()`, `emitBody(?int $bufferLength = null)`.

**Misc:**
`reset()` — clears headers + body, keeps config (stream factory, buffer, cookie/CORS settings).

Full IANA status code list: `Response::HTTP_*` constants, reason phrases in `HttpStatusTrait::$statusReasons`. Full mime-type key map: `MimesTypesTrait::$mimesTypes` (hundreds of entries — grep it rather than guessing a key).

## Session (`Pebble\Http\Session`)

`__construct(?SessionHandlerInterface $handler = null)` — pass a handler for custom storage (Redis, DB, etc.), otherwise uses PHP's default.

| Intent | Method |
|---|---|
| Start/resume session, expire flash & temp data | `start(?string $id = null): static` |
| Current session ID (null if not started) | `id(): ?string` |
| All session data | `all(): array` |
| Has / get / set a value | `has(string $name)`, `get(string $name, $default = null)`, `set(string $name, $value)` |
| Set a value that survives one more request | `setFlash(string $name, $value)` |
| Set a value that expires after N seconds | `setTemp(string $name, $value, int $time = 300)` |
| Mark/unmark existing keys as flash/temp without re-setting the value | `markFlash()`, `unmarkFlash()`, `markTemp()`, `unmarkTemp()` |
| Delete one key | `delete(string $name)` |
| Clear all session vars (keeps the session open) | `reset()` |
| Destroy the session entirely | `destroy()` |
| Write and close (before a long-running response) | `close()` |

## Exceptions (`Pebble\Http\Exceptions`)

All extend `ResponseException` (itself extends `\Exception`, implements `JsonSerializable`). Throw the specific subclass instead of picking a status code by hand.

| Class | Status | Typical use |
|---|---|---|
| `UserException` | 400 Bad Request | Invalid input / validation failure |
| `AccessException` | 401 Unauthorized | Missing/invalid credentials |
| `ForbiddenException` | 403 Forbidden | Authenticated but not allowed |
| `EmptyException` | 404 Not Found | Resource doesn't exist |
| `NotAcceptableException` | 406 Not Acceptable | Can't satisfy requested representation |
| `ExpiredException` | 419 Page Expired (non-standard) | Expired token/session/CSRF |
| `LockException` | 423 Locked | Resource locked (e.g. concurrent edit) |
| `SystemException` | 500 Internal Server Error | Unexpected failure |
| `ResponseException` | 418 by default, or via `createWithStatus()` | Anything not covered above |

`ResponseException` API:
- `::create(string $error = 'default')`, `::createWithStatus(int $status, string $error = 'default')`
- `setErrors(array)`, `addError(string $key, $value)` — field-level validation details
- `setExtra(array)`, `addExtra(string $key, $value)` — any other payload data
- `jsonSerialize()` → `['status' => ..., 'error' => ..., 'errors' => ..., 'extra' => ...]` — `errors`/`extra` serialize to `null` (not `[]`) when empty
