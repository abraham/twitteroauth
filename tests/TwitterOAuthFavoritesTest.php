<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\MockHttpClient;

class TwitterOAuthFavoritesTest extends TestCase
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

    public function testPostFavoritesCreate()
    {
        $this->mockClient->useFixture('testPostFavoritesCreate');
        $result = $this->twitter->post('favorites/create', [
            'id' => '6242973112',
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }

    /**
     * @depends testPostFavoritesCreate
     */
    public function testPostFavoritesDestroy()
    {
        $this->mockClient->useFixture('testPostFavoritesDestroy');
        $this->twitter->post('favorites/destroy', ['id' => '6242973112']);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }
}
