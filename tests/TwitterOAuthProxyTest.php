<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\MockHttpClient;

class TwitterOAuthProxyTest extends TestCase
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

    public function testSetProxy()
    {
        $this->mockClient->useFixture('testSetProxy');
        $this->twitter->setProxy([
            'CURLOPT_PROXY' => PROXY,
            'CURLOPT_PROXYUSERPWD' => PROXYUSERPWD,
            'CURLOPT_PROXYPORT' => PROXYPORT,
        ]);
        $this->twitter->setTimeouts(60, 60);
        $result = $this->twitter->get('account/verify_credentials');
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $this->assertObjectHasProperty('id', $result);
    }
}
