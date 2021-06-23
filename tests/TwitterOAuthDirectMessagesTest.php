<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterOAuthDirectMessagesTest extends TestCase
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
     * @vcr testPostDirectMessagesEventsNew.json
     */
    public function testPostDirectMessagesEventsNew()
    {
        $data = [
            'event' => [
                'type' => 'message_create',
                'message_create' => [
                    'target' => [
                        'recipient_id' => $this->userId,
                    ],
                    'message_data' => [
                        'text' => 'Hello World!',
                    ],
                ],
            ],
        ];
        $result = $this->twitter->post(
            'direct_messages/events/new',
            $data,
            true,
        );
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        return $result;
    }

    /**
     * @depends testPostDirectMessagesEventsNew
     * @vcr testDeleteDirectMessagesEventsDestroy.json
     */
    public function testDeleteDirectMessagesEventsDestroy($message)
    {
        $this->twitter->delete('direct_messages/events/destroy', [
            'id' => $message->event->id,
        ]);
        $this->assertEquals(204, $this->twitter->getLastHttpCode());
    }
}
