<?php

namespace Pebble\Http\Exceptions;

use Exception;
use JsonSerializable;

class ResponseException extends Exception implements JsonSerializable
{
    private array $errors = [];
    private array $extra = [];

    // -------------------------------------------------------------------------

    public function __construct(string $error = 'default', int $status = 418)
    {
        parent::__construct($error, $status);
    }

    public static function create(string $error = 'default'): static
    {
        return new static($error);
    }

    public static function createWithStatus(int $status, string $error = 'default'): static
    {
        return new static($error, $status);
    }

    // -------------------------------------------------------------------------
    // HTTP Status
    // -------------------------------------------------------------------------

    /**
     * @return integer
     */
    protected function status(): int
    {
        return $this->getCode();
    }

    // -------------------------------------------------------------------------
    // Additionnal data
    // -------------------------------------------------------------------------

    /**
     * Set error details
     *
     * @param array $errors
     * @return static
     */
    public function setErrors(array $errors): static
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Add error detail
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addError(string $key, mixed $value): static
    {
        $this->errors[$key] = $value;
        return $this;
    }

    /**
     * Set extra data
     *
     * @param array $extra
     * @return static
     */
    public function setExtra(array $extra): static
    {
        $this->extra = $extra;
        return $this;
    }

    /**
     * Add extra value
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addExtra(string $key, mixed $value): static
    {
        $this->extra[$key] = $value;
        return $this;
    }

    // -------------------------------------------------------------------------

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'status' => $this->getCode(),
            'error' => $this->getMessage(),
            'errors' => $this->errors ?: null,
            'extra' => $this->extra ?: null,
        ];
    }

    // -------------------------------------------------------------------------
}
