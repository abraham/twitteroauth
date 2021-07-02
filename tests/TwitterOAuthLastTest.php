<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterOAuthLastTest extends TestCase
{
    /** @var TwitterOAuth */
    protected $twitter;

    protected function setUp(): void
    {
        $this->twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            ACCESS_TOKEN,
            ACCESS_TOKEN_SECRET,
        );
        $this->userId = explode('-', ACCESS_TOKEN)[0];
    }

    /**
     * @vcr testLastResult.json
     */
    public function testLastResult()
    {
        $this->twitter->get('search/tweets', ['q' => 'twitter']);
        $this->assertEquals('search/tweets', $this->twitter->getLastApiPath());
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $this->assertObjectHasAttribute(
            'statuses',
            $this->twitter->getLastBody(),
        );
    }

    /**
     * @depends testLastResult
     * @vcr testResetLastResponse.json
     */
    public function testResetLastResponse()
    {
        $this->twitter->resetLastResponse();
        $this->assertEquals('', $this->twitter->getLastApiPath());
        $this->assertEquals(0, $this->twitter->getLastHttpCode());
        $this->assertEquals([], $this->twitter->getLastBody());
    }
}
