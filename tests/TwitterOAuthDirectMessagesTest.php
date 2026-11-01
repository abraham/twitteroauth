<?php

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
    }

    public function testDirectMessageMedia()
    {
        $this->mockClient->useFixture('testDirectMessageMedia');
        $result = $this->twitter->directMessageMedia(
            'https://ton.x.com/i/ton/data/dm/1234567890/1234567891/abcdEFGH.jpg',
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
            ['https://ton.twitter.com/1.1/ton/data/dm/1/2/a.jpg'],
            ['http://ton.x.com/i/ton/data/dm/1/2/a.jpg'],
            ['https://example.com/i/ton/data/dm/1/2/a.jpg'],
            ['https://ton.x.com.example.com/a.jpg'],
            ['https://user@ton.x.com/a.jpg'],
            ['https://ton.x.com:8443/a.jpg'],
            ['/etc/passwd'],
        ];
    }
}
