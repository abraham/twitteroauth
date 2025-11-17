<?php

declare(strict_types=1);

namespace Abraham\TwitterOAuth;

/**
 * @author Abraham Williams <abraham@abrah.am>
 */
class TwitterOAuthException extends \Exception
{
    /**
     * Attempts to parse message as JSON. If parsing fails, returns message directly.
     *
     * @return array|object|string
     */
    public function parsedMessage()
    {
        $decoded = json_decode($this->message, false);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $this->message;
    }
}
