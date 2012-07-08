<?php
/**
 * @file
 * Take the user when they return from Twitter. Get access tokens.
 * Verify credentials and redirect to based on response from Twitter.
 */

session_start(); // Starts the session
require_once __DIR__ . '/../boot.php'; // Includes the autoloader
require_once 'config.php'; // Includes the configuration

use \Abraham\TwitterOAuth\OAuth\OAuthConsumer;
use \Abraham\TwitterOAuth\OAuth\OAuthToken;
use \Abraham\TwitterOAuth\TwitterOAuth;

if (isset($_REQUEST['oauth_token'])
    && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
    $_SESSION['oauth_status'] = 'oldtoken';
    header('Location: ./clearsessions.php'); // If the oauth_token is old redirect to the connect page.
}

$connection = new TwitterOAuth( // Create TwitteroAuth object with app key/secret and token key/secret from default phase
    new OAuthConsumer(CONSUMER_KEY, CONSUMER_SECRET),
    new OAuthToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret'])
);

$accessToken = $connection->getAccessToken($_REQUEST['oauth_verifier']); // Request access tokens from twitter

$_SESSION['access_token'] = $accessToken; // Save the access tokens. Normally these would be saved in a database for future use

unset($_SESSION['oauth_token']); // Remove no longer needed request tokens
unset($_SESSION['oauth_token_secret']);

if ($connection->lastStatusCode() == 200) {
    $_SESSION['status'] = 'verified';
    header('Location: ./index.php'); // The user has been verified and the access tokens can be saved for future use
    exit();
}

header('Location: ./clearsessions.php'); // Save HTTP status for error dialog on connnect page.
