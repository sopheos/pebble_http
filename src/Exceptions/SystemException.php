<?php

namespace Pebble\Http\Exceptions;

use Pebble\Http\Response;

class SystemException extends ResponseException
{
    public function __construct(string $error = 'default')
    {
        parent::__construct($error, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
