<?php

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\HttpClientInterface;
use Abraham\TwitterOAuth\TwitterOAuthException;

class TwitterOAuthMediaV2Test extends TestCase
{
    private TwitterOAuth $twitter;
    private HttpClientInterface $client;

    protected function setUp(): void
    {
        $this->client = new class implements HttpClientInterface {
            public array $requests = [];
            public string $responseBody = '{"data":{}}';

            public function request(
                string $url,
                string $method,
                array $headers,
                string $body,
                array $curlOptions,
            ): array {
                $this->requests[] = compact('url', 'method', 'headers', 'body');
                return [
                    'body' => $this->responseBody,
                    'headers' => [],
                    'httpCode' => 200,
                ];
            }
        };
        $this->twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            ACCESS_TOKEN,
            ACCESS_TOKEN_SECRET,
            $this->client,
        );
    }

    public function testUpload()
    {
        $file = __DIR__ . '/kitten.jpg';
        $this->twitter->upload('media/upload', [
            'media' => $file,
            'media_category' => 'tweet_image',
        ]);
        $request = $this->client->requests[0];
        $this->assertSame('POST', $request['method']);
        $this->assertSame('https://api.x.com/2/media/upload', $request['url']);
        $this->assertContains(
            'Content-type: application/json',
            $request['headers'],
        );
        $this->assertSame(
            [
                'media' => base64_encode(file_get_contents($file)),
                'media_category' => 'tweet_image',
            ],
            json_decode($request['body'], true),
        );
    }

    public function testUploadChunked()
    {
        $file = __DIR__ . '/video.mp4';
        $chunkSize = (int) ceil(filesize($file) / 2);
        $this->twitter->setChunkSize($chunkSize);
        $this->client->responseBody = '{"data":{"id":"123"}}';
        $this->twitter->upload(
            'media/upload',
            [
                'media' => $file,
                'media_type' => 'video/mp4',
                'media_category' => 'tweet_video',
            ],
            ['chunkedUpload' => true],
        );
        $requests = $this->client->requests;
        $this->assertSame(
            [
                'https://api.x.com/2/media/upload/initialize',
                'https://api.x.com/2/media/upload/123/append',
                'https://api.x.com/2/media/upload/123/append',
                'https://api.x.com/2/media/upload/123/finalize',
            ],
            array_column($requests, 'url'),
        );
        $this->assertSame(
            [
                'total_bytes' => filesize($file),
                'media_type' => 'video/mp4',
                'media_category' => 'tweet_video',
            ],
            json_decode($requests[0]['body'], true),
        );
        $contents = file_get_contents($file);
        foreach ([1, 2] as $segmentIndex => $i) {
            $this->assertSame(
                [
                    'segment_index' => $segmentIndex,
                    'media' => base64_encode(
                        substr(
                            $contents,
                            $segmentIndex * $chunkSize,
                            $chunkSize,
                        ),
                    ),
                ],
                json_decode($requests[$i]['body'], true),
            );
        }
        $this->assertSame('', $requests[3]['body']);
    }

    public function testUploadChunkedThrowsWithoutMediaId()
    {
        $this->client->responseBody =
            '{"errors":[{"title":"Invalid Request"}]}';
        $this->expectException(TwitterOAuthException::class);
        $this->expectExceptionMessage('Invalid Request');
        $this->twitter->upload(
            'media/upload',
            ['media' => __DIR__ . '/video.mp4', 'media_type' => 'video/mp4'],
            ['chunkedUpload' => true],
        );
    }

    public function testMediaStatus()
    {
        $this->twitter->mediaStatus('123');
        $this->assertSame(
            'https://api.x.com/2/media/upload?command=STATUS&media_id=123',
            $this->client->requests[0]['url'],
        );
    }

    public function testCreateSubtitles()
    {
        $parameters = [
            'id' => '1',
            'media_category' => 'TweetVideo',
            'subtitles' => [
                'id' => '2',
                'language_code' => 'EN',
                'display_name' => 'English',
            ],
        ];
        $this->twitter->post('media/subtitles', $parameters);
        $request = $this->client->requests[0];
        $this->assertSame('POST', $request['method']);
        $this->assertSame(
            'https://api.x.com/2/media/subtitles',
            $request['url'],
        );
        $this->assertContains(
            'Content-type: application/json',
            $request['headers'],
        );
        $this->assertSame(json_encode($parameters), $request['body']);
    }

    public function testDeleteSubtitles()
    {
        $parameters = [
            'id' => '1',
            'media_category' => 'TweetVideo',
            'language_code' => 'EN',
        ];
        $this->twitter->delete('media/subtitles', $parameters, [
            'jsonPayload' => true,
        ]);
        $request = $this->client->requests[0];
        $this->assertSame('DELETE', $request['method']);
        $this->assertSame(
            'https://api.x.com/2/media/subtitles',
            $request['url'],
        );
        $this->assertContains(
            'Content-type: application/json',
            $request['headers'],
        );
        $this->assertSame(json_encode($parameters), $request['body']);
    }

    public function testCreateMetadata()
    {
        $parameters = [
            'id' => '1',
            'metadata' => [
                'alt_text' => ['text' => 'A kitten sitting in a basket'],
            ],
        ];
        $this->twitter->post('media/metadata', $parameters);
        $request = $this->client->requests[0];
        $this->assertSame('POST', $request['method']);
        $this->assertSame(
            'https://api.x.com/2/media/metadata',
            $request['url'],
        );
        $this->assertContains(
            'Content-type: application/json',
            $request['headers'],
        );
        $this->assertSame(json_encode($parameters), $request['body']);
        $authorization = implode("\n", $request['headers']);
        $this->assertStringContainsString('OAuth ', $authorization);
        $this->assertStringNotContainsString('alt_text', $authorization);
    }

    public function testDeleteDefaultsToQueryString()
    {
        $this->twitter->delete('tweets/1', ['a' => 'b']);
        $request = $this->client->requests[0];
        $this->assertSame('https://api.x.com/2/tweets/1?a=b', $request['url']);
        $this->assertSame('', $request['body']);
    }
}
