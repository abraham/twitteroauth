<?php
namespace Abraham\TwitterOAuth\OAuth;

abstract class OAuthSignatureMethod
{
    /**
     * Needs to return the name of the Signature Method (ie HMAC-SHA1)
     *
     * @return string
     */
    public abstract function getName();

    /**
     * Build up the signature
     * NOTE: The output of this function MUST NOT be urlencoded.
     * the encoding is handled in OAuthRequest when the final
     * request is serialized
     *
     * @param \Abraham\TwitterOAuth\OAuth\OAuthRequest $request
     * @param \Abraham\TwitterOAuth\OAuth\OAuthConsumer $consumer
     * @param \Abraham\TwitterOAuth\OAuth\OAuthToken $token
     * @return string
     */
    public abstract function buildSignature(
        OAuthRequest $request,
        OAuthConsumer $consumer,
        OAuthToken $token = null
    );

    /**
     * Verifies that a given signature is correct
     *
     * @param \Abraham\TwitterOAuth\OAuth\OAuthRequest $request
     * @param \Abraham\TwitterOAuth\OAuth\OAuthConsumer $consumer
     * @param \Abraham\TwitterOAuth\OAuth\OAuthToken $token
     * @param string $signature
     * @return bool
     */
    public function checkSignature(
        OAuthRequest $request,
        OAuthConsumer $consumer,
        OAuthToken $token = null,
        $signature
    ) {
        $built = $this->buildSignature($request, $consumer, $token);

        return $built == $signature;
    }
}