<?php

namespace Pebble\Http\Exceptions;

class SystemException extends ResponseException
{
    /**
     * @return integer
     */
    protected function status(): int
    {
        return 500;
    }
}
