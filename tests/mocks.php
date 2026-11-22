<?php

declare(strict_types=1);

namespace Abraham\TwitterOAuth;

// Mock time and random values for consistent tests with VCR
function time()
{
    return MOCK_TIME;
}

function random_bytes(int $length): string
{
    return substr(
        str_repeat(
            hex2bin('2b67ebbeace76543f356ba8bbd59abde'),
            (int) ceil($length / 16),
        ),
        0,
        $length,
    );
}
