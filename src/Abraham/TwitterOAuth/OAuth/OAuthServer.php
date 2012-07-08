<?php
namespace Abraham\TwitterOAuth\OAuth;

class OAuthServer
{
    /**
     * In seconds
     *
     * @var int
     */
    protected $timestampThreshold;

    /**
     * @var array
     */
    protected $signatureMethods;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var \Abraham\TwitterOAuth\OAuth\OAuthDataStore
     */
    protected $dataStore;

    /**
     * @param \Abraham\TwitterOAuth\OAuth\OAuthDataStore $dataStore
     */
    public function __construct(OAuthDataStore $dataStore)
    {
        $this->timestampThreshold = 300;
        $this->signatureMethods = array();
        $this->version = '1.0';

        $this->dataStore = $dataStore;
    }

    /**
     * @param \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod $signatureMethod
     */
    public function addSignatureMethod(OAuthSignatureMethod $signatureMethod)
    {
        $this->signatureMethods[$signatureMethod->getName()] = $signatureMethod;
    }

    /**
     * process a request_token request
     * returns the request token on success
     */
    public function fetchRequestToken(OAuthRequest $request)
    {
        $this->getVersion($request);

        $consumer = $this->getConsumer($request);
        $token = null; // no token required for the initial token request

        $this->checkSignature($request, $consumer, $token);

        // Rev A change
        $callback = $request->getParameter('oauth_callback');
        $newToken = $this->dataStore->newRequestToken($consumer, $callback);

        return $newToken;
    }

    /**
     * process an access_token request
     * returns the access token on success
     */
    public function fetchAccessToken(OAuthRequest $request)
    {
        $this->getVersion($request);

        $consumer = $this->getConsumer($request);
        $token = $this->getToken($request, $consumer, 'request'); // requires authorized request token

        $this->checkSignature($request, $consumer, $token);

        // Rev A change
        $verifier = $request->getParameter('oauth_verifier');
        $newToken = $this->dataStore->newAccessToken(
            $token,
            $consumer,
            $verifier
        );

        return $newToken;
    }

    /**
     * verify an api call, checks all the parameters
     */
    public function verifyRequest(OAuthRequest $request)
    {
        $this->getVersion($request);
        $consumer = $this->getConsumer($request);
        $token = $this->getToken($request, $consumer, 'access');
        $this->checkSignature($request, $consumer, $token);

        return array($consumer, $token);
    }

    /**
     * Returns the OAuth version
     *
     * Service Providers MUST assume the protocol version to be 1.0 if
     * this parameter is not present.
     * Chapter 7.0 ('Accessing Protected Ressources')
     *
     * @param \Abraham\TwitterOAuth\OAuth\OAuthRequest $request
     * @return string
     * @throws \Abraham\TwitterOAuth\OAuth\OAuthException
     */
    private function getVersion(OAuthRequest $request)
    {
        $version = $request->getParameter('oauth_version') ?: '1.0';

        if ($version !== $this->version) {
            throw new OAuthException(
                'OAuth version "' . $version . '" not supported'
            );
        }

        return $version;
    }

    /**
     * Figure out the signature with some defaults
     *
     * @param \Abraham\TwitterOAuth\OAuth\OAuthRequest $request
     * @return \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod
     * @throws \Abraham\TwitterOAuth\OAuth\OAuthException When no signature was found
     * @throws \Abraham\TwitterOAuth\OAuth\OAuthException When the server does not support the requested signature
     */
    private function getSignatureMethod(OAuthRequest $request)
    {
        $signatureMethod = $request->getParameter('oauth_signature_method');

        if (!$signatureMethod) {
            throw new OAuthException(
                'No signature method parameter. This parameter is required'
            );
        }

        if (!in_array($signatureMethod, array_keys($this->signatureMethods))) {
            throw new OAuthException(
                'Signature method "' . $signatureMethod . '" not supported'
                . ' try one of the following: '
                . implode(', ', array_keys($this->signatureMethods))
            );
        }

        return $this->signatureMethods[$signatureMethod];
    }

    /**
     * try to find the consumer for the provided request's consumer key
     *
     * @param \Abraham\TwitterOAuth\OAuth\OAuthRequest $request
     * @return \Abraham\TwitterOAuth\OAuth\OAuthConsumer
     * @throws \Abraham\TwitterOAuth\OAuth\OAuthException
     */
    private function getConsumer(OAuthRequest $request)
    {
        $consumerKey = $request->getParameter('oauth_consumer_key');

        if (!$consumerKey) {
            throw new OAuthException('Invalid consumer key');
        }

        $consumer = $this->dataStore->lookupConsumer($consumerKey);

        if (!$consumer) {
            throw new OAuthException('Invalid consumer');
        }

        return $consumer;
    }

    /**
     * try to find the token for the provided request's token key
     *
     * @param \Abraham\TwitterOAuth\OAuth\OAuthRequest $request
     * @param \Abraham\TwitterOAuth\OAuth\OAuthConsumer $consumer
     * @param string $tokenType
     * @return \Abraham\TwitterOAuth\OAuth\OAuthToken
     * @throws \Abraham\TwitterOAuth\OAuth\OAuthException
     */
    private function getToken(
        OAuthRequest $request,
        OAuthConsumer $consumer,
        $tokenType = 'access'
    ) {
        $token_field = $request->getParameter('oauth_token');
        $token = $this->dataStore->lookupToken($consumer, $tokenType,
                $token_field);

        if (! $token) {
            throw new OAuthException('Invalid $token_type token: $token_field');
        }

        return $token;
    }

    /**
     * all-in-one function to check the signature on a request
     * should guess the signature method appropriately
     */
    private function checkSignature(
        OAuthRequest $request,
        OAuthConsumer $consumer,
        OAuthToken $token = null
    ) {
        $timestamp = $request->getParameter('oauth_timestamp');
        $this->checkTimestamp($timestamp);

        $this->checkNonce(
            $consumer,
            $token,
            $request->getParameter('oauth_nonce'),
            $timestamp
        );

        $signatureMethod = $this->getSignatureMethod($request);

        $signature = $request->getParameter('oauth_signature');
        $isValid = $signatureMethod->checkSignature(
            $request,
            $consumer,
            $token,
            $signature
        );

        if (!$isValid) {
            throw new OAuthException('Invalid signature');
        }
    }

    /**
     * check that the timestamp is new enough
     */
    private function checkTimestamp($timestamp)
    {
        if (!$timestamp) {
            throw new OAuthException(
                'Missing timestamp parameter. The parameter is required'
            );
        }

        if (abs(time() - $timestamp) > $this->timestampThreshold) {
            throw new OAuthException(
                'Expired timestamp, yours $timestamp, ours $now'
            );
        }
    }

    /**
     * check that the nonce is not repeated
     */
    private function checkNonce($consumer, $token, $nonce, $timestamp)
    {
        if ($nonce === null) {
            throw new OAuthException(
                'Missing nonce parameter. The parameter is required'
            );
        }

        $found = $this->dataStore->lookupNonce(
            $consumer,
            $token,
            $nonce,
            $timestamp
        );

        if ($found) {
            throw new OAuthException('Nonce already used: ' . $nonce);
        }
    }
}