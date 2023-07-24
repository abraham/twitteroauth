<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterOAuthTest extends TestCase
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
        $this->twitter->setApiVersion('1.1');
        $this->userId = explode('-', ACCESS_TOKEN)[0];
    }

    /**
     * @vcr testGetAccountVerifyCredentials.json
     */
    public function testGetAccountVerifyCredentials()
    {
        $user = $this->twitter->get('account/verify_credentials', [
            'include_entities' => false,
            'include_email' => true,
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $this->assertObjectHasAttribute('email', $user);
    }
}
