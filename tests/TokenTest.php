<?php

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\Token;

class TokenTest extends TestCase
{
    #[DataProvider('tokenProvider')]
    public function testToString($expected, $key, $secret)
    {
        $token = new Token($key, $secret);

        $this->assertEquals($expected, $token->__toString());
    }

    public static function tokenProvider(): array
    {
        return [
            ['oauth_token=key&oauth_token_secret=secret', 'key', 'secret'],
            [
                'oauth_token=key%2Bkey&oauth_token_secret=secret',
                'key+key',
                'secret',
            ],
            [
                'oauth_token=key~key&oauth_token_secret=secret',
                'key~key',
                'secret',
            ],
        ];
    }
}
