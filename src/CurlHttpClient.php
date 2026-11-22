<?php

/**
 * The most popular PHP library for use with the Twitter OAuth REST API.
 *
 * @license MIT
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth;

/**
 * cURL-based HTTP client implementation
 *
 * @author Abraham Williams <abraham@abrah.am>
 */
class CurlHttpClient implements HttpClientInterface
{
    /**
     * Execute an HTTP request using cURL
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
    ): array {
        $curlOptions[CURLOPT_URL] = $url;
        $curlOptions[CURLOPT_HTTPHEADER] = $headers;

        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                $curlOptions[CURLOPT_POST] = true;
                if (!empty($body)) {
                    $curlOptions[CURLOPT_POSTFIELDS] = $body;
                }
                break;
            case 'DELETE':
                $curlOptions[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                if (!empty($body)) {
                    $curlOptions[CURLOPT_POSTFIELDS] = $body;
                }
                break;
            case 'PUT':
                $curlOptions[CURLOPT_CUSTOMREQUEST] = 'PUT';
                if (!empty($body)) {
                    $curlOptions[CURLOPT_POSTFIELDS] = $body;
                }
                break;
        }

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, $curlOptions);
        $response = curl_exec($curlHandle);

        // Throw exceptions on cURL errors.
        if (curl_errno($curlHandle) > 0) {
            $error = curl_error($curlHandle);
            $errorNo = curl_errno($curlHandle);
            throw new TwitterOAuthException($error, $errorNo);
        }

        $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);
        [$responseHeader, $responseBody] = $this->parseResponse(
            $response,
            $headerSize,
        );
        $headers = $this->parseHeaders($responseHeader);

        return [
            'body' => $responseBody,
            'headers' => $headers,
            'httpCode' => $httpCode,
        ];
    }

    /**
     * Separate the final response header block and body using cURL's reported header size.
     *
     * @param string $response
     * @param int    $headerSize
     *
     * @return array{0: string, 1: string}
     */
    private function parseResponse(string $response, int $headerSize): array
    {
        $rawHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);
        $headerBlocks = explode("\r\n\r\n", rtrim($rawHeaders, "\r\n"));
        $responseHeader = array_pop($headerBlocks);

        return [$responseHeader, $responseBody];
    }

    /**
     * Get the header info to store.
     *
     * @param string $header
     *
     * @return array<string, string>
     */
    private function parseHeaders(string $header): array
    {
        $headers = [];
        foreach (explode("\r\n", $header) as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $key = str_replace('-', '_', strtolower(trim($key)));
                $headers[$key] = trim($value);
            }
        }
        return $headers;
    }
}
