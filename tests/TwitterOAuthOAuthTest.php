<?php

/**
 * WARNING: Running tests will post and delete through the actual Twitter account when updating or saving VCR cassettes.
 */

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Test;

use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterOAuthOAuthTest extends TestCase
{
    /** @var TwitterOAuth */
    protected $twitter;

    protected function setUp(): void
    {
        $this->twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            ACCESS_TOKEN,
            ACCESS_TOKEN_SECRET,
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
            $accessToken->access_token,
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
            \Abraham\TwitterOAuth\TwitterOAuthException::class,
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
            \Abraham\TwitterOAuth\TwitterOAuthException::class,
        );
        $this->expectErrorMessage('Invalid oauth_verifier parameter');
        $twitter = new TwitterOAuth(
            CONSUMER_KEY,
            CONSUMER_SECRET,
            $requestToken['oauth_token'],
            $requestToken['oauth_token_secret'],
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
            $url,
        );
    }
}
