<?php

/**
 * The most popular PHP library for use with the Twitter OAuth REST API.
 *
 * @license MIT
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth;

use Abraham\TwitterOAuth\Util\JsonDecoder;
use Composer\CaBundle\CaBundle;

/**
 * TwitterOAuth class for interacting with the Twitter API.
 *
 * @author Abraham Williams <abraham@abrah.am>
 */
class TwitterOAuth extends Config
{
    private const API_VERSION = '1.1';
    private const API_HOST = 'https://api.twitter.com';
    private const UPLOAD_HOST = 'https://upload.twitter.com';

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

    /**
     * Constructor
     *
     * @param string      $consumerKey      The Application Consumer Key
     * @param string      $consumerSecret   The Application Consumer Secret
     * @param string|null $oauthToken       The Client Token (optional)
     * @param string|null $oauthTokenSecret The Client Token Secret (optional)
     */
    public function __construct(
        string $consumerKey,
        string $consumerSecret,
        ?string $oauthToken = null,
        ?string $oauthTokenSecret = null
    ) {
        $this->resetLastResponse();
        $this->signatureMethod = new HmacSha1();
        $this->consumer = new Consumer($consumerKey, $consumerSecret);
        if (!empty($oauthToken) && !empty($oauthTokenSecret)) {
            $this->setOauthToken($oauthToken, $oauthTokenSecret);
        }
        if (empty($oauthToken) && !empty($oauthTokenSecret)) {
            $this->setBearer($oauthTokenSecret);
        }
    }

    /**
     * @param string $oauthToken
     * @param string $oauthTokenSecret
     */
    public function setOauthToken(
        string $oauthToken,
        string $oauthTokenSecret
    ): void {
        $this->token = new Token($oauthToken, $oauthTokenSecret);
        $this->bearer = null;
    }

    /**
     * @param string $oauthTokenSecret
     */
    public function setBearer(string $oauthTokenSecret): void
    {
        $this->bearer = $oauthTokenSecret;
        $this->token = null;
    }

    /**
     * @return string|null
     */
    public function getLastApiPath(): ?string
    {
        return $this->response->getApiPath();
    }

    /**
     * @return int
     */
    public function getLastHttpCode(): int
    {
        return $this->response->getHttpCode();
    }

    /**
     * @return array
     */
    public function getLastXHeaders(): array
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
    public function resetLastResponse(): void
    {
        $this->response = new Response();
    }

    /**
     * Resets the attempts number.
     */
    private function resetAttemptsNumber(): void
    {
        $this->attempts = 0;
    }

    /**
     * Delays the retries when they're activated.
     */
    private function sleepIfNeeded(): void
    {
        if ($this->maxRetries && $this->attempts) {
            sleep($this->retriesDelay);
        }
    }

