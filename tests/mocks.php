<?php

declare(strict_types=1);

namespace Abraham\TwitterOAuth;

// Mock time and random values for consistent tests with VCR
function time()
{
    return MOCK_TIME;
}

function microtime()
{
    return 'FAKE_MICROTIME';
}

function random_int()
{
    return 123_456_789;
}
