<?php
namespace Abraham\TwitterOAuth\OAuth;

/**
 * OAuthUtil test case.
 */
class OAuthUtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function arraysMustBeProperlyEncoded()
    {
        $input = array(
            'foo @+%/',
            'sales and marketing/Miami',
            '~testing tildes.',
            new \stdClass()
        );

        $expected = array(
            'foo%20%40%2B%25%2F',
            'sales%20and%20marketing%2FMiami',
            '~testing%20tildes.',
            ''
        );

        $this->assertEquals($expected, OAuthUtil::rfc3986Encode($input));
    }

    /**
     * @test
     */
    public function arraysMustBeProperlyDecoded()
    {
        $input = array(
            'foo%20%40%2B%25%2F',
            'sales%20and%20marketing%2FMiami',
            '~testing%20tildes',
            'id=3&name=Luis%20Otavio&email=lcobucci%40gmail.com&products%5B%5D=1&products%5B%5D=2',
            new \stdClass()
        );

        $expected = array(
            'foo @+%/',
            'sales and marketing/Miami',
            '~testing tildes',
            'id=3&name=Luis Otavio&email=lcobucci@gmail.com&products[]=1&products[]=2',
            ''
        );

        $this->assertEquals($expected, OAuthUtil::rfc3986Decode($input));
    }

    /**
     * @test
     */
    public function queryStringMustBeDecodedAndTransformedIntoArray()
    {
        $input = 'id=3&name=Luis%20Otavio&email=lcobucci%40gmail.com&'
                 . 'products[]=1&products[]=2';

        $expected = array(
            'id' => 3,
            'name' => 'Luis Otavio',
            'email' => 'lcobucci@gmail.com',
            'products' => array(1, 2)
        );

        $this->assertEquals($expected, OAuthUtil::parseParameters($input));
    }

    /**
     * @test
     */
    public function paramsMustBeConvertedIntoQueryString()
    {
        $input = array(
            'id' => 3,
            'name' => 'Luis Otavio',
            'email' => 'lcobucci@gmail.com',
            'products' => array(1, 2)
        );

        $expected = 'id=3&name=Luis%20Otavio&email=lcobucci%40gmail.com&'
                    . 'products%5B0%5D=1&products%5B1%5D=2';

        $this->assertEquals($expected, OAuthUtil::buildHttpQuery($input));
    }
}