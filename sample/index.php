<?php
/**
 * @file
 * User has successfully authenticated with Twitter. Access tokens saved to session and DB.
 */

session_start(); // Starts the session
require_once __DIR__ . '/../boot.php'; // Includes the autoloader
require_once 'config.php'; // Includes the configuration

use \Abraham\TwitterOAuth\OAuth\OAuthConsumer;
use \Abraham\TwitterOAuth\OAuth\OAuthToken;
use \Abraham\TwitterOAuth\TwitterOAuth;

if (empty($_SESSION['access_token'])
    || empty($_SESSION['access_token']['oauth_token'])
    || empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: ./clearsessions.php'); // If access tokens are not available redirect to connect page.
}

$accessToken = $_SESSION['access_token']; // Get user access tokens out of the session.

$connection = new TwitterOAuth( // Create a TwitterOauth object with consumer/user tokens.
    new OAuthConsumer(CONSUMER_KEY, CONSUMER_SECRET),
    new OAuthToken($accessToken['oauth_token'], $accessToken['oauth_token_secret'])
);

/* If method is set change API call made. Test is called by default. */
$content = $connection->get('account/verify_credentials');

/* Some example calls */
//$connection->get('users/show', array('screen_name' => 'abraham'));
//$connection->post('statuses/update', array('status' => date(DATE_RFC822)));
//$connection->post('statuses/destroy', array('id' => 5437877770));
//$connection->post('friendships/create', array('id' => 9436992));
//$connection->post('friendships/destroy', array('id' => 9436992));

include 'html.inc'; // Include HTML to display on the page