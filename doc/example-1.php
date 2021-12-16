<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/../vendor/autoload.php';

$_proxy = new \Dduers\PhpRestProxy\RestProxy([

    // for dev, don't verify ssl certs
    'verify' => false,

    // use HTTP/2
    'version' => 2
]);

$_proxy->mount('myapi', 'https://domain19.local/v1');
$_proxy->exec();
$_proxy->dump();
