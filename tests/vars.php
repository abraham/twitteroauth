<?php

declare(strict_types=1);

// These keys have been revoked and are only valid for teh VCR cassettes.
// To request new VCR cassettes please open an issue: https://github.com/abraham/twitteroauth/issues

// To update VCR cassettes
// 1. Delete all `tests/fixtures/*` files
//    `rm tests/fixtures/*`
// 2. Set application and suer authorization credentials below
// 3. Set MOCK_TIME to current unix time
// 4. Run PHPUnit tests
// 5. Reset application credentials on Twitter dashboard
// 6. Commit new cassettes and revoked credentials

// TwitterOAuth dev
define('CONSUMER_KEY', 'awJfND4zFGapGOFKfdjg');
define('CONSUMER_SECRET', 'LfkmNSRPIXwkQkZUB9DNWSzx5LIaivSknV4rxngojJc');
define('OAUTH_CALLBACK', 'https://twitteroauth.com/callback.php');

// oauthlibtest
define('ACCESS_TOKEN', '93915746-KjE3c27dCt8awONxuUAaJ00yishXXwcH5CdLBnO1x');
define('ACCESS_TOKEN_SECRET', 'vurSbgJw6nHvv7xBfqKnBLWEQekOi59KFkXDLiY3Vqn3u');

// Timestamp the VCR cassettes were last updated
define('MOCK_TIME', 1587861062);

// https://free-proxy-list.net/
define('PROXY', '12.218.209.130');
define('PROXYUSERPWD', '');
define('PROXYPORT', '53281');
