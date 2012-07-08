<?php
namespace Abraham\TwitterOAuth;

class TwitterClient extends TwitterOAuth
{
    /**
     * @return mixed
     */
    public function publicTimeline()
    {
        return $this->get('statuses/public_timeline');
    }

    /**
     * @return mixed
     */
    public function homeTimeline()
    {
        return $this->get('statuses/home_timeline');
    }

    /**
     * @return mixed
     */
    public function friendsTimeline()
    {
        return $this->get('statuses/friends_timeline');
    }

    /**
     * @return mixed
     */
    public function userTimeline()
    {
        return $this->get('statuses/user_timeline');
    }

    /**
     * @return mixed
     */
    public function mentions()
    {
        return $this->get('statuses/mentions');
    }

    /**
     * @return mixed
     */
    public function retweetedByMe()
    {
        return $this->get('statuses/retweeted_by_me');
    }

    /**
     * @return mixed
     */
    public function retweetedToMe()
    {
        return $this->get('statuses/retweeted_to_me');
    }

    /**
     * @return mixed
     */
    public function retweetsOfMe()
    {
        return $this->get('statuses/retweets_of_me');
    }

    /**
     * @param string $status
     * @return mixed
     */
    public function updateStatus($status)
    {
        return $this->post(
            'statuses/update',
            array('status' => (string) $status)
        );
    }

    /**
     * @param string $screenName
     * @return mixed
     */
    public function createFriendship($screenName)
    {
        return $this->post(
            'friendships/create',
            array('screen_name' => $screenName)
        );
    }

    /**
     * @param boolean $includeEntities
     * @param boolean $skipStatus
     * @return mixed
     */
    public function verifyCredentials(
        $includeEntities = null,
        $skipStatus = null
    ) {
        $params = array();

        if ($includeEntities !== null) {
            $params['include_entities'] = (string) $includeEntities;
        }

        if ($skipStatus !== null) {
            $params['skip_status'] = (string) $skipStatus;
        }

        return $this->get('account/verify_credentials', $params);
    }
}