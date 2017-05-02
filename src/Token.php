<?php
/**
 * The MIT License
 * Copyright (c) 2007 Andy Smith
 */

namespace Abraham\TwitterOAuth;

class Token
{
    /** @var string */
    public $key;
    /** @var string */
    public $secret;

    /**
     * @param string $key    The OAuth Token
     * @param string $secret The OAuth Token Secret
     */
    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * Generates the basic string serialization of a token that a server
     * would respond to request_token and access_token calls with
     *
     * @return string
     */
    public function __toString()
    {
        return 'oauth_token=' . Util::urlencodeRfc3986($this->key) . '&oauth_token_secret=' . Util::urlencodeRfc3986($this->secret);
    }
}
