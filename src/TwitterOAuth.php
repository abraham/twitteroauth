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
class TwitterOAuth extends Config
{
    const API_VERSION = '1.1';
    const API_HOST = 'https://api.twitter.com';
    const API_EXTENSION = 'json';
    const UPLOAD_HOST = 'https://upload.twitter.com';
    const STATUS_SUCCES = 200;

    /** @var Response details about the result of the last request */
    private $response;
    /** @var string|null Application bearer token */
    private $bearer;
    /** @var Consumer Twitter application details */
    private $consumer;
    /** @var Token|null User access token details */
    private $token;
    /** @var HmacSha1 OAuth 1 signature type used by Twitter */
    private $signatureMethod;
    /** @var int Number of attempts we made for the request */
    private $attempts = 0;
    /** @var string|null Version of the API */
    private $apiVersion;
    /** @var string|null Host of the API */
    private $apiHost;
    /** @var string|null Extension of the API */
    private $apiExtension;

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
        $this->setApiVersion(self::API_VERSION);
        $this->setApiHost(self::API_HOST);
        $this->setApiExtension(self::API_EXTENSION);
        $this->resetLastResponse();
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
     * @param string $version
     */
    public function setApiVersion($version)
    {
        $this->apiVersion = $version;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * @param string $host
     */
    public function setApiHost($host)
    {
        $this->apiHost = $host;
    }

    /**
     * @return string
     */
    public function getApiHost()
    {
        return $this->apiHost;
    }

    /**
     * @param string $extension
     */
    public function setApiExtension($extension)
    {
        $this->apiExtension = $extension;
    }

    /**
     * @return string
     */
    public function getApiExtension()
    {
        return $this->apiExtension;
    }

    /**
     * @param boolean $decodeJsonAsArray
     */
    public function setDecodeJsonAsArray($decodeJsonAsArray)
    {
        $this->decodeJsonAsArray = $decodeJsonAsArray;
    }

    /**
     * @return boolean
     */
    public function isDecodeJsonAsArray()
    {
        return $this->decodeJsonAsArray;
    }

    /**
     * @return string|null
     */
    public function getLastApiPath()
    {
        return $this->response->getApiPath();
    }

    /**
     * @return int
     */
    public function getLastHttpCode()
    {
        return $this->response->getHttpCode();
    }

    /**
     * @return array
     */
    public function getLastXHeaders()
    {
        return $this->response->getXHeaders();
    }

    /**
     * @return array|object|null
     */
    public function getLastBody()
    {
        return $this->response->getBody();
    }

    /**
     * Resets the last response cache.
     */
    public function resetLastResponse()
    {
        $this->response = new Response();
    }

    /**
     * Resets the attempts number.
     */
    private function resetAttemptsNumber()
    {
        $this->attempts = 0;
    }

    /**
     * Delays the retries when they're activated.
     */
    private function sleepIfNeeded()
    {
        if ($this->maxRetries && $this->attempts) {
            sleep($this->retriesDelay);
        }
    }

    /**
     * Make URLs for user browser navigation.
     *
     * @param integer $code
     *
     * @return string
     */
    private function getError($code)
    {
        $error = array(
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            406 => 'Not Acceptable',
            410 => 'Gone',
            420 => 'Enhance Your Calm',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway timeout',
        );

        return array_key_exists($code, $error) ? $error[$code] : 'Unknown error';
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
        $this->resetLastResponse();
        $this->response->setApiPath($path);
        $host = $this->getApiHost();
        $query = http_build_query($parameters);
        return sprintf('%s/%s?%s', $host, $path, $query);
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
    public function oauth($path, array $parameters = [])
    {
        $response = [];
        $this->resetLastResponse();
        $this->response->setApiPath($path);
        $host = $this->getApiHost();
        $url = sprintf('%s/%s', $host, $path);
        $result = $this->oAuthRequest($url, 'POST', $parameters);

        if ($this->getLastHttpCode() != self::STATUS_SUCCES) {
            throw new TwitterOAuthException($result);
        }

        parse_str($result, $response);
        $this->response->setBody($response);

        return $response;
    }

    /**
     * Make /oauth2/* requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function oauth2($path, array $parameters = [])
    {
        $method = 'POST';
        $this->resetLastResponse();
        $this->response->setApiPath($path);
        $host = $this->getApiHost();
        $url = sprintf('%s/%s', $host, $path);
        $request = Request::fromConsumerAndToken($this->consumer, $this->token, $method, $url, $parameters);
        $authorization = 'Authorization: Basic ' . $this->encodeAppAuthorization($this->consumer);
        $result = $this->request($request->getNormalizedHttpUrl(), $method, $authorization, $parameters);
        $response = JsonDecoder::decode($result, $this->decodeJsonAsArray);
        $this->response->setBody($response);
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
    public function get($path, array $parameters = [])
    {
        $host = $this->getApiHost();

        return $this->http('GET', $host, $path, $parameters);
    }

    /**
     * Make POST requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function post($path, array $parameters = [])
    {
        $host = $this->getApiHost();

        return $this->http('POST', $host, $path, $parameters);
    }

    /**
     * Make DELETE requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function delete($path, array $parameters = [])
    {
        $host = $this->getApiHost();

        return $this->http('DELETE', $host, $path, $parameters);
    }

    /**
     * Make PUT requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function put($path, array $parameters = [])
    {
        $host = $this->getApiHost();

        return $this->http('PUT', $host, $path, $parameters);
    }

    /**
     * Upload media to upload.twitter.com.
     *
     * @param string $path
     * @param array  $parameters
     * @param boolean  $chunked
     *
     * @return array|object
     */
    public function upload($path, array $parameters = [], $chunked = false)
    {
        if ($chunked) {
            return $this->uploadMediaChunked($path, $parameters);
        } else {
            return $this->uploadMediaNotChunked($path, $parameters);
        }
    }

    /**
     * Private method to upload media (not chunked) to upload.twitter.com.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    private function uploadMediaNotChunked($path, array $parameters)
    {
        $file = file_get_contents($parameters['media']);
        $base = base64_encode($file);
        $parameters['media'] = $base;
        return $this->http('POST', self::UPLOAD_HOST, $path, $parameters);
    }

    /**
     * Private method to upload media (chunked) to upload.twitter.com.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    private function uploadMediaChunked($path, array $parameters)
    {
        $init = $this->http('POST', self::UPLOAD_HOST, $path, $this->mediaInitParameters($parameters));
        // Append
        $segmentIndex = 0;
        $media = fopen($parameters['media'], 'rb');
        while (!feof($media)) {
            $this->http('POST', self::UPLOAD_HOST, 'media/upload', [
                'command' => 'APPEND',
                'media_id' => $init->media_id_string,
                'segment_index' => $segmentIndex++,
                'media_data' => base64_encode(fread($media, $this->chunkSize))
            ]);
        }
        fclose($media);
        // Finalize
        $finalize = $this->http('POST', self::UPLOAD_HOST, 'media/upload', [
            'command' => 'FINALIZE',
            'media_id' => $init->media_id_string
        ]);
        return $finalize;
    }

    /**
     * Private method to get params for upload media chunked init.
     * Twitter docs: https://dev.twitter.com/rest/reference/post/media/upload-init.html
     *
     * @param array  $parameters
     *
     * @return array
     */
    private function mediaInitParameters(array $parameters)
    {
        $return = [
            'command' => 'INIT',
            'media_type' => $parameters['media_type'],
            'total_bytes' => filesize($parameters['media'])
        ];
        if (isset($parameters['additional_owners'])) {
            $return['additional_owners'] = $parameters['additional_owners'];
        }
        if (isset($parameters['media_category'])) {
            $return['media_category'] = $parameters['media_category'];
        }
        return $return;
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
        $this->resetLastResponse();
        $this->resetAttemptsNumber();
        $version = $this->getApiVersion();
        $extension = $this->getApiExtension();
        $pathWithExtension = $path;
        if ($extension !== null) {
            $pathWithExtension .= '.'.$extension;
        }
        $url = sprintf('%s/%s/%s', $host, $version, $pathWithExtension);
        $this->response->setApiPath($path);
        return $this->makeRequests($url, $method, $parameters);
    }

    /**
     *
     * Make requests and retry them (if enabled) in case of Twitter's problems.
     *
     * @param string $method
     * @param string $url
     * @param string $method
     * @param array  $parameters
     *
     * @return array|object
     * @throws TwitterOAuthException
     */
    private function makeRequests($url, $method, array $parameters)
    {
        do {
            $this->sleepIfNeeded();
            $result = $this->oAuthRequest($url, $method, $parameters);
            $response = JsonDecoder::decode($result, $this->decodeJsonAsArray);
            $this->response->setBody($response);
            $this->attempts++;
            // Retry up to our $maxRetries number if we get errors greater than 500 (over capacity etc)
        } while ($this->requestsAvailable());

        $httpCode = $this->getLastHttpCode();

        if ($httpCode !== self::STATUS_SUCCES) {
            $error = $this->getError($httpCode);

            throw new TwitterOAuthException($error);
        }

        return $response;
    }

    /**
     * Checks if we have to retry request if API is down.
     *
     * @return bool
     */
    private function requestsAvailable()
    {
        return ($this->maxRetries && ($this->attempts <= $this->maxRetries) && $this->getLastHttpCode() >= 500);
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
        $request = Request::fromConsumerAndToken($this->consumer, $this->token, $method, $url, $parameters);
        if (array_key_exists('oauth_callback', $parameters)) {
            // Twitter doesn't like oauth_callback as a parameter.
            unset($parameters['oauth_callback']);
        }
        if ($this->bearer === null) {
            $request->signRequest($this->signatureMethod, $this->consumer, $this->token);
            $authorization = $request->toHeader();
            if (array_key_exists('oauth_verifier', $parameters)) {
                // Twitter doesn't always work with oauth in the body and in the header
                // and it's already included in the $authorization header
                unset($parameters['oauth_verifier']);
            }
        } else {
            $authorization = 'Authorization: Bearer ' . $this->bearer;
        }
        return $this->request($request->getNormalizedHttpUrl(), $method, $authorization, $parameters);
    }

    /**
     * Set Curl options.
     *
     * @return array
     */
    private function curlOptions()
    {
        $options = [
            // CURLOPT_VERBOSE => true,
            CURLOPT_CONNECTTIMEOUT => $this->connectionTimeout,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgent,
        ];

        if ($this->useCAFile()) {
            $options[CURLOPT_CAINFO] = __DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem';
        }

        if ($this->gzipEncoding) {
            $options[CURLOPT_ENCODING] = 'gzip';
        }

        if (!empty($this->proxy)) {
            $options[CURLOPT_PROXY] = $this->proxy['CURLOPT_PROXY'];
            $options[CURLOPT_PROXYUSERPWD] = $this->proxy['CURLOPT_PROXYUSERPWD'];
            $options[CURLOPT_PROXYPORT] = $this->proxy['CURLOPT_PROXYPORT'];
            $options[CURLOPT_PROXYAUTH] = CURLAUTH_BASIC;
            $options[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
        }

        return $options;
    }

    /**
     * Make an HTTP request
     *
     * @param string $url
     * @param string $method
     * @param string $authorization
     * @param array $postfields
     *
     * @return string
     * @throws TwitterOAuthException
     */
    private function request($url, $method, $authorization, array $postfields)
    {
        $options = $this->curlOptions();
        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_HTTPHEADER] = ['Accept: application/json', $authorization, 'Expect:'];

        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = Util::buildHttpQuery($postfields);
                break;
            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                break;
        }

        if (in_array($method, ['GET', 'PUT', 'DELETE']) && !empty($postfields)) {
            $options[CURLOPT_URL] .= '?' . Util::buildHttpQuery($postfields);
        }


        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, $options);
        $response = curl_exec($curlHandle);

        // Throw exceptions on cURL errors.
        if (curl_errno($curlHandle) > 0) {
            throw new TwitterOAuthException(curl_error($curlHandle), curl_errno($curlHandle));
        }

        $this->response->setHttpCode(curl_getinfo($curlHandle, CURLINFO_HTTP_CODE));
        $parts = explode("\r\n\r\n", $response);
        $responseBody = array_pop($parts);
        $responseHeader = array_pop($parts);
        $this->response->setHeaders($this->parseHeaders($responseHeader));

        curl_close($curlHandle);

        return $responseBody;
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
        $headers = [];
        foreach (explode("\r\n", $header) as $line) {
            if (strpos($line, ':') !== false) {
                list ($key, $value) = explode(': ', $line);
                $key = str_replace('-', '_', strtolower($key));
                $headers[$key] = trim($value);
            }
        }
        return $headers;
    }

    /**
     * Encode application authorization header with base64.
     *
     * @param Consumer $consumer
     *
     * @return string
     */
    private function encodeAppAuthorization(Consumer $consumer)
    {
        $key = rawurlencode($consumer->key);
        $secret = rawurlencode($consumer->secret);
        return base64_encode($key . ':' . $secret);
    }

    /**
     * Is the code running from a Phar module.
     *
     * @return boolean
     */
    private function pharRunning()
    {
        return class_exists('Phar') && \Phar::running(false) !== '';
    }

    /**
     * Use included CA file instead of OS provided list.
     *
     * @return boolean
     */
    private function useCAFile()
    {
        /* Use CACert file when not in a PHAR file. */
        return !$this->pharRunning();
    }
}
