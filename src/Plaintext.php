<?php
/**
 * The MIT License
 * Copyright (c) 2007 Andy Smith
 */
namespace Abraham\TwitterOAuth;

/**
 * The PLAINTEXT method does not provide any security protection and SHOULD only be used
 * over a secure channel such as HTTPS. It does not use the Signature Base String.
 *   - Chapter 9.4 ("PLAINTEXT")
 *
 * @author Abraham Williams <abraham@abrah.am>
 */
class Plaintext extends SignatureMethod
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return "PLAINTEXT";
    }

    /**
     * oauth_signature is set to the concatenated encoded values of the Consumer Secret and
     * Token Secret, separated by a '&' character (ASCII code 38), even if either secret is
     * empty. The result MUST be encoded again.
     *   - Chapter 9.4.1 ("Generating Signatures")
     *
     * Please note that the second encoding MUST NOT happen in the SignatureMethod, as
     * OAuthRequest handles this!
     *
     * {@inheritDoc}
     */
    public function buildSignature(Request $request, Consumer $consumer, Token $token = null)
    {
        $parts = array($consumer->secret, null !== $token ? $token->secret : "");

        $parts = Util::urlencodeRfc3986($parts);
        $key = implode('&', $parts);
        $request->baseString = $key;

        return $key;
    }
}
