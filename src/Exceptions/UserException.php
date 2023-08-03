<?php

namespace Pebble\Http\Exceptions;

class UserException extends ResponseException
{
    protected function status(): int
    {
        return 400;
    }
}
