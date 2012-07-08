<?php
namespace Abraham\TwitterOAuth\OAuth;

class OAuthRequest
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @var string
     */
    private $httpMethod;

    /**
     * @var string
     */
    private $httpUrl;

    /**
     * @var string
     */
    public $baseString;

    /**
     * @var string
     */
    public static $version = '1.0';

    /**
     * @var string
     */
    public static $POST_INPUT = 'php://input';

    /**
     * @param string $httpMethod
     * @param string $httpUrl
     * @param array $parameters
     */
    public function __construct(
        $httpMethod,
        $httpUrl,
        array $parameters = array()
    ) {
        $parameters = array_merge(
            OAuthUtil::parseParameters(parse_url($httpUrl, PHP_URL_QUERY)),
            $parameters
        );

        $this->parameters = $parameters;
        $this->httpMethod = $httpMethod;
        $this->httpUrl = $httpUrl;
    }

    /**
     * Attempt to build up a request from what was passed to the server
     *
     * @param string $httpMethod
     * @param string $httpUrl
     * @param array $parameters
     * @return \Abraham\TwitterOAuth\OAuth\OAuthRequest
     */
    public static function fromRequest(
        $httpMethod = null,
        $httpUrl = null,
        array $parameters = null
    ) {
        $httpMethod = $httpMethod ?: $_SERVER['REQUEST_METHOD'];
        $scheme = !isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on"
                  ? 'http'
                  : 'https';

        $httpUrl = $httpUrl ?: $scheme . '://' . $_SERVER['HTTP_HOST'] . ':'
                   . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];

        if ($parameters === null) {
            $requestHeaders = OAuthUtil::getHeaders();

            $parameters = array_merge(
                OAuthUtil::parseParameters($_SERVER['QUERY_STRING']),
                static::getPostParameters($httpMethod, $requestHeaders),
                static::getHeaderParameters($httpMethod, $requestHeaders)
            );
        }

        return new static($httpMethod, $httpUrl, $parameters);
    }

    /**
     * @param array $requestHeaders
     * @return array
     */
    protected static function getHeaderParameters(array $requestHeaders)
    {
        if (isset($requestHeaders['Authorization'])
            && substr($requestHeaders['Authorization'], 0, 6) == "OAuth ") {
            return OAuthUtil::splitHeader($requestHeaders['Authorization']);
        }

        return array();
    }

    /**
     * @param string $httpMethod
     * @param array $requestHeaders
     * @return array
     */
    protected static function getPostParameters(
        $httpMethod,
        array $requestHeaders
    ) {
        if ($httpMethod != 'POST'
            || !isset($requestHeaders["Content-Type"])
            || strstr($requestHeaders["Content-Type"], "application/x-www-form-urlencoded") === false) {
            return array();
        }

        return OAuthUtil::parseParameters(
            file_get_contents(static::$POST_INPUT)
        );
    }

    /**
     * Pretty much a helper function to set up the request
     *
     * @param \Abraham\TwitterOAuth\OAuth\OAuthConsumer $consumer
     * @param \Abraham\TwitterOAuth\OAuth\OAuthToken $token
     * @param string $httpMethod
     * @param string $httpUrl
     * @param array $parameters
     * @return \Abraham\TwitterOAuth\OAuth\OAuthRequest
     */
    public static function fromConsumerAndToken(
        OAuthConsumer $consumer,
        OAuthToken $token = null,
        $httpMethod,
        $httpUrl,
        array $parameters = array()
    ) {
        $defaults = array(
            "oauth_version" => static::$version,
            "oauth_nonce" => static::generateNonce(),
            "oauth_timestamp" => static::generateTimestamp(),
            "oauth_consumer_key" => $consumer->key
        );

        if ($token !== null) {
            $defaults['oauth_token'] = $token->key;
        }

        return new static(
            $httpMethod,
            $httpUrl,
            array_merge($defaults, $parameters)
        );
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param boolean $onDuplicateStack
     * @return mixed
     */
    public function setParameter($name, $value, $onDuplicateStack = true)
    {
        if ($onDuplicateStack && isset($this->parameters[$name])) {
            if (is_scalar($this->parameters[$name])) {
                $this->parameters[$name] = (array) $this->parameters[$name];
            }

            return $this->parameters[$name][] = $value;
        }

        return $this->parameters[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getParameter($name)
    {
        return isset($this->parameters[$name])
               ? $this->parameters[$name]
               : null;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string $name
     */
    public function unsetParameter($name)
    {
        unset($this->parameters[$name]);
    }

    /**
     * The request parameters, sorted and concatenated into a normalized string.
     * Removes the oauth_signature param (following Spec: 9.1.1)
     *
     * @return string
     */
    public function getSignableParameters()
    {
        $params = $this->parameters;

        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        return OAuthUtil::buildHttpQuery($params);
    }

    /**
     * Returns the base string of this request
     *
     * The base string defined as the method, the url
     * and the parameters (normalized), each urlencoded
     * and the concated with &.
     *
     * @return string
     */
    public function getSignatureBaseString()
    {
        $parts = array(
            $this->getNormalizedHttpMethod(),
            $this->getNormalizedHttpUrl(),
            $this->getSignableParameters()
        );

        return implode('&', OAuthUtil::rfc3986Encode($parts));
    }

    /**
     * just uppercases the http method
     *
     * @return string
     */
    public function getNormalizedHttpMethod()
    {
        return strtoupper($this->httpMethod);
    }

    /**
     * parses the url and rebuilds it to be
     * scheme://host/path
     *
     * @return string
     */
    public function getNormalizedHttpUrl()
    {
        $parts = parse_url($this->httpUrl);

        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $port = isset($parts['port']) ? $parts['port'] : null;
        $path = isset($parts['path']) ? $parts['path'] : '';

        $port = $port ?: $scheme == 'https' ? '443' : '80';

        if (($scheme == 'https' && $port != '443')
            || ($scheme == 'http' && $port != '80')) {
            $host .= ':' . $port;
        }

        return $scheme . '://' . $host . $path;
    }

    /**
     * builds a url usable for a GET request
     *
     * @return string
     */
    public function toUrl()
    {
        $url = $this->getNormalizedHttpUrl();

        if ($queryString = $this->toPostData()) {
            $url .= '?' . $queryString;
        }

        return $url;
    }

    /**
     * builds the data one would send in a POST request
     *
     * @return string
     */
    public function toPostData()
    {
        return OAuthUtil::buildHttpQuery($this->parameters);
    }

    /**
     * builds the Authorization: header
     *
     * @return string
     */
    public function toHeader($realm = null)
    {
        $first = true;

        if ($realm) {
            $out = 'Authorization: OAuth realm="' .
                     OAuthUtil::urlencode_rfc3986($realm) . '"';
            $first = false;
        } else {
            $out = 'Authorization: OAuth';
        }

        foreach ($this->parameters as $key => $value) {
            if (substr($key, 0, 5) != "oauth") {
                continue;
            }

            if (is_array($value)) {
                throw new OAuthException('Arrays not supported in headers');
            }

            $out .= $first ? ' ' : ',';
            $out .= OAuthUtil::rfc3986Encode($key) . '="'
                    . OAuthUtil::rfc3986Encode($value) . '"';

            $first = false;
        }

        return $out;
    }

    /**
     * @param \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod $signatureMethod
     * @param \Abraham\TwitterOAuth\OAuth\OAuthConsumer $consumer
     * @param \Abraham\TwitterOAuth\OAuth\OAuthToken $token
     */
    public function signRequest(
        OAuthSignatureMethod $signatureMethod,
        OAuthConsumer $consumer,
        OAuthToken $token = null
    ) {
        $this->setParameter(
            'oauth_signature_method',
            $signatureMethod->getName(),
            false
        );

        $this->setParameter(
            'oauth_signature',
            $this->buildSignature($signatureMethod, $consumer, $token),
            false
        );
    }

    /**
     * @param \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod $signatureMethod
     * @param \Abraham\TwitterOAuth\OAuth\OAuthConsumer $consumer
     * @param \Abraham\TwitterOAuth\OAuth\OAuthToken $token
     */
    public function buildSignature(
        OAuthSignatureMethod $signatureMethod,
        OAuthConsumer $consumer,
        OAuthToken $token = null
    ) {
        return $signatureMethod->buildSignature($this, $consumer, $token);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toUrl();
    }

    /**
     * util function: current timestamp
     *
     * @return number
     */
    private static function generateTimestamp()
    {
        return time();
    }

    /**
     * util function: current nonce
     *
     * @return string
     */
    private static function generateNonce()
    {
        return md5(microtime() . mt_rand()); // md5s look nicer than numbers
    }
}