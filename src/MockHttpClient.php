<?php

/**
 * The most popular PHP library for use with the Twitter OAuth REST API.
 *
 * @license MIT
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth;

/**
 * Mock HTTP client for testing that loads responses from VCR fixture files
 *
 * @author Abraham Williams <abraham@abrah.am>
 */
class MockHttpClient implements HttpClientInterface
{
    /** @var array<string, array> Loaded fixtures keyed by fixture name */
    private array $fixtures = [];

    /** @var ?string Current fixture name to use for next request */
    private ?string $currentFixture = null;

    /** @var int Current request index within multi-request fixtures */
    private int $requestIndex = 0;

    /**
     * Set the fixture to use for the next request(s)
     *
     * @param string $fixtureName The name of the fixture file (without .json extension)
     */
    public function useFixture(string $fixtureName): void
    {
        $this->currentFixture = $fixtureName;
        $this->requestIndex = 0;

        // Load fixture if not already loaded
        if (!isset($this->fixtures[$fixtureName])) {
            $fixturePath =
                __DIR__ . '/../tests/fixtures/' . $fixtureName . '.json';
            if (!file_exists($fixturePath)) {
                throw new TwitterOAuthException(
                    "Fixture file not found: {$fixturePath}",
                );
            }

            $fixtureContent = file_get_contents($fixturePath);
            $fixtureData = json_decode($fixtureContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new TwitterOAuthException(
                    "Invalid JSON in fixture: {$fixturePath}",
                );
            }

            $this->fixtures[$fixtureName] = $fixtureData;
        }
    }

    /**
     * Execute a mock HTTP request by returning data from the loaded fixture
     *
     * @param string $url           The URL to request
     * @param string $method        The HTTP method (GET, POST, PUT, DELETE)
     * @param array  $headers       The HTTP headers
     * @param string $body          The request body
     * @param array  $curlOptions   Additional cURL options (ignored in mock)
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
        if ($this->currentFixture === null) {
            throw new TwitterOAuthException(
                'No fixture set. Call useFixture() before making requests.',
            );
        }

        $fixtureData = $this->fixtures[$this->currentFixture];

        // Get the appropriate request/response pair
        if (!isset($fixtureData[$this->requestIndex])) {
            throw new TwitterOAuthException(
                "Request index {$this->requestIndex} not found in fixture {$this->currentFixture}",
            );
        }

        $interaction = $fixtureData[$this->requestIndex];
        $this->requestIndex++;

        // Extract response data from VCR format
        $response = $interaction['response'];
        $httpCode = (int) $response['status']['code'];
        $responseBody = $response['body'] ?? '';

        // Handle responses where headers are in the 'headers' field
        if (isset($response['headers'])) {
            $responseHeaders = $this->normalizeHeaders($response['headers']);
        } else {
            // For proxy-recorded responses, headers might be empty
            $responseHeaders = [];
        }

        // Some responses (like proxy responses) have headers embedded in the body
        // Extract the actual body content if it contains HTTP headers
        if (str_contains($responseBody, "\r\n\r\n")) {
            $parts = explode("\r\n\r\n", $responseBody);
            if (count($parts) > 1) {
                // Last part is the actual body, previous parts are headers
                $responseBody = array_pop($parts);
            }
        }

        return [
            'body' => $responseBody,
            'headers' => $responseHeaders,
            'httpCode' => $httpCode,
        ];
    }

    /**
     * Normalize headers from VCR format to match expected format
     *
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            // Convert to lowercase and replace hyphens with underscores
            $normalizedKey = str_replace('-', '_', strtolower($key));
            $normalized[$normalizedKey] = $value;
        }
        return $normalized;
    }

    /**
     * Reset the request index (useful for tests that need to replay fixtures)
     */
    public function reset(): void
    {
        $this->requestIndex = 0;
    }
}
