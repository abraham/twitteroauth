<?php
/* Start session and load library. */
session_start(); // Starts the session
require_once __DIR__ . '/../boot.php'; // Includes the autoloader
require_once 'config.php'; // Includes the configuration

use \Abraham\TwitterOAuth\OAuth\OAuthConsumer;
use \Abraham\TwitterOAuth\TwitterOAuth;

$connection = new TwitterOAuth(new OAuthConsumer(CONSUMER_KEY, CONSUMER_SECRET)); // Build TwitterOAuth object with client credentials.

$requestToken = $connection->getRequestToken(OAUTH_CALLBACK); // Get temporary credentials.

/* Save temporary credentials to session. */
$_SESSION['oauth_token'] = $token = $requestToken['oauth_token'];
$_SESSION['oauth_token_secret'] = $requestToken['oauth_token_secret'];

/* If last connection failed don't display authorization link. */
switch ($connection->lastStatusCode()) {
    case 200:
        /* Build authorize URL and redirect user to Twitter. */
        $url = $connection->getAuthorizeURL($token);
        header('Location: ' . $url);
        break;
    default:
        /* Show notification if something went wrong. */
         echo 'Could not connect to Twitter. Refresh the page or try again later.';
}