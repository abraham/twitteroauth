<?php
/**
 * @file
 * 
 */

/* Load required lib files. */
session_start();
require_once('test_functions.php');
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

/* If method is set change API call made. Test is called by default. */
$content = $connection->get('account/rate_limit_status');
echo "Current API hits remaining: {$content->remaining_hits}.";

/* Get logged in user to help with tests. */
$user = $connection->get('account/verify_credentials');

$active = FALSE;
if (empty($active) || empty($_GET['confirmed']) || $_GET['confirmed'] !== 'TRUE') {
  echo '<h1>Warning! This page will make many requests to Twitter.</h1>';
  echo '<h3>Performing these test might max out your rate limit.</h3>';
  echo '<h3>Statuses/DMs will be created and deleted. Accounts will be un/followed.</h3>';
  echo '<h3>Profile information/design will be changed.</h3>';
  echo '<h2>USE A DEV ACCOUNT!</h2>';
  echo '<h4>Before use you must set $active = TRUE in test.php</h4>';
  echo '<a href="./test.php?confirmed=TRUE">Continue</a> or <a href="./index.php">go back</a>.';
  exit;
}

/* Start table. */
echo '<br><br>';
echo '<table border="1" cellpadding="2" cellspacing="0">';
echo '<tr>';
echo '<th>API Method</th>';
echo '<th>HTTP Code</th>';
echo '<th>Response Length</th>';
echo '<th>Parameters</th>';
echo '</tr><tr>';
echo '<th colspan="4">Response Snippet</th>';
echo '</tr>';

/**
 * Help Methods.
 */
twitteroauthHeader('Help Methods');

/* help/test */
twitteroauthRow('help/test', $connection->get('help/test'), $connection->http_code);


/**
 * Timeline Methods.
 */
twitteroauthHeader('Timeline Methods');

/* statuses/public_timeline */
twitteroauthRow('statuses/public_timeline', $connection->get('statuses/public_timeline'), $connection->http_code);

/* statuses/public_timeline */
twitteroauthRow('statuses/home_timeline', $connection->get('statuses/home_timeline'), $connection->http_code);

/* statuses/friends_timeline */
twitteroauthRow('statuses/friends_timeline', $connection->get('statuses/friends_timeline'), $connection->http_code);

/* statuses/user_timeline */
twitteroauthRow('statuses/user_timeline', $connection->get('statuses/user_timeline'), $connection->http_code);

/* statuses/mentions */
twitteroauthRow('statuses/mentions', $connection->get('statuses/mentions'), $connection->http_code);

/* statuses/retweeted_by_me */
twitteroauthRow('statuses/retweeted_by_me', $connection->get('statuses/retweeted_by_me'), $connection->http_code);

/* statuses/retweeted_to_me */
twitteroauthRow('statuses/retweeted_to_me', $connection->get('statuses/retweeted_to_me'), $connection->http_code);

/* statuses/retweets_of_me */
twitteroauthRow('statuses/retweets_of_me', $connection->get('statuses/retweets_of_me'), $connection->http_code);


/**
 * Status Methods.
 */
twitteroauthHeader('Status Methods');

/* statuses/update */
date_default_timezone_set('GMT');
$parameters = array('status' => date(DATE_RFC822));
$status = $connection->post('statuses/update', $parameters);
twitteroauthRow('statuses/update', $status, $connection->http_code, $parameters);

/* statuses/show */
$method = "statuses/show/{$status->id}";
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* statuses/destroy */
$method = "statuses/destroy/{$status->id}";
twitteroauthRow($method, $connection->delete($method), $connection->http_code);

/* statuses/retweet */
$method = 'statuses/retweet/6242973112';
twitteroauthRow($method, $connection->post($method), $connection->http_code);

/* statuses/retweets */
$method = 'statuses/retweets/6242973112';
twitteroauthRow($method, $connection->get($method), $connection->http_code);


/**
 * User Methods.
 */
twitteroauthHeader('User Methods');

/* users/show */
$method = 'users/show/27831060';
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* users/search */
$parameters = array('q' => 'oauth');
twitteroauthRow('users/search', $connection->get('users/search', $parameters), $connection->http_code, $parameters);

/* statuses/friends */
$method = 'statuses/friends/27831060';
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* statuses/followers */
$method = 'statuses/followers/27831060';
twitteroauthRow($method, $connection->get($method), $connection->http_code);


/**
 * List Methods.
 */
twitteroauthHeader('List Methods');

/* POST lists */
$method = "{$user->screen_name}/lists";
$parameters = array('name' => 'Twitter OAuth');
$list = $connection->post($method, $parameters);
twitteroauthRow($method, $list, $connection->http_code, $parameters);

/* POST lists id */
$method = "{$user->screen_name}/lists/{$list->id}";
$parameters = array('name' => 'Twitter OAuth List 2');
$list = $connection->post($method, $parameters);
twitteroauthRow($method, $list, $connection->http_code, $parameters);

