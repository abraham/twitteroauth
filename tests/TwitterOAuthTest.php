<?php

namespace Abraham\TwitterOAuth\Test;

require __DIR__ . '/../vendor/autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

define('CONSUMER_KEY', getenv('CONSUMER_KEY'));
define('CONSUMER_SECRET', getenv('CONSUMER_SECRET'));
define('ACCESS_TOKEN', getenv('ACCESS_TOKEN'));
define('ACCESS_TOKEN_SECRET', getenv('ACCESS_TOKEN_SECRET'));
define('OAUTH_CALLBACK', getenv('OAUTH_CALLBACK'));

class TwitterTest extends \PHPUnit_Framework_TestCase {

    protected $twitter;

    protected function setUp() {
        $this->twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
    }

    public function testBuildClient() {
        $this->assertObjectHasAttribute('consumer', $this->twitter);
        $this->assertObjectHasAttribute('token', $this->twitter);
    }

    public function testGetAccountVerifyCredentials() {
        $result = $this->twitter->get('account/verify_credentials');
        $this->assertEquals(200, $this->twitter->http_code);
    }

    public function testGetStatusesMentionsTimeline() {
        $result = $this->twitter->get('statuses/mentions_timeline');
        $this->assertEquals(200, $this->twitter->http_code);
    }

    public function testGetSearchTweets() {
        $result = $this->twitter->get('search/tweets', array('q' => 'twitter'));
        $this->assertEquals(200, $this->twitter->http_code);
        return $result->statuses;
    }

    /**
     * @depends testGetSearchTweets
     */
    public function testGetSearchTweetsWithMaxId($statuses) {
        $max_id = array_pop($statuses)->id_str;
        $result = $this->twitter->get('search/tweets', array('q' => 'twitter', 'max_id' => $max_id));
        $this->assertEquals(200, $this->twitter->http_code);
    }

    public function testPostFavoritesCreate() {
        $result = $this->twitter->post('favorites/create', array('id' => '6242973112'));
        if ($this->twitter->http_code == 403) {
            // Status already favorited
            $this->assertEquals(139, $result->errors[0]->code);
        } else {
            $this->assertEquals(200, $this->twitter->http_code);
        }
    }

    /**
     * @depends testPostFavoritesCreate
     */
    public function testPostFavoritesDestroy() {
        $result = $this->twitter->post('favorites/destroy', array('id' => '6242973112'));
        $this->assertEquals(200, $this->twitter->http_code);
    }

    public function testPostStatusesUpdate() {
        $result = $this->twitter->post('statuses/update', array('status' => 'test ' . time()));
        $this->assertEquals(200, $this->twitter->http_code);
        return $result;
    }

    /**
     * @depends testPostStatusesUpdate
     */
    public function testPostStatusesDestroy($status) {
        $result = $this->twitter->post('statuses/destroy/' . $status->id_str);
        $this->assertEquals(200, $this->twitter->http_code);
    }

}
