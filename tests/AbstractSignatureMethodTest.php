<?php

namespace Abraham\TwitterOAuth\Tests;

use Abraham\TwitterOAuth\SignatureMethod;

abstract class AbstractSignatureMethodTest extends \PHPUnit_Framework_TestCase
{
    protected $name;

    /**
     * @return SignatureMethod
     */
    abstract public function getClass();

    abstract protected function signatureDataProvider();

    public function testGetName()
    {
        $this->assertEquals($this->name, $this->getClass()->getName());
    }

    /**
     * @dataProvider signatureDataProvider
     * @param mixed $expected
     * @param mixed $request
     * @param mixed $consumer
     * @param mixed $token
     */
    public function testBuildSignature($expected, $request, $consumer, $token)
    {
        $this->assertEquals($expected, $this->getClass()->buildSignature($request, $consumer, $token));
    }

    protected function getRequest()
    {
        return $this->getMockBuilder('Abraham\TwitterOAuth\Request')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getConsumer($key = null, $secret = null, $callbackUrl = null)
    {
        return $this->getMockBuilder('Abraham\TwitterOAuth\Consumer')
            ->setConstructorArgs([$key, $secret, $callbackUrl])
            ->getMock();
    }

    protected function getToken($key = null, $secret = null)
    {
        return $this->getMockBuilder('Abraham\TwitterOAuth\Token')
            ->setConstructorArgs([$key, $secret])
            ->getMock();
    }
}
