<?php

use GuzzleHttp\Psr7\HttpFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Stream as NyholmStream;
use Pebble\Http\Response;
use Pebble\Http\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class ResponseBodyTest extends TestCase
{
    public function testDefaultStreamFactoryIsNyholm()
    {
        $res = Response::create();

        self::assertInstanceOf(StreamFactoryInterface::class, $res->getStreamFactory());
        self::assertInstanceOf(Psr17Factory::class, $res->getStreamFactory());
    }

    public function testDefaultStreamFactoryIsMemoized()
    {
        $res = Response::create();

        self::assertSame($res->getStreamFactory(), $res->getStreamFactory());
    }

    public function testEmptyBody()
    {
        $res = Response::create();

        self::assertInstanceOf(StreamInterface::class, $res->getBody());
        self::assertSame('', (string) $res->getBody());
    }

    public function testSetBodyFromString()
    {
        $res = Response::create()->setBody('hello');

        self::assertInstanceOf(StreamInterface::class, $res->getBody());
        self::assertSame('hello', (string) $res->getBody());
    }

    public function testSetBodyFromResource()
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'from resource');
        rewind($handle);

        $res = Response::create()->setBody($handle);

        self::assertInstanceOf(StreamInterface::class, $res->getBody());
        self::assertSame('from resource', (string) $res->getBody());
    }

    public function testSetBodyFromStreamInterfaceIsKeptAsIs()
    {
        $stream = NyholmStream::create('raw');
        $res = Response::create()->setBody($stream);

        self::assertSame($stream, $res->getBody());
        self::assertSame('raw', (string) $res->getBody());
    }

    public function testSetBodyFromLegacyStreamDecorator()
    {
        $stream = Stream::create('legacy');

        self::assertInstanceOf(StreamInterface::class, $stream);
        self::assertSame('legacy', (string) $stream);

        $res = Response::create()->setBody($stream);

        self::assertSame($stream, $res->getBody());
        self::assertSame('legacy', (string) $res->getBody());
    }

    public function testSetBodyRejectsInvalidValue()
    {
        $this->expectException(InvalidArgumentException::class);

        Response::create()->setBody(['not', 'a', 'body']);
    }

    public function testSetJson()
    {
        $res = Response::create()->setJson(['ok' => true]);

        self::assertJson((string) $res->getBody());
        self::assertSame('{"ok":true}', (string) $res->getBody());
        self::assertSame(
            ['application/json; charset=UTF-8'],
            $res->getHeader('Content-Type')
        );
    }

    public function testSetJsonThrowsOnInvalidData()
    {
        $this->expectException(JsonException::class);

        Response::create()->setJson("\xB1\x31");
    }

    public function testContentTypeIsNotDuplicated()
    {
        $res = Response::create()->setText('hello')->setJson(['ok' => true]);

        self::assertCount(1, $res->getHeader('Content-Type'));
    }

    public function testSetBodyFile()
    {
        $res = Response::create()->setBodyFile(__FILE__);

        self::assertInstanceOf(StreamInterface::class, $res->getBody());
        self::assertSame(file_get_contents(__FILE__), (string) $res->getBody());
    }

    public function testCustomStreamFactory()
    {
        if (!class_exists(HttpFactory::class)) {
            self::markTestSkipped('guzzlehttp/psr7 is not installed.');
        }

        $factory = new HttpFactory();
        $res = Response::create()->setStreamFactory($factory)->setBody('hello');

        self::assertSame($factory, $res->getStreamFactory());
        self::assertInstanceOf(StreamInterface::class, $res->getBody());
        self::assertSame('hello', (string) $res->getBody());
    }

    public function testCustomStreamFactoryIsUsedForEmptyBody()
    {
        $stream = NyholmStream::create('injected');

        $factory = $this->createMock(StreamFactoryInterface::class);
        $factory->expects(self::once())
            ->method('createStream')
            ->with('')
            ->willReturn($stream);

        $res = Response::create()->setStreamFactory($factory);

        self::assertSame($stream, $res->getBody());
        self::assertSame($stream, $res->getBody());
    }

    public function testResetKeepsStreamFactory()
    {
        $factory = new Psr17Factory();
        $res = Response::create()->setStreamFactory($factory)->setBody('hello');

        $res->reset();

        self::assertSame($factory, $res->getStreamFactory());
        self::assertSame('', (string) $res->getBody());
    }

    public function testEmitBody()
    {
        $res = Response::create()->setBody('emitted');

        ob_start();
        $res->emitBody();
        $output = ob_get_clean();

        self::assertSame('emitted', $output);
    }

    public function testEmitBodyWithBuffer()
    {
        $res = Response::create()->setBodyFile(__FILE__)->setBuffer(8192);

        ob_start();
        $res->emitBody();
        $output = ob_get_clean();

        self::assertSame(file_get_contents(__FILE__), $output);
    }

    public function testEmitBodyRewindsSeekableStream()
    {
        $res = Response::create()->setBody('rewound');
        $res->getBody()->getContents();

        ob_start();
        $res->emitBody();
        $output = ob_get_clean();

        self::assertSame('rewound', $output);
    }
}
