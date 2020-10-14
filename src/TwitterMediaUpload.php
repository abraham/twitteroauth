<?php


namespace Abraham\TwitterOAuth;

/**
 * TwitterMediaUpload class for interacting with the Twitter Media Upload API.
 *
 * @author evin Hoang
 */
class TwitterMediaUpload extends TwitterOAuth
{
    protected const DEFAULT_UPLOAD_HOST = 'https://upload.twitter.com';

    protected $apiHost = self::DEFAULT_UPLOAD_HOST;

    /**
     * Set the Media Upload Host
     *
     * @param string $host
     * @return TwitterOAuth
     */
    public function setUploadHost($host = self::DEFAULT_UPLOAD_HOST)
    {
        return parent::setApiHost($host);
    }

    /**
     * Upload media to upload.twitter.com.
     *
     * @param string $path
     * @param array  $parameters
     * @param boolean  $chunked
     *
     * @return array|object
     */
    public function upload(
        string $path,
        array $parameters = [],
        bool $chunked = false
    ) {
        if ($chunked) {
            return $this->uploadMediaChunked($path, $parameters);
        } else {
            return $this->uploadMediaNotChunked($path, $parameters);
        }
    }

    /**
     * Progression of media upload
     *
     * @param string $mediaId
     *
     * @return array|object
     */
    public function mediaStatus(string $mediaId)
    {
        return $this->http(
            'GET',
            $this->apiHost,
            'media/upload',
            [
                'command' => 'STATUS',
                'media_id' => $mediaId,
            ],
            false
        );
    }

    /**
     * Private method to upload media (not chunked) to upload.twitter.com.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    private function uploadMediaNotChunked(string $path, array $parameters)
    {
        if (
            !is_readable($parameters['media']) ||
            ($file = file_get_contents($parameters['media'])) === false
        ) {
            throw new \InvalidArgumentException(
                'You must supply a readable file'
            );
        }
        $parameters['media'] = base64_encode($file);
        return $this->http(
            'POST',
            $this->apiHost,
            $path,
            $parameters,
            false
        );
    }

    /**
     * Private method to upload media (chunked) to upload.twitter.com.
     *
     * @param string $path
     * @param array  $parameters
     *
     * @return array|object
     */
    private function uploadMediaChunked(string $path, array $parameters)
    {
        $init = $this->http(
            'POST',
            $this->apiHost,
            $path,
            $this->mediaInitParameters($parameters),
            false
        );
        // Append
        $segmentIndex = 0;
        $media = fopen($parameters['media'], 'rb');
        while (!feof($media)) {
            $this->http(
                'POST',
                $this->apiHost,
                'media/upload',
                [
                    'command' => 'APPEND',
                    'media_id' => $init->media_id_string,
                    'segment_index' => $segmentIndex++,
                    'media_data' => base64_encode(
                        fread($media, $this->chunkSize)
                    ),
                ],
                false
            );
        }
        fclose($media);
        // Finalize
        $finalize = $this->http(
            'POST',
            $this->apiHost,
            'media/upload',
            [
                'command' => 'FINALIZE',
                'media_id' => $init->media_id_string,
            ],
            false
        );
        return $finalize;
    }

    /**
     * Private method to get params for upload media chunked init.
     * Twitter docs: https://dev.twitter.com/rest/reference/post/media/upload-init.html
     *
     * @param array  $parameters
     *
     * @return array
     */
    private function mediaInitParameters(array $parameters): array
    {
        $allowed_keys = [
            'media_type',
            'additional_owners',
            'media_category',
            'shared',
        ];
        $base = [
            'command' => 'INIT',
            'total_bytes' => filesize($parameters['media']),
        ];
        $allowed_parameters = array_intersect_key(
            $parameters,
            array_flip($allowed_keys)
        );
        return array_merge($base, $allowed_parameters);
    }
}