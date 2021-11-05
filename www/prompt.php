<?php

$response = \SimpleSAML\Module\cirrusgeneral\Auth\Process\PromptAttributeRelease::handleRequest();
if (is_null($response->getTwig())) {
    $response->show();
} else {
    $response->send();
}
