<?php
/**
 * @link https://github.com/himiklab/virtual-whiteboard
 * @copyright Copyright (c) 2018 HimikLab
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

namespace app;

use Clue\React\Redis\Client as RedisClient;
use Clue\React\Redis\Factory as RedisFactory;
use React\EventLoop\LoopInterface;

class ImageStorageRedis implements ImageStorageInterface
{
    /** @var int */
    private $historySize;
    /** @var string */
    private $imagesPrefix;
    /** @var RedisClient */
    private $redis;

    public function __construct(
        LoopInterface $loop,
        int $historySize,
        string $redisAddress,
        string $imagesPrefix,
        int $redisDb = null
    )
    {
        $this->historySize = $historySize;
        $this->imagesPrefix = $imagesPrefix;
        (new RedisFactory($loop))->createClient($redisAddress)->then(function (RedisClient $client) use ($redisDb) {
            $this->redis = $client;
            if ($redisDb !== null) {
                $this->redis->select($redisDb);
            }
        });
    }

    public function __destruct()
    {
        $this->redis->end();
    }

    public function push(string $whiteboardId, string $image, callable $callback): void
    {
        $this->count($whiteboardId, function ($count) use ($whiteboardId, $image, $callback) {
            $pushCallback = function () use ($whiteboardId, $image, $callback) {
                $this->redis->rpush($this->getImagesKey($whiteboardId), $image)->then(function () use ($callback) {
                    $callback();
                });
            };

            if ($count >= $this->historySize) {
                $this->redis->lpop($this->getImagesKey($whiteboardId))->then($pushCallback);
            } else {
                $pushCallback();
            }
        });
    }

    public function pop(string $whiteboardId, callable $callback): void
    {
        $this->redis->rpop($this->getImagesKey($whiteboardId))->then(function ($response) use ($callback) {
            $callback((string)$response);
        });
    }

    public function top(string $whiteboardId, callable $callback): void
    {
        $this->redis->lrange($this->getImagesKey($whiteboardId), -1, -1)->then(function ($response) use ($callback) {
            $callback((string)$response[0]);
        });
    }

    public function count(string $whiteboardId, callable $callback): void
    {
        $this->redis->llen($this->getImagesKey($whiteboardId))->then(function ($response) use ($callback) {
            $callback((int)$response);
        });
    }

    public function clear(string $whiteboardId, callable $callback): void
    {
        $this->redis->del($this->getImagesKey($whiteboardId))->then($callback);
    }

    private function getImagesKey(string $whiteboardId): string
    {
        return $this->imagesPrefix . $whiteboardId;
    }
}
