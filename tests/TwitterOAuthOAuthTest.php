<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\MockHttpClient;

class TwitterOAuthOAuthTest extends TestCase
{
    /** @var TwitterOAuth */
    protected $twitter;
    /** @var MockHttpClient */
    protected $mockClient;

    protected function setUp(): void
    {
        $this->mockClient = new MockHttpClient();
        $this->twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            ACCESS_TOKEN,
            ACCESS_TOKEN_SECRET,
            $this->mockClient,
        );
        $this->twitter->setApiVersion('1.1');
        $this->userId = explode('-', ACCESS_TOKEN)[0];
    }

    public function testBuildClient()
    {
        $this->assertObjectHasProperty('consumer', $this->twitter);
        $this->assertObjectHasProperty('token', $this->twitter);
    }

    public function testSetOauthToken()
    {
        $this->mockClient->useFixture('testSetOauthToken');
        $twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            null,
            null,
            $this->mockClient,
        );
        $twitter->setApiVersion('1.1');
        $twitter->setOauthToken(ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
        $this->assertObjectHasProperty('consumer', $twitter);
        $this->assertObjectHasProperty('token', $twitter);
        $twitter->get('friendships/show', [
            'target_screen_name' => 'twitterapi',
        ]);
        $this->assertEquals(200, $twitter->getLastHttpCode());
    }

    public function testOauth2Token()
    {
        $this->mockClient->useFixture('testOauth2Token');
        $twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            null,
            null,
            $this->mockClient,
        );
        $result = $twitter->oauth2('oauth2/token', [
            'grant_type' => 'client_credentials',
        ]);
        $this->assertEquals(200, $twitter->getLastHttpCode());
        $this->assertObjectHasProperty('token_type', $result);
        $this->assertObjectHasProperty('access_token', $result);
        $this->assertEquals('bearer', $result->token_type);
        return $result;
    }

    /**
     * @depends testOauth2Token
     */
    public function testOauth2BearerToken($accessToken)
    {
        $this->mockClient->useFixture('testOauth2BearerToken');
        $twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            null,
            $accessToken->access_token,
            $this->mockClient,
        );
        $twitter->setApiVersion('1.1');
        $result = $twitter->get('statuses/user_timeline', [
            'screen_name' => 'twitterapi',
        ]);
        $this->assertEquals(200, $twitter->getLastHttpCode());
        return $accessToken;
    }

    /**
     * @depends testOauth2BearerToken
     */
    public function testOauth2TokenInvalidate($accessToken)
    {
        $this->mockClient->useFixture('testOauth2TokenInvalidate');
        $twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            null,
            null,
            $this->mockClient,
        );
        // HACK: access_token is already urlencoded but gets urlencoded again breaking the invalidate request.
        $result = $twitter->oauth2('oauth2/invalidate_token', [
            'access_token' => urldecode($accessToken->access_token),
        ]);
        $this->assertEquals(200, $twitter->getLastHttpCode());
        $this->assertObjectHasProperty('access_token', $result);
    }

    public function testOauthRequestToken()
    {
        $this->mockClient->useFixture('testOauthRequestToken');
        $twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            null,
            null,
            $this->mockClient,
        );
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

    public function testOauthRequestTokenException()
    {
        $this->mockClient->useFixture('testOauthRequestTokenException');
        $caught = false;
        try {
            $twitter = new TwitterOAuth(
                'CONSUMER_KEY',
                'CONSUMER_SECRET',
                null,
                null,
                $this->mockClient,
            );
            $result = $twitter->oauth('oauth/request_token', [
                'oauth_callback' => OAUTH_CALLBACK,
            ]);
        } catch (\Abraham\TwitterOAuth\TwitterOAuthException $e) {
            $this->assertStringContainsString(
                'Could not authenticate you',
                $e->getMessage(),
            );
            $caught = true;
        }
        assert($caught);
    }

    /**
     * @depends testOauthRequestToken
     */
    public function testOauthAccessTokenTokenException(array $requestToken)
    {
        $this->mockClient->useFixture('testOauthAccessTokenTokenException');
        // Can't test this without a browser logging into Twitter so check for the correct error instead.
        $caught = false;
        try {
            $twitter = new TwitterOAuth(
                CONSUMER_KEY,
                CONSUMER_SECRET,
                $requestToken['oauth_token'],
                $requestToken['oauth_token_secret'],
                $this->mockClient,
            );
            $twitter->oauth('oauth/access_token', [
                'oauth_verifier' => 'fake_oauth_verifier',
            ]);
        } catch (\Abraham\TwitterOAuth\TwitterOAuthException $e) {
            $this->assertStringContainsString(
                'Invalid oauth_verifier parameter',
                $e->getMessage(),
            );
            $caught = true;
        }
        assert($caught);
    }

    public function testUrl()
    {
        $url = $this->twitter->url('oauth/authorize', [
            'foo' => 'bar',
            'baz' => 'qux',
        ]);
        $this->assertEquals(
            'https://api.twitter.com/oauth/authorize?foo=bar&baz=qux',
            $url,
        );
    }
}
