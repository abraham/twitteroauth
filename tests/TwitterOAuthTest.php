<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterOAuthTest extends TestCase
{
    /** @var TwitterOAuth */
    protected $twitter;

    protected function setUp(): void
    {
        $this->twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            ACCESS_TOKEN,
            ACCESS_TOKEN_SECRET
        );
        $this->userId = explode('-', ACCESS_TOKEN)[0];
    }

    public function testBuildClient()
    {
        $this->assertObjectHasAttribute('consumer', $this->twitter);
        $this->assertObjectHasAttribute('token', $this->twitter);
    }

    /**
     * @vcr testSetOauthToken.json
     */
    public function testSetOauthToken()
    {
        $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
        $twitter->setOauthToken(ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
        $this->assertObjectHasAttribute('consumer', $twitter);
        $this->assertObjectHasAttribute('token', $twitter);
        $twitter->get('friendships/show', [
            'target_screen_name' => 'twitterapi',
        ]);
        $this->assertEquals(200, $twitter->getLastHttpCode());
    }

    /**
     * @vcr testOauth2Token.json
     */
    public function testOauth2Token()
    {
        $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
        $result = $twitter->oauth2('oauth2/token', [
            'grant_type' => 'client_credentials',
        ]);
        $this->assertEquals(200, $twitter->getLastHttpCode());
        $this->assertObjectHasAttribute('token_type', $result);
        $this->assertObjectHasAttribute('access_token', $result);
        $this->assertEquals('bearer', $result->token_type);
        return $result;
    }

    /**
     * @depends testOauth2Token
     * @vcr testOauth2BearerToken.json
     */
    public function testOauth2BearerToken($accessToken)
    {
        $twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            null,
            $accessToken->access_token
        );
        $result = $twitter->get('statuses/user_timeline', [
            'screen_name' => 'twitterapi',
        ]);
        $this->assertEquals(200, $twitter->getLastHttpCode());
        return $accessToken;
    }

    /**
     * @depends testOauth2BearerToken
     * @vcr testOauth2TokenInvalidate.json
     */
    public function testOauth2TokenInvalidate($accessToken)
    {
        $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
        // HACK: access_token is already urlencoded but gets urlencoded again breaking the invalidate request.
        $result = $twitter->oauth2('oauth2/invalidate_token', [
            'access_token' => urldecode($accessToken->access_token),
        ]);
        $this->assertEquals(200, $twitter->getLastHttpCode());
        $this->assertObjectHasAttribute('access_token', $result);
    }

    /**
     * @vcr testOauthRequestToken.json
     */
    public function testOauthRequestToken()
    {
        $twitter = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
        $result = $twitter->oauth('oauth/request_token', [
            'oauth_callback' => OAUTH_CALLBACK,
        ]);
        $this->assertEquals(200, $twitter->getLastHttpCode());
        $this->assertArrayHasKey('oauth_token', $result);
        $this->assertArrayHasKey('oauth_token_secret', $result);
        $this->assertArrayHasKey('oauth_callback_confirmed', $result);
        $this->assertEquals('true', $result['oauth_callback_confirmed']);
        return $result;
    }

    /**
     * @vcr testOauthRequestTokenException.json
     */
    public function testOauthRequestTokenException()
    {
        $this->expectException(
            \Abraham\TwitterOAuth\TwitterOAuthException::class
        );
        $this->expectErrorMessage('Could not authenticate you');
        $twitter = new TwitterOAuth('CONSUMER_KEY', 'CONSUMER_SECRET');
        $result = $twitter->oauth('oauth/request_token', [
            'oauth_callback' => OAUTH_CALLBACK,
        ]);
    }

    /**
     * @depends testOauthRequestToken
     * @vcr testOauthAccessTokenTokenException.json
     */
    public function testOauthAccessTokenTokenException(array $requestToken)
    {
        // Can't test this without a browser logging into Twitter so check for the correct error instead.
        $this->expectException(
            \Abraham\TwitterOAuth\TwitterOAuthException::class
        );
        $this->expectErrorMessage('Invalid oauth_verifier parameter');
        $twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            $requestToken['oauth_token'],
            $requestToken['oauth_token_secret']
        );
        $twitter->oauth('oauth/access_token', [
            'oauth_verifier' => 'fake_oauth_verifier',
        ]);
    }

    public function testUrl()
    {
        $url = $this->twitter->url('oauth/authorize', [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);
        $this->assertEquals(
            'https://api.twitter.com/oauth/authorize?foo=bar&baz=qux',
            $url
        );
    }

    /**
     * @vcr testGetAccountVerifyCredentials.json
     */
    public function testGetAccountVerifyCredentials()
    {
        $user = $this->twitter->get('account/verify_credentials', [
            'include_entities' => false,
            'include_email' => true,
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $this->assertObjectHasAttribute('email', $user);
    }

    /**
     * @vcr testSetProxy.json
     */
    public function testSetProxy()
    {
        $this->twitter->setProxy([
            'CURLOPT_PROXY' => PROXY,
            'CURLOPT_PROXYUSERPWD' => PROXYUSERPWD,
            'CURLOPT_PROXYPORT' => PROXYPORT,
        ]);
        $this->twitter->setTimeouts(60, 60);
        $result = $this->twitter->get('account/verify_credentials');
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $this->assertObjectHasAttribute('id', $result);
    }

    /**
     * @vcr testGetStatusesMentionsTimeline.json
     */
    public function testGetStatusesMentionsTimeline()
    {
        $this->twitter->get('statuses/mentions_timeline');
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }

    /**
     * @vcr testGetSearchTweets.json
     */
    public function testGetSearchTweets()
    {
        $result = $this->twitter->get('search/tweets', ['q' => 'twitter']);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        return $result->statuses;
    }

    /**
     * @depends testGetSearchTweets
     * @vcr testGetSearchTweetsWithMaxId.json
     */
    public function testGetSearchTweetsWithMaxId($statuses)
    {
        $maxId = array_pop($statuses)->id_str;
        $this->twitter->get('search/tweets', [
            'q' => 'twitter',
            'max_id' => $maxId,
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }

    /**
     * @vcr testPostFavoritesCreate.json
     */
    public function testPostFavoritesCreate()
    {
        $result = $this->twitter->post('favorites/create', [
            'id' => '6242973112',
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }

    /**
     * @depends testPostFavoritesCreate
     * @vcr testPostFavoritesDestroy.json
     */
    public function testPostFavoritesDestroy()
    {
        $this->twitter->post('favorites/destroy', ['id' => '6242973112']);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }

    /**
     * @vcr testPostDirectMessagesEventsNew.json
     */
    public function testPostDirectMessagesEventsNew()
    {
        $data = [
            'event' => [
                'type' => 'message_create',
                'message_create' => [
                    'target' => [
                        'recipient_id' => $this->userId,
                    ],
                    'message_data' => [
                        'text' => 'Hello World!',
                    ],
                ],
            ],
        ];
        $result = $this->twitter->post(
            'direct_messages/events/new',
            $data,
            true
        );
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        return $result;
    }

    /**
     * @depends testPostDirectMessagesEventsNew
     * @vcr testDeleteDirectMessagesEventsDestroy.json
     */
    public function testDeleteDirectMessagesEventsDestroy($message)
    {
        $this->twitter->delete('direct_messages/events/destroy', [
            'id' => $message->event->id,
        ]);
        $this->assertEquals(204, $this->twitter->getLastHttpCode());
    }

    /**
     * @vcr testPostStatusesUpdateWithMedia.json
     */
    public function testPostStatusesUpdateWithMedia()
    {
        $this->twitter->setTimeouts(60, 60);
        // Image source https://www.flickr.com/photos/titrans/8548825587/
        $file_path = __DIR__ . '/kitten.jpg';
        $result = $this->twitter->upload('media/upload', [
            'media' => $file_path,
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $this->assertObjectHasAttribute('media_id_string', $result);
        $parameters = [
            'status' => 'Hello World ' . MOCK_TIME,
            'media_ids' => $result->media_id_string,
        ];
        $result = $this->twitter->post('statuses/update', $parameters);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $result = $this->twitter->post('statuses/destroy/' . $result->id_str);
        return $result;
    }

    /**
     * @vcr testPostStatusUpdateWithInvalidMediaThrowsException.json
     */
    public function testPostStatusUpdateWithInvalidMediaThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $file_path = __DIR__ . '/12345678900987654321.jpg';
        $this->assertFalse(\is_readable($file_path));
        $result = $this->twitter->upload('media/upload', [
            'media' => $file_path,
        ]);
    }

    /**
     * @vcr testPostStatusesUpdateWithMediaChunked.json
     */
    public function testPostStatusesUpdateWithMediaChunked()
    {
        $this->twitter->setTimeouts(60, 30);
        // Video source http://www.sample-videos.com/
        $file_path = __DIR__ . '/video.mp4';
        $result = $this->twitter->upload(
            'media/upload',
            ['media' => $file_path, 'media_type' => 'video/mp4'],
            true
        );
        $this->assertEquals(201, $this->twitter->getLastHttpCode());
        $this->assertObjectHasAttribute('media_id_string', $result);
        $parameters = [
            'status' => 'Hello World ' . MOCK_TIME,
            'media_ids' => $result->media_id_string,
        ];
        $result = $this->twitter->post('statuses/update', $parameters);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $result = $this->twitter->post('statuses/destroy/' . $result->id_str);
        return $result;
    }

    /**
     * @vcr testPostStatusesUpdateUtf8.json
     */
    public function testPostStatusesUpdateUtf8()
    {
        $result = $this->twitter->post('statuses/update', [
            'status' => 'xこんにちは世界 ' . MOCK_TIME,
        ]);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        return $result;
    }

    /**
     * @depends testPostStatusesUpdateUtf8
     * @vcr testPostStatusesDestroy.json
     */
    public function testPostStatusesDestroy($status)
    {
        $this->twitter->post('statuses/destroy/' . $status->id_str);
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
    }

    /**
     * @vcr testLastResult.json
     */
    public function testLastResult()
    {
        $this->twitter->get('search/tweets', ['q' => 'twitter']);
        $this->assertEquals('search/tweets', $this->twitter->getLastApiPath());
        $this->assertEquals(200, $this->twitter->getLastHttpCode());
        $this->assertObjectHasAttribute(
            'statuses',
            $this->twitter->getLastBody()
        );
    }

    /**
     * @depends testLastResult
     * @vcr testResetLastResponse.json
     */
    public function testResetLastResponse()
    {
        $this->twitter->resetLastResponse();
        $this->assertEquals('', $this->twitter->getLastApiPath());
        $this->assertEquals(0, $this->twitter->getLastHttpCode());
        $this->assertEquals([], $this->twitter->getLastBody());
    }
}
