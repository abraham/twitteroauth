<?php

namespace Abraham\TwitterOAuth;
use Abraham\TwitterOAuth\OAuth;
/*
 * Abraham Williams (abraham@abrah.am) https://abrah.am
 *
 * The first PHP Library to support OAuth 1.0A for Twitter's REST API.
 */

/* Load OAuth lib. You can find it at http://oauth.net */
require_once('OAuth.php');

/**
 * Twitter OAuth class
 */
class TwitterOAuth {
  /* Contains the last HTTP status code returned. */
  public $http_code;
  /* Contains the last API call. */
  public $url;
  /* Set up the API root URL. */
  public $host = "https://api.twitter.com/1.1/";
  /* Set timeout default. */
  public $timeout = 5;
  /* Set connect timeout. */
  public $connecttimeout = 5; 
  /* Decode returned json data to an array. See http://php.net/manual/en/function.json-decode.php */
  public $decode_json_assoc = FALSE;
  /* Contains the last HTTP headers returned. */
  public $http_info;
  /* Set the useragnet. */
  public $useragent = 'TwitterOAuth v0.3.0-dev';
  /* Immediately retry the API call if the response was not successful. */
  //public $retry = TRUE;




  /**
   * Set API URLS
   */
  function accessTokenURL()  { return 'https://api.twitter.com/oauth/access_token'; }
  function authenticateURL() { return 'https://api.twitter.com/oauth/authenticate'; }
  function authorizeURL()    { return 'https://api.twitter.com/oauth/authorize'; }
  function requestTokenURL() { return 'https://api.twitter.com/oauth/request_token'; }

  /**
   * Debug helpers
   */
  function lastStatusCode() { return $this->http_code; }
  function lastAPICall() { return $this->last_api_call; }

  /**
   * construct TwitterOAuth object
   */
  function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $this->sha1_method = new OAuth\OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuth\OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuth\OAuthConsumer($oauth_token, $oauth_token_secret);
    } else {
      $this->token = NULL;
    }
  }


  /**
   * Get a request_token from Twitter
   *
   * @returns a key/value array containing oauth_token and oauth_token_secret
   */
  function getRequestToken($oauth_callback) {
    $parameters = array();
    $parameters['oauth_callback'] = $oauth_callback; 
    $request = $this->oAuthRequest($this->requestTokenURL(), 'GET', $parameters);
    $token = OAuth\OAuthUtil::parse_parameters($request);
    $this->token = new OAuth\OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * Get the authorize URL
   *
   * @returns a string
   */
  function getAuthorizeURL($token, $sign_in_with_twitter = TRUE, $urlparams = array()) {
    if (is_array($token)) {
      $token = $token['oauth_token'];
    }
    $params = "";
    if (is_array($urlparams)) {
	    foreach ($urlparams as $urlparamkey=>$urlparamvalue) {
		    $params .= "&" . $urlparamkey . "=" . $urlparamvalue;
	    }
    }
    if (empty($sign_in_with_twitter)) {
      return $this->authorizeURL() . "?oauth_token={$token}" . $params;
    } else {
      return $this->authenticateURL() . "?oauth_token={$token}" . $params;
    }
  }

  /**
   * Exchange request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @returns array("oauth_token" => "the-access-token",
   *                "oauth_token_secret" => "the-access-secret",
   *                "user_id" => "9436992",
   *                "screen_name" => "abraham")
   */
  function getAccessToken($oauth_verifier) {
    $parameters = array();
    $parameters['oauth_verifier'] = $oauth_verifier;
    $request = $this->oAuthRequest($this->accessTokenURL(), 'GET', $parameters);
    $token = OAuth\OAuthUtil::parse_parameters($request);
    $this->token = new OAuth\OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * One time exchange of username and password for access token and secret.
   *
   * @returns array("oauth_token" => "the-access-token",
   *                "oauth_token_secret" => "the-access-secret",
   *                "user_id" => "9436992",
   *                "screen_name" => "abraham",
   *                "x_auth_expires" => "0")
   */  
  function getXAuthToken($username, $password) {
    $parameters = array();
    $parameters['x_auth_username'] = $username;
    $parameters['x_auth_password'] = $password;
    $parameters['x_auth_mode'] = 'client_auth';
    $request = $this->oAuthRequest($this->accessTokenURL(), 'POST', $parameters);
    $token = OAuth\OAuthUtil::parse_parameters($request);
    $this->token = new OAuth\OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * GET wrapper for oAuthRequest.
   */
  function get($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'GET', $parameters);
    return json_decode($response, $this->decode_json_assoc);
  }
  
  /**
   * POST wrapper for oAuthRequest.
   */
  function post($url, $parameters = array()) {
    $response = $this->oAuthRequest($url, 'POST', $parameters);
    return json_decode($response, $this->decode_json_assoc);
  }

  /**
   * Format and sign an OAuth / API request
   */
  function oAuthRequest($url, $method, $parameters) {
    $url = "{$this->host}{$url}.json";
    $request = OAuth\OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);
    return $this->http($request->get_normalized_http_url(), $method, $request->to_header(), $parameters);
  }

  /**
   * Make an HTTP request
   *
   * @return API results
   */
  function http($url, $method, $header, $postfields = NULL) {

    /* Curl settings */
    $options = array(
      // CURLOPT_VERBOSE => TRUE,
      CURLOPT_CAINFO => 'cacert.pem',
      CURLOPT_CAPATH => __DIR__,
      CURLOPT_CONNECTTIMEOUT => $this->connecttimeout,
      CURLOPT_HEADER => FALSE,
      CURLOPT_HEADERFUNCTION => array($this, 'getHeader'),
      CURLOPT_HTTPHEADER => array($header, 'Expect:'),
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_SSL_VERIFYHOST => 2,
      CURLOPT_SSL_VERIFYPEER => TRUE,
      CURLOPT_TIMEOUT => $this->timeout,
      CURLOPT_URL => $url,
      CURLOPT_USERAGENT => $this->useragent,
    );

    switch ($method) {
      case 'GET':
        if (!empty($postfields)) {
          $options[CURLOPT_URL] = $options[CURLOPT_URL] . '?' . OAuth\OAuthUtil::build_http_query($postfields);
        }
        break;
      case 'POST':
        $options[CURLOPT_POST] = TRUE;
        if (!empty($postfields)) {
          $options[CURLOPT_POSTFIELDS] = OAuth\OAuthUtil::build_http_query($postfields);
        }
        break;
    }

    $ci = curl_init();
    curl_setopt_array($ci, $options);
    $response = curl_exec($ci);
    $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    $this->http_info = curl_getinfo($ci);
    $this->url = $url;
    curl_close($ci);

    return $response;
  }

  /**
   * Get the header info to store.
   */
  function getHeader($ch, $header) {
    $i = strpos($header, ':');
    if (!empty($i)) {
      $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
      $value = trim(substr($header, $i + 2));
      $this->http_header[$key] = $value;
    }
    return strlen($header);
  }
}
