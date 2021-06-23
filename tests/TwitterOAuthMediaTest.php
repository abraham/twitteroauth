<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterOAuthMediaTest extends TestCase
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
     * @vcr testPostStatusesUpdateWithMedia.json
     */
    public function testPostStatusesUpdateWithMedia()
    {
        $this->twitter->setTimeouts(60, 60);
        // Image source https://www.flickr.com/photos/titrans/8548825587/
        $file_path = __DIR__ . '/kitten.jpg';
        $result = $this->twitter->upload('media/upload', [
            'media' => $file_path,
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $this->assertObjectHasAttribute('media_id_string', $result);
        $parameters = [
            'status' => 'Hello World ' . MOCK_TIME,
            'media_ids' => $result->media_id_string,
        ];
        $result = $this->twitter->post('statuses/update', $parameters);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $result = $this->twitter->post('statuses/destroy/' . $result->id_str);
        return $result;
    }

    /**
     * @vcr testPostStatusUpdateWithInvalidMediaThrowsException.json
     */
    public function testPostStatusUpdateWithInvalidMediaThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $file_path = __DIR__ . '/12345678900987654321.jpg';
        $this->assertFalse(\is_readable($file_path));
        $result = $this->twitter->upload('media/upload', [
            'media' => $file_path,
        ]);
    }

    /**
     * @vcr testPostStatusesUpdateWithMediaChunked.json
     */
    public function testPostStatusesUpdateWithMediaChunked()
    {
        $this->twitter->setTimeouts(60, 30);
        // Video source http://www.sample-videos.com/
        $file_path = __DIR__ . '/video.mp4';
        $result = $this->twitter->upload(
            'media/upload',
            ['media' => $file_path, 'media_type' => 'video/mp4'],
            true,
        );
        $this->assertEquals(201, $this->twitter->getLastHttpCode());
        $this->assertObjectHasAttribute('media_id_string', $result);
        $parameters = [
            'status' => 'Hello World ' . MOCK_TIME,
            'media_ids' => $result->media_id_string,
        ];
        $result = $this->twitter->post('statuses/update', $parameters);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $result = $this->twitter->post('statuses/destroy/' . $result->id_str);
        return $result;
    }
}
