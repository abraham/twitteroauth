<?php
// require twitterOAuth lib
require_once('twitteroauth/twitterOAuth.php');

/* Sessions are used to keep track of tokens while user authenticates with twitter */
session_start();
/* Consumer key from twitter */
$consumer_key = '';
/* Consumer Secret from twitter */
$consumer_secret = '';
/* Set up placeholder */
$content = NULL;
/* Set state if previous session */
$state = $_SESSION['oauth_state'];
/* Checks if oauth_token is set from returning from twitter */
$session_token = $_SESSION['oauth_request_token'];
/* Checks if oauth_token is set from returning from twitter */
$oauth_token = $_REQUEST['oauth_token'];
/* Set section var */
$section = $_REQUEST['section'];

/* Clear PHP sessions */
if ($_REQUEST['test'] === 'clear') {/*{{{*/
  session_destroy();
  session_start();
}/*}}}*/

/* If oauth_token is missing get it */
if ($_REQUEST['oauth_token'] != NULL && $_SESSION['oauth_state'] === 'start') {/*{{{*/
  $_SESSION['oauth_state'] = $state = 'returned';
}/*}}}*/

/*
 * Switch based on where in the process you are
 *
 * 'default': Get a request token from twitter for new user
 * 'returned': The user has authorize the app on twitter
 */
switch ($state) {/*{{{*/
  default:
    /* Create TwitterOAuth object with app key/secret */
    $to = new TwitterOAuth($consumer_key, $consumer_secret);
    /* Request tokens from twitter */
    $tok = $to->getRequestToken();

    /* Save tokens for later */
    $_SESSION['oauth_request_token'] = $token = $tok['oauth_token'];
    $_SESSION['oauth_request_token_secret'] = $tok['oauth_token_secret'];
    $_SESSION['oauth_state'] = "start";

    /* Build the authorization URL */
    $request_link = $to->getAuthorizeURL($token);

    /* Build link that gets user to twitter to authorize the app */
    $content = 'Click on the link to go to twitter to authorize your account.';
    $content .= '<br /><a href="'.$request_link.'">'.$request_link.'</a>';
    break;
  case 'returned':
    /* If the access tokens are already set skip to the API call */
    if ($_SESSION['oauth_access_token'] === NULL && $_SESSION['oauth_access_token_secret'] === NULL) {
      /* Create TwitterOAuth object with app key/secret and token key/secret from default phase */
      $to = new TwitterOAuth($consumer_key, $consumer_secret, $_SESSION['oauth_request_token'], $_SESSION['oauth_request_token_secret']);
      /* Request access tokens from twitter */
      $tok = $to->getAccessToken();

      /* Save the access tokens. Normally these would be saved in a database for future use. */
      $_SESSION['oauth_access_token'] = $tok['oauth_token'];
      $_SESSION['oauth_access_token_secret'] = $tok['oauth_token_secret'];
    }
    /* Random copy */
    $content = 'your account should now be registered with twitter. Check here:<br />';
    $content .= '<a href="https://twitter.com/account/connections">https://twitter.com/account/connections</a>';

    /* Create TwitterOAuth with app key/secret and user access key/secret */
    $to = new TwitterOAuth($consumer_key, $consumer_secret, $_SESSION['oauth_access_token'], $_SESSION['oauth_access_token_secret']);
    /* Run request on twitter API as user. */
    $content = $to->OAuthRequest('https://twitter.com/account/verify_credentials.xml', array(), 'POST');
    //$content = $to->OAuthRequest('https://twitter.com/statuses/update.xml', array('status' => 'Test OAuth update. #testoauth'), 'POST');
    //$content = $to->OAuthRequest('https://twitter.com/statuses/replies.xml', array(), 'POST');
    break;
}/*}}}*/
?>

<html>
  <head>
    <title>Twitter OAuth in PHP</title>
  </head>
  <body>
    <h2>Welcome to a Twitter OAuth PHP example.</h2>
    <p>This site is a basic showcase of Twitters new OAuth authentication method. Everything is saved in sessions. If you want to start over <a href='<?php echo $_SERVER['PHP_SELF']; ?>?test=clear'>clear sessions</a>.</p>

    <p>
      Get the code powering this at <a href='http://github.com/poseurtech/twitteroauth'>http://github.com/poseurtech/twitteroauth</a>
      <br />
      Read the documentation at <a href='https://docs.google.com/View?docID=dcf2dzzs_2339fzbfsf4'>https://docs.google.com/View?docID=dcf2dzzs_2339fzbfsf4</a> 
    </p>

    <p><pre><?php print_r($content); ?><pre></p>

  </body>
</html>
