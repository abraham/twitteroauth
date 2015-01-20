<?php
/**
 * The most popular PHP library for use with the Twitter OAuth REST API.
 *
 * @license MIT
 */
namespace Abraham\TwitterOAuth;

use Abraham\TwitterOAuth\Util\JsonDecoder;

/**
 * TwitterOAuth class for interacting with the Twitter API.
 *
 * @author Abraham Williams <abraham@abrah.am>
 */
class TwitterOAuth
{
    const API_VERSION = '1.1';
    const API_HOST = 'https://api.twitter.com';
    const UPLOAD_HOST = 'https://upload.twitter.com';

    /** @var int How long to wait for a response from the API */
    private $timeout = 5;
    /** @var int how long to wait while connecting to the API */
    private $connectionTimeout = 5;
    /**
     * Decode JSON Response as associative Array
     *
     * @see http://php.net/manual/en/function.json-decode.php
     *
     * @var bool
     */
    private $decodeJsonAsArray = false;
    /** @var string User-Agent header */
    private $userAgent = 'TwitterOAuth (+https://twitteroauth.com)';
    /** @var array Store proxy connection details */
    private $proxy = array();
    /** @var string|null API path from the most recent request */
    private $lastApiPath;
    /** @var int|null HTTP status code from the most recent request */
    private $lastHttpCode;
    /** @var array HTTP headers from the most recent request */
    private $lastHttpHeaders = array();
    /** @var array */
    private $lastHttpInfo = array();
    /** @var string|null HTTP method from the most recent request */
    private $lastHttpMethod;
    /** @var array HTTP headers from the most recent request that start with X */
    private $lastXHeaders = array();
    /** @var array|object|null HTTP body from the most recent request */
    private $lastResponse;
    /** @var string|null Application bearer token */
    private $bearer;
    /** @var Consumer Twitter application details */
    private $consumer;
    /** @var Token|null User access token details */
    private $token;
    /** @var HmacSha1 OAuth 1 signature type used by Twitter */
    private $signatureMethod;

    /**
     * Constructor
     *
     * @param string      $consumerKey      The Application Consumer Key
     * @param string      $consumerSecret   The Application Consumer Secret
     * @param string|null $oauthToken       The Client Token (optional)
     * @param string|null $oauthTokenSecret The Client Token Secret (optional)
     */
    public function __construct($consumerKey, $consumerSecret, $oauthToken = null, $oauthTokenSecret = null)
    {
        $this->resetLastResult();
        $this->signatureMethod = new HmacSha1();
        $this->consumer = new Consumer($consumerKey, $consumerSecret);
        if (!empty($oauthToken) && !empty($oauthTokenSecret)) {
            $this->token = new Token($oauthToken, $oauthTokenSecret);
        }
        if (empty($oauthToken) && !empty($oauthTokenSecret)) {
            $this->bearer = $oauthTokenSecret;
        }
    }

