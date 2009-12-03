<?php
/**
 * @file
 * 
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

function twitteroauth_row($method, $response, $http_code, $parameters = '') {
  echo '<tr>';
  echo "<td><b>{$method}</b></td>";
  switch ($http_code) {
    case '200':
    case '304':
      $color = 'green';
      break;
    case '400':
    case '401':
    case '403':
    case '404':
    case '406':
      $color = 'red';
      break;
    case '500':
    case '502':
    case '503':
      $color = 'orange';
      break;
    default:
      $color = 'grey';
  }
  echo "<td style='background: {$color};'>{$http_code}</td>";
  if (!is_string($response)) {
    $response = print_r($response, TRUE);
  }
  if (!is_string($parameters)) {
    $parameters = print_r($parameters, TRUE);
  }
  echo '<td>', strlen($response), '</td>';
  echo '<td>', $parameters, '</td>';
  echo '</tr><tr>';
  echo '<td colspan="4">', substr($response, 0, 400), '...</td>';
  echo '</tr>';

}

function twitteroauth_header($header) {
  echo '<tr><th colspan="4" style="background: grey;">', $header, '</th></tr>';
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
twitteroauth_header('Help Methods');

/* help/test */
twitteroauth_row('help/test', $connection->get('help/test'), $connection->http_code);


/**
 * Timeline Methods.
 */
twitteroauth_header('Timeline Methods');

/* statuses/public_timeline */
twitteroauth_row('statuses/public_timeline', $connection->get('statuses/public_timeline'), $connection->http_code);

/* statuses/public_timeline */
twitteroauth_row('statuses/home_timeline', $connection->get('statuses/home_timeline'), $connection->http_code);

/* statuses/friends_timeline */
twitteroauth_row('statuses/friends_timeline', $connection->get('statuses/friends_timeline'), $connection->http_code);

/* statuses/user_timeline */
twitteroauth_row('statuses/user_timeline', $connection->get('statuses/user_timeline'), $connection->http_code);

/* statuses/mentions */
twitteroauth_row('statuses/mentions', $connection->get('statuses/mentions'), $connection->http_code);

/* statuses/retweeted_by_me */
twitteroauth_row('statuses/retweeted_by_me', $connection->get('statuses/retweeted_by_me'), $connection->http_code);

/* statuses/retweeted_to_me */
twitteroauth_row('statuses/retweeted_to_me', $connection->get('statuses/retweeted_to_me'), $connection->http_code);

/* statuses/retweets_of_me */
twitteroauth_row('statuses/retweets_of_me', $connection->get('statuses/retweets_of_me'), $connection->http_code);


/**
 * Status Methods.
 */
twitteroauth_header('Status Methods');

/* statuses/update */
date_default_timezone_set('GMT');
$parameters = array('status' => date(DATE_RFC822));
$status = $connection->post('statuses/update', $parameters);
twitteroauth_row('statuses/update', $status, $connection->http_code, $parameters);

/* statuses/show */
$method = "statuses/show/{$status->id}";
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* statuses/destroy */
$method = "statuses/destroy/{$status->id}";
twitteroauth_row($method, $connection->delete($method), $connection->http_code);

/* statuses/retweet */
$method = 'statuses/retweet/6242973112';
twitteroauth_row($method, $connection->post($method), $connection->http_code);

/* statuses/retweets */
$method = 'statuses/retweets/6242973112';
twitteroauth_row($method, $connection->get($method), $connection->http_code);


/**
 * User Methods.
 */
twitteroauth_header('User Methods');

/* users/show */
$method = 'users/show/27831060';
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* users/search */
$parameters = array('q' => 'oauth');
twitteroauth_row('users/search', $connection->get('users/search', $parameters), $connection->http_code, $parameters);

/* statuses/friends */
$method = 'statuses/friends/27831060';
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* statuses/followers */
$method = 'statuses/followers/27831060';
twitteroauth_row($method, $connection->get($method), $connection->http_code);


/**
 * List Methods.
 */
twitteroauth_header('List Methods');

/* POST lists */
$method = "{$user->screen_name}/lists";
$parameters = array('name' => 'Twitter OAuth');
$list = $connection->post($method, $parameters);
twitteroauth_row($method, $list, $connection->http_code, $parameters);

/* POST lists id */
$method = "{$user->screen_name}/lists/{$list->id}";
$parameters = array('name' => 'Twitter OAuth List 2');
$list = $connection->post($method, $parameters);
twitteroauth_row($method, $list, $connection->http_code, $parameters);

