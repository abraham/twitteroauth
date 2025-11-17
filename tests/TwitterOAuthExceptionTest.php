<?php

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuthException;

class TwitterOAuthExceptionTest extends TestCase
{
    public function testParseMessageWithValidJson()
    {
        $jsonString = '{"error":"rate limit exceeded","code":88}';
        $result = new TwitterOAuthException($jsonString);

        $this->assertIsObject($result->parsedMessage());
        $this->assertEquals(
            'rate limit exceeded',
            $result->parsedMessage()->error,
        );
        $this->assertEquals(88, $result->parsedMessage()->code);
    }

    public function testParseMessageWithInvalidJson()
    {
        $plainString = 'This is not JSON';
        $result = new TwitterOAuthException($plainString);

        $this->assertIsString($result->parsedMessage());
        $this->assertEquals('This is not JSON', $result->parsedMessage());
    }

    public function testParseMessageWithEmptyString()
    {
        $emptyString = '';
        $result = new TwitterOAuthException($emptyString);

        $this->assertIsString($result->parsedMessage());
        $this->assertEquals('', $result->parsedMessage());
    }
}