    /**
     * @param string $oauthToken
     * @param string $oauthTokenSecret
     */
    public function setOauthToken($oauthToken, $oauthTokenSecret)
    {
        $this->token = new Token($oauthToken, $oauthTokenSecret);
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

    /**
     * @return null|string
     */
    public function lastApiPath()
    {
        return $this->lastApiPath;
    }

    /**
     * @return int|null
     */
    public function lastHttpCode()
    {
        return $this->lastHttpCode;
    }

    /**
     * @return string|null
     */
    public function lastHttpMethod()
    {
        return $this->lastHttpMethod;
    }

    /**
     * @return array
     */
    public function lastXHeaders()
    {
        return $this->lastXHeaders;
    }

    /**
     * @return array|object|null
     */
    public function lastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Resets the last response information
     */
    public function resetLastResult()
    {
        $this->lastApiPath = null;
        $this->lastHttpCode = null;
        $this->lastHttpInfo = array();
        $this->lastHttpHeaders = array();
        $this->lastHttpMethod = null;
        $this->lastXHeaders = array();
        $this->lastResponse = array();
    }

    /**
     * Make URLs for user browser navigation.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return string
     */
    public function url($path, array $parameters)
    {
        $this->resetLastResult();
        $this->lastApiPath = $path;
        $query = http_build_query($parameters);
        $response = sprintf('%s/%s?%s', self::API_HOST, $path, $query);
        $this->lastResponse = $response;

        return $response;
    }

    /**
     * Make /oauth/* requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array
     * @throws TwitterOAuthException
     */
    public function oauth($path, array $parameters = array())
    {
        $this->resetLastResult();
        $this->lastApiPath = $path;
        $url = sprintf('%s/%s', self::API_HOST, $path);
        $result = $this->oAuthRequest($url, 'POST', $parameters);
        if ($this->lastHttpCode() == 200) {
            parse_str($result, $response);
            $this->lastResponse = $response;

            return $response;
        } else {
            throw new TwitterOAuthException($result);
        }
    }

    /**
     * Make /oauth2/* requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function oauth2($path, array $parameters = array())
    {
        $method = 'POST';
        $this->resetLastResult();
        $this->lastApiPath = $path;
        $this->lastHttpMethod = $method;
        $url = sprintf('%s/%s', self::API_HOST, $path);
        $request = Request::fromConsumerAndToken($this->consumer, $this->token, $method, $url, $parameters);
        $headers = 'Authorization: Basic ' . $this->encodeAppAuthorization($this->consumer);
        $result = $this->request($request->getNormalizedHttpUrl(), $method, $headers, $parameters);
        $response = JsonDecoder::decode($result, $this->decodeJsonAsArray);
        $this->lastResponse = $response;
        return $response;
    }

    /**
     * Make GET requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function get($path, array $parameters = array())
    {
        return $this->http('GET', self::API_HOST, $path, $parameters);
    }

    /**
     * Make POST requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function post($path, array $parameters = array())
    {
        return $this->http('POST', self::API_HOST, $path, $parameters);
    }

    /**
     * Upload media to upload.twitter.com.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function upload($path, array $parameters = array())
    {
        $file = file_get_contents($parameters['media']);
        $base = base64_encode($file);
        $parameters['media'] = $base;
        return $this->http('POST', self::UPLOAD_HOST, $path, $parameters);
    }

    /**
     * @param string $method
     * @param string $host
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    private function http($method, $host, $path, array $parameters)
    {
        $this->resetLastResult();
        $url = sprintf('%s/%s/%s.json', $host, self::API_VERSION, $path);
        $this->lastApiPath = $path;
        $result = $this->oAuthRequest($url, $method, $parameters);
        $response = JsonDecoder::decode($result, $this->decodeJsonAsArray);
        $this->lastResponse = $response;

        return $response;
    }

    /**
     * Format and sign an OAuth / API request
     *
     * @param string $url
     * @param string $method
     * @param array  $parameters
     *
     * @return string
     * @throws TwitterOAuthException
     */
    private function oAuthRequest($url, $method, array $parameters)
    {
        $this->lastHttpMethod = $method;
        $request = Request::fromConsumerAndToken($this->consumer, $this->token, $method, $url, $parameters);
        if (array_key_exists('oauth_callback', $parameters)) {
            // Twitter doesn't like oauth_callback as a parameter.
            unset($parameters['oauth_callback']);
        }
        if ($this->bearer === null) {
            $request->signRequest($this->signatureMethod, $this->consumer, $this->token);
            $headers = $request->toHeader();
        } else {
            $headers = 'Authorization: Bearer ' . $this->bearer;
        }
        return $this->request($request->getNormalizedHttpUrl(), $method, $headers, $parameters);
    }

    /**
     * Make an HTTP request
     *
     * @param $url
     * @param $method
     * @param $headers
     * @param $postfields
     *
     * @return string
     * @throws TwitterOAuthException
     */
    private function request($url, $method, $headers, $postfields)
    {
        /* Curl settings */
        $options = array(
            // CURLOPT_VERBOSE => true,
            CURLOPT_CAINFO => __DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem',
            CURLOPT_CAPATH => __DIR__,
            CURLOPT_CONNECTTIMEOUT => $this->connectionTimeout,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array($headers, 'Expect:'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_ENCODING => 'gzip',
        );

        if (!empty($this->proxy)) {
            $options[CURLOPT_PROXY] = $this->proxy['CURLOPT_PROXY'];
            $options[CURLOPT_PROXYUSERPWD] = $this->proxy['CURLOPT_PROXYUSERPWD'];
            $options[CURLOPT_PROXYPORT] = $this->proxy['CURLOPT_PROXYPORT'];
            $options[CURLOPT_PROXYAUTH] = CURLAUTH_BASIC;
            $options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
        }

        switch ($method) {
            case 'GET':
                if (!empty($postfields)) {
                    $options[CURLOPT_URL] .= '?' . Util::buildHttpQuery($postfields);
                }
                break;
            case 'POST':
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = Util::buildHttpQuery($postfields);
                break;
        }

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, $options);
        $response = curl_exec($curlHandle);

        $curlErrno = curl_errno($curlHandle);
        switch ($curlErrno) {
            case 28:
                throw new TwitterOAuthException('Request timed out.');
            case 51:
                throw new TwitterOAuthException('The remote servers SSL certificate or SSH md5 fingerprint failed validation.');
            case 56:
                throw new TwitterOAuthException('Response from server failed or was interrupted.');
        }

        $this->lastHttpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        if (empty($this->proxy)) {
            list($header, $body) = explode("\r\n\r\n", $response, 2);
        } else {
            list(, $header, $body) = explode("\r\n\r\n", $response, 3);
        }
        list($this->lastHttpHeaders, $this->lastXHeaders) = $this->parseHeaders($header);
        $this->lastHttpInfo = curl_getinfo($curlHandle);
        curl_close($curlHandle);

        return $body;
    }

    /**
     * Get the header info to store.
     *
     * @param string $header
     *
     * @return array
     */
    private function parseHeaders($header)
    {
        $headers = array();
        $xHeaders = array();
        foreach (explode("\r\n", $header) as $i => $line) {
            $i = strpos($line, ':');
            if (!empty($i)) {
                list ($key, $value) = explode(': ', $line);
                $key = str_replace('-', '_', strtolower($key));
                $headers[$key] = trim($value);
                if (substr($key, 0, 1) == 'x') {
                    $xHeaders[$key] = trim($value);
                }
            }
        }
        return array($headers, $xHeaders);
    }

    /**
     * Encode application authorization header with base64.
     *
     * @param Consumer $consumer
     *
     * @return string
     */
    private function encodeAppAuthorization($consumer)
    {
        // TODO: key and secret should be rfc 1738 encoded
        $key = $consumer->key;
        $secret = $consumer->secret;
        return base64_encode($key . ':' . $secret);
    }
}
