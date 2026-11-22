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

    protected function getRequest()
    {
        return $this->createStub(Request::class);
    }
}
