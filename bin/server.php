<?php
/**
 * @link https://github.com/himiklab/virtual-whiteboard
 * @copyright Copyright (c) 2018 HimikLab
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

use app\ClientStorage;
use app\WhiteboardServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as EventLoopFactory;
use React\Socket\Server as SocketServer;

require \dirname(__DIR__) . '/vendor/autoload.php';

$loop = EventLoopFactory::create();
$imageStorage = new \app\ImageStorageRedis($loop, 100, 'localhost', 'whiteboard_images_', 2);
//$imageStorage = new \app\ImageStoragePHP(100);

$server = new IoServer(
    new HttpServer(
        new WsServer(
            new WhiteboardServer($loop, new ClientStorage(), $imageStorage, true)
        )
    ),
    new SocketServer('0.0.0.0:8090', $loop),
    $loop
);

$server->run();
