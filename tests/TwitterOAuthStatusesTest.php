<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterOAuthStatusesTest extends TestCase
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
     * @vcr testGetStatusesMentionsTimeline.json
     */
    public function testGetStatusesMentionsTimeline()
    {
        $this->twitter->get('statuses/mentions_timeline');
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }

    /**
     * @vcr testGetSearchTweets.json
     */
    public function testGetSearchTweets()
    {
        $result = $this->twitter->get('search/tweets', ['q' => 'twitter']);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        return $result->statuses;
    }

    /**
     * @depends testGetSearchTweets
     * @vcr testGetSearchTweetsWithMaxId.json
     */
    public function testGetSearchTweetsWithMaxId($statuses)
    {
        $maxId = array_pop($statuses)->id_str;
        $this->twitter->get('search/tweets', [
            'q' => 'twitter',
            'max_id' => $maxId,
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }

    /**
     * @vcr testPostStatusesUpdateUtf8.json
     */
    public function testPostStatusesUpdateUtf8()
    {
        $result = $this->twitter->post('statuses/update', [
            'status' => 'xこんにちは世界 ' . MOCK_TIME,
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        return $result;
    }

    /**
     * @depends testPostStatusesUpdateUtf8
     * @vcr testPostStatusesDestroy.json
     */
    public function testPostStatusesDestroy($status)
    {
        $this->twitter->post('statuses/destroy/' . $status->id_str);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }
}
