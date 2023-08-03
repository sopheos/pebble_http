<?php

namespace Pebble\Http\Exceptions;

class LockException extends ResponseException
{
    protected function status(): int
    {
        return 423;
    }
}
