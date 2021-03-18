<?php

$response = \SimpleSAML\Module\cirrusgeneral\Auth\Process\PromptAttributeRelease::handleRequest();
$response->send();
