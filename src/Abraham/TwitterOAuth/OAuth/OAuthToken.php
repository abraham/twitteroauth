<?php
namespace Abraham\TwitterOAuth\OAuth;

class OAuthToken
{
    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $secret;

    /**
     * @param string $key The token
     * @param string $secret The token secret
     */
    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'oauth_token=' . OAuthUtil::rfc3986Encode($this->key)
               . '&oauth_token_secret='
               . OAuthUtil::rfc3986Encode($this->secret);
    }
}