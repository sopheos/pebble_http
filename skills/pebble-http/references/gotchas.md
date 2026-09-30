# pebble_http — gotchas

Things the method names don't tell you. Grouped by class.

## Request

- **Reads superglobals once, by value.** `createFromServer()` snapshots `$_SERVER`/`$_GET`/`$_POST`/`$_COOKIE`/`$_FILES`/`php://input` at call time. Build it once per request; a second call won't see anything that changed since (and re-reading `php://input` a second time may come back empty depending on the SAPI anyway).
- **JSON auto-parsing requires an exact `Content-Type: application/json`.** The check is `$contentType !== 'application/json'` — a client sending `application/json; charset=utf-8` (very common) fails it. The code then falls back to treating the raw body as a query string (`mb_parse_str`), which silently produces a garbage or partial `bodyParams()` array instead of an error. If clients may send a charset parameter, don't rely on the built-in detection — parse the body yourself when needed.
- **`isSecure()` / `baseUrl()` / `currentUrl()` only check `$_SERVER['HTTPS']`.** There's no `X-Forwarded-Proto` support. Behind a reverse proxy terminating TLS, make sure the proxy sets `HTTPS=on` in the FastCGI/PHP-FPM params, or these will report `http://` even on a secure connection.
- **`ip()` only reads `REMOTE_ADDR`** (validated via `FILTER_VALIDATE_IP`, defaults to `'0.0.0.0'` if missing/invalid). No `X-Forwarded-For` handling — behind a load balancer or proxy, this returns the proxy's IP. Resolve `server('HTTP_X_FORWARDED_FOR')` yourself if you need the real client IP, and validate/trust it according to your proxy setup (don't trust it blindly — it's client-controllable unless your proxy overwrites it).
- **`attachements()` restructures `$_FILES` for array-style file inputs** (`<input type="file" name="docs[]">`) into a list of per-file associative arrays under that field name, but leaves single-file fields in PHP's native `$_FILES` shape. Don't assume one consistent shape across both cases — check whether the field is a multi-upload before iterating.
- **`method()` returns `'CLI'`** (not `null` or `''`) when there's no `REQUEST_METHOD`, e.g. running via `php script.php`. Useful as a truthy default in shared bootstrap code, but don't compare it against real HTTP verbs without accounting for this.

## Response

- **Mutable fluent builder, not an immutable PSR-7 message.** Every `setX()`/`addX()` mutates `$this` and returns it — there is no `withX()` clone semantics, despite the body being a real PSR-7 `StreamInterface` under the hood.
- **`redirect()` calls `reset()` internally.** It wipes all headers and the body, then re-adds only the `Set-Cookie` headers that existed *before* the call. Any `Content-Type`, custom header, or body set earlier is discarded. Always set cookies immediately before calling `redirect()`, and don't set anything else you expect to survive it.
- **`emitHeaders()` no-ops silently if `headers_sent()` is already true** — no exception, no warning. If any output (even a stray newline before `<?php`, a warning, or an earlier `echo`) happened before `emit()`, your status code and headers are just dropped with no signal that it happened. If a response "isn't working," check for premature output first.
- **`setJson()` uses `JSON_THROW_ON_ERROR`.** Encoding failures (resources, `NAN`/`INF` floats, recursive references) throw `\JsonException`, not a `Pebble\Http\Exceptions\ResponseException` — a generic `catch (ResponseException $ex)` around your endpoint won't catch it. Treat it as a bug in the data being serialized, not a normal error path.
- **`addCookie()`'s `samesite` setting only accepts `'lax'` or `'strict'`** (case-insensitive, checked via `in_array`). Anything else — including `'none'`, `''`, or a typo — is silently skipped, so no `SameSite` attribute is emitted at all rather than raising an error.
- **`addCookie()` URL-encodes the value for you** (`urlencode($value)`). Don't double-encode before passing it in.
- **`setContentType()` looks up the mime-map key first, falls back to the literal string.** `setContentType('json')` resolves through `MimesTypesTrait::$mimesTypes['json'][0]`. If you pass a key that *isn't* in that map (e.g. a typo, or a newer extension not in the table), it's used as-is as the literal `Content-Type` value — no error, just a wrong header. When in doubt, pass the full mime string directly (`'application/vnd.api+json'`) instead of guessing a short key.
- **`setStatusCode()` falls back to `"Unknown Status"`** as the reason phrase for codes not present in `HttpStatusTrait::$statusReasons` (pass `$reason` explicitly for such codes, e.g. a custom application status).
- **`createFromServer()` reads CORS *request* headers into config but never calls `cors()` for you.** You still decide when/if `Access-Control-Allow-*` headers actually get emitted, including for the actual (non-OPTIONS) response — see the CORS recipe in `SKILL.md`.

## Session

- **`start()` must run on every request, not just once.** It's the only place expiry bookkeeping happens: it walks the internal cache of flash/temp keys and expires/promotes them. Skipping it on some requests (e.g. a code path that returns early) means flash data can linger longer than "one more request," and temp data won't get cleaned up until a request that does call `start()`.
- **Flash/temp tracking only fires if the key already exists in `$_SESSION` at the moment you mark it.** `setFlash()`/`setTemp()` call `set()` first internally so this is handled for you — but if you call `markFlash()`/`markTemp()` directly on a key that isn't set yet, nothing is tracked (silently a no-op).
- **`id()` returns `null` until `start()` has been called**, even if a session is technically active at the PHP level — don't call it before `start()`.

## Exceptions

- **`ResponseException`'s default status is 418**, not 500 or 400, if you construct it directly without a subclass or `createWithStatus()`. Prefer a named subclass so the status is explicit at the call site.
- **`jsonSerialize()` turns empty `errors`/`extra` arrays into `null`**, not `[]` (`$this->errors ?: null`). If a frontend expects a consistent array type, normalize on that side rather than assuming `[]`.
- **`ExpiredException` maps to HTTP 419**, which is not a registered IANA status code (a Laravel-originated convention for "page expired"). If the consuming client (e.g. a JS fetch layer) special-cases standard status codes only, make sure it also handles 419, or catch it and remap if this needs to be a standards-compliant API.
