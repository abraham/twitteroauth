<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\TwitterOAuthException;
use Abraham\TwitterOAuth\MockHttpClient;

class TwitterOAuthTest extends TestCase
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
    }

    public function testGetUsersMe()
    {
        $this->mockClient->useFixture('testGetUsersMe');
        $user = $this->twitter->get('users/me', [
            'user.fields' => 'created_at',
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $this->assertObjectHasProperty('created_at', $user->data);
    }

    public function testApiVersion1Unsupported()
    {
        $this->expectException(TwitterOAuthException::class);
        $this->twitter->setApiVersion('1.1');
    }
}
