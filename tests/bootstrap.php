<?php

include_once __DIR__ . '/../vendor/bolt/bolt/tests/phpunit/bootstrap.php';

define('EXTENSION_TEST_ROOT', dirname(__DIR__));
define('EXTENSION_AUTOLOAD',  realpath(dirname(__DIR__) . '/vendor/autoload.php'));

require_once EXTENSION_AUTOLOAD;

require_once(__DIR__ . '/../vendor/psiphp/object-agent/tests/Functional/AgentTestTrait.php');
