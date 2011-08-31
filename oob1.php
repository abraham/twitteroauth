<?php
/**
* Use this script for out-of-bounds twitter oauth login (e.g. for commandline apps)
*
* It is based on redirect.php
*/

/* Start session and load library. */
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

/* Build TwitterOAuth object with client credentials. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
 
/* Get temporary credentials. */
$request_token = $connection->getRequestToken(OAUTH_CALLBACK);

file_put_contents("oauth_token.dat",serialize($request_token));

/* Build authorize URL and tell user to visit Twitter. */
$url = $connection->getAuthorizeURL($request_token['oauth_token'],$sign_in_with_twitter = TRUE); //For all from a browser
echo "Please visit this URL to authorize the app and get a PIN number:\n  $url\n";
?>