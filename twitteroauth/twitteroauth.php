<?php
/*
 * Abraham Williams (abraham@abrah.am) http://abrah.am
 * Cristiano Diniz da Silva (@mcloide) http://mcloide.com
 *
 * The first PHP Library to support OAuth for Twitter's REST API.
 * Slightly modified to support other OAuth's URL's that have similar funcionality as Twitter REST API
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
	protected $host = "https://api.twitter.com/1.1/";

	/* Set up the API access token url. */
	protected $accessTokenUrl = 'https://api.twitter.com/oauth/access_token';

	/* Set up the API authenticate url . */
	protected $authenticateUrl = 'https://api.twitter.com/oauth/authenticate';

	/* Set up the API authorize url. */
	protected $authorizeUrl = 'https://api.twitter.com/oauth/authorize';

	/* Set up the API request token url. */
	protected $requestTokenUrl = 'https://api.twitter.com/oauth/request_token';

	/* Set timeout default. */
	public $timeout = 30;

	/* Set connect timeout. */
	public $connecttimeout = 30;

	/* Verify SSL Cert. */
	public $ssl_verifypeer = FALSE;

	/* Respons format. */
	public $format = 'json';

	/* Decode returned json data. */
	public $decode_json = TRUE;

	/* Contains the last HTTP headers returned. */
	public $http_info;

	/* Set the useragnet. */
	public $useragent = 'TwitterOAuth v0.2.0-beta2';


	/**
	 * Constructor
	 *
	 * @param string $consumer_key
	 * @param string $consumer_secret
	 * @param string $oauth_token (optional)
	 * @param string $oauth_token_secret (optional)
	 *
	 * @return void
	 */
	public function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
		$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);

		$this->token = (!empty($oauth_token) && !empty($oauth_token_secret)) ? new OAuthConsumer($oauth_token, $oauth_token_secret) : NULL;
	}


	/**
	 * Will return the access token url
	 *
	 * @return string
	 * @deprecated
	 */
	public function accessTokenURL() {
		return $this->getUrl('access');
	}

	/**
	 * will return the authentication url
	 *
	 * @return string
	 * @deprecated
	 */
	public function authenticateURL() {
		return $this->getUrl('authenticate');
	}

	/**
	 * Will return the authorization url
	 *
	 * @return string
	 * @deprecated
	 */
	public function authorizeURL() {
		return $this->getUrl('authorize');
	}

	/**
	 * Will return the request token url
	 *
	 * @return string
	 * @deprecated
	 */
	public function requestTokenURL() {
		return $this->getUrl('request_token');
	}

	/**
	 * Will return the url for a given type
	 *
	 * @param string $type Default's to access. Values: <access_token>, <authenticate>, <authorize> and <request_token>
	 * @return string
	 */
	public function getUrl($type = 'access_token') {
		$url = '';
		switch ($type) {
			case 'access_token':
				$url = (!empty($this->accessTokenUrl)) ? $this->accessTokenUrl : 'https://api.twitter.com/oauth/access_token';
				break;
			case 'authenticate':
				(!empty($this->authenticateUrl)) ? $this->authenticateUrl : 'https://api.twitter.com/oauth/authenticate';
				break;
			case 'authorize':
				$url = (!empty($this->authorizeUrl)) ? $this->authorizeUrl : 'https://api.twitter.com/oauth/authorize';
				break;
			case 'request_token':
				$url = (!empty($this->requestTokenUrl)) ? $this->requestTokenUrl : 'https://api.twitter.com/oauth/request_token';
				break;
			default:
				throw new \Exception('Invalid url type');
		}
		return $url;
	}

	/**
	 * Will set one of the following params: <host>, <accessTokenUrl>, <authenticateUrl>, <authorizeUrl>, <requestTokenUrl>
	 *
	 * @param string $param Accepted values: <host>, <accessTokenUrl>, <authenticateUrl>, <authorizeUrl>, <requestTokenUrl>
	 * @param string $value (optional) If empty will set the param with null
	 * 
	 * @return void
	 * @throws Exception if the param passed is not an string or if the param is not part of the list
	 */
	public function setParam($param, $value = null) {
		$params = array('host', 'accessTokenUrl', 'authenticateUrl', 'authorizeUrl', 'requestTokenUrl');
		
		if (empty($param) || !is_string($param) || !in_array($param, $params)) {
			throw new \Exception('Unable to set the OAuth information.');
		}

		$value = (empty($value) || !is_string($value)) ? null : $value;

		$this->$param = $value;
	}

	/**
	 * Debug helper to get the last Status Code
	 *
	 * @return mixed
	 */
	function lastStatusCode() {
		return $this->http_status;
	}

	/**
	 * Debug helper to get the last api call made
	 *
	 * @return string
	 */
	public function lastAPICall() {
		return $this->last_api_call;
	}

	/**
	 * Get a request_token from Twitter
	 *
	 * @returns a key/value array containing oauth_token and oauth_token_secret
	 * @throws Exception if the OAuth request fails
	 */
	public function getRequestToken($oauth_callback) {
		$request = $this->oAuthRequest($this->requestTokenURL(), 'GET', array('oauth_callback' => $oauth_callback));

		$token = OAuthUtil::parse_parameters($request);

		if (empty($token['oauth_token']) || empty($token['oauth_token_secret'])) {
			throw new \Exception('OAuth request failed.');
		}

		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	/**
	 * Get the authorize URL
	 *
	 * @returns a string
	 */
	function getAuthorizeURL($token, $sign_in_with_twitter = TRUE) {
		$token = (is_array($token)) ? $token['oauth_token'] : $token;

		$base = (empty($sign_in_with_twitter)) ? $this->getUrl('authorize') : $this->getUrl('authenticate');
		return "{$base}?oauth_token={$token}";
	}

	/**
	 * Exchange request token and secret for an access token and
	 * secret, to sign API calls.
	 *
	 * @returns ["oauth_token" => "the-access-token",
	 *			 "oauth_token_secret" => "the-access-secret",
	 *			 "user_id" => "9436992",
	 *			 "screen_name" => "abraham"]
	 * @throws Exception if the OAuth request fails
	 */
	function getAccessToken($oauth_verifier) {
		$request = $this->oAuthRequest($this->accessTokenURL(), 'GET', array('oauth_verifier' => $oauth_verifier));

		$token = OAuthUtil::parse_parameters($request);

		if (empty($token['oauth_token']) || empty($token['oauth_token_secret'])) {
			throw new \Exception('OAuth request failed.');
		}

		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	/**
	 * One time exchange of username and password for access token and secret.
	 *
	 * @returns ["oauth_token" => "the-access-token",
	 *			 "oauth_token_secret" => "the-access-secret",
	 *			 "user_id" => "9436992",
	 *			 "screen_name" => "abraham",
	 *			 "x_auth_expires" => "0"]
	 * @throws Exception if the OAuth request fails
	 */  
	function getXAuthToken($username, $password) {
		$parameters = array('x_auth_username' => $username, 'x_auth_password' => $password, 'x_auth_mode' => 'client_auth');
		$request = $this->oAuthRequest($this->accessTokenURL(), 'POST', $parameters);
		$token = OAuthUtil::parse_parameters($request);

		if (empty($token['oauth_token']) || empty($token['oauth_token_secret'])) {
			throw new \Exception('OAuth request failed.');
		}

		$this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}

	/**
	 * GET wrapper for oAuthRequest.
	 *
	 * @param string $url
	 * @param array $parameters (optional)
	 * @return mixed $response
	 */
	function get($url, $parameters = array()) {
		$response = $this->oAuthRequest($url, 'GET', $parameters);
		return ($this->format === 'json' && $this->decode_json) ? json_decode($response) : $response;
	}
  
	/**
	 * POST wrapper for oAuthRequest.
	 *
	 * @param string $url
	 * @param array $parameters (optional)
	 * @return mixed $response
	 */
	function post($url, $parameters = array()) {
		$response = $this->oAuthRequest($url, 'POST', $parameters);
		return ($this->format === 'json' && $this->decode_json) ? json_decode($response) : $response;
	}

	/**
	 * DELETE wrapper for oAuthReqeust.
	 *
	 * @param string $url
	 * @param array $parameters (optional)
	 * @return mixed $response
	 */
	function delete($url, $parameters = array()) {
		$response = $this->oAuthRequest($url, 'DELETE', $parameters);
		return ($this->format === 'json' && $this->decode_json) ? json_decode($response) : $response;
	}

	/**
	 * Format and sign an OAuth / API request
	 *
	 * @param string $url
	 * @param string $method
	 * @param array $parameters (optional)
	 * 
	 * @return mixed $response
	 */
	function oAuthRequest($url, $method, $parameters) {
		if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
			$url = "{$this->host}{$url}.{$this->format}";
		}

		$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
		$request->sign_request($this->sha1_method, $this->consumer, $this->token);

		return ($method == 'GET') ?
			$this->http($request->to_url(), 'GET') :
			$this->http($request->get_normalized_http_url(), $method, $request->to_postdata());
	}

	/**
	 * Make an HTTP request
	 *
	 * @param string $url
	 * @param string $method
	 * @param string $postfields
	 * 
	 * @return mixed API results
	 */
	function http($url, $method, $postfields = NULL) {
		$this->http_info = array();

		$ci = curl_init();
		/* Curl settings */
		curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->ssl_verifypeer);
		curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
		curl_setopt($ci, CURLOPT_HEADER, FALSE);

		switch ($method) {
			case 'POST':
				curl_setopt($ci, CURLOPT_POST, TRUE);
				if (!empty($postfields)) {
					curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
				}
			break;
			case 'DELETE':
				curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if (!empty($postfields)) {
					$url = "{$url}?{$postfields}";
				}
		}

		curl_setopt($ci, CURLOPT_URL, $url);
		$response = curl_exec($ci);

		$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ci));
		$this->url = $url;
		curl_close ($ci);

		return $response;
	}

	/**
	 * Get the header info to store.
	 *
	 * @param mixed CURL handler
	 * @param mixed $header
	 *
	 * @return integer
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