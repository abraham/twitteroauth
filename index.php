<?php include "twitteroauth/twitteroauth/twitteroauth.php"; ?>
<?php
$consumer = "yqzs7r5uHLg9ohnOJnlFKMlsZ";
$consumerSecret = "WvAScqgW9BYm75tfDEYhrqefAq8Vynv720FBmHc2qvCmpHwJZm";
$accessToken = "365275877-oU4w8t1C7ozMfFFU7WZM6wIjZwBb25usNiOjBOhc";
$accessTokenSecret = "Vf1hvGvSLSBKlThsV2OsN7wWatjbcQ6vWiMDmB0rcdT10";
$twitter = new TwitterOauth($consumer,$consumerSecret,$accessToken,$accessTokenSecret);

?>
<html>
	
	<head>
		<title>
			Twitter feeds

		</title>
	</head>
	<body>
		<form action="" method="POST">
		<label> Search : <input type ="" name="keyword"/></label>	

<?php
if( isset($_POST['keyword'])){
$tweets = $twitter->get('https://api.twitter.com/1.1/search/tweets.json?q='.$_POST['keyword'].'&result_type=recent&count=4');
	// foreach($tweets as $tweet){
		//foreach ($tweet as $t) {
			print_r($tweets); 
			//echo "<br>";

					# code...
		//}
	//}
}
	
//<?php print_r($tweets);?>


</body>
</html>