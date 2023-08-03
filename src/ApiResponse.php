<?php

namespace Pebble\Http;

class ApiResponse
{
    private $status;
    private $headers;
    private $body;

    /**
     * @param integer $status
     * @param array $headers
     * @param string $body
     */
    public function __construct(int $status, array $headers, string $body)
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = trim($body);
    }

    /**
     * @return integer
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function header(string $name): ?string
    {
        if (!isset($this->headers[$name][0])) return null;
        return mb_strtolower($this->headers[$name][0]);
    }

    /**
     * @return string
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * @param boolean $assoc
     * @return array|object
     */
    public function json(bool $assoc = false)
    {
        if ($this->body) {
            $type = $this->header('Content-Type');
            if ($type && mb_strpos($type, 'application/json') !== false) {
                return json_decode($this->body, $assoc);
            }
        }

        return $assoc ? [] : new \stdClass();
    }
}
