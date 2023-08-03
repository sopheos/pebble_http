<?php

use Pebble\Http\Exceptions\AccessException;
use Pebble\Http\Exceptions\EmptyException;
use Pebble\Http\Exceptions\ForbiddenException;
use Pebble\Http\Exceptions\LockException;
use Pebble\Http\Exceptions\ResponseException;
use Pebble\Http\Exceptions\SystemException;
use Pebble\Http\Exceptions\UserException;
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testResponse()
    {
        $ex = ResponseException::create('default');

        self::assertIsObject($ex);
        self::assertInstanceOf(ResponseException::class, $ex);
        self::assertSame(418, $ex->getCode());
        self::assertSame('default', $ex->getMessage());

        $ex->setErrors(['name' => 'setErrorName', 'age' => 'setErrorAge']);
        $ex->addError('name', 'addErrorName');
        $ex->addError('city', 'addErrorCity');
        $ex->setExtra(['name' => 'setExtraName', 'age' => 'setExtraAge']);
        $ex->addExtra('name', 'addExtraName');
        $ex->addExtra('city', 'addExtraCity');

        $json = json_encode($ex->jsonSerialize());
        self::assertJson($json);
        $export = json_decode($json, true);

        self::assertIsArray($export);

        self::assertArrayHasKey('status', $export);
        self::assertSame(418, $export['status']);

        self::assertArrayHasKey('error', $export);
        self::assertSame('default', $export['error']);

        self::assertArrayHasKey('errors', $export);
        self::assertIsArray($export['errors']);
        self::assertArrayHasKey('name', $export['errors']);
        self::assertArrayHasKey('age', $export['errors']);
        self::assertArrayHasKey('city', $export['errors']);
        self::assertSame('addErrorName', $export['errors']['name']);
        self::assertSame('setErrorAge', $export['errors']['age']);
        self::assertSame('addErrorCity', $export['errors']['city']);

        self::assertArrayHasKey('extra', $export);
        self::assertIsArray($export['extra']);
        self::assertArrayHasKey('name', $export['extra']);
        self::assertArrayHasKey('age', $export['extra']);
        self::assertArrayHasKey('city', $export['extra']);
        self::assertSame('addExtraName', $export['extra']['name']);
        self::assertSame('setExtraAge', $export['extra']['age']);
        self::assertSame('addExtraCity', $export['extra']['city']);
    }

    public function testAccess()
    {
        $ex = AccessException::create();

        self::assertIsObject($ex);
        self::assertInstanceOf(AccessException::class, $ex);
        self::assertSame(401, $ex->getCode());
    }

    public function testEmpty()
    {
        $ex = EmptyException::create();

        self::assertIsObject($ex);
        self::assertInstanceOf(EmptyException::class, $ex);
        self::assertSame(404, $ex->getCode());
    }

    public function testForbidden()
    {
        $ex = ForbiddenException::create();

        self::assertIsObject($ex);
        self::assertInstanceOf(ForbiddenException::class, $ex);
        self::assertSame(403, $ex->getCode());
    }

    public function testLock()
    {
        $ex = LockException::create();

        self::assertIsObject($ex);
        self::assertInstanceOf(LockException::class, $ex);
        self::assertSame(423, $ex->getCode());
    }

    public function testSystem()
    {
        $ex = SystemException::create();

        self::assertIsObject($ex);
        self::assertInstanceOf(SystemException::class, $ex);
        self::assertSame(500, $ex->getCode());
    }

    public function testUser()
    {
        $ex = UserException::create();

        self::assertIsObject($ex);
        self::assertInstanceOf(UserException::class, $ex);
        self::assertSame(400, $ex->getCode());
    }
}
