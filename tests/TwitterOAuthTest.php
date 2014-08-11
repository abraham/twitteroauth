<?php

namespace Abraham\TwitterOAuth\Test;

require_once('config.php');

use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterTest extends \PHPUnit_Framework_TestCase {

    public function testBuildClient() {
        $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
        $this->assertObjectHasAttribute('consumer', $twitter);
        $this->assertObjectHasAttribute('token', $twitter);
        return $twitter;
    }

    /**
     * @depends testBuildClient
     */
    public function testGetAccountVerifyCredentials($twitter) {
        $result = $twitter->get('account/verify_credentials');
        $this->assertEquals(200, $twitter->http_code);
    }

    /**
     * @depends testBuildClient
     */
    public function testGetStatusesMentionsTimeline($twitter) {
        $result = $twitter->get('statuses/mentions_timeline');
        $this->assertEquals(200, $twitter->http_code);
    }

    /**
     * @depends testBuildClient
     */
    public function testGetSearchTweets($twitter) {
        $result = $twitter->get('search/tweets', array('q' => 'twitter'));
        $this->assertEquals(200, $twitter->http_code);
        return $result->statuses;
    }

    /**
     * @depends testBuildClient
     * @depends testGetSearchTweets
     */
    public function testGetSearchTweetsWithMaxId($twitter, $statuses) {
        $max_id = array_pop($statuses)->id_str;
        $result = $twitter->get('search/tweets', array('q' => 'twitter', 'max_id' => $max_id));
        $this->assertEquals(200, $twitter->http_code);
    }

    /**
     * @depends testBuildClient
     */
    public function testPostFavoritesCreate($twitter) {
        $result = $twitter->post('favorites/create', array('id' => '6242973112'));
        if ($twitter->http_code == 403) {
            // Status already favorited
            $this->assertEquals(139, $result->errors[0]->code);
        } else {
            $this->assertEquals(200, $twitter->http_code);
        }
    }

    /**
     * @depends testBuildClient
     * @depends testPostFavoritesCreate
     */
    public function testPostFavoritesDestroy($twitter) {
        $result = $twitter->post('favorites/destroy', array('id' => '6242973112'));
        $this->assertEquals(200, $twitter->http_code);
    }

    /**
     * @depends testBuildClient
     */
    public function testPostStatusesUpdate($twitter) {
        $result = $twitter->post('statuses/update', array('status' => 'test ' . time()));
        $this->assertEquals(200, $twitter->http_code);
        return $result;
    }

    /**
     * @depends testBuildClient
     * @depends testPostStatusesUpdate
     */
    public function testPostStatusesDestroy($twitter, $status) {
        $result = $twitter->post('statuses/destroy/' . $status->id_str);
        $this->assertEquals(200, $twitter->http_code);
    }

}
