<?php
/**
 * @link https://github.com/himiklab/virtual-whiteboard
 * @copyright Copyright (c) 2018 HimikLab
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

if (!isset($_GET['uid'])) {
    \header('Location: ' . $_SERVER['REQUEST_URI'] . '?uid=' . \random_int(0, 1000000000000));
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Виртуальная доска</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"
          integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous"/>
    <link rel="stylesheet" href="css/whiteboard.css"/>
</head>
<body>
<div class="container-fluid">
    <div class="row top-buffer">
        <div class="col-2">
            <button id="btn-clear" class="btn btn-danger">Очистить</button>
            <button id="btn-new" class="btn btn-danger">Новая доска</button>
        </div>
        <div class="col-2">
            <button class="btn btn-sm border" id="btn-text">&nbsp;&nbsp;&nbsp;Текст&nbsp;&nbsp;&nbsp;</button>
            <button class="btn btn-sm border" id="btn-draw">Рисование</button>
        </div>
        <div class="col-2" id="color-buttons">
            <button class="btn btn-sm border" style="background:rgb(0, 0, 0)">&nbsp;&nbsp;</button>
            <button class="btn btn-sm border" style="background:rgb(255, 0, 0)">&nbsp;&nbsp;</button>
            <button class="btn btn-sm border" style="background:rgb(0, 255, 0)">&nbsp;&nbsp;</button>
            <button class="btn btn-sm border" style="background:rgb(0, 0, 255)">&nbsp;&nbsp;</button>
            <button class="btn btn-sm border" style="background:rgb(255, 255, 255)">&nbsp;&nbsp;</button>
        </div>
        <div class="col-3" id="width-buttons">
            <button class="btn btn-sm" data-brush-width="2">Тонкий</button>
            <button class="btn btn-sm" data-brush-width="5">Нормальный</button>
            <button class="btn btn-sm" data-brush-width="10">Большой</button>
            <button class="btn btn-sm" data-brush-width="20">Огромный</button>
        </div>
        <div class="col-1">
            <input type="file" id="input-file"/>
        </div>
    </div>
    <div class="row top-buffer">
        <div class="col-8 mx-auto" id="canvas-div">
            <canvas id="canvas" width="1280" height="1024" class="border"></canvas>
        </div>
    </div>
</div>
<div id="whiteboard-id" data-uid="<?= \htmlspecialchars($_GET['uid']) ?>"></div>

<script src="js/whiteboard.js"></script>
</body>
</html>