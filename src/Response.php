<?php

declare(strict_types=1);

namespace Abraham\TwitterOAuth;

/**
 * The result of the most recent API request.
 *
 * @author Abraham Williams <abraham@abrah.am>
 */
class Response
{
    /** @var string|null API path from the most recent request */
    private ?string $apiPath = null;
    /** @var int HTTP status code from the most recent request */
    private int $httpCode = 0;
    /** @var array HTTP headers from the most recent request */
    private array $headers = [];
    /** @var array|object Response body from the most recent request */
    private $body = [];
    /** @var array HTTP headers from the most recent request that start with X */
    private array $xHeaders = [];

    /**
     * @param string $apiPath
     */
    public function setApiPath(string $apiPath): void
    {
        $this->apiPath = $apiPath;
    }

    /**
     * @return string|null
     */
    public function getApiPath(): ?string
    {
        return $this->apiPath;
    }

    /**
     * @param array|object $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return array|object|string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param int $httpCode
     */
    public function setHttpCode(int $httpCode): void
    {
        $this->httpCode = $httpCode;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers): void
    {
        foreach ($headers as $key => $value) {
            if (substr($key, 0, 1) == 'x') {
                $this->xHeaders[$key] = $value;
            }
        }
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function getsHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $xHeaders
     */
    public function setXHeaders(array $xHeaders = []): void
    {
        $this->xHeaders = $xHeaders;
    }

    /**
     * @return array
     */
    public function getXHeaders(): array
    {
        return $this->xHeaders;
    }
}
