<?php

namespace Pebble\Http\Exceptions;

class EmptyException extends ResponseException
{
    protected function status(): int
    {
        return 404;
    }
}
