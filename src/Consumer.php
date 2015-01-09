<?php
/**
 * The MIT License
 * Copyright (c) 2007 Andy Smith
 */
namespace Abraham\TwitterOAuth;

class Consumer
{
    public $key;
    public $secret;

    public function __construct($key, $secret, $callback_url = null)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->callback_url = $callback_url;
    }

    public function __toString()
    {
        return "Consumer[key=$this->key,secret=$this->secret]";
    }
}
