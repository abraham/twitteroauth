<?php
/**
 * The MIT License
 * Copyright (c) 2007 Andy Smith
 */
namespace Abraham\TwitterOAuth;

/**
 * The RSA-SHA1 signature method uses the RSASSA-PKCS1-v1_5 signature algorithm as defined in
 * [RFC3447] section 8.2 (more simply known as PKCS#1), using SHA-1 as the hash function for
 * EMSA-PKCS1-v1_5. It is assumed that the Consumer has provided its RSA public key in a
 * verified way to the Service Provider, in a manner which is beyond the scope of this
 * specification.
 *   - Chapter 9.3 ("RSA-SHA1")
 */
abstract class RsaSha1 extends SignatureMethod
{
    /**
     * {@inheritDoc}
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
     * Either way should return a string representation of the certificate
     *
     * @param Request $request
     *
     * @return string
     */
    abstract protected function fetchPublicCert(Request &$request);

    /**
     * Up to the SP to implement this lookup of keys. Possible ideas are:
     * (1) do a lookup in a table of trusted certs keyed off of consumer
     *
     * Either way should return a string representation of the certificate
     *
     * @param Request $request
     *
     * @return string
     */
    abstract protected function fetchPrivateCert(Request &$request);

    /**
     * {@inheritDoc}
     */
    public function buildSignature(Request $request, Consumer $consumer, Token $token = null)
    {
        $signatureBase = $request->getSignatureBaseString();
        $request->baseString = $signatureBase;

        // Fetch the private key cert based on the request
        $cert = $this->fetchPrivateCert($request);

        // Pull the private key ID from the certificate
        $privateKey = openssl_get_privatekey($cert);

        // Sign using the key
        openssl_sign($signatureBase, $signature, $privateKey);

        // Release the key resource
        openssl_free_key($privateKey);

        return base64_encode($signature);
    }

    /**
     * @param Request $request
     * @param Consumer $consumer
     * @param Token $token
     * @param string $signature
     *
     * @return bool
     */
    public function checkSignature(Request $request, Consumer $consumer, Token $token, $signature)
    {
        $signature = base64_decode($signature);
        $signatureBase = $request->getSignatureBaseString();

        // Fetch the public key cert based on the request
        $cert = $this->fetchPublicCert($request);

        // Pull the public key ID from the certificate
        $publicKey = openssl_get_publickey($cert);

        // Check the computed signature against the one passed in the query
        $verifyCode = openssl_verify($signatureBase, $signature, $publicKey);

        // Release the key resource
        openssl_free_key($publicKey);

        return $verifyCode === 1;
    }
}
