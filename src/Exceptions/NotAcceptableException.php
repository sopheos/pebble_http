<?php

namespace Pebble\Http\Exceptions;

use Pebble\Http\Response;

class NotAcceptableException extends ResponseException
{
    public function __construct(string $error = 'default')
    {
        parent::__construct($error, Response::HTTP_NOT_ACCEPTABLE);
    }
}
