<?php

namespace Abraham\TwitterOAuth;
use Abraham\TwitterOAuth\OAuth;
/*
 * Abraham Williams (abraham@abrah.am) https://abrah.am
 *
 * The first PHP Library to support OAuth 1.0A for Twitter's REST API.
 */

/* Load OAuth lib. You can find it at http://oauth.net */
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'OAuth.php');

/**
 * Generic exception class
 */
class TwitterOAuthException extends \Exception {
  // pass
}

/**
 * Twitter OAuth class
 */
class TwitterOAuth {
  /* Set up the API root URL. */
  private $api_host = "https://api.twitter.com";
  /* Set up the API root URL. */
  private $api_version = "1.1";
  /* Set timeout default. */
  private $timeout = 5;
  /* Set connect timeout. */
  private $connecttimeout = 5; 
  /* Decode returned json data to an array. See http://php.net/manual/en/function.json-decode.php */
  private $decode_json_assoc = FALSE;
  /* Contains the last HTTP headers returned. */
  private $http_info;
  /* Set the useragnet. */
  private $useragent = 'TwitterOAuth v0.3.0-dev';
  /* Cache details about the most recent API request. */
  private $last_api_path;
  private $last_http_code;
  private $last_http_method;
  private $last_rate_limit;
  private $last_response;
  /* OAuth stuffs */
  private $consumer;
  private $token;
  private $sha1_method;

  /**
   * A bunch of setter.
   */
  function setApiHost($value) { $this->api_host = $value; }
  function setApiVersion($value) { $this->api_version = $value; }
  function setTimeout($value) { $this->timeout = $value; }
  function setConnectionTimeout($value) { $this->connecttimeout = $value; }
  function setDecodeJsonAssoc($value) { $this->decode_json_assoc = $value; }
  function setUserAgent($value) { $this->useragent = $value; }

  /**
   * Get info about the last request made.
   */
  function lastApiPath() { return $this->last_api_path; }
  function lastHttpCode() { return $this->last_http_code; }
  function lastHttpMethod() { return $this->last_http_method; }
  function lastRateLimit() { return $this->last_rate_limit; }
  function lastResponse() { return $this->last_response; }
  function resetLastResult() {
    $this->last_api_path = '';
    $this->last_http_code = 0;
    $this->last_http_method = '';
    $this->last_rate_limit = array();
    $this->last_response = array();
  }

  /**
   * construct TwitterOAuth object
   */
  function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $this->resetLastResult();
    $this->sha1_method = new OAuth\OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuth\OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuth\OAuthToken($oauth_token, $oauth_token_secret);
    }
  }

  /**
   * Make URLs for user browser navigation.
   */
  function url($path, $parameters) {
    $this->resetLastResult();
    $this->last_api_path = $path;
    $query = http_build_query($parameters);
    $response = "{$this->api_host}/{$path}?{$query}";
    $this->last_response = $response;
    return $response;
  }

  /**
   * Make /oauth/* requests to the API.
   */
  function oauth($path, $parameters = array()) {
    $this->resetLastResult();
    $this->last_api_path = $path;
    $url = "{$this->api_host}/{$path}";
    $result = $this->oAuthRequest($url, 'POST', $parameters);
    if ($this->lastHttpCode() == 200) {
      $response = OAuth\OAuthUtil::parse_parameters($result);
      $this->last_response = $response;
      return $response;
    } else {
      throw new TwitterOAuthException($result);
    }
  }

  /**
   * Make GET requests to the API.
   */
  function get($path, $parameters = array()) {
    $this->resetLastResult();
    $this->last_api_path = $path;
    $url = "{$this->api_host}/{$this->api_version}/{$path}.json";
    $result = $this->oAuthRequest($url, 'GET', $parameters);
    $response = json_decode($result, $this->decode_json_assoc, 512, JSON_BIGINT_AS_STRING);
    $this->last_response = $response;
    return $response;
  }
  
  /**
   * Make POST requests to the API.
   */
  function post($path, $parameters = array()) {
    $this->resetLastResult();
    $this->last_api_path = $path;
    $url = "{$this->api_host}/{$this->api_version}/{$path}.json";
    $result = $this->oAuthRequest($url, 'POST', $parameters);
    $response = json_decode($result, $this->decode_json_assoc, 512, JSON_BIGINT_AS_STRING);
    $this->last_response = $response;
    return $response;
  }

  /**
   * Format and sign an OAuth / API request
   */
  function oAuthRequest($url, $method, $parameters) {
    $this->last_http_method = $method;
    $request = OAuth\OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
    if (array_key_exists('oauth_callback', $parameters)) {
      // Twitter doesn't like oauth_callback as a parameter.
      unset($parameters['oauth_callback']);
    }
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);
    return $this->http($request->get_normalized_http_url(), $method, $request->to_header(), $parameters);
  }

  /**
   * Make an HTTP request
   *
   * @return API results
   */
  function http($url, $method, $header, $postfields) {
    /* Curl settings */
    $options = array(
      // CURLOPT_VERBOSE => TRUE,
      CURLOPT_CAINFO => __DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem',
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
        $options[CURLOPT_POSTFIELDS] = OAuth\OAuthUtil::build_http_query($postfields);
        break;
    }

    $ci = curl_init();
    curl_setopt_array($ci, $options);
    $response = curl_exec($ci);
    $this->last_http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    $this->http_info = curl_getinfo($ci);
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
