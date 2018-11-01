<?php
/**
 * @link https://github.com/himiklab/virtual-whiteboard
 * @copyright Copyright (c) 2018 HimikLab
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

namespace app;

interface ImageStorageInterface
{
    public function push(string $whiteboardId, string $image, callable $callback): void;

    public function pop(string $whiteboardId, callable $callback): void;

    public function top(string $whiteboardId, callable $callback): void;

    public function count(string $whiteboardId, callable $callback): void;

    public function clear(string $whiteboardId, callable $callback): void;
}
