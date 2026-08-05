<?php

namespace Pebble\Http;

use Nyholm\Psr7\Stream as NyholmStream;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 stream decorator.
 *
 * Kept for backward compatibility: internally delegates to nyholm/psr7.
 * New code should type-hint Psr\Http\Message\StreamInterface and build
 * streams through Response::getStreamFactory().
 *
 * @deprecated 2.0 Use a PSR-17 StreamFactoryInterface instead.
 */
class Stream implements StreamInterface
{
    private StreamInterface $stream;

    /**
     * @param StreamInterface|resource|string|null $body
     */
    public function __construct($body = '')
    {
        if ($body instanceof StreamInterface) {
            $this->stream = $body;
        } elseif (is_resource($body)) {
            $this->stream = NyholmStream::create($body);
        } else {
            $this->stream = NyholmStream::create((string) $body);
        }
    }

    /**
     * @param StreamInterface|resource|string|null $body
     */
    public static function create($body = ''): static
    {
        return new static($body);
    }

    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    public function __toString(): string
    {
        return $this->stream->__toString();
    }

    public function close(): void
    {
        $this->stream->close();
    }

    public function detach()
    {
        return $this->stream->detach();
    }

    public function getSize(): ?int
    {
        return $this->stream->getSize();
    }

    public function tell(): int
    {
        return $this->stream->tell();
    }

    public function eof(): bool
    {
        return $this->stream->eof();
    }

    public function isSeekable(): bool
    {
        return $this->stream->isSeekable();
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->stream->seek($offset, $whence);
    }

    public function rewind(): void
    {
        $this->stream->rewind();
    }

    public function isWritable(): bool
    {
        return $this->stream->isWritable();
    }

    public function write(string $string): int
    {
        return $this->stream->write($string);
    }

    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    public function read(int $length): string
    {
        return $this->stream->read($length);
    }

    public function getContents(): string
    {
        return $this->stream->getContents();
    }

    public function getMetadata(?string $key = null)
    {
        return $this->stream->getMetadata($key);
    }
}
