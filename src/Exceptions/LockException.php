<?php

namespace Pebble\Http\Exceptions;

class LockException extends ResponseException
{
    public function __construct(string $error = 'default')
    {
        parent::__construct($error, 423);
    }
}
