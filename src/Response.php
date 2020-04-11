<?php

namespace Abraham\TwitterOAuth;

use ArrayObject;

/**
 * The result of the most recent API request.
 *
 * @author Abraham Williams <abraham@abrah.am>
 */
class Response
{
    /** @var string|null API path from the most recent request */
    private $apiPath;
    /** @var int HTTP status code from the most recent request */
    private $httpCode = 0;
    /** @var array HTTP headers from the most recent request */
    private $headers = [];
    /** @var array|object Response body from the most recent request */
    private $body = [];
    /** @var array HTTP headers from the most recent request that start with X */
    private $xHeaders = [];

    /**
     * @param string $apiPath
     */
    public function setApiPath($apiPath)
    {
        $this->apiPath = $apiPath;
    }

    /**
     * @return string|null
     */
    public function getApiPath()
    {
        return $this->apiPath;
    }

    /**
     * @param array|object $body
     */
    public function setBody($body)
    {
        $this->body = new ArrayObject($body, ArrayObject::ARRAY_AS_PROPS);
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
    public function setHttpCode($httpCode)
    {
        $this->httpCode = $httpCode;
    }

    /**
     * @return int
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
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
    public function getsHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $xHeaders
     */
    public function setXHeaders(array $xHeaders = [])
    {
        $this->xHeaders = $xHeaders;
    }

    /**
     * @return array
     */
    public function getXHeaders()
    {
        return $this->xHeaders;
    }

    /**
     * Get Oauth2 Access Token .
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->getBody()->access_token ?? '';
    }

    /**
     * Get Oauth Token.
     *
     * @return string
     */
    public function getAuthToken(): string
    {
        return $this->getBody()->oauth_token ?? '';
    }

    /**
     * Get Oauth Token Secret.
     *
     * @return string
     */
    public function getAuthTokenSecret(): string
    {
        return $this->getBody()->oauth_token_secret ?? '';
    }

    /**
     * Check For Valid Callback.
     *
     * @return bool
     */
    public function checkAuthCallbackConfirmed(): bool
    {
        return $this->getBody()->oauth_callback_confirmed ?? false;
    }
}
