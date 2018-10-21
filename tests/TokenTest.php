<?php

namespace Abraham\TwitterOAuth\Tests;

use Abraham\TwitterOAuth\Token;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    /**
     * @dataProvider tokenProvider
     */
    public function testToString($expected, $key, $secret)
    {
        $token = new Token($key, $secret);

        $this->assertEquals($expected, $token->__toString());
    }

    public function tokenProvider()
    {
        return [
            ['oauth_token=key&oauth_token_secret=secret', 'key', 'secret'],
            ['oauth_token=key%2Bkey&oauth_token_secret=secret', 'key+key', 'secret'],
            ['oauth_token=key~key&oauth_token_secret=secret', 'key~key', 'secret'],
        ];
    }
}
