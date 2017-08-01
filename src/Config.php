<?php

namespace Abraham\TwitterOAuth;

/**
 * Handle setting and storing config for TwitterOAuth.
 *
 * @author Abraham Williams <abraham@abrah.am>
 */
class Config
{
    /** @var int How long to wait for a response from the API */
    protected $timeout = 5;
    /** @var int how long to wait while connecting to the API */
    protected $connectionTimeout = 5;
    /** @var int How many times we retry request when API is down */
    protected $maxRetries = 0;
    /** @var int Delay in seconds before we retry the request */
    protected $retriesDelay = 1;



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

    /** @var bool Whether to encode the curl requests with gzip or not */
    protected $gzipEncoding = true;

    /** @var integer Size for Chunked Uploads */
    protected $chunkSize = 250000; // 0.25 MegaByte

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
     *  Set the number of times to retry on error and how long between each.
     *
     * @param int $maxRetries
     * @param int $retriesDelay
     */
    public function setRetries($maxRetries, $retriesDelay)
    {
        $this->maxRetries = (int)$maxRetries;
        $this->retriesDelay = (int)$retriesDelay;
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

    /**
     * Whether to encode the curl requests with gzip or not.
     *
     * @param boolean $gzipEncoding
     */
    public function setGzipEncoding($gzipEncoding)
    {
        $this->gzipEncoding = (bool)$gzipEncoding;
    }

    /**
     * Set the size of each part of file for chunked media upload.
     *
     * @param int $value
     */
    public function setChunkSize($value)
    {
        $this->chunkSize = (int)$value;
    }
}
