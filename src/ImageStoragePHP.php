<?php
/**
 * @link https://github.com/himiklab/virtual-whiteboard
 * @copyright Copyright (c) 2018 HimikLab
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

namespace app;

use SplDoublyLinkedList;

class ImageStoragePHP implements ImageStorageInterface
{
    /** @var int */
    private $historySize;
    /** @var SplDoublyLinkedList[] */
    private $images = [];

    public function __construct($historySize)
    {
        $this->historySize = $historySize;
    }

    public function push(string $whiteboardId, string $image, callable $callback): void
    {
        $imagesList = $this->getImagesList($whiteboardId);
        if ($imagesList->count() >= $this->historySize) {
            $imagesList->shift();
        }

        $imagesList->push($image);
        $callback();
    }

    public function pop(string $whiteboardId, callable $callback): void
    {
        $value = $this->getImagesList($whiteboardId)->pop();
        $callback($value);
    }

    public function top(string $whiteboardId, callable $callback): void
    {
        $callback($this->getImagesList($whiteboardId)->top());
    }

    public function count(string $whiteboardId, callable $callback): void
    {
        $callback($this->getImagesList($whiteboardId)->count());
    }

    public function clear(string $whiteboardId, callable $callback): void
    {
        unset($this->images[$whiteboardId]);
        $callback();
    }

    private function getImagesList(string $whiteboardId): SplDoublyLinkedList
    {
        return $this->images[$whiteboardId] ?? ($this->images[$whiteboardId] = new SplDoublyLinkedList());
    }
}
