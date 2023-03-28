<?php

use SimpleSAML\Logger;

$config['module.enable']['exampleauth'] = true;
$config['module.enable']['cirrusgeneral'] = true;
$config = [
        'enable.saml20-idp' => true,
        // Need minimum 12 characters for password stuffing limiter
        'secretsalt' => 'testsalt9012',
        'logging.level' => Logger::DEBUG,
        'auth.adminpassword' => 'secret',

    ] + $config;
