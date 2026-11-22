<?php

namespace Abraham\TwitterOAuth\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Abraham\TwitterOAuth\Util\JsonDecoder;

class JsonDecoderTest extends TestCase
{
    #[DataProvider('jsonProvider')]
    public function testDecode($input, $asArray, $expected)
    {
        $this->assertEquals($expected, JsonDecoder::decode($input, $asArray));
    }

    public static function jsonProvider(): array
    {
        return [
            ['[]', true, []],
            ['[1,2,3]', true, [1, 2, 3]],
            [
                '[{"id": 556179961825226750}]',
                true,
                [['id' => 556_179_961_825_226_750]],
            ],
            ['[]', false, []],
            ['[1,2,3]', false, [1, 2, 3]],
            [
                '[{"id": 556179961825226750}]',
                false,
                [
                    self::getClass(function ($object) {
                        $object->id = 556_179_961_825_226_750;
                        return $object;
                    }),
                ],
            ],
        ];
    }

    /**
     * @param callable $callable
     *
     * @return \stdClass
     */
    private static function getClass(\Closure $callable)
    {
        $object = new \stdClass();

        return $callable($object);
    }
}
