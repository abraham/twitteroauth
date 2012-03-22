<?php
 
session_start ();
require_once ('twitteroauth/twitteroauth.php');
require_once ('config.php');
 
/* If access tokens are not available redirect to connect page. */
if (empty ($_SESSION['access_token']) || empty ($_SESSION['access_token']['oauth_token']) || empty ($_SESSION['access_token']['oauth_token_secret'])) {
    //header('Location: ./clearsessions.php');
    //tampilkan link OAuth
?>
<a href="./redirect.php"><img src="./images/lighter.png" alt="Sign in with Twitter"/></a>
<?php
}
else
{
    //wah, sudah berhasil tersambung nih. Lakukan proses disini
    $access_token = $_SESSION['access_token'];
 
    $connection = new TwitterOAuth (CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
 
    $content = $connection->get ('account/verify_credentials');
 
    var_dump ($content);
}

Read more: http://bayu.freelancer.web.id/2010/10/20/cara-membuat-koneksi-ke-twitter-menggunakan-oauth-dan-php/#ixzz1ppHRasgl
Under Creative Commons License: Attribution

