<?php
/**
 * Use this after running oob1.php, after you have the PIN code
 *
 * This is based on callback.php
 *
 * It takes the PIN code as first parameter.
 */


/* Load lib */
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');


if(!file_exists("oauth_token.dat")){
    fwrite(STDERR,"Please run oob1.php first. It will create \"oauth_token.dat\"\n");
    exit;
    }

if($argc!=2){
    fwrite(STDERR,"Must give exactly one param: the PIN code\n");
    exit;
    }

$pin=$argv[1];


$request_token=unserialize(file_get_contents("oauth_token.dat"));

/* Create TwitterOAuth object with app key/secret and token key/secret from default phase */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET,
    $request_token['oauth_token'],$request_token['oauth_token_secret']);

/* Request access tokens from twitter */
$access_token = $connection->getAccessToken($pin);

if($connection->http_code!=200){
    fwrite(STDERR,"Failed to getAccessToken; http error:{$connection->http_code}\n");print_r($access_token);
    exit;
    }

if(!array_key_exists('oauth_token',$access_token)){
    fwrite(STDERR,"Failed to getAccessToken:\n");print_r($access_token);
    exit;
    }

file_put_contents("oauth_success.dat",serialize($access_token));
unlink("oauth_token.dat");  //Not needed any more

print_r($access_token); //Just for debug
?>