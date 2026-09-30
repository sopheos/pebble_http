---
name: pebble-http
description: How to correctly build HTTP request/response handling, JSON APIs, redirects, cookies, CORS, and sessions (flash/temp data) using the sopheos/pebble_http PHP library (namespace Pebble\Http — classes Request, Response, Session, and ResponseException subclasses like UserException, AccessException, EmptyException). Use this whenever the project's composer.json requires sopheos/pebble_http, code imports from Pebble\Http\*, or you're asked to add/change an HTTP endpoint, controller, API response, redirect, cookie, CORS handling, file download, or session flash/temp data in a PHP project that has this library available — even if the request is phrased generically like "add an endpoint" or "return JSON" or "set a cookie" without naming the library. Also check this before writing raw header()/setcookie()/$_SESSION/php://input code in such a project, since this library replaces those primitives and has non-obvious behavior (redirect() resets headers, JSON auto-parsing needs an exact Content-Type, Session::start() must run every request) that plain PHP code would miss.
---

# pebble_http

`sopheos/pebble_http` is a small, framework-agnostic HTTP layer for PHP 8.1+: a `Request` reader, a fluent `Response` builder (PSR-7 body via `nyholm/psr7` under the hood), a `Session` wrapper with flash/temp data, and a family of `ResponseException` subclasses that map 1:1 to HTTP status codes. It does **not** include routing, a DI container, or middleware — you wire it into whatever entrypoint/controller shape the project already uses.

Namespace: `Pebble\Http\*`. Source lives in `vendor/sopheos/pebble_http/src/`; read it directly when you need an exact method signature — this skill focuses on *how the pieces fit together* and the behavior that isn't obvious from the method names.

## Orientation

- `Request` — read-only snapshot of the incoming HTTP request. Build it once via `Request::createFromServer()`.
- `Response` — mutable, fluent HTTP response builder. Build it via `Response::createFromServer()`, configure it, then call `emit()` once at the very end.
- `Session` — thin wrapper over `$_SESSION` with flash (survives one more request) and temp (survives N seconds) semantics.
- `Exceptions\ResponseException` (+ subclasses `UserException`, `AccessException`, `ForbiddenException`, `EmptyException`, `NotAcceptableException`, `ExpiredException`, `LockException`, `SystemException`) — throw these from business logic instead of manually picking a status code; each subclass already carries its own status.
- `Response::HTTP_*` constants — always prefer these over magic numbers; they also drive the auto-filled reason phrase.

For a full method cheat-sheet and the exception → status-code table, see `references/api-reference.md`. For the complete list of easy-to-miss behaviors, see `references/gotchas.md` — read it before debugging something that "should just work."

## Core recipes

### Bootstrap

```php
use Pebble\Http\Request;
use Pebble\Http\Response;

$request  = Request::createFromServer();   // reads $_SERVER/$_GET/$_POST/$_COOKIE/$_FILES/php://input ONCE
$response = Response::createFromServer();  // picks up protocol version, HTTPS, and CORS request headers from $_SERVER
```

Build each of these exactly once per request. `Request::createFromServer()` captures superglobals by value at call time, so calling it again later in the request won't see anything new.

### JSON API endpoint with typed errors

Throw the specific `ResponseException` subclass that matches the situation; catch `ResponseException` once at the boundary and convert it with `setJsonException()`. This keeps status-code decisions in the business logic and formatting in one place.

```php
use Pebble\Http\Exceptions\{ResponseException, UserException, EmptyException};

try {
    $email = $request->bodyParam('email');
    if (!$email) {
        throw UserException::create('validation_error')->addError('email', 'required');
    }

    $user = $repo->findByEmail($email);
    if (!$user) {
        throw EmptyException::create('user_not_found');
    }

    $response->setJson(['id' => $user->id, 'email' => $user->email]);
} catch (ResponseException $ex) {
    $response->setJsonException($ex); // sets status code from $ex and body to $ex->jsonSerialize()
}

$response->emit();
```

`setJson()` throws `\JsonException` (not a `ResponseException`) if the data can't be encoded — that's a bug to fix (e.g. a resource or NAN in the payload), not something to catch and convert.

### Redirect that keeps a cookie

`redirect()` wipes headers and body to guarantee a clean redirect response — **set cookies before calling it**, not after, and don't expect any `Content-Type`/body set earlier to survive:

```php
$response->addCookie('remember_token', $token, strtotime('+30 days'));
$response->redirect('/dashboard'); // headers reset, but the Set-Cookie above is preserved
$response->emit();
```

### CORS preflight

`createFromServer()` already reads the `Origin`/`Access-Control-Request-*` headers into the response's CORS settings, but it does **not** call `cors()` for you — you decide when CORS headers actually get sent:

```php
if ($request->method() === 'OPTIONS') {
    $response->cors()->setStatusCode(Response::HTTP_NO_CONTENT)->emit();
    exit;
}

// on the real response too, if the API is meant to be cross-origin:
$response->cors()->setJson($data)->emit();
```

### Session flash message

```php
use Pebble\Http\Session;

$session = new Session(); // optionally pass a SessionHandlerInterface for custom storage
$session->start();        // call this on EVERY request — see gotchas

$session->setFlash('success', 'Saved!'); // readable on this request AND the next one, then auto-deleted

// ... redirect ...

// next request:
$session->start();
echo $session->get('success'); // 'Saved!' — gone after this request finishes
```

Use `setTemp($name, $value, $seconds)` instead of `setFlash()` when data should expire after a duration rather than after one extra page load (e.g. a one-time code).

### Streaming a file download

Prefer `setBodyFile()` over `setBody(file_get_contents(...))` for anything non-trivial in size — it streams from disk instead of loading the whole file into memory:

```php
$response
    ->setContentType('pdf')
    ->addHeader('Content-Disposition', 'attachment; filename="invoice.pdf"')
    ->setBodyFile('/var/app/storage/invoice.pdf')
    ->emit();
```

## Behavior to keep in mind while writing code

- **`Response` is a mutable builder, not an immutable PSR-7 message.** `setX()`/`addX()` mutate `$this` and return it — there's no `withX()` clone semantics despite the PSR-7 flavored body handling.
- **`redirect()` calls `reset()` internally**, clearing all headers and the body except cookies already added. Order matters: cookies first, then `redirect()`.
- **`emitHeaders()` silently no-ops if `headers_sent()` is already true.** No exception, no warning — if anything printed before `emit()` runs, your status code and headers are just dropped.
- **Automatic JSON body parsing needs an exact `Content-Type: application/json`.** `application/json; charset=utf-8` fails the check and falls back to query-string parsing, silently producing a wrong `bodyParams()`. Check `references/gotchas.md` if incoming JSON isn't showing up as expected.
- **`Session::start()` must run on every request**, not just once at bootstrap — it's what promotes/expires flash and temp session data each time it's called.
- **Use `Response::HTTP_*` constants** for status codes; `setStatusCode()` auto-fills the correct reason phrase from them, and falls back to `"Unknown Status"` for codes it doesn't recognize.

Read `references/gotchas.md` for the rest (proxy/HTTPS detection, IP address caveats, cookie `SameSite` validation, `setContentType()` key lookup, etc.) before assuming default PHP behavior applies.
