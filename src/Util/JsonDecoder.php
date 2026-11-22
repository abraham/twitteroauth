<?php

declare(strict_types=1);

namespace Abraham\TwitterOAuth\Util;

/**
 * @author louis <louis@systemli.org>
 */
class JsonDecoder
{
    /**
     * Decodes a JSON string to stdObject or associative array
     *
     * @param string $string
     * @param bool   $asArray
     *
     * @return array|object|null
     */
    public static function decode(string $string, bool $asArray)
    {
        return json_decode($string, $asArray, 512, JSON_BIGINT_AS_STRING);
    }
}
