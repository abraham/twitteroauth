<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\MockHttpClient;

class TwitterOAuthLastTest extends TestCase
{
    /** @var TwitterOAuth */
    protected $twitter;
    /** @var MockHttpClient */
    protected $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = new MockHttpClient();
        $this->twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            ACCESS_TOKEN,
            ACCESS_TOKEN_SECRET,
            $this->mockClient,
        );
        $this->twitter->setApiVersion('1.1');
        $this->userId = explode('-', ACCESS_TOKEN)[0];
    }

    public function testLastResult()
    {
        $this->mockClient->useFixture('testLastResult');
        $this->twitter->get('search/tweets', ['q' => 'twitter']);
        $this->assertEquals('search/tweets', $this->twitter->getLastApiPath());
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $this->assertObjectHasProperty(
            'statuses',
            $this->twitter->getLastBody(),
        );
    }

    /**
     * @depends testLastResult
     */
    public function testResetLastResponse()
    {
        $this->mockClient->useFixture('testResetLastResponse');
        $this->twitter->resetLastResponse();
        $this->assertEquals('', $this->twitter->getLastApiPath());
        $this->assertEquals(0, $this->twitter->getLastHttpCode());
        $this->assertEquals([], $this->twitter->getLastBody());
    }
}
