<?php
namespace Abraham\TwitterOAuth\OAuth;

class OAuthPlainTextSignature extends OAuthSignatureMethod
{
    /**
     * @see \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod::getName()
     */
    public function getName()
    {
        return "PLAINTEXT";
    }

    /**
     * oauth_signature is set to the concatenated encoded values of the Consumer
     * Secret and
     * Token Secret, separated by a '&' character (ASCII code 38), even if
     * either secret is
     * empty.
     * The result MUST be encoded again.
     * - Chapter 9.4.1 ("Generating Signatures")
     *
     * Please note that the second encoding MUST NOT happen in the
     * SignatureMethod, as
     * OAuthRequest handles this!
     *
     * @see \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod::buildSignature()
     */
    public function buildSignature(
        OAuthRequest $request,
        OAuthConsumer $consumer,
        OAuthToken $token = null
    ) {
        $keyParts = $keyParts = array(
            $consumer->secret,
            $token !== null ? $token->secret : ''
        );

        $request->baseString = implode(
            '&',
            OAuthUtil::rfc3986Encode($keyParts)
        );

        return $request->baseString;
    }
}