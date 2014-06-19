<?php

/**
 * @file
 * A single location to store configuration.
 * Set dev for local development
 * set the $dev variable to switch from the config for production or development machine
 */
$dev = true;
if ($dev){
 define('CONSUMER_KEY',    'CONSUMER_KEY_HERE');
 define('CONSUMER_SECRET', 'CONSUMER_SECRET_HERE');
 define('OAUTH_CALLBACK',  'http://127.0.0.1/callback.php');
} else {
 define('CONSUMER_KEY',    'CONSUMER_KEY_HERE');
 define('CONSUMER_SECRET', 'CONSUMER_SECRET_HERE');
 define('OAUTH_CALLBACK',  'http://example/callback.php');
}
