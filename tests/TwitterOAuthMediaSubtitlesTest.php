<?php

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\HttpClientInterface;

class TwitterOAuthMediaSubtitlesTest extends TestCase
{
    private TwitterOAuth $twitter;
    private HttpClientInterface $client;

    protected function setUp(): void
    {
        $this->client = new class implements HttpClientInterface {
            public array $requests = [];

            public function request(
                string $url,
                string $method,
                array $headers,
                string $body,
                array $curlOptions,
            ): array {
                $this->requests[] = compact('url', 'method', 'headers', 'body');
                return [
                    'body' => '{"data":{}}',
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

    public function testDeleteDefaultsToQueryString()
    {
        $this->twitter->delete('tweets/1', ['a' => 'b']);
        $request = $this->client->requests[0];
        $this->assertSame('https://api.x.com/2/tweets/1?a=b', $request['url']);
        $this->assertSame('', $request['body']);
    }
}
