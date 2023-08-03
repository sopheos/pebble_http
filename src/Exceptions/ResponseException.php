<?php

namespace Pebble\Http\Exceptions;

use Exception;
use JsonSerializable;

class ResponseException extends Exception implements JsonSerializable
{
    private array $errors = [];
    private array $extra = [];

    // -------------------------------------------------------------------------

    /**
     * @param string $error
     */
    public function __construct(string $error = 'default')
    {
        parent::__construct($error, $this->status());
    }

    /**
     * @param string $error
     * @return static
     */
    public static function create(string $error = 'default'): static
    {
        return new static($error);
    }

    // -------------------------------------------------------------------------
    // HTTP Status
    // -------------------------------------------------------------------------

    /**
     * @return integer
     */
    protected function status(): int
    {
        return 418;
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

    public function jsonSerialize(): mixed
    {
        return [
            'status' => $this->getCode(),
            'error' => $this->getMessage(),
            'errors' => $this->errors,
            'extra' => $this->extra,
        ];
    }

    // -------------------------------------------------------------------------
}
