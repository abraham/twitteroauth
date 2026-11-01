<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\MockHttpClient;

class TwitterOAuthMediaTest extends TestCase
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

    public function testUploadWithInvalidMediaThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $file_path = __DIR__ . '/12345678900987654321.jpg';
        $this->assertFalse(\is_readable($file_path));
        $this->twitter->upload('media/upload', [
            'media' => $file_path,
        ]);
    }

    public function testUploadRejectsSymlinkForNotChunked()
    {
        $target = __DIR__ . '/kitten.jpg';
        $link = __DIR__ . '/kitten-symlink.jpg';

        @unlink($link);
        if (!symlink($target, $link)) {
            $this->markTestSkipped('Unable to create symlink on this system.');
        }

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->twitter->upload('media/upload', ['media' => $link]);
        } finally {
            @unlink($link);
        }
    }

    public function testUploadRejectsSymlinkForChunked()
    {
        $target = __DIR__ . '/video.mp4';
        $link = __DIR__ . '/video-symlink.mp4';

        @unlink($link);
        if (!symlink($target, $link)) {
            $this->markTestSkipped('Unable to create symlink on this system.');
        }

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->twitter->upload(
                'media/upload',
                ['media' => $link, 'media_type' => 'video/mp4'],
                ['chunkedUpload' => true],
            );
        } finally {
            @unlink($link);
        }
    }
}
