<?php
/**
 * The MIT License
 * Copyright (c) 2007 Andy Smith
 */
namespace Abraham\TwitterOAuth;

class DataStore
{
    public function lookup_consumer($consumer_key)
    {
        // implement me
    }

    public function lookup_token($consumer, $token_type, $token)
    {
        // implement me
    }

    public function lookup_nonce($consumer, $token, $nonce, $timestamp)
    {
        // implement me
    }

    public function new_request_token($consumer, $callback = null)
    {
        // return a new token attached to this consumer
    }

    public function new_access_token($token, $consumer, $verifier = null)
    {
        // return a new access token attached to this consumer
        // for the user associated with this token if the request token
        // is authorized
        // should also invalidate the request token
    }
}
