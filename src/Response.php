<?php

namespace Pebble\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Pebble\Http\Exceptions\ResponseException;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class Response
{
    use HttpStatusTrait;
    use MimesTypesTrait;

    protected array $settings = [];

    private string $version = '1.1';
    private int $statusCode = 200;
    private string $statusReason = 'OK';
    private array $headers = [];
    private ?StreamInterface $body = null;

    private ?StreamFactoryInterface $streamFactory = null;
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
        return $server["SERVER_PROTOCOL"] ?? "1.1";
    }

    // -------------------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------------------

    /**
     * Sets the PSR-17 stream factory used to build bodies
     *
     * @param StreamFactoryInterface $factory
     * @return static
     */
    public function setStreamFactory(StreamFactoryInterface $factory): static
    {
        $this->streamFactory = $factory;
        return $this;
    }

    /**
     * Returns the stream factory, defaults to nyholm/psr7
     *
     * @return StreamFactoryInterface
     */
    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory ??= new Psr17Factory();
    }

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
     * Resets headers and body.
     * Configuration (stream factory, buffer, cookie/cors settings) is kept.
     *
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
     * Returns values for a given header
     *
     * @param string $header
     * @return string[]
     */
    public function getHeader(string $header): array
    {
        return $this->headers[self::normalizeHeaderName($header)] ?? [];
    }

    /**
     * Checks if a header is set
     *
     * @param string $header
     * @return bool
     */
    public function hasHeader(string $header): bool
    {
        return $this->getHeader($header) !== [];
    }

    /**
     * Adds HTTP header
     */
    public function addHeader(string $name, string $value, bool $replace = false): static
    {
        $name = self::normalizeHeaderName($name);

        if ($replace) {
            $this->removeHeader($name);
        }

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
        $mime  = self::$mimesTypes[$mime][0] ?? $mime;
        $mime = $charset ? "{$mime}; charset={$charset}" : $mime;
        return $this->addHeader("Content-Type", $mime, true);
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

        return $this->addHeader('Set-Cookie', $header);
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
        $headers = $this->getHeaders();
        $cookies = $headers['Set-Cookie'] ?? [];

        $this->reset();
        $this->setStatusCode($temporary ? 302 : 301);
        $this->addHeader("Location", filter_var($url, FILTER_SANITIZE_URL));
        foreach ($cookies as $cookie) {
            $this->addHeader('Set-Cookie', $cookie);
        }

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
        $this->addHeader("Pragma", "public");
        $this->addHeader("Cache-Control", "max-age=" . $age);
        $this->addHeader("Expires", self::gmdate(time() + $age));

        return $this;
    }

    /**
     * Force HTTP no cache
     *
     * @return $this
     */
    public function noCache(): static
    {
        $this->addHeader("Expires", "Mon, 26 Jul 1990 05:00:00 GMT");
        $this->addHeader("Last-Modified", "" . gmdate("D, d M Y H:i:s") . " GMT");
        $this->addHeader("Cache-Control", "no-store, no-cache, must-revalidate");
        $this->addHeader("Cache-Control", "post-check=0, pre-check=0");
        $this->addHeader("Pragma", "no-cache");

        return $this;
    }

    /**
     * Enables CORS
     *
     * @param string|null $origin
     * @param string|null $methods
     * @return $this
     */
    public function cors(?string $origin = null, ?string $methods = null, ?string $headers = null): static
    {
        $origin = $origin ?? $this->corsSettings['origin'];
        $methods = $methods ?? $this->corsSettings['methods'];
        $headers = $headers ?? $this->corsSettings['headers'];

        $this->addHeader("Access-Control-Allow-Origin", $origin);
        $this->addHeader("Access-Control-Allow-Credentials", "true");
        $this->addHeader("Access-Control-Max-Age", "86400");
        $this->addHeader("Access-Control-Allow-Methods", $methods);
        $this->addHeader("Access-Control-Allow-Headers", $headers);

        return $this;
    }

    // -------------------------------------------------------------------------
    // Body
    // -------------------------------------------------------------------------

    /**
     * Returns body
     *
     * @return StreamInterface
     */
    public function getBody(): StreamInterface
    {
        if ($this->body === null) {
            $this->body = $this->getStreamFactory()->createStream('');
        }

        return $this->body;
    }

    /**
     * Sets body
     *
     * @param StreamInterface|resource|string|null $body
     * @return static
     */
    public function setBody($body = ""): static
    {
        if ($body instanceof StreamInterface) {
            $this->body = $body;
        } elseif (is_resource($body)) {
            $this->body = $this->getStreamFactory()->createStreamFromResource($body);
        } elseif ($body === null || is_scalar($body) || $body instanceof \Stringable) {
            $this->body = $this->getStreamFactory()->createStream((string) $body);
        } else {
            throw new \InvalidArgumentException(
                'Body must be a StreamInterface, resource, string or Stringable.'
            );
        }

        return $this;
    }

    /**
     * Sets body from a file, streamed without loading it in memory
     *
     * @param string $filename
     * @param string $mode
     * @return static
     */
    public function setBodyFile(string $filename, string $mode = 'r'): static
    {
        $this->body = $this->getStreamFactory()->createStreamFromFile($filename, $mode);
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
     * @param int $flags
     * @return static
     * @throws \JsonException
     */
    public function setJson(mixed $data = null, int $flags = 0): static
    {
        return $this
            ->setContentType('json')
            ->setBody(json_encode($data, $flags | JSON_THROW_ON_ERROR));
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
    public function emit(?int $bufferLength = null): void
    {
        $this->emitHeaders();
        $this->emitBody($bufferLength);
    }

    /**
     * Sends headers
     */
    public function emitHeaders(): void
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
     * @return void
     */
    public function emitBody(?int $bufferLength = null): void
    {
        $bufferLength = $bufferLength ?? $this->buffer;

        $body = $this->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        if ($bufferLength <= 0) {
            echo $body->getContents();
            return;
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
