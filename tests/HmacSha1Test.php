<?php

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Tests;

use Abraham\TwitterOAuth\Consumer;
use Abraham\TwitterOAuth\HmacSha1;
use Abraham\TwitterOAuth\Token;

class HmacSha1Test extends AbstractSignatureMethodTestCase
{
    protected $name = 'HMAC-SHA1';

    public function getClass()
    {
        return new HmacSha1();
    }

    public static function signatureDataProvider(): array
    {
        return [
            [
                '5CoEcoq7XoKFjwYCieQvuzadeUA=',
                new Consumer(null, null),
                new Token(null, null),
            ],
            [
                'EBw0gHngam3BTx8kfPfNNSyKem4=',
                new Consumer('key', 'secret'),
                new Token(null, null),
            ],
            [
                'kDsHFZzws2a5M6cAQjfpdNBo+v8=',
                new Consumer('key', 'secret'),
                new Token('key', 'secret'),
            ],
            [
                'EBw0gHngam3BTx8kfPfNNSyKem4=',
                new Consumer('key', 'secret'),
                null,
            ],
        ];
    }
}
