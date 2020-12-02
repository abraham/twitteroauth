<?php

/**
 * The MIT License
 * Copyright (c) 2007 Andy Smith
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth;

class Request
{
    protected $parameters;
    protected $httpMethod;
    protected $httpUrl;
    protected $json;
    public static $version = '1.0';

    /**
     * Constructor
     *
     * @param string     $httpMethod
     * @param string     $httpUrl
     * @param array|null $parameters
     */
    public function __construct(
        string $httpMethod,
        string $httpUrl,
        ?array $parameters = []
    ) {
        $parameters = array_merge(
            Util::parseParameters(parse_url($httpUrl, PHP_URL_QUERY)),
            $parameters,
        );
        $this->parameters = $parameters;
        $this->httpMethod = $httpMethod;
        $this->httpUrl = $httpUrl;
    }

    /**
     * pretty much a helper function to set up the request
     *
     * @param Consumer $consumer
     * @param Token    $token
     * @param string   $httpMethod
     * @param string   $httpUrl
     * @param array    $parameters
     *
     * @return Request
     */
    public static function fromConsumerAndToken(
        Consumer $consumer,
        Token $token = null,
        string $httpMethod,
        string $httpUrl,
        array $parameters = [],
        $json = false
    ) {
        $defaults = [
            'oauth_version' => Request::$version,
            'oauth_nonce' => Request::generateNonce(),
            'oauth_timestamp' => time(),
            'oauth_consumer_key' => $consumer->key,
        ];
        if (null !== $token) {
            $defaults['oauth_token'] = $token->key;
        }

        // The json payload is not included in the signature on json requests,
        // therefore it shouldn't be included in the parameters array.
        if ($json) {
            $parameters = $defaults;
        } else {
            $parameters = array_merge($defaults, $parameters);
        }

        return new Request($httpMethod, $httpUrl, $parameters);
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setParameter(string $name, string $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    public function getParameter(string $name): ?string
    {
        return isset($this->parameters[$name])
            ? $this->parameters[$name]
            : null;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param string $name
     */
    public function removeParameter(string $name): void
    {
        unset($this->parameters[$name]);
    }

    /**
     * The request parameters, sorted and concatenated into a normalized string.
     *
     * @return string
     */
    public function getSignableParameters(): string
    {
        // Grab all parameters
        $params = $this->parameters;

        // Remove oauth_signature if present
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        return Util::buildHttpQuery($params);
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
    public function getSignatureBaseString(): string
    {
        $parts = [
            $this->getNormalizedHttpMethod(),
            $this->getNormalizedHttpUrl(),
            $this->getSignableParameters(),
        ];

        $parts = Util::urlencodeRfc3986($parts);

        return implode('&', $parts);
    }

    /**
     * Returns the HTTP Method in uppercase
     *
     * @return string
     */
    public function getNormalizedHttpMethod(): string
    {
        return strtoupper($this->httpMethod);
    }

    /**
     * parses the url and rebuilds it to be
     * scheme://host/path
     *
     * @return string
     */
    public function getNormalizedHttpUrl(): string
    {
        $parts = parse_url($this->httpUrl);

        $scheme = $parts['scheme'];
        $host = strtolower($parts['host']);
        $path = $parts['path'];

        return "$scheme://$host$path";
    }

    /**
     * Builds a url usable for a GET request
     *
     * @return string
     */
    public function toUrl(): string
    {
        $postData = $this->toPostdata();
        $out = $this->getNormalizedHttpUrl();
        if ($postData) {
            $out .= '?' . $postData;
        }
        return $out;
    }

    /**
     * Builds the data one would send in a POST request
     *
     * @return string
     */
    public function toPostdata(): string
    {
        return Util::buildHttpQuery($this->parameters);
    }

    /**
     * Builds the Authorization: header
     *
     * @return string
     * @throws TwitterOAuthException
     */
    public function toHeader(): string
    {
        $first = true;
        $out = 'Authorization: OAuth';
        foreach ($this->parameters as $k => $v) {
            if (substr($k, 0, 5) != 'oauth') {
                continue;
            }
            if (is_array($v)) {
                throw new TwitterOAuthException(
                    'Arrays not supported in headers',
                );
            }
            $out .= $first ? ' ' : ', ';
            $out .=
                Util::urlencodeRfc3986($k) .
                '="' .
                Util::urlencodeRfc3986($v) .
                '"';
            $first = false;
        }
        return $out;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toUrl();
    }

    /**
     * @param SignatureMethod $signatureMethod
     * @param Consumer        $consumer
     * @param Token           $token
     */
    public function signRequest(
        SignatureMethod $signatureMethod,
        Consumer $consumer,
        Token $token = null
    ) {
        $this->setParameter(
            'oauth_signature_method',
            $signatureMethod->getName(),
        );
        $signature = $this->buildSignature($signatureMethod, $consumer, $token);
        $this->setParameter('oauth_signature', $signature);
    }

    /**
     * @param SignatureMethod $signatureMethod
     * @param Consumer        $consumer
     * @param Token           $token
     *
     * @return string
     */
    public function buildSignature(
        SignatureMethod $signatureMethod,
        Consumer $consumer,
        Token $token = null
    ): string {
        return $signatureMethod->buildSignature($this, $consumer, $token);
    }

    /**
     * @return string
     */
    public static function generateNonce(): string
    {
        return md5(microtime() . mt_rand());
    }
}
