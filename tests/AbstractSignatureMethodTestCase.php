<?php

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Tests;

use Abraham\TwitterOAuth\Consumer;
use Abraham\TwitterOAuth\Request;
use Abraham\TwitterOAuth\SignatureMethod;
use Abraham\TwitterOAuth\Token;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

abstract class AbstractSignatureMethodTestCase extends TestCase
{
    protected $name;

    /**
     * @return SignatureMethod
     */
    abstract public function getClass();

    abstract public static function signatureDataProvider(): array;

    public function testGetName()
    {
        $this->assertEquals($this->name, $this->getClass()->getName());
    }

    #[DataProvider('signatureDataProvider')]
    public function testBuildSignature(
        string $expected,
        Consumer $consumer,
        ?Token $token,
    ) {
        $this->assertEquals(
            $expected,
            $this->getClass()->buildSignature(
                $this->getRequest(),
                $consumer,
                $token,
            ),
        );
    }

    public function testCheckSignature(): void
    {
        $method = $this->getClass();
        $request = $this->getRequest();
        $consumer = new Consumer('key', 'secret');
        $token = new Token('key', 'secret');
        $valid = $method->buildSignature($request, $consumer, $token);

        $this->assertTrue(
            $method->checkSignature($request, $consumer, $token, $valid),
        );
        $this->assertFalse(
            $method->checkSignature($request, $consumer, $token, 'invalid'),
        );
        $this->assertFalse(
            $method->checkSignature($request, $consumer, $token, ''),
        );
    }

    protected function getRequest()
    {
        return $this->createStub(Request::class);
    }
}
