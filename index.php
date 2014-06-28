<?php
/**
 * @file
 * User has successfully authenticated with Twitter. Access tokens saved to session and DB.
 */

/* Load required lib files. */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

/* If access tokens are not available redirect to connect page. */
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: ./clearsessions.php');
}
/* Get user access tokens out of the session. */
$access_token = $_SESSION['access_token'];

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

// echo '<pre>';
// print_r($connection);
// echo '</pre>';

/* If method is set change API call made. Test is called by default. */
$content = $connection->get('account/verify_credentials');

/* Some example calls */
//$connection->get('users/show', array('screen_name' => 'abraham'));
//$connection->post('statuses/update', array('status' => date(DATE_RFC822)));
//$connection->post('statuses/destroy', array('id' => 5437877770));
//$connection->post('friendships/create', array('id' => 9436992));
//$connection->post('friendships/destroy', array('id' => 9436992));

// echo '<pre>';
// print_r($content);
// echo '</pre>';



// Insert information into database here:

if(!isset($content)){
    echo "Oops, login via twitter required.";
} else {

    // Let's find the account by its ID
	$q = "SELECT * FROM `rss_twitter_users` WHERE oauth_provider = 'twitter' AND `oauth_uid` = ". $content->id_str;

	$rs = $mysqli->query($q);
    if (!$rs) {
        printf("Error: %s\n", $mysqli->error);
    }

    // If not, let's add it to the database
    if($rs->num_rows == 0){

		$q = "INSERT INTO `rss_twitter_users` (
					oauth_provider, 
					oauth_uid, 
					username, 
					oauth_token, 
					oauth_secret,
					date_created
				) 
				VALUES (
					'twitter', 
					'{$content->id_str}', 
					'{$content->screen_name}', 
					'{$connection->token->key}', 
					'{$connection->token->secret}',
					date('Y-m-d H:i:s')
				)";

	    if (!$mysqli->query($q)) {
	        printf("Error: %s\n", $mysqli->error);
	    }


    } else {
        // Update the tokens
		$q = "UPDATE `rss_twitter_users` SET 
			oauth_token = '{$connection->token->key}', 
			oauth_secret = '{$connection->token->secret}',
			date_modified = date('Y-m-d H:i:s')
			WHERE oauth_provider = 'twitter' AND oauth_uid = {$content->id_str}";

	    if (!$mysqli->query($q)) {
	        printf("Error: %s\n", $mysqli->error);
	    }
    }

    $_SESSION['twitter']['id'] = $content->id_str;
}




/* Include HTML to display on the page */
include('html.inc');
