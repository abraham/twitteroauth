TwitterOAuth
------------

PHP library for working with Twitter's OAuth API.

Flow Overview
=============

1. Build TwitterOAuth object using client credentials.
2. Request temporary credentials from Twitter.
3. Build authorize URL for Twitter.
4. Redirect user to authorize URL.
5. User authorizes access and returns from Twitter.
6. Rebuild TwitterOAuth object with client credentials and temporary credentials.
7. Get token credentials from Twitter.
8. Rebuild TwitterOAuth object with client credentials and token credentials.
9. Query Twitter API.

Terminology
===========

The terminology has changed since 0.1.x to better match the draft-hammer-oauth IETF
RFC. You can read that at http://tools.ietf.org/html/draft-hammer-oauth. Some of the
terms will differ from those Twitter uses as well.

client credentials - Consumer key/secret you get when registering an app with Twitter.
temporary credentials - Previously known as the request token.
token credentials - Previously known as the access token.

Parameters
==========

There are a number of parameters you can modify after creating a TwitterOAuth object.

The latest revisions of TwitterOAuth support the Twitter API v1.1 but if you want to
update an old install from v1.0 you can do the following.

    $connection->host = "https://api.twitter.com/1.1/";

Custom useragent.

    $connection->useragent = 'Custom useragent string';

Verify Twitters SSL certificate.

    $connection->ssl_verifypeer = TRUE;

There are several more you can find in TwitterOAuth.php.

Extended flow using example code
================================

To use TwitterOAuth with the Twitter API you need *TwitterOAuth.php*, *OAuth.php* and
client credentials. You can get client credentials by registering your application at
[dev.twitter.com/apps](https://dev.twitter.com/apps).

Users start out on connect.php which displays the "Sign in with Twitter" image hyperlinked
to redirect.php. This button should be displayed on your homepage in your login section. The
client credentials are saved in config.php as `CONSUMER_KEY` and `CONSUMER_SECRET`. You can
save a static callback URL in the app settings page, in the config file or use a dynamic
callback URL later in step 2. In example use https://example.com/callback.php.

1) When a user lands on redirect.php we build a new TwitterOAuth object using the client credentials.
If you have your own configuration method feel free to use it instead of config.php.

    $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET); // Use config.php client credentials
    $connection = new TwitterOAuth('abc890', '123xyz');

2) Using the built $connection object you will ask Twitter for temporary credentials. The `oauth_callback` value is required.

    $temporary_credentials = $connection->getRequestToken(OAUTH_CALLBACK); // Use config.php callback URL.

3) Now that we have temporary credentials the user has to go to Twitter and authorize the app
to access and updates their data. You can also pass a second parameter of FALSE to not use [Sign
in with Twitter](https://dev.twitter.com/docs/auth/sign-twitter).

    $redirect_url = $connection->getAuthorizeURL($temporary_credentials); // Use Sign in with Twitter
    $redirect_url = $connection->getAuthorizeURL($temporary_credentials, FALSE);

4) You will now have a Twitter URL that you must send the user to.

    https://api.twitter.com/oauth/authenticate?oauth_token=xyz123

5) The user is now on twitter.com and may have to login. Once authenticated with Twitter they will
will either have to click on allow/deny, or will be automatically redirected back to the callback.

6) Now that the user has returned to callback.php and allowed access we need to build a new
TwitterOAuth object using the temporary credentials.

    $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'],
    $_SESSION['oauth_token_secret']);

7) Now we ask Twitter for long lasting token credentials. These are specific to the application
and user and will act like password to make future requests. Normally the token credentials would
get saved in your database but for this example we are just using sessions.

    $token_credentials = $connection->getAccessToken($_REQUEST['oauth_verifier']);

8) With the token credentials we build a new TwitterOAuth object.

    $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $token_credentials['oauth_token'],
    $token_credentials['oauth_token_secret']);

9) And finally we can make requests authenticated as the user. You can GET, POST, and DELETE API
methods. Directly copy the path from the API documentation and add an array of any parameter
you wish to include for the API method such as curser or in_reply_to_status_id.

    $account = $connection->get('account/verify_credentials');
    $status = $connection->post('statuses/update', array('status' => 'Text of status here', 'in_reply_to_status_id' => 123456));
    $status = $connection->delete('statuses/destroy/12345');

Contributors
============

* [Abraham Williams](https://twitter.com/abraham) - Main developer, current maintainer.
