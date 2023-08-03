<?php

namespace Pebble\Http\Exceptions;

class AccessException extends ResponseException
{
    protected function status(): int
    {
        return 401;
    }
}
