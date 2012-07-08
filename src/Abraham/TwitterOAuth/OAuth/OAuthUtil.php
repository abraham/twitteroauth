<?php
namespace Abraham\TwitterOAuth\OAuth;

/**
 * @author Luís Otávio Cobucci Oblonczyk <lcobucci@gmail.com>
 */
class OAuthUtil
{
    /**
     * @var string
     */
    const HEADER_PATTERN = '/(([-_a-z]*)=("([^"]*)"|([^,]*)),?)/';

    /**
     * Encodes the data following the RFC 3986
     *
     * @param array|string $input
     * @return array|string
     */
    public static function rfc3986Encode($input)
    {
        if (is_array($input)) {
            return array_map(
                array('\Abraham\TwitterOAuth\OAuth\OAuthUtil', 'rfc3986Encode'),
                $input
            );
        }

        if (is_scalar($input)) {
            return rawurlencode($input);
        }

        return '';
    }

    /**
     * Decodes the data following the RFC 3986
     *
     * @param array|string $string
     * @return array|string
     */
    public static function rfc3986Decode($input)
    {
        if (is_array($input)) {
            return array_map(
                array('\Abraham\TwitterOAuth\OAuth\OAuthUtil', 'rfc3986Decode'),
                $input
            );
        }

        if (is_scalar($input)) {
            return rawurldecode($input);
        }

        return '';
    }

    /**
     * @param string $header
     * @param boolean $OAuthParamsOnly
     * @return array
     */
    public static function splitHeader($header, $OAuthParamsOnly = true)
    {
        $offset = 0;
        $params = array();

        while (preg_match(static::HEADER_PATTERN, $header, $matches,
            PREG_OFFSET_CAPTURE, $offset) > 0) {
            $match = $matches[0];
            $headerName = $matches[2][0];
            $headerContent = isset($matches[5])
                             ? $matches[5][0] : $matches[4][0];

            if (preg_match('/^oauth_/', $headerName) || !$OAuthParamsOnly) {
                $params[$headerName] = static::rfc3986Decode($headerContent);
            }

            $offset = $match[1] + strlen($match[0]);
        }

        if (isset($params['realm'])) {
            unset($params['realm']);
        }

        return $params;
    }

    /**
     * Fetch HTTP headers
     *
     * @param array $server
     * @param array $env
     * @return array
     */
    public static function getHeaders(array $server, array $env)
    {
        if ($headers = static::getApacheHeaders()) {
            return $headers;
        }

        if (isset($server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $server['CONTENT_TYPE'];
        }

        if (isset($env['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $env['CONTENT_TYPE'];
        }

        array_walk(
            $server,
            function ($value, $key) use (&$headers)
            {
                if (substr($key, 0, 5) == "HTTP_") {
                    $key = static::prepareHeaderKey(ltrim($key, 'HTTP_'));
                    $headers[$key] = $value;
                }
            }
        );

        return $headers;
    }

    /**
     * Fetch the request headers from apache
     *
     * @return array
     */
    protected static function getApacheHeaders()
    {
        if (!function_exists('apache_request_headers')) {
            return null;
        }

        $headers = apache_request_headers();
        $out = array();

        foreach ($headers as $key => $value) {
            $key = static::prepareHeaderKey($key);

            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * Transforms strings like ACCEPT_ENCODING and ACCEPT-ENCODING
     * into Accept-Encoding
     *
     * @param string $key
     * @return string
     */
    protected static function prepareHeaderKey($key)
    {
        return str_replace(
            ' ',
            '-',
            ucwords(strtolower(preg_replace('/(\-|\_)/', ' ', $key)))
        );
    }

    /**
     * Creates an array from the query string
     *
     * @param string $input
     * @return array
     */
    public static function parseParameters($input)
    {
        if (trim($input) == '') {
            return array();
        }

        $input = static::rfc3986Decode($input);
        parse_str($input, $parameters);

        return $parameters;
    }

    /**
     * Build the query string from the parameters
     *
     * @param array $params
     * @return string
     */
    public static function buildHttpQuery(array $params)
    {
        if (count($params) == 0) {
            return null;
        }

        uksort($params, 'strcmp');

        return str_replace('+', '%20', http_build_query($params, null, '&'));
    }
}