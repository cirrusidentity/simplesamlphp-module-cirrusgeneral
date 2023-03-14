<?php

$projectRoot = dirname(__DIR__);
require_once($projectRoot . '/vendor/autoload.php');
putenv('SIMPLESAMLPHP_CONFIG_DIR=' . __DIR__ . '/config');


// Symlink module into ssp vendor lib so that templates and urls can resolve correctly
$linkPath = $projectRoot . '/vendor/simplesamlphp/simplesamlphp/modules/cirrusgeneral';
if (file_exists($linkPath) === false) {
    print "Linking '$linkPath' to '$projectRoot'\n";
    symlink($projectRoot, $linkPath);
}
