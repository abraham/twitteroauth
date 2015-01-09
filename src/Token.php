<?php
/**
 * The MIT License
 * Copyright (c) 2007 Andy Smith
 */
namespace Abraham\TwitterOAuth;

class Token
{
    // access tokens and request tokens
    public $key;
    public $secret;

    /**
     * key = the token
     * secret = the token secret
     */
    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * generates the basic string serialization of a token that a server
     * would respond to request_token and access_token calls with
     */
    function to_string()
    {
        return "oauth_token=" . Util::urlencode_rfc3986($this->key) .
               "&oauth_token_secret=" . Util::urlencode_rfc3986($this->secret);
    }

    function __toString()
    {
        return $this->to_string();
    }
}
