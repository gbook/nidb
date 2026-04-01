<?php

require 'vendor/autoload.php';

use TusPhp\Tus\Server;

$server = new Server('file');

// directory where uploaded files are stored
$server->setUploadDir(__DIR__ . '/uploads');

// handle the request
$response = $server->serve();

// send response
$response->send();

// optional: run garbage collection
$server->garbageCollect();
?>