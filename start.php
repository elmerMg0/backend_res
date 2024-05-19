<?php

use Workerman\Worker;
require_once __DIR__ . '/vendor/autoload.php';

$connections = [];

$ws_worker = new Worker('websocket://127.0.0.1:8282');

$ws_worker->onConnect = function ($connection) {
	global $connections;
	echo "New connection: {$connection->id}\n";
	$connections[$connection -> id] = $connection;
	$data = json_encode("Connected");
	$connection->send(json_encode(['codigo' => $data]));
};

// Emitted when data received
$ws_worker->onMessage = function ($connection, $data) {
	global $connections;

	$filteredConnections = array_filter($connections, function($item) use ($connection) {
                return $item -> id !== $connection->id;
        });

	foreach($filteredConnections as $connection2){
            $connection2->send(json_encode(['codigo' => $data]));
        }
};

// Emitted when connection closed
$ws_worker->onClose = function ($connection) {
    echo "Connection closed\n";
    global $connections; 
    echo $connection -> id;
    $connections = array_filter($connections, function($item) use ($connection) {
                return $item -> id !== $connection->id;
    });
};

Worker::runAll();