/* GET lists */
$method = "{$user->screen_name}/lists";
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* GET lists id */
$method = "{$user->screen_name}/lists/{$list->id}";
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* DELETE list */
$method = "{$user->screen_name}/lists/{$list->id}";
twitteroauthRow($method, $connection->delete($method), $connection->http_code);

/* GET list statuses */
$method = "oauthlib/lists/4097351/statuses";
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* GET list members */
$method = "{$user->screen_name}/lists/memberships";
twitteroauthRow($method, $connection->get($method), $connection->http_code);


/* GET list subscriptions */
$method = "{$user->screen_name}/lists/subscriptions";
twitteroauthRow($method, $connection->get($method), $connection->http_code);


/**
 * List Members Methods.
 */
twitteroauthHeader('List Members Methods');

/* Create temp list for list member methods. */
$method = "{$user->screen_name}/lists";
$parameters = array('name' => 'Twitter OAuth Temp');
$list = $connection->post($method, $parameters);


/* POST list members */
$parameters = array('id' => 27831060);
$method = "{$user->screen_name}/{$list->id}/members";
twitteroauthRow($method, $connection->post($method, $parameters), $connection->http_code, $parameters);

/* GET list members */
$method = "{$user->screen_name}/{$list->id}/members";
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* GET list members id */
$method = "{$user->screen_name}/{$list->id}/members/27831060";
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* DELETE list members */
$parameters = array('id' => 27831060);
$method = "{$user->screen_name}/{$list->id}/members";
twitteroauthRow($method, $connection->delete($method, $parameters), $connection->http_code, $parameters);

/* Delete the temp list */
$method = "{$user->screen_name}/lists/{$list->id}";
$connection->delete($method);


/**
 * List Subscribers Methods.
 */
twitteroauthHeader('List Subscribers Methods');


/* POST list subscribers */
$method = 'oauthlib/test-list/subscribers';
twitteroauthRow($method, $connection->post($method), $connection->http_code);

/* GET list subscribers */
$method = 'oauthlib/test-list/subscribers';
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* GET list subscribers id */
$method = "oauthlib/test-list/subscribers/{$user->id}";
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* DELETE list subscribers */
$method = 'oauthlib/test-list/subscribers';
twitteroauthRow($method, $connection->delete($method), $connection->http_code);


/**
 * Direct Message Methdos.
 */
twitteroauthHeader('Direct Message Methods');

/* direct_messages/new */
$parameters = array('user_id' => $user->id, 'text' => 'Testing out @oauthlib code');
$method = 'direct_messages/new';
$dm = $connection->post($method, $parameters);
twitteroauthRow($method, $dm, $connection->http_code, $parameters);

/* direct_messages */
$method = 'direct_messages';
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* direct_messages/sent */
$method = 'direct_messages/sent';
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* direct_messages/sent */
$method = "direct_messages/destroy/{$dm->id}";
twitteroauthRow($method, $connection->delete($method), $connection->http_code);


/**
 * Friendships Methods.
 */ 
twitteroauthHeader('Friendships Methods');

/* friendships/create */
$method = 'friendships/create/93915746';
twitteroauthRow($method, $connection->post($method), $connection->http_code);

/* friendships/show */
$parameters = array('target_id' => 27831060);
$method = 'friendships/show';
twitteroauthRow($method, $connection->get($method, $parameters), $connection->http_code, $parameters);

/* friendships/destroy */
$method = 'friendships/destroy/93915746';
twitteroauthRow($method, $connection->post($method), $connection->http_code);


/**
 * Social Graph Methods.
 */
twitteroauthHeader('Social Graph Methods');

/* friends/ids */
$method = 'friends/ids';
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* friends/ids */
$method = 'friends/ids';
twitteroauthRow($method, $connection->get($method), $connection->http_code);


/**
 * Account Methods.
 */
twitteroauthHeader('Account Methods');

/* account/verify_credentials */
$method = 'account/verify_credentials';
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* account/rate_limit_status */
$method = 'account/rate_limit_status';
twitteroauthRow($method, $connection->get($method), $connection->http_code);

/* account/update_profile_colors */
$parameters = array('profile_background_color' => 'fff');
$method = 'account/update_profile_colors';
twitteroauthRow($method, $connection->post($method, $parameters), $connection->http_code, $parameters);

/* account/update_profile */
$parameters = array('location' => 'Teh internets');
$method = 'account/update_profile';
twitteroauthRow($method, $connection->post($method, $parameters), $connection->http_code, $parameters);




/**
 * OAuth Methods.
 */
twitteroauthHeader('OAuth Methods');

/* oauth/request_token */
$oauth = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
twitteroauthRow('oauth/reqeust_token', $oauth->getRequestToken(), $oauth->http_code);
