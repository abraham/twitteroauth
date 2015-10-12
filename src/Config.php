<?php

namespace Abraham\TwitterOAuth;

/**
 * Handle setting and storing config for TwitterOAuth.
 *
 * @author Abraham Williams <abraham@abrah.am>
 */
class Config
{
    const API_HOST = 'https://api.twitter.com';
    const UPLOAD_HOST = 'https://upload.twitter.com';

    /** @var int How long to wait for a response from the API */
    protected $timeout = 5;
    /** @var int how long to wait while connecting to the API */
    protected $connectionTimeout = 5;
    /**
     * Decode JSON Response as associative Array
     *
     * @see http://php.net/manual/en/function.json-decode.php
     *
     * @var bool
     */
    protected $decodeJsonAsArray = false;
    /** @var string User-Agent header */
    protected $userAgent = 'TwitterOAuth (+https://twitteroauth.com)';
    /** @var array Store proxy connection details */
    protected $proxy = [];

    /** @var string Twitter api host */
    protected $apiHost = self::API_HOST;

    /** @var string Twitter upload host */
    protected $uploadHost = self::UPLOAD_HOST;

    /**
     * Set the api endpoint used
     *
     * @param string $host
     */
    public function setApiHost($host)
    {
        $this->apiHost = $host;
    }

    /**
     * Set the api endpoint for uploads
     *
     * @param string $host
     */
    public function setUploadHost($host)
    {
        $this->uploadHost = $host;
    }

    /**
     * Set the connection and response timeouts.
     *
     * @param int $connectionTimeout
     * @param int $timeout
     */
    public function setTimeouts($connectionTimeout, $timeout)
    {
        $this->connectionTimeout = (int)$connectionTimeout;
        $this->timeout = (int)$timeout;
    }

    /**
     * @param bool $value
     */
    public function setDecodeJsonAsArray($value)
    {
        $this->decodeJsonAsArray = (bool)$value;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = (string)$userAgent;
    }

    /**
     * @param array $proxy
     */
    public function setProxy(array $proxy)
    {
        $this->proxy = $proxy;
    }
}
