<?php

/**
* Example of how to use commandline, once PIN accepted.
*
* It takes one required param, which is the command, and one of:
*     sut:  statuses/user_timeline
*     av:   account/verify_credentials
*     up:  statuses/update: 2nd param is the tweet to post
*     sd:   statuses/destroy: 2nd param is a tweet ID
*
* (Any other command that takes no parameters can be used too.)
*/

require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

if($argc<2){
    fwrite(STDERR,"Must give at least one param: the command\n");
    exit;
    }

$command=$argv[1];
$p1=trim(array_key_exists(2,$argv)?$argv[2]:'');

if(!file_exists("oauth_success.dat")){
    fwrite(STDERR,"Please run oob2.php first. It will create \"oauth_success.dat\"\n");
    exit;
    }


//Get the keys to the user's account
$access_token=unserialize(file_get_contents("oauth_success.dat"));

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

switch($command){   //Expand out abbreviations
    case 'sut':$command='statuses/user_timeline';break;
    case 'av':$command='account/verify_credentials';break;
    case 'up':$command='statuses/update';break;
    case 'sd':$command='statuses/destroy';break;
    }

switch($command){
    case 'statuses/update':
        if(!$p1)$content="ERROR: must give 2nd param, for the tweet to send.";
        else $content = $connection->post('statuses/update', array('status' => $p1));
        break;

    case 'statuses/destroy':
        if(!$p1)$content="ERROR: give the ID of the tweet to delete. Run statuses/user_timeline to discover the id.";
        else $content =$connection->post('statuses/destroy', array('id' => $p1));
        break;

    default:    //Assume no parameters
        $content = $connection->get($command);
        break;
    }


if(is_string($content))fwrite(STDERR,$content."\n");
elseif(!$content)fwrite(STDERR,"ERROR: unrecognized command, or network problem.\n");
else print_r($content);

?>