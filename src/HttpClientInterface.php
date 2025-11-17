<?php

/**
 * The most popular PHP library for use with the Twitter OAuth REST API.
 *
 * @license MIT
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth;

/**
 * Interface for HTTP client implementations
 *
 * @author Abraham Williams <abraham@abrah.am>
 */
interface HttpClientInterface
{
    /**
     * Execute an HTTP request
     *
     * @param string $url           The URL to request
     * @param string $method        The HTTP method (GET, POST, PUT, DELETE)
     * @param array  $headers       The HTTP headers
     * @param string $body          The request body
     * @param array  $curlOptions   Additional cURL options
     *
     * @return array{body: string, headers: array<string, string>, httpCode: int}
     * @throws TwitterOAuthException
     */
    public function request(
        string $url,
        string $method,
        array $headers,
        string $body,
        array $curlOptions,
    ): array;
}
