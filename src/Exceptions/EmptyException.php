<?php

namespace Pebble\Http\Exceptions;

class EmptyException extends ResponseException
{
    public function __construct(string $error = 'default')
    {
        parent::__construct($error, 404);
    }
}
