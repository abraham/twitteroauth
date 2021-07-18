<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterOAuthV2Test extends TestCase
{
    /** @var TwitterOAuth */
    protected $twitter;

    protected function setUp(): void
    {
        $this->markTestSkipped('Fixtures need to be updated');
        $this->twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            ACCESS_TOKEN,
            ACCESS_TOKEN_SECRET,
        );
        $this->userId = explode('-', ACCESS_TOKEN)[0];
        $this->twitter->setApiVersion('2');
    }

    /**
     * @vcr testV2GetUsers.json
     */
    public function testV2GetUsers()
    {
        $this->twitter->get('users', ['ids' => 12]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }
}
