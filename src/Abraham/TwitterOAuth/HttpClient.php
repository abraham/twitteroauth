<?php
namespace Abraham\TwitterOAuth;

class HttpClient
{
    /**
     * @var string
     */
    const USER_AGENT = 'TwitterOAuth v0.2.0-beta2';

    /**
     * The timeout
     *
     * @var int
     */
    private $timeout;

    /**
     * The connect timeout
     *
     * @var int
     */
    private $connectTimeout;

    /**
     * Verify SSL Cert
     *
     * @var boolean
     */
    private $sslVerifyPeer;

    /**
     * Contains the information about the last request
     *
     * @var string
     */
    private $httpInfo;

    /**
     * @param int $timeout
     * @param int $connectTimeout
     * @param boolean $sslVerifyPeer
     */
    public function __construct(
        $timeout = 30,
        $connectTimeout = 30,
        $sslVerifyPeer = false
    ) {
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->sslVerifyPeer = $sslVerifyPeer;
    }

    /**
     * Do a GET request
     *
     * @param string $url
     * @return string
     */
    public function get($url)
    {
        $handler = $this->createHandler($url);

        return $this->getResponse($handler);
    }

    /**
     * Do a POST request
     *
     * @param string $url
     * @param string $parameters
     * @return string
     */
    public function post($url, $parameters)
    {
        $handler = $this->createHandler($url);

        curl_setopt($handler, CURLOPT_POST, true);

        if (!empty($parameters)) {
            curl_setopt($handler, CURLOPT_POSTFIELDS, $parameters);
        }

        return $this->getResponse($handler);
    }

    /**
     * Do a DELETE request
     *
     * @param string $url
     * @param string $parameters
     * @return string
     */
    public function delete($url, $parameters)
    {
        if (!empty($parameters)) {
            $url .= '?' . $parameters;
        }

        $handler = $this->createHandler($url);

        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, 'DELETE');

        return $this->getResponse($handler);
    }

    /**
     * @return string
     */
    public function getLastRequestInformation()
    {
        return $this->httpInfo;
    }

    /**
     * @return string
     */
    public function getLastRequestedUrl()
    {
        return $this->httpInfo['url'];
    }

    /**
     * @return integer
     */
    public function getLastStatusCode()
    {
        return (int) $this->httpInfo['http_code'];
    }

    /**
     * @return array
     */
    public function getLastHttpHeaders()
    {
        return $this->httpInfo['headers'];
    }

    /**
     * @return resource
     */
    private function createHandler($url)
    {
        $this->httpInfo = array('headers' => array());

        $handler = curl_init();

        curl_setopt($handler, CURLOPT_USERAGENT, static::USER_AGENT);
        curl_setopt($handler, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($handler, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, $this->sslVerifyPeer);
        curl_setopt($handler, CURLOPT_HEADER, false);
        curl_setopt($handler, CURLOPT_URL, $url);

        curl_setopt(
            $handler,
            CURLOPT_HEADERFUNCTION,
            array($this, 'appendHeader')
        );

        return $handler;
    }

    /**
     * @param resource $handler
     * @return string
     */
    private function getResponse($handler)
    {
        $response = curl_exec($handler);

        $this->httpCode = curl_getinfo($handler, CURLINFO_HTTP_CODE);
        $this->httpInfo = array_merge($this->httpInfo, curl_getinfo($handler));

        curl_close($handler);

        return $response;
    }

    /**
     * Get the header info to store.
     */
    public function appendHeader($handler, $header)
    {
        $position = strpos($header, ':');

        if (!empty($position)) {
            $key = str_replace(
                '-',
                '_',
                strtolower(substr($header, 0, $position))
            );

            $value = trim(substr($header, $position + 2));
            $this->httpInfo['headers'][$key] = $value;
        }

        return strlen($header);
    }
}