<?php
namespace Abraham\TwitterOAuth\OAuth;

class OAuthHmacSha1Signature extends OAuthSignatureMethod
{
    /**
     * @see \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod::getName()
     */
    function getName()
    {
        return "HMAC-SHA1";
    }

    /**
     * @see \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod::buildSignature()
     */
    public function buildSignature(
        OAuthRequest $request,
        OAuthConsumer $consumer,
        OAuthToken $token = null
    ) {
        $keyParts = array(
            $consumer->secret,
            $token ? $token->secret : ''
        );

        $request->baseString = $request->getSignatureBaseString();

        return base64_encode(
            hash_hmac(
                'sha1',
                $request->baseString,
                implode('&', OAuthUtil::rfc3986Encode($keyParts)),
                true
            )
        );
    }
}