<?php
/*
 * Abraham Williams (abraham@abrah.am) http://abrah.am
 *
 * Basic lib to work with Twitter's OAuth beta. This is untested and should not
 * be used in production code. Twitter's beta could change at anytime.
 *
 * Code based on:
 * Fire Eagle code - http://github.com/myelin/fireeagle-php-lib
 * twitterlibphp - http://github.com/poseurtech/twitterlibphp
 */

/* Load OAuth lib. You can find it at http://oauth.net */
require_once('OAuth.php');

/**
 * Twitter OAuth class
 */
class TwitterOAuth {
  /* Contains the last HTTP status code returned */
  private $http_code;
  /* Contains the last API call */
  private $last_api_call;
  /* Set up the API root URL */
  public static $host = "https://api.twitter.com/1";
  /* Set timeout default */
  public static $timeout = 30;
  /* Set connect timeout */
  public static $connecttimeout = 30; 
  /* Verify SSL Cert */
  public static $ssl_verifypeer = FALSE;




  /**
   * Set API URLS
   */
  function accessTokenURL()  { return 'https://twitter.com/oauth/access_token'; }
  function authenticateURL() { return 'https://twitter.com/oauth/authenticate'; }
  function authorizeURL()    { return 'https://twitter.com/oauth/authorize'; }
  function requestTokenURL() { return 'https://twitter.com/oauth/request_token'; }

  /**
   * Debug helpers
   */
  function lastStatusCode() { return $this->http_status; }
  function lastAPICall() { return $this->last_api_call; }

  /**
   * construct TwitterOAuth object
   */
  function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    } else {
      $this->token = NULL;
    }
  }/*}}}*/


  /**
   * Get a request_token from Twitter
   *
   * @returns a key/value array containing oauth_token and oauth_token_secret
   */
  function getRequestToken() {
    $r = $this->oAuthRequest($this->requestTokenURL());
    $token = $this->oAuthParseResponse($r);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }/*}}}*/

  /**
   * Parse a URL-encoded OAuth response
   *
   * @return a key/value array
   */
  function oAuthParseResponse($responseString) {
    $r = array();
    foreach (explode('&', $responseString) as $param) {
      $pair = explode('=', $param, 2);
      if (count($pair) != 2) continue;
      $r[urldecode($pair[0])] = urldecode($pair[1]);
    }
    return $r;
  }

  /**
   * Get the authorize URL
   *
   * @returns a string
   */
  function getAuthorizeURL($token) {
    if (is_array($token)) $token = $token['oauth_token'];
    return $this->authorizeURL() . '?oauth_token=' . $token;
  }/*}}}*/

  /**
   * Exchange the request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @returns array("oauth_token" => the access token,
   *                "oauth_token_secret" => the access secret)
   */
  function getAccessToken($token = NULL) {
    $r = $this->oAuthRequest($this->accessTokenURL());
    $token = $this->oAuthParseResponse($r);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }/*}}}*/

  /**
   * Format and sign an OAuth / API request
   */
  function oAuthRequest($url, $args = array(), $method = NULL) {
    if (empty($method)) $method = empty($args) ? "GET" : "POST";
    $req = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $args);
    $req->sign_request($this->sha1_method, $this->consumer, $this->token);
    switch ($method) {
    case 'GET': return $this->http($req->to_url());
    case 'POST': return $this->http($req->get_normalized_http_url(), $req->to_postdata());
    }
  }/*}}}*/

  /**
   * Make an HTTP request
   *
   * @return API results
   */
  function http($url, $postfields = null) {
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
    curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
    curl_setopt($ci, CURLOPT_URL, $this->url);
    if (defined("CURL_CA_BUNDLE_PATH")) {
      curl_setopt($ci, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
    }
    if (isset($postfields)) {
      curl_setopt($ci, CURLOPT_POST, 1);
      curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
    }
    $response = curl_exec($ci);
    $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    $this->last_api_call = $url;
    curl_close ($ci);
    return $response;
  }
}
