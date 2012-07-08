<?php
namespace Abraham\TwitterOAuth;

use \Abraham\TwitterOAuth\OAuth\OAuthHmacSha1Signature;
use \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod;
use \Abraham\TwitterOAuth\OAuth\OAuthConsumer;
use \Abraham\TwitterOAuth\OAuth\OAuthRequest;
use \Abraham\TwitterOAuth\OAuth\OAuthToken;
use \Abraham\TwitterOAuth\OAuth\OAuthUtil;

class TwitterOAuth
{
    /**
     * @var string
     */
    const API_ROOT = 'https://api.twitter.com/1/';

    /**
     * @var string
     */
    const ACCESS_TOKEN_URL = 'https://api.twitter.com/oauth/access_token';

    /**
     * @var string
     */
    const AUTHENTICATE_URL = 'https://api.twitter.com/oauth/authenticate';

    /**
     * @var string
     */
    const AUTHORIZE_URL = 'https://api.twitter.com/oauth/authorize';

    /**
     * @var string
     */
    const REQUEST_TOKEN_URL = 'https://api.twitter.com/oauth/request_token';

    /**
     * @var \Abraham\TwitterOAuth\HttpClient
     */
    private $client;

    /**
     * Response format
     *
     * @var string
     */
    public $format;

    /**
     * Decode returned json data.
     *
     * @var boolean
     */
    public $decodeJson;

    /**
     * @var \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod
     */
    public $signMethod;

    /**
     * @var \Abraham\TwitterOAuth\OAuth\OAuthConsumer
     */
    private $consumer;

    /**
     * @var \Abraham\TwitterOAuth\OAuth\OAuthToken
     */
    private $token;

    /**
     * Construct TwitterOAuth object
     *
     * @param \Abraham\TwitterOAuth\OAuth\OAuthConsumer $consumer
     * @param \Abraham\TwitterOAuth\OAuth\OAuthToken $token
     * @param \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod $signMethod
     * @param \Abraham\TwitterOAuth\HttpClient $httpClient
     */
    public function __construct(
        OAuthConsumer $consumer,
        OAuthToken $token = null,
        OAuthSignatureMethod $signMethod = null,
        HttpClient $httpClient = null
    ) {
        $this->signMethod = $signMethod ?: new OAuthHmacSha1Signature();
        $this->client = $httpClient ?: new HttpClient();
        $this->consumer = $consumer;
        $this->format = 'json';
        $this->decodeJson = true;

        if ($token) {
            $this->token = $token;
        }
    }

    /**
     * @return integer
     */
    public function lastStatusCode()
    {
        return $this->client->getLastStatusCode();
    }

    /**
     * @return string
     */
    public function lastAPICall()
    {
        return $this->client->getLastRequestedUrl();
    }

    /**
     * Get the authorize URL
     *
     * @return string
     */
    public function getAuthorizeURL($token, $signInWithTwitter = true)
    {
        if (is_array($token)) {
            $token = $token['oauth_token'];
        }

        if (empty($signInWithTwitter)) {
            return static::AUTHORIZE_URL . '?oauth_token=' . $token;
        }

        return static::AUTHENTICATE_URL . '?oauth_token=' . $token;
    }

    /**
     * Get a request_token from Twitter
     *
     * @param string $oauthCallback
     * @return \Abraham\TwitterOAuth\OAuth\OAuthConsumer
     */
    public function getRequestToken($oauthCallback = null)
    {
        $parameters = array();

        if (!empty($oauthCallback)) {
            $parameters['oauth_callback'] = $oauthCallback;
        }

        $request = $this->oAuthRequest(
            static::REQUEST_TOKEN_URL,
            'GET',
            $parameters
        );

        $token = OAuthUtil::parseParameters($request);

        $this->token = new OAuthToken(
            $token['oauth_token'],
            $token['oauth_token_secret']
        );

        return $token;
    }

    /**
     * Exchange request token and secret for an access token and
     * secret, to sign API calls.
     *
     * @return array array('oauth_token' => 'the-access-token',
     *         'oauth_token_secret' => 'the-access-secret',
     *         'user_id' => '9436992',
     *         'screen_name' => 'abraham')
     */
    public function getAccessToken($oauthVerifier = null)
    {
        $parameters = array();

        if (!empty($oauthVerifier)) {
            $parameters['oauth_verifier'] = $oauthVerifier;
        }

        $request = $this->oAuthRequest(
            static::ACCESS_TOKEN_URL,
            'GET',
            $parameters
        );

        $token = OAuthUtil::parseParameters($request);

        $this->token = new OAuthToken(
            $token['oauth_token'],
            $token['oauth_token_secret']
        );

        return $token;
    }

    /**
     * One time exchange of username and password for access token and secret.
     *
     * @return array array('oauth_token' => 'the-access-token',
     *         'oauth_token_secret' => 'the-access-secret',
     *         'user_id' => '9436992',
     *         'screen_name' => 'abraham',
     *         'x_auth_expires' => '0')
     */
    public function getXAuthToken($username, $password)
    {
        $parameters = array();
        $parameters['x_auth_username'] = $username;
        $parameters['x_auth_password'] = $password;
        $parameters['x_auth_mode'] = 'client_auth';

        $request = $this->oAuthRequest(
            static::ACCESS_TOKEN_URL,
            'POST',
            $parameters
        );

        $params = OAuthUtil::parseParameters($request);

        $this->token = new OAuthConsumer(
            $params['oauth_token'],
            $params['oauth_token_secret']
        );

        return $params;
    }

    /**
     * GET wrapper for oAuthRequest.
     */
    public function get($url, array $parameters = array())
    {
        $response = $this->oAuthRequest($url, 'GET', $parameters);

        if ($this->format === 'json' && $this->decodeJson) {
            return json_decode($response);
        }

        return $response;
    }

    /**
     * POST wrapper for oAuthRequest.
     */
    public function post($url, array $parameters = array())
    {
        $response = $this->oAuthRequest($url, 'POST', $parameters);

        if ($this->format === 'json' && $this->decodeJson) {
            return json_decode($response);
        }

        return $response;
    }

    /**
     * DELETE wrapper for oAuthReqeust.
     */
    public function delete($url, array $parameters = array())
    {
        $response = $this->oAuthRequest($url, 'DELETE', $parameters);

        if ($this->format === 'json' && $this->decodeJson) {
            return json_decode($response);
        }

        return $response;
    }

    /**
     * Format and sign an OAuth / API request
     */
    private function oAuthRequest($url, $method, array $parameters)
    {
        if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
            $url = static::API_ROOT . $url . '.' . $this->format;
        }

        $request = OAuthRequest::fromConsumerAndToken(
            $this->consumer,
            $this->token,
            $method,
            $url,
            $parameters
        );

        $request->signRequest($this->signMethod, $this->consumer, $this->token);

        if ($method == 'GET') {
            return $this->http($request->toUrl(), $method);
        }

        return $this->http(
            $request->getNormalizedHttpUrl(),
            $method,
            $request->toPostData()
        );
    }

    /**
     * Make an HTTP request
     *
     * @return string
     */
    private function http($url, $method, $postfields = null)
    {
        if ($method == 'GET') {
            return $this->client->get($url);
        }

        if ($method == 'POST') {
            return $this->client->post($url, $postfields);
        }

        return $this->client->delete($url, $postfields);
    }
}