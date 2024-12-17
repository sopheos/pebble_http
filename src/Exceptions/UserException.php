<?php

namespace Pebble\Http\Exceptions;

class UserException extends ResponseException
{
    public function __construct(string $error = 'default')
    {
        parent::__construct($error, 400);
    }
}
