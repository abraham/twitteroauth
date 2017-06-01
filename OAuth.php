<?php
 
require_once("twitteroauth/twitteroauth.php");
 
$consumerKey = "9bVPg5PCpLAhY3V61hdYqubb9";
$consumerSecret = "sKZ39tgnASk3sJIqQVZ44wBpAcOdBFQZXqhw5RC8NmtYCeFu44";
$accessToken = "832452683009110017-OCjPUwDrLMrMGDtjN5sYmSUrcM0MbCZ";
$accessTokenSecret = "1VUQmuQJLSbvEDhrM1iUhPyymTVQJoigGfiwu7O1CV2OX";
 
$twObj = new TwitterOAuth($consumerKey,$consumerSecret,$accessToken,$accessTokenSecret);
