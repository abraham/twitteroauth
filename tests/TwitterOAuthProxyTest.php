<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterOAuthProxyTest extends TestCase
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
     * @vcr testSetProxy.json
     */
    public function testSetProxy()
    {
        $this->twitter->setProxy([
            'CURLOPT_PROXY' => PROXY,
            'CURLOPT_PROXYUSERPWD' => PROXYUSERPWD,
            'CURLOPT_PROXYPORT' => PROXYPORT,
        ]);
        $this->twitter->setTimeouts(60, 60);
        $result = $this->twitter->get('account/verify_credentials');
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $this->assertObjectHasAttribute('id', $result);
    }
}
