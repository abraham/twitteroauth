<?php

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use Abraham\TwitterOAuth\CurlHttpClient;
use PHPUnit\Framework\TestCase;

class CurlHttpClientTest extends TestCase
{
    public function testParseResponsePreservesBodyContainingBlankLines(): void
    {
        $client = new CurlHttpClient();
        $method = new \ReflectionMethod($client, 'parseResponse');

        $rawHeaders =
            "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n";
        $body = "{\"text\":\"line1\r\n\r\nline2\"}";
        $response = $rawHeaders . $body;

        [$headerBlock, $parsedBody] = $method->invoke(
            $client,
            $response,
            strlen($rawHeaders),
        );

        $this->assertSame(
            "HTTP/1.1 200 OK\r\nContent-Type: application/json",
            $headerBlock,
        );
        $this->assertSame($body, $parsedBody);
    }

    public function testParseResponseUsesFinalHeaderBlock(): void
    {
        $client = new CurlHttpClient();
        $method = new \ReflectionMethod($client, 'parseResponse');

        $rawHeaders =
            "HTTP/1.1 100 Continue\r\n\r\n" .
            "HTTP/1.1 200 OK\r\nX-Rate-Limit-Remaining: 14\r\n\r\n";
        $body = '{"ok":true}';

        [$headerBlock, $parsedBody] = $method->invoke(
            $client,
            $rawHeaders . $body,
            strlen($rawHeaders),
        );

        $this->assertSame(
            "HTTP/1.1 200 OK\r\nX-Rate-Limit-Remaining: 14",
            $headerBlock,
        );
        $this->assertSame($body, $parsedBody);
    }

    public function testParseHeadersHandlesColonsWithoutTrailingSpace(): void
    {
        $client = new CurlHttpClient();
        $method = new \ReflectionMethod($client, 'parseHeaders');

        $header =
            "HTTP/1.1 200 OK\r\n" .
            "X-Rate-Limit-Remaining: 14\r\n" .
            "X-Empty:\r\n" .
            "X-Compact:foo:bar\r\n";

        $headers = $method->invoke($client, $header);

        $this->assertSame(
            [
                'x_rate_limit_remaining' => '14',
                'x_empty' => '',
                'x_compact' => 'foo:bar',
            ],
            $headers,
        );
    }
}
