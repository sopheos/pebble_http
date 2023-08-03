<?php

namespace Pebble\Http\Exceptions;

class ForbiddenException extends ResponseException
{
    protected function status(): int
    {
        return 403;
    }
}
