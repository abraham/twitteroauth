<?php

require __DIR__ . '/../vendor/autoload.php';
require 'vars.php';
require 'mocks.php';

\VCR\VCR::configure()
    ->setStorage('json');
\VCR\VCR::turnOn();
