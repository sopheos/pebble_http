<?php

namespace Pebble\Http\Exceptions;

class AccessException extends ResponseException
{
    public function __construct(string $error = 'default')
    {
        parent::__construct($error, 401);
    }
}
