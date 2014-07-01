
<?php       
require_once('oAuth/twitteroauth.php');
define('CONSUMER_KEY', '//');
define('CONSUMER_SECRET', '//');
define('ACCESS_TOKEN', '//');
define('ACCESS_TOKEN_SECRET', '//');

$Connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
$status = "Samet twitter api 1.1 test";
$Connection->post('statuses/update', array('status' => $status));
?><?php    
   
require_once('oAuth/twitteroauth.php');

define('CONSUMER_KEY', '//');
define('CONSUMER_SECRET', '//');
define('ACCESS_TOKEN', '//');
define('ACCESS_TOKEN_SECRET', '//');

$Connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

$tweets = $Connection->get("https://api.twitter.com/1.1/statuses/user_timeline.json?screen_name=smt_arabacioglu&count=2");
foreach($tweets as $tweet) {

$tweetID = $tweet->id_str; // Atılan Tweet'in idsi
$tweetText = $tweet->text; // Atılan Tweet'in içeriği
$tweetTime = $tweet->created_at; // Atılan Tweet'in atılma tarihi
$tweetFavCount = $tweet->favorite_count; // Atılan Tweet'in Favori Sayısı
$tweetRtCount = $tweet->retweet_count; // Atılan Tweet'in Retweet Sayısı

echo $tweetText;
}
?><?php    
   
require_once('oAuth/twitteroauth.php');

define('CONSUMER_KEY', '');
define('CONSUMER_SECRET', '');
define('ACCESS_TOKEN', '');
define('ACCESS_TOKEN_SECRET', '');

$Connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
$Connection->post("https://api.twitter.com/1.1/favorites/create.json?id=$tweetID");
?>
