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

function mt_rand()
{
    return 123456789;
}
