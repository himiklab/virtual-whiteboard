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

class DeskServer implements MessageComponentInterface
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
                $this->clientStorage->add($from, $data['deskId']);
                $this->imageStorage->count($data['deskId'], function ($count) use ($from, $data) {
                    if ($count !== 0) {
                        $this->sendUpdate($from, $data['deskId'], false);
                    }
                });
                break;

            case self::WS_MESSAGE_CLEAR:
                $this->imageStorage->clear(
                    $this->clientStorage->getDeskId($from),
                    function () use ($from) {
                        $this->sendClear($from);
                    }
                );
                break;

            case self::WS_MESSAGE_UPDATE:
                $deskId = $this->clientStorage->getDeskId($from);
                $this->imageStorage->push(
                    $deskId,
                    $data['data'],
                    function () use ($from, $deskId) {
                        $this->sendUpdate($from, $deskId, true);
                    }
                );
                break;

            case self::WS_MESSAGE_UNDO:
                $deskId = $this->clientStorage->getDeskId($from);
                $this->imageStorage->count($deskId, function ($count) use ($deskId, $from) {
                    if ($count !== 0) {
                        $this->imageStorage->pop($deskId, function () use ($deskId, $from, $count) {
                            if ($count === 1) {
                                $this->sendClear($from);
                            } else {
                                $this->sendUpdate($from, $deskId, true);
                            }
                        });
                    }
                });
                break;
        }
    }

    private function sendUpdate(ConnectionInterface $sentClient, string $deskId, bool $toAll): void
    {
        $this->imageStorage->top($deskId, function ($data) use ($sentClient, $toAll) {
            /** @var ConnectionInterface[] $clients */
            if ($toAll) {
                $clients = $this->clientStorage->getFromSameDesk($sentClient);
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
        foreach ($this->clientStorage->getFromSameDesk($sentClient) as $client) {
            /** @var ConnectionInterface $client */
            $client->send(
                \json_encode([
                    'type' => self::WS_MESSAGE_CLEAR,
                ])
            );
        }
    }
}
