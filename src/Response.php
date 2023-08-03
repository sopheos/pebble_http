<?php

namespace Pebble\Http;

use Pebble\Http\Exceptions\ResponseException;
use Stringable;

class Response
{
    const HTTP_CONTINUE = 100;
    const HTTP_SWITCHING_PROTOCOLS = 101;
    const HTTP_PROCESSING = 102;                                      // RFC2518
    const HTTP_EARLY_HINTS = 103;                                     // RFC8297
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    const HTTP_NO_CONTENT = 204;
    const HTTP_RESET_CONTENT = 205;
    const HTTP_PARTIAL_CONTENT = 206;
    const HTTP_MULTI_STATUS = 207;                                    // RFC4918
    const HTTP_ALREADY_REPORTED = 208;                                // RFC5842
    const HTTP_IM_USED = 226;                                         // RFC3229
    const HTTP_MULTIPLE_CHOICES = 300;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_USE_PROXY = 305;
    const HTTP_RESERVED = 306;
    const HTTP_TEMPORARY_REDIRECT = 307;
    const HTTP_PERMANENTLY_REDIRECT = 308;                            // RFC7238
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    const HTTP_REQUEST_TIMEOUT = 408;
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_LENGTH_REQUIRED = 411;
    const HTTP_PRECONDITION_FAILED = 412;
    const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    const HTTP_REQUEST_URI_TOO_LONG = 414;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const HTTP_EXPECTATION_FAILED = 417;
    const HTTP_I_AM_A_TEAPOT = 418;                                   // RFC2324
    const HTTP_MISDIRECTED_REQUEST = 421;                             // RFC7540
    const HTTP_UNPROCESSABLE_ENTITY = 422;                            // RFC4918
    const HTTP_LOCKED = 423;                                          // RFC4918
    const HTTP_FAILED_DEPENDENCY = 424;                               // RFC4918
    const HTTP_TOO_EARLY = 425;                                       // RFC-ietf-httpbis-replay-04
    const HTTP_UPGRADE_REQUIRED = 426;                                // RFC2817
    const HTTP_PRECONDITION_REQUIRED = 428;                           // RFC6585
    const HTTP_TOO_MANY_REQUESTS = 429;                               // RFC6585
    const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                 // RFC6585
    const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_BAD_GATEWAY = 502;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    const HTTP_GATEWAY_TIMEOUT = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;
    const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;            // RFC2295
    const HTTP_INSUFFICIENT_STORAGE = 507;                            // RFC4918
    const HTTP_LOOP_DETECTED = 508;                                   // RFC5842
    const HTTP_NOT_EXTENDED = 510;                                    // RFC2774
    const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;                 // RFC6585

    use HttpStatusTrait;
    use MimesTypesTrait;

    protected array $settings = [];

    private string $version = '';
    private int $statusCode = 200;
    private string $statusReason = 'OK';
    private array $headers = [];
    private ?Stream $body = null;

    private int $buffer = 0;

    private array $cookieSettings = [
        'prefix' => '',
        'domain' => '',
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => ''
    ];

    private array $corsSettings = [
        'origin' => '*',
        'methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        'headers' => '*'
    ];


    /**
     * @param array $settings
     * @return static
     */
    public static function create(): static
    {
        return new static();
    }

