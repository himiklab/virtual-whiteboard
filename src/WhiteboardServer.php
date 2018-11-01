<?php
/**
 * @link https://github.com/himiklab/virtual-whiteboard
 * @copyright Copyright (c) 2018 HimikLab
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

namespace app;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use React\EventLoop\LoopInterface;

class WhiteboardServer implements MessageComponentInterface
{
    private const WS_MESSAGE_CONNECT = 'connect';
    private const WS_MESSAGE_CLEAR = 'clear';
    private const WS_MESSAGE_UPDATE = 'update';
    private const WS_MESSAGE_UNDO = 'undo';

    /** @var \React\EventLoop\LoopInterface */
    private $loop;
    /** @var ClientStorage */
    private $clientStorage;
    /** @var ImageStorageRedis */
    private $imageStorage;
    /** @var bool */
    private $debug;

    public function __construct(
        LoopInterface $loop,
        ClientStorage $clientStorage,
        ImageStorageInterface $imageStorage,
        bool $debug = false
    )
    {
        $this->loop = $loop;
        $this->clientStorage = $clientStorage;
        $this->imageStorage = $imageStorage;
        $this->debug = $debug;
    }

    public function onOpen(ConnectionInterface $connection): void
    {
    }

    public function onMessage(ConnectionInterface $from, $message): void
    {
        $this->parseMessage($from, $message);
    }

    public function onClose(ConnectionInterface $connection): void
    {
        $this->clientStorage->delete($connection);

        if ($this->debug && $this->clientStorage->count() === 0) {
            $this->loop->stop();
        }
    }

    public function onError(ConnectionInterface $connection, Exception $e): void
    {
        $connection->close();
        if ($this->debug) {
            throw $e;
        }

        echo "An error has occurred: {$e->getMessage()}\n";
    }

    private function parseMessage(ConnectionInterface $from, string $message): void
    {
        $data = \json_decode($message, true);

        switch ($data['type']) {
            case self::WS_MESSAGE_CONNECT:
                $this->clientStorage->add($from, $data['whiteboardId']);
                $this->imageStorage->count($data['whiteboardId'], function ($count) use ($from, $data) {
                    if ($count !== 0) {
                        $this->sendUpdate($from, $data['whiteboardId'], false);
                    }
                });
                break;

            case self::WS_MESSAGE_CLEAR:
                $this->imageStorage->clear(
                    $this->clientStorage->getWhiteboardId($from),
                    function () use ($from) {
                        $this->sendClear($from);
                    }
                );
                break;

            case self::WS_MESSAGE_UPDATE:
                $whiteboardId = $this->clientStorage->getWhiteboardId($from);
                $this->imageStorage->push(
                    $whiteboardId,
                    $data['data'],
                    function () use ($from, $whiteboardId) {
                        $this->sendUpdate($from, $whiteboardId, true);
                    }
                );
                break;

            case self::WS_MESSAGE_UNDO:
                $whiteboardId = $this->clientStorage->getWhiteboardId($from);
                $this->imageStorage->count($whiteboardId, function ($count) use ($whiteboardId, $from) {
                    if ($count !== 0) {
                        $this->imageStorage->pop($whiteboardId, function () use ($whiteboardId, $from, $count) {
                            if ($count === 1) {
                                $this->sendClear($from);
                            } else {
                                $this->sendUpdate($from, $whiteboardId, true);
                            }
                        });
                    }
                });
                break;
        }
    }

    private function sendUpdate(ConnectionInterface $sentClient, string $whiteboardId, bool $toAll): void
    {
        $this->imageStorage->top($whiteboardId, function ($data) use ($sentClient, $toAll) {
            /** @var ConnectionInterface[] $clients */
            if ($toAll) {
                $clients = $this->clientStorage->getFromSameWhiteboard($sentClient);
            } else {
                $clients = [$sentClient];
            }

            foreach ($clients as $client) {
                $client->send(
                    \json_encode([
                        'type' => self::WS_MESSAGE_UPDATE,
                        'data' => $data,
                    ])
                );
            }
        });
    }

    private function sendClear(ConnectionInterface $sentClient): void
    {
        foreach ($this->clientStorage->getFromSameWhiteboard($sentClient) as $client) {
            /** @var ConnectionInterface $client */
            $client->send(
                \json_encode([
                    'type' => self::WS_MESSAGE_CLEAR,
                ])
            );
        }
    }
}
