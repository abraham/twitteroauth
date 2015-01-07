<?php
/**
 * WARNING: Running these tests will post and delete through the actual Twitter account.
 */

namespace Abraham\TwitterOAuth\Test;

require __DIR__ . '/../vendor/autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

define('CONSUMER_KEY', getenv('TEST_CONSUMER_KEY'));
define('CONSUMER_SECRET', getenv('TEST_CONSUMER_SECRET'));
define('ACCESS_TOKEN', getenv('TEST_ACCESS_TOKEN'));
define('ACCESS_TOKEN_SECRET', getenv('TEST_ACCESS_TOKEN_SECRET'));
define('OAUTH_CALLBACK', getenv('TEST_OAUTH_CALLBACK'));

class TwitterTest extends \PHPUnit_Framework_TestCase {

    protected $twitter;

    protected function setUp() {
        $this->twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
    }

    public function testBuildClient() {
        $this->assertObjectHasAttribute('consumer', $this->twitter);
        $this->assertObjectHasAttribute('token', $this->twitter);
    }

    public function testOauthRequestToken() {
        $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
        $result = $twitter->oauth('oauth/request_token', array('oauth_callback' => OAUTH_CALLBACK));
        $this->assertEquals(200, $twitter->http_code);
        $this->assertArrayHasKey('oauth_token', $result);
        $this->assertArrayHasKey('oauth_token_secret', $result);
        $this->assertArrayHasKey('oauth_callback_confirmed', $result);
        $this->assertEquals('true', $result['oauth_callback_confirmed']);
        return $result;
    }

    /**
     * @depends testOauthRequestToken
     */
    public function testOauthAccessToken($request_token) {
        // Can't test this without a browser logging into Twitter so check for the correct error instead.
        $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $request_token['oauth_token'], $request_token['oauth_token_secret']);
        $result = $twitter->oauth("oauth/access_token", array("oauth_verifier" => "fake_oauth_verifier"));
        $this->assertEquals(401, $twitter->http_code);
        $this->assertEquals(array('Invalid request token' => ''), $result);
    }

    public function testUrl() {
        $url = $this->twitter->url('oauth/authorize', array('foo' => 'bar', 'baz' => 'qux'));
        $this->assertEquals('https://api.twitter.com/oauth/authorize?foo=bar&baz=qux', $url);
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
        $result = $this->twitter->post('statuses/update', array('status' => 'Hello World ' . time()));
        $this->assertEquals(200, $this->twitter->http_code);
        return $result;
    }

    public function testPostStatusesUpdateUtf8() {
        $result = $this->twitter->post('statuses/update', array('status' => 'xこんにちは世界 ' . time()));
        $this->assertEquals(200, $this->twitter->http_code);
        if ($this->twitter->http_code == 200) {
            $result = $this->twitter->post('statuses/destroy/' . $result->id_str);
        }
        return $result;
    }

    /**
     * @depends testPostStatusesUpdate
     */
    public function testPostStatusesDestroy($status) {
        $result = $this->twitter->post('statuses/destroy/' . $status->id_str;
        $this->assertEquals(200, $this->twitter->http_code);
    }

}
