<?php
namespace Abraham\TwitterOAuth\OAuth;

class OAuthConsumer
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
     * @var string
     */
    public $callbackUrl;

    /**
     * @param string $key
     * @param string $secret
     * @param string $callbackUrl
     */
    public function __construct($key, $secret, $callbackUrl = null)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $callback = $this->callbackUrl
                    ? ',callbackUrl=' . $this->callbackUrl
                    : '';

        return 'OAuthConsumer[key=' . $this->key
               . ',secret=' . $this->secret . $callback . ']';
    }
}