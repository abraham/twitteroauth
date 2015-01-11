<?php

namespace Abraham\TwitterOAuth\Tests;

use Abraham\TwitterOAuth\Plaintext;

class PlainTextTest extends AbstractSignatureMethodTest
{
    protected $name = 'PLAINTEXT';

    public function getClass()
    {
        return new Plaintext();
    }

    public function signatureDataProvider()
    {
        return array(
            array('&', $this->getRequest(), $this->getConsumer(), $this->getToken()),
            array('secret&', $this->getRequest(), $this->getConsumer('key', 'secret'), null),
            array('secret&', $this->getRequest(), $this->getConsumer('key', 'secret'), $this->getToken()),
            array('secret&secret', $this->getRequest(), $this->getConsumer('key', 'secret'), $this->getToken('key', 'secret')),
        );
    }

}