/* GET lists */
$method = "{$user->screen_name}/lists";
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* GET lists id */
$method = "{$user->screen_name}/lists/{$list->id}";
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* DELETE list */
$method = "{$user->screen_name}/lists/{$list->id}";
twitteroauth_row($method, $connection->delete($method), $connection->http_code);

/* GET list statuses */
$method = "oauthlib/lists/4097351/statuses";
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* GET list members */
$method = "{$user->screen_name}/lists/memberships";
twitteroauth_row($method, $connection->get($method), $connection->http_code);


/* GET list subscriptions */
$method = "{$user->screen_name}/lists/subscriptions";
twitteroauth_row($method, $connection->get($method), $connection->http_code);


/**
 * List Members Methods.
 */
twitteroauth_header('List Members Methods');

/* Create temp list for list member methods. */
$method = "{$user->screen_name}/lists";
$parameters = array('name' => 'Twitter OAuth Temp');
$list = $connection->post($method, $parameters);


/* POST list members */
$parameters = array('id' => 27831060);
$method = "{$user->screen_name}/{$list->id}/members";
twitteroauth_row($method, $connection->post($method, $parameters), $connection->http_code, $parameters);

/* GET list members */
$method = "{$user->screen_name}/{$list->id}/members";
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* GET list members id */
$method = "{$user->screen_name}/{$list->id}/members/27831060";
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* DELETE list members */
$parameters = array('id' => 27831060);
$method = "{$user->screen_name}/{$list->id}/members";
twitteroauth_row($method, $connection->delete($method, $parameters), $connection->http_code, $parameters);

/* Delete the temp list */
$method = "{$user->screen_name}/lists/{$list->id}";
$connection->delete($method);


/**
 * List Subscribers Methods.
 */
twitteroauth_header('List Subscribers Methods');


/* POST list subscribers */
$method = 'oauthlib/test-list/subscribers';
twitteroauth_row($method, $connection->post($method), $connection->http_code);

/* GET list subscribers */
$method = 'oauthlib/test-list/subscribers';
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* GET list subscribers id */
$method = "oauthlib/test-list/subscribers/{$user->id}";
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* DELETE list subscribers */
$method = 'oauthlib/test-list/subscribers';
twitteroauth_row($method, $connection->delete($method), $connection->http_code);


/**
 * Direct Message Methdos.
 */
twitteroauth_header('Direct Message Methods');

/* direct_messages/new */
$parameters = array('user_id' => $user->id, 'text' => 'Testing out @oauthlib code');
$method = 'direct_messages/new';
$dm = $connection->post($method, $parameters);
twitteroauth_row($method, $dm, $connection->http_code, $parameters);

/* direct_messages */
$method = 'direct_messages';
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* direct_messages/sent */
$method = 'direct_messages/sent';
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* direct_messages/sent */
$method = "direct_messages/destroy/{$dm->id}";
twitteroauth_row($method, $connection->delete($method), $connection->http_code);


/**
 * Friendships Methods.
 */ 
twitteroauth_header('Friendships Methods');

/* friendships/create */
$method = 'friendships/create/93915746';
twitteroauth_row($method, $connection->post($method), $connection->http_code);

/* friendships/show */
$parameters = array('target_id' => 27831060);
$method = 'friendships/show';
twitteroauth_row($method, $connection->get($method, $parameters), $connection->http_code, $parameters);

/* friendships/destroy */
$method = 'friendships/destroy/93915746';
twitteroauth_row($method, $connection->post($method), $connection->http_code);


/**
 * Social Graph Methods.
 */
twitteroauth_header('Social Graph Methods');

/* friends/ids */
$method = 'friends/ids';
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* friends/ids */
$method = 'friends/ids';
twitteroauth_row($method, $connection->get($method), $connection->http_code);


/**
 * Account Methods.
 */
twitteroauth_header('Account Methods');

/* account/verify_credentials */
$method = 'account/verify_credentials';
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* account/rate_limit_status */
$method = 'account/rate_limit_status';
twitteroauth_row($method, $connection->get($method), $connection->http_code);

/* account/update_profile_colors */
$parameters = array('profile_background_color' => 'fff');
$method = 'account/update_profile_colors';
twitteroauth_row($method, $connection->post($method, $parameters), $connection->http_code, $parameters);

/* account/update_profile */
$parameters = array('location' => 'Teh internets');
$method = 'account/update_profile';
twitteroauth_row($method, $connection->post($method, $parameters), $connection->http_code, $parameters);




/**
 * OAuth Methods.
 */
twitteroauth_header('OAuth Methods');

/* oauth/request_token */
$oauth = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
twitteroauth_row('oauth/reqeust_token', $oauth->getRequestToken(), $oauth->http_code);