    /**
     * Make URLs for user browser navigation.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return string
     */
    public function url(string $path, array $parameters): string
    {
        $this->resetLastResponse();
        $this->response->setApiPath($path);
        $query = http_build_query($parameters);
        return sprintf('%s/%s?%s', self::API_HOST, $path, $query);
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
    public function oauth(string $path, array $parameters = []): array
    {
        $response = [];
        $this->resetLastResponse();
        $this->response->setApiPath($path);
        $url = sprintf('%s/%s', self::API_HOST, $path);
        $result = $this->oAuthRequest($url, 'POST', $parameters);

        if ($this->getLastHttpCode() != 200) {
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
    public function oauth2(string $path, array $parameters = [])
    {
        $method = 'POST';
        $this->resetLastResponse();
        $this->response->setApiPath($path);
        $url = sprintf('%s/%s', self::API_HOST, $path);
        $request = Request::fromConsumerAndToken(
            $this->consumer,
            $this->token,
            $method,
            $url,
            $parameters
        );
        $authorization =
            'Authorization: Basic ' .
            $this->encodeAppAuthorization($this->consumer);
        $result = $this->request(
            $request->getNormalizedHttpUrl(),
            $method,
            $authorization,
            $parameters
        );
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
    public function get(string $path, array $parameters = [])
    {
        return $this->http('GET', self::API_HOST, $path, $parameters, false);
    }

    /**
     * Make POST requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     * @param bool   $json
     *
     * @return array|object
     */
    public function post(
        string $path,
        array $parameters = [],
        bool $json = false
    ) {
        return $this->http('POST', self::API_HOST, $path, $parameters, $json);
    }

    /**
     * Make DELETE requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function delete(string $path, array $parameters = [])
    {
        return $this->http('DELETE', self::API_HOST, $path, $parameters, false);
    }

    /**
     * Make PUT requests to the API.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    public function put(string $path, array $parameters = [])
    {
        return $this->http('PUT', self::API_HOST, $path, $parameters, false);
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
    public function upload(
        string $path,
        array $parameters = [],
        bool $chunked = false
    ) {
        if ($chunked) {
            return $this->uploadMediaChunked($path, $parameters);
        } else {
            return $this->uploadMediaNotChunked($path, $parameters);
        }
    }

    /**
     * Progression of media upload
     *
     * @param string $media_id
     *
     * @return array|object
     */
    public function mediaStatus(string $media_id)
    {
        return $this->http(
            'GET',
            self::UPLOAD_HOST,
            'media/upload',
            [
                'command' => 'STATUS',
                'media_id' => $media_id,
            ],
            false
        );
    }

    /**
     * Private method to upload media (not chunked) to upload.twitter.com.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    private function uploadMediaNotChunked(string $path, array $parameters)
    {
        if (
            !is_readable($parameters['media']) ||
            ($file = file_get_contents($parameters['media'])) === false
        ) {
            throw new \InvalidArgumentException(
                'You must supply a readable file'
            );
        }
        $parameters['media'] = base64_encode($file);
        return $this->http(
            'POST',
            self::UPLOAD_HOST,
            $path,
            $parameters,
            false
        );
    }

    /**
     * Private method to upload media (chunked) to upload.twitter.com.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    private function uploadMediaChunked(string $path, array $parameters)
    {
        $init = $this->http(
            'POST',
            self::UPLOAD_HOST,
            $path,
            $this->mediaInitParameters($parameters),
            false
        );
        // Append
        $segmentIndex = 0;
        $media = fopen($parameters['media'], 'rb');
        while (!feof($media)) {
            $this->http(
                'POST',
                self::UPLOAD_HOST,
                'media/upload',
                [
                    'command' => 'APPEND',
                    'media_id' => $init->media_id_string,
                    'segment_index' => $segmentIndex++,
                    'media_data' => base64_encode(
                        fread($media, $this->chunkSize)
                    ),
                ],
                false
            );
        }
        fclose($media);
        // Finalize
        $finalize = $this->http(
            'POST',
            self::UPLOAD_HOST,
            'media/upload',
            [
                'command' => 'FINALIZE',
                'media_id' => $init->media_id_string,
            ],
            false
        );
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
    private function mediaInitParameters(array $parameters): array
    {
        $allowed_keys = [
            'media_type',
            'additional_owners',
            'media_category',
            'shared',
        ];
        $base = [
            'command' => 'INIT',
            'total_bytes' => filesize($parameters['media']),
        ];
        $allowed_parameters = array_intersect_key(
            $parameters,
            array_flip($allowed_keys)
        );
        return array_merge($base, $allowed_parameters);
    }

    /**
     * Cleanup any parameters that are known not to work.
     *
     * @param array  $parameters
     *
     * @return array
     */
    private function cleanUpParameters(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            // PHP coerces `true` to `"1"` which some Twitter APIs don't like.
            if (is_bool($value)) {
                $parameters[$key] = var_export($value, true);
            }
        }
        return $parameters;
    }

    /**
     * @param string $method
     * @param string $host
     * @param string $path
     * @param array  $parameters
     * @param bool   $json
     *
     * @return array|object
     */
    private function http(
        string $method,
        string $host,
        string $path,
        array $parameters,
        bool $json
    ) {
        $this->resetLastResponse();
        $this->resetAttemptsNumber();
        $url = sprintf('%s/%s/%s.json', $host, self::API_VERSION, $path);
        $this->response->setApiPath($path);
        if (!$json) {
            $parameters = $this->cleanUpParameters($parameters);
        }
        return $this->makeRequests($url, $method, $parameters, $json);
    }

    /**
     *
     * Make requests and retry them (if enabled) in case of Twitter's problems.
     *
     * @param string $method
     * @param string $url
     * @param string $method
     * @param array  $parameters
     * @param bool   $json
     *
     * @return array|object
     */
    private function makeRequests(
        string $url,
        string $method,
        array $parameters,
        bool $json
    ) {
        do {
            $this->sleepIfNeeded();
            $result = $this->oAuthRequest($url, $method, $parameters, $json);
            $response = JsonDecoder::decode($result, $this->decodeJsonAsArray);
            $this->response->setBody($response);
            $this->attempts++;
            // Retry up to our $maxRetries number if we get errors greater than 500 (over capacity etc)
        } while ($this->requestsAvailable());

        return $response;
    }

    /**
     * Checks if we have to retry request if API is down.
     *
     * @return bool
     */
    private function requestsAvailable(): bool
    {
        return $this->maxRetries &&
            $this->attempts <= $this->maxRetries &&
            $this->getLastHttpCode() >= 500;
    }

    /**
     * Format and sign an OAuth / API request
     *
     * @param string $url
     * @param string $method
     * @param array  $parameters
     * @param bool   $json
     *
     * @return string
     * @throws TwitterOAuthException
     */
    private function oAuthRequest(
        string $url,
        string $method,
        array $parameters,
        bool $json = false
    ) {
        $request = Request::fromConsumerAndToken(
            $this->consumer,
            $this->token,
            $method,
            $url,
            $parameters,
            $json
        );
        if (array_key_exists('oauth_callback', $parameters)) {
            // Twitter doesn't like oauth_callback as a parameter.
            unset($parameters['oauth_callback']);
        }
        if ($this->bearer === null) {
            $request->signRequest(
                $this->signatureMethod,
                $this->consumer,
                $this->token
            );
            $authorization = $request->toHeader();
            if (array_key_exists('oauth_verifier', $parameters)) {
                // Twitter doesn't always work with oauth in the body and in the header
                // and it's already included in the $authorization header
                unset($parameters['oauth_verifier']);
            }
        } else {
            $authorization = 'Authorization: Bearer ' . $this->bearer;
        }
        return $this->request(
            $request->getNormalizedHttpUrl(),
            $method,
            $authorization,
            $parameters,
            $json
        );
    }

    /**
     * Set Curl options.
     *
     * @return array
     */
    private function curlOptions(): array
    {
        $bundlePath = CaBundle::getSystemCaRootBundlePath();
        $options = [
            // CURLOPT_VERBOSE => true,
            CURLOPT_CONNECTTIMEOUT => $this->connectionTimeout,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgent,
            $this->curlCaOpt($bundlePath) => $bundlePath,
        ];

        if ($this->gzipEncoding) {
            $options[CURLOPT_ENCODING] = 'gzip';
        }

        if (!empty($this->proxy)) {
            $options[CURLOPT_PROXY] = $this->proxy['CURLOPT_PROXY'];
            $options[CURLOPT_PROXYUSERPWD] =
                $this->proxy['CURLOPT_PROXYUSERPWD'];
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
     * @param bool $json
     *
     * @return string
     * @throws TwitterOAuthException
     */
    private function request(
        string $url,
        string $method,
        string $authorization,
        array $postfields,
        bool $json = false
    ): string {
        $options = $this->curlOptions();
        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_HTTPHEADER] = [
            'Accept: application/json',
            $authorization,
            'Expect:',
        ];

        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                $options[CURLOPT_POST] = true;
                if ($json) {
                    $options[CURLOPT_HTTPHEADER][] =
                        'Content-type: application/json';
                    $options[CURLOPT_POSTFIELDS] = json_encode($postfields);
                } else {
                    $options[CURLOPT_POSTFIELDS] = Util::buildHttpQuery(
                        $postfields
                    );
                }
                break;
            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                break;
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                break;
        }

        if (
            in_array($method, ['GET', 'PUT', 'DELETE']) &&
            !empty($postfields)
        ) {
            $options[CURLOPT_URL] .= '?' . Util::buildHttpQuery($postfields);
        }

        $curlHandle = curl_init();
        curl_setopt_array($curlHandle, $options);
        $response = curl_exec($curlHandle);

        // Throw exceptions on cURL errors.
        if (curl_errno($curlHandle) > 0) {
            $error = curl_error($curlHandle);
            $errorNo = curl_errno($curlHandle);
            curl_close($curlHandle);
            throw new TwitterOAuthException($error, $errorNo);
        }

        $this->response->setHttpCode(
            curl_getinfo($curlHandle, CURLINFO_HTTP_CODE)
        );
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
    private function parseHeaders(string $header): array
    {
        $headers = [];
        foreach (explode("\r\n", $header) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(': ', $line);
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
    private function encodeAppAuthorization(Consumer $consumer): string
    {
        $key = rawurlencode($consumer->key);
        $secret = rawurlencode($consumer->secret);
        return base64_encode($key . ':' . $secret);
    }

    /**
     * Get Curl CA option based on whether the given path is a directory or file.
     *
     * @param string $path
     * @return int
     */
    private function curlCaOpt(string $path): int
    {
        return is_dir($path) ? CURLOPT_CAPATH : CURLOPT_CAINFO;
    }
}
