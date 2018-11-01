<?php
/**
 * @link https://github.com/himiklab/virtual-whiteboard
 * @copyright Copyright (c) 2018 HimikLab
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

namespace app;

use Ratchet\ConnectionInterface;

class ClientStorage
{
    /** @var Object[] */
    private $clients = [];

    /** @var string[] */
    private $whiteboards = [];

    public function add(ConnectionInterface $connection, string $whiteboardId): void
    {
        $connectionHash = $this->getConnectionId($connection);
        $this->clients[$connectionHash] = $connection;
        $this->whiteboards[$connectionHash] = $whiteboardId;
    }

    public function delete(ConnectionInterface $connection): void
    {
        $connectionHash = $this->getConnectionId($connection);
        unset($this->clients[$connectionHash], $this->whiteboards[$connectionHash]);
    }

    public function getFromSameWhiteboard(ConnectionInterface $connection): \Generator
    {
        $connectionHash = $this->getConnectionId($connection);
        foreach (\array_keys($this->whiteboards, $this->whiteboards[$connectionHash]) as $currentHash) {
            yield $this->clients[$currentHash];
        }
    }

    public function count(): int
    {
        return \count($this->clients);
    }

    public function getWhiteboardId(ConnectionInterface $connection): string
    {
        return $this->whiteboards[$this->getConnectionId($connection)];
    }

    private function getConnectionId(ConnectionInterface $connection): string
    {
        return (string)\spl_object_id($connection);
    }
}
