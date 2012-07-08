<?php
namespace Abraham\TwitterOAuth\OAuth;

abstract class OAuthRsaSha1Signature extends OAuthSignatureMethod
{
    /**
     * @see \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod::getName()
     */
    public function getName()
    {
        return "RSA-SHA1";
    }

    /**
     * Up to the SP to implement this lookup of keys. Possible ideas are:
     * (1) do a lookup in a table of trusted certs keyed off of consumer
     * (2) fetch via http using a url provided by the requester
     * (3) some sort of specific discovery code based on request
     *
     * @param \Abraham\TwitterOAuth\OAuth\OAuthRequest $request
     * @return string Representation of the certificate
     */
    protected abstract function fetchPublicCert(OAuthRequest $request);

    /**
     * Up to the SP to implement this lookup of keys. Possible ideas are:
     * (1) do a lookup in a table of trusted certs keyed off of consumer
     *
     * @param \Abraham\TwitterOAuth\OAuth\OAuthRequest $request
     * @return string Representation of the certificate
     */
    protected abstract function fetchPrivateCert(OAuthRequest $request);

    /**
     * @see \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod::buildSignature()
     */
    public function buildSignature(
        OAuthRequest $request,
        OAuthConsumer $consumer,
        OAuthToken $token = null
    ) {
        $baseString = $request->getSignatureBaseString();
        $request->baseString = $baseString;

        $cert = $this->fetchPrivateCert($request); // Fetch the private key cert based on the request
        $privateKeyId = openssl_get_privatekey($cert); // Pull the private key ID from the certificate
        $ok = openssl_sign($baseString, $signature, $privateKeyId); // Sign using the key
        openssl_free_key($privateKeyId); // Release the key resource

        return base64_encode($signature);
    }

    /**
     * @see \Abraham\TwitterOAuth\OAuth\OAuthSignatureMethod::checkSignature()
     */
    public function checkSignature(
        OAuthRequest $request,
        OAuthConsumer $consumer,
        OAuthToken $token = null,
        $signature
    ) {
        $decodedSignature = base64_decode($signature);
        $baseString = $request->getSignatureBaseString();

        $cert = $this->fetchPublicCert($request); // Fetch the public key cert based on the request
        $publicKeyId = openssl_get_publickey($cert); // Pull the public key ID from the certificate
        $ok = openssl_verify($baseString, $decodedSignature, $publicKeyId); // Check the computed signature against the one passed in the query
        openssl_free_key($publicKeyId); // Release the key resource

        return $ok == 1;
    }
}