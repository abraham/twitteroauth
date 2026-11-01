<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\MockHttpClient;

class TwitterOAuthDirectMessagesTest extends TestCase
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

    public function testPostDirectMessagesEventsNew()
    {
        $this->mockClient->useFixture('testPostDirectMessagesEventsNew');
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
        $result = $this->twitter->post('direct_messages/events/new', $data, [
            'jsonPayload' => true,
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        return $result;
    }

    /**
     * @depends testPostDirectMessagesEventsNew
     */
    public function testDeleteDirectMessagesEventsDestroy($message)
    {
        $this->mockClient->useFixture('testDeleteDirectMessagesEventsDestroy');
        $this->twitter->delete('direct_messages/events/destroy', [
            'id' => $message->event->id,
        ]);
        $this->assertEquals(204, $this->twitter->getLastHttpCode());
    }

    public function testDirectMessageMedia()
    {
        $this->mockClient->useFixture('testDirectMessageMedia');
        $result = $this->twitter->directMessageMedia(
            'https://ton.twitter.com/1.1/ton/data/dm/1234567890/1234567891/abcdEFGH.jpg',
        );
        $this->assertSame('fake-jpeg-bytes', $result);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }

    /**
     * @dataProvider invalidDirectMessageMediaUrls
     */
    public function testDirectMessageMediaRejectsOtherUrls(string $url)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->twitter->directMessageMedia($url);
    }

    public static function invalidDirectMessageMediaUrls(): array
    {
        return [
            ['http://ton.twitter.com/1.1/ton/data/dm/1/2/a.jpg'],
            ['https://example.com/1.1/ton/data/dm/1/2/a.jpg'],
            ['https://ton.twitter.com.example.com/a.jpg'],
            ['https://user@ton.twitter.com/a.jpg'],
            ['https://ton.twitter.com:8443/a.jpg'],
            ['/etc/passwd'],
        ];
    }
}
