<?php

namespace Pebble\Http\Exceptions;

class SystemException extends ResponseException
{
    public function __construct(string $error = 'default')
    {
        parent::__construct($error, 500);
    }
}
