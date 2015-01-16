<?php
/*
 * Abraham Williams (abraham@abrah.am) https://abrah.am
 *
 * The first PHP Library to support OAuth 1.0A for Twitter's REST API.
 */
namespace Abraham\TwitterOAuth;

/**
 * Twitter OAuth class
 *
 * @author Abraham Williams <abraham@abrah.am>
 */
class TwitterOAuth
{
    /** @var string */
    private $apiHost = "https://api.twitter.com";
    /** @var string */
    private $uploadHost = "https://upload.twitter.com";
    /** @var string */
    private $apiVersion = "1.1";
    /** @var int */
    private $timeout = 5;
    /** @var int */
    private $connectionTimeout = 5;
    /**
     * Decode JSON Response as associative Array
     *
     * @see http://php.net/manual/en/function.json-decode.php
     *
     * @var bool
     */
    private $decodeJsonAsArray = false;
    /** @var string */
    private $userAgent = 'TwitterOAuth (+https://twitteroauth.com)';
    /** @var array */
    private $proxy = array();
    /** @var string|null */
    private $lastApiPath;
    /** @var int|null */
    private $lastHttpCode;
    /** @var array */
    private $lastHttpHeaders = array();
    /** @var array */
    private $lastHttpInfo = array();
    /** @var string|null */
    private $lastHttpMethod;
    /** @var array */
    private $lastXHeaders = array();
    /** @var array|object|null */
    private $lastResponse;
    /** @var Consumer */
    private $consumer;
    /** @var Token */
    private $token;
    /** @var HmacSha1 */
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
    }

    /**
     * @param string $host
     */
    public function setApiHost($host)
    {
        $this->apiHost = $host;
    }

    /**
     * @param string $version
     */
    public function setApiVersion($version)
    {
        $this->apiVersion = $version;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int)$timeout;
    }

    /**
     * @param int $timeout
     */
    public function setConnectionTimeout($timeout)
    {
        $this->connectionTimeout = (int)$timeout;
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
     * @return null|string
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
     * @return array|null|object
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
        $response = "{$this->apiHost}/{$path}?{$query}";
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
        $url = "{$this->apiHost}/{$path}";
        $result = $this->oAuthRequest($url, 'POST', $parameters);
        if ($this->lastHttpCode() == 200) {
            $response = Util::parseParameters($result);
            $this->lastResponse = $response;

            return $response;
        } else {
            throw new TwitterOAuthException($result);
        }
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
        return $this->http('GET', $this->apiHost, $path, $parameters);
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
        return $this->http('POST', $this->apiHost, $path, $parameters);
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
        return $this->http('POST', $this->uploadHost, $path, $parameters);
    }

    /**
     * @param string $method
     * @param string $host
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function http($method, $host, $path, array $parameters)
    {
        $this->resetLastResult();
        $url = "{$host}/{$this->apiVersion}/{$path}.json";
        $this->lastApiPath = $path;
        $result = $this->oAuthRequest($url, $method, $parameters);
        $response = $this->jsonDecode($result);
        $this->lastResponse = $response;

        return $response;
    }

    /**
     * Format and sign an OAuth / API request
     *
     * @param string $url
     * @param string $method
     * @param array $parameters
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
        $request->signRequest($this->signatureMethod, $this->consumer, $this->token);
        return $this->request($request->getNormalizedHttpUrl(), $method, $request->toHeader(), $parameters);
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
            list($connect, $header, $body) = explode("\r\n\r\n", $response, 3);
        }
        list($this->lastHttpHeaders, $this->lastXHeaders) = $this->parseHeaders($header);
        $this->lastHttpInfo = curl_getinfo($curlHandle);
        curl_close($curlHandle);

        return $body;
    }

    /**
     * @param string $string JSON to decode
     * @return array|object
     * @throws \Exception if json is not decoded properly
     */
    private function jsonDecode($string)
    {
        // BUG: https://github.com/abraham/twitteroauth/issues/288
        // BUG: https://bugs.php.net/bug.php?id=63520
        // BUG: https://www.drupal.org/node/2209795
        // Code fix from: https://github.com/firebase/php-jwt/blob/master/Authentication/JWT.php
        if (version_compare(PHP_VERSION, '5.4.0', '>=') && !(defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) {
            /** In PHP >=5.4.0, json_decode() accepts an options parameter, that allows you
             * to specify that large ints (like twitter IDs) should be treated as
             * strings, rather than the PHP default behaviour of converting them to floats.
             */
            $result = json_decode($string, $this->decodeJsonAsArray, 512, JSON_BIGINT_AS_STRING);
        } else {
            /** Not all servers will support that, however, so for older versions (or ones with the buggy
             *  JSON_C_VERSION implementation, fall back to the vanilla json_decode
             *  call, and assume users will use the 'id_str' field provided by the Twitter API
             */
            $result = json_decode($string, $this->decodeJsonAsArray);
        }
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            throw new \Exception('JSON decode error: '.$errno);
        } elseif ($result === null && $string !== 'null') {
            throw new \Exception('Null result with non-null input');
        }
        return $result;
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
}
