<?php

namespace Pebble\Http\Exceptions;

class ForbiddenException extends ResponseException
{
    public function __construct(string $error = 'default')
    {
        parent::__construct($error, 403);
    }
}
