<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\MockHttpClient;

class TwitterOAuthStatusesTest extends TestCase
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

    public function testGetStatusesMentionsTimeline()
    {
        $this->mockClient->useFixture('testGetStatusesMentionsTimeline');
        $this->twitter->get('statuses/mentions_timeline');
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }

    public function testGetSearchTweets()
    {
        $this->mockClient->useFixture('testGetSearchTweets');
        $result = $this->twitter->get('search/tweets', ['q' => 'twitter']);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        return $result->statuses;
    }

    /**
     * @depends testGetSearchTweets
     */
    public function testGetSearchTweetsWithMaxId($statuses)
    {
        $this->mockClient->useFixture('testGetSearchTweetsWithMaxId');
        $maxId = array_pop($statuses)->id_str;
        $this->twitter->get('search/tweets', [
            'q' => 'twitter',
            'max_id' => $maxId,
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }

    public function testPostStatusesUpdateUtf8()
    {
        $this->mockClient->useFixture('testPostStatusesUpdateUtf8');
        $result = $this->twitter->post('statuses/update', [
            'status' => 'xこんにちは世界 ' . MOCK_TIME,
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        return $result;
    }

    /**
     * @depends testPostStatusesUpdateUtf8
     */
    public function testPostStatusesDestroy($status)
    {
        $this->mockClient->useFixture('testPostStatusesDestroy');
        $this->twitter->post('statuses/destroy/' . $status->id_str);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }
}