    public static function createFromServer(): static
    {
        $res = static::create()
            ->setProtocolVersion(self::findProtocolVersion($_SERVER))
            ->setCookieSecure(self::isSecure($_SERVER))
            ->setCorsOrigin();

        if (isset($_SERVER["SERVER_PROTOCOL"])) {
            $res->setProtocolVersion($_SERVER["SERVER_PROTOCOL"]);
        }

        if (isset($_SERVER["HTTP_ORIGIN"])) {
            $res->setCorsOrigin($_SERVER["HTTP_ORIGIN"]);
        }

        if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"])) {
            $res->setCorsMethods($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_METHOD"]);
        }

        if (isset($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"])) {
            $res->setCorsHeaders($_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"]);
        }

        return $res;
    }

    private static function isSecure(array $server): bool
    {
        $https = $server["HTTPS"] ?? "";
        return !empty($https) && $https !== "off";
    }

    private static function findProtocolVersion(array $server): string
    {
        return $server["SERVER_PROTOCOL"] ?? "1.0";
    }

    // -------------------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------------------

    public function setBuffer(int $buffer): static
    {
        $this->buffer = $buffer;
        return $this;
    }

    public function setCookiePrefix(string $prefix = ''): static
    {
        $this->cookieSettings['prefix'] = $prefix;
        return $this;
    }

    public function setCookieDomain(string $domain = ''): static
    {
        $this->cookieSettings['domain'] = $domain;
        return $this;
    }

    public function setCookiePath(string $path = '/'): static
    {
        $this->cookieSettings['path'] = $path;
        return $this;
    }

    public function setCookieSecure(bool $secure = true): static
    {
        $this->cookieSettings['secure'] = $secure;
        return $this;
    }

    public function setCookieHttponly(bool $httponly = true): static
    {
        $this->cookieSettings['httponly'] = $httponly;
        return $this;
    }

    public function setCookieSamesite(string $samesite = ''): static
    {
        $this->cookieSettings['samesite'] = $samesite;
        return $this;
    }

    public function setCorsOrigin(string $origin = '*'): static
    {
        $this->corsSettings['origin'] = $origin;
        return $this;
    }

    public function setCorsMethods(string $methods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS'): static
    {
        $this->corsSettings['methods'] = $methods;
        return $this;
    }

    public function setCorsHeaders(string $headers = '*'): static
    {
        $this->corsSettings['headers'] = $headers;
        return $this;
    }

    // -------------------------------------------------------------------------
    // Reset headers & body
    // -------------------------------------------------------------------------

    /**
     * @return static
     */
    public function reset(): static
    {
        $this->headers = [];
        $this->body = null;

        return $this;
    }

    // -------------------------------------------------------------------------
    // Status
    // -------------------------------------------------------------------------

    /**
     * Returns HTTP protocole version
     *
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    /**
     * Sets HTTP protocole version
     *
     * @param string $version
     * @return $this
     */
    public function setProtocolVersion(string $version): static
    {
        if (mb_strpos($version, "HTTP/") === 0) {
            $version = mb_substr($version, 5);
        }

        $this->version = $version;

        return $this;
    }

    /**
     * Returns HTTP status
     *
     * @return integer
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Sets HTTP status
     *
     * @param integer $code
     * @param string|null $reason
     * @return $this
     */
    public function setStatusCode(int $code, ?string $reason = null): static
    {
        $this->statusCode = $code;
        $this->statusReason = $reason ?? self::$statusReasons[$code] ?? "Unknown Status";

        return $this;
    }

    // -------------------------------------------------------------------------
    // Headers
    // -------------------------------------------------------------------------

    /**
     * Returns HTTP headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Adds HTTP header
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    public function addHeader(string $name, string $value): static
    {
        $name = self::normalizeHeaderName($name);

        if (!isset($this->headers[$name])) {
            $this->headers[$name] = [];
        }

        $this->headers[$name][] = $value;

        return $this;
    }

    /**
     * Removes a header
     *
     * @param string $name
     * @return static
     */
    public function removeHeader(string $name): static
    {
        $name = self::normalizeHeaderName($name);

        if (isset($this->headers[$name])) {
            unset($this->headers[$name]);
        }

        return $this;
    }

    /**
     * Sets content-type
     *
     * @param string $mime
     * @return $this
     */
    public function setContentType(string $mime, string $charset = "UTF-8"): static
    {
        $mime = self::$mimesTypes[$mime][0] ?? $mime;

        if ($charset) {
            $this->addHeader("content-type", "{$mime}; charset={$charset}");
        } else {
            $this->addHeader("content-type", "{$mime}");
        }

        return $this;
    }

    /**
     * Creates a new cookie
     *
     * @param string $name
     * @param mixed $value
     * @param integer $expire
     * @return $this
     */
    public function addCookie(string $name, $value, int $expire = 0, $settings = []): static
    {
        $settings = $settings + $this->cookieSettings;

        $header = $name . '=' . urlencode($value);

        if (($domain = $settings['domain'] ?? null)) {
            $header .= '; domain=' . $domain;
        }

        if (($path = $settings['path'] ?? null)) {
            $header .= '; path=' . $path;
        }

        if ($expire) {
            $header .= '; expires=' . self::gmdate($expire);
        }

        if (($settings['secure'] ?? null)) {
            $header .= '; secure';
        }

        if (($settings['httponly'] ?? null)) {
            $header .= '; HttpOnly';
        }

        $samesite = $settings['samesite'] ?? null;
        if ($samesite && in_array(strtolower($samesite), ['lax', 'strict'], true)) {
            $header .= '; SameSite=' . $samesite;
        }

        return $this->addHeader('Set-cookie', $header);
    }

    /**
     * Removes a cookie
     *
     * @param string $name
     * @return $this
     */
    public function removeCookie(string $name): static
    {
        return $this->addCookie($name, "", strtotime("-1 day"));
    }

    /**
     * HTTP Redirect
     *
     * @param string $url
     * @param boolean $temporary
     * @return $this
     */
    public function redirect(string $url = "/", bool $temporary = true): static
    {
        $this->reset();
        $this->setStatusCode($temporary ? 302 : 301);
        $this->addHeader("location", filter_var($url, FILTER_SANITIZE_URL));

        return $this;
    }

    /**
     * Sets HTTP cache
     *
     * @param integer $age
     * @return $this
     */
    public function cache(int $age = 86400): static
    {
        $this->addHeader("pragma", "public");
        $this->addHeader("cache-control", "max-age=" . $age);
        $this->addHeader("expires", self::gmdate(time() + $age));

        return $this;
    }

    /**
     * Force HTTP no cache
     *
     * @return $this
     */
    public function noCache(): static
    {
        $this->addHeader("expires", "Mon, 26 Jul 1990 05:00:00 GMT");
        $this->addHeader("last-modified", "" . gmdate("D, d M Y H:i:s") . " GMT");
        $this->addHeader("cache-control", "no-store, no-cache, must-revalidate");
        $this->addHeader("cache-control", "post-check=0, pre-check=0", false);
        $this->addHeader("pragma", "no-cache");

        return $this;
    }

    /**
     * Enables CORS
     *
     * @param string|null $origin
     * @param string|null $method
     * @return $this
     */
    public function cors(?string $origin = null, ?string $methods = null, ?string $headers = null): static
    {
        $origin = $origin ?? $this->corsSettings['origin'];
        $methods = $methods ?? $this->corsSettings['methods'];
        $headers = $headers ?? $this->corsSettings['headers'];

        $this->addHeader("access-control-allow-origin", $origin);
        $this->addHeader("access-control-allow-credentials", "true");
        $this->addHeader("access-control-max-age", "86400");
        $this->addHeader("access-control-allow-methods", $methods);
        $this->addHeader("access-control-allow-headers", $headers);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Body
    // -------------------------------------------------------------------------

    /**
     * Returns body
     *
     * @return Stream
     */
    public function getBody(): Stream
    {
        if ($this->body === null) {
            $this->body = new Stream("");
        }

        return $this->body;
    }

    /**
     * Sets body
     *
     * @param mixed $body
     * @return object
     */
    public function setBody($body = ""): static
    {
        if ($body instanceof Stream) {
            $this->body = $body;
        } else {
            $this->body = new Stream($body);
        }

        return $this;
    }

    /**
     * Convert string into plain text output
     *
     * @param string $data
     * @return static
     */
    public function setText(string $data = ''): static
    {
        return $this->setContentType('txt')->setBody($data);
    }

    /**
     * Convert data into json output
     *
     * @param mixed $data
     * @return static
     */
    public function setJson(mixed $data = null): static
    {
        return $this->setContentType('json')->setBody(json_encode($data));
    }

    /**
     * Convert ResponseException into json output
     *
     * @param ResponseException $ex
     * @return static
     */
    public function setJsonException(ResponseException $ex): static
    {
        return $this->setStatusCode($ex->getCode())->setJson($ex);
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    /**
     * Sends headers & body
     *
     * @return void
     */
    public function emit(int $bufferLength = 0)
    {
        $this->emitHeaders();
        $this->emitBody($bufferLength);
    }

    /**
     * Sends headers
     *
     * @return $this
     */
    public function emitHeaders()
    {
        // Headers have already been sent by the developer
        if (headers_sent()) {
            return;
        }

        // Status line
        header("HTTP/{$this->version} {$this->statusCode} {$this->statusReason}", true, $this->statusCode);

        // Headers
        foreach ($this->headers as $name => $headers) {
            $firstReplace = ($name === 'Set-Cookie') ? false : true;
            foreach ($headers as $value) {
                header("{$name}: {$value}", $firstReplace);
                $firstReplace = false;
            }
        }
    }

    /**
     * Sends Content
     *
     * @return
     */
    public function emitBody(int $bufferLength = 0)
    {
        if ($bufferLength === null) {
            $bufferLength = $this->buffer;
        }

        if (!$bufferLength) {
            echo $this->getBody();
            return;
        }

        $body = $this->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (!$body->eof()) {
            echo $body->read($bufferLength);
        }
    }

    // -------------------------------------------------------------------------

    private static function normalizeHeaderName(string $header): string
    {
        $header = str_replace('-', ' ', $header);
        $header = strtolower($header);
        $header = ucwords($header);
        $header = str_replace(' ', '-', $header);

        return $header;
    }

    private static function gmdate(int $date)
    {
        return gmdate("D, d M Y H:i:s T", $date);
    }

    // -------------------------------------------------------------------------
}
