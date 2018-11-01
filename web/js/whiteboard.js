/**
 * @link https://github.com/himiklab/virtual-whiteboard
 * @copyright Copyright (c) 2018 HimikLab
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

"use strict";
const WS_MESSAGE_CONNECT = "connect";
const WS_MESSAGE_CLEAR = "clear";
const WS_MESSAGE_UPDATE = "update";
const WS_MESSAGE_UNDO = "undo";
const MODE_DRAW = "draw";
const MODE_TEXT = "text";
const MAX_UID = 1000000000000;

const whiteboardId = document.getElementById("whiteboard-id").dataset.uid;
const WSConnection = new WebSocket('ws://localhost:8090');

let isMousePressed = false;
let isHasTextInput = false;
let currentPoint = {x: 0, y: 0};
let previousPoint = {x: 0, y: 0};
let mode = MODE_DRAW;

const canvas = document.getElementById("canvas");
const canvasContext = canvas.getContext("2d");
canvasContext.lineWidth = 5;
canvasContext.font = "25px sans-serif";

function sendWS(data) {
    WSConnection.send(JSON.stringify(data));
}

WSConnection.onopen = function () {
    sendWS({whiteboardId: whiteboardId, type: WS_MESSAGE_CONNECT});
};
WSConnection.onmessage = function (e) {
    let dataObject = JSON.parse(e.data);
    if (dataObject.type === WS_MESSAGE_CLEAR) {
        canvasClear();
    } else if (dataObject.type === WS_MESSAGE_UPDATE || dataObject.type === WS_MESSAGE_UNDO) {
        canvasDeserialize(dataObject.data);
    }
};

canvas.addEventListener("mousedown", function (e) {
    if (mode === MODE_DRAW) {
        canvasDraw(e.offsetX, e.offsetY);
        isMousePressed = true;
    } else if (mode === MODE_TEXT) {
        if (isHasTextInput) {
            return;
        }

        canvasTextInput(e.offsetX, e.offsetY);
    }
});

canvas.addEventListener("mousemove", function (e) {
    if (mode === MODE_DRAW) {
        if (isMousePressed) {
            canvasDraw(e.offsetX, e.offsetY);
        }
    }
});

document.getElementById("btn-clear").addEventListener("click", function () {
    canvasClear();
    sendWS({type: WS_MESSAGE_CLEAR});
});

document.getElementById("btn-draw").addEventListener("click", function () {
    mode = MODE_DRAW;
});

document.getElementById("btn-text").addEventListener("click", function () {
    mode = MODE_TEXT;
});

document.getElementById("btn-new").addEventListener("click", function () {
    window.location.href = location.protocol + '//' + location.host + location.pathname + "?uid=" +
        Math.floor(Math.random() * MAX_UID);
});

document.getElementById("input-file").addEventListener("change", function (e) {
    let fileReader = new FileReader();
    let img = new Image();

    img.onload = function () {
        canvasContext.drawImage(img, 0, 0, canvasContext.canvas.width, canvasContext.canvas.height);
    };
    fileReader.onloadend = function () {
        img.src = fileReader.result;
    };
    fileReader.readAsDataURL(e.target.files[0]);
});

document.body.addEventListener("mouseup", function () {
    if (mode === MODE_DRAW) {
        if (isMousePressed) {
            isMousePressed = false;
            sendWS({
                type: WS_MESSAGE_UPDATE,
                data: canvasSerialize()
            });
        }
    }
});

document.addEventListener("keydown", function (e) {
    if (e.keyCode === 90 && e.ctrlKey) {
        sendWS({type: WS_MESSAGE_UNDO});
    }
});

[].forEach.call(document.getElementById("color-buttons").getElementsByTagName("button"), function (item) {
    item.addEventListener("click", function () {
        canvasSetColor(this.style.backgroundColor);
    });
});

[].forEach.call(document.getElementById("width-buttons").getElementsByTagName("button"), function (item) {
    item.addEventListener("click", function () {
        canvasSetWidth(this.dataset.brushWidth);
    });
});

//-------------------------------------------------------

function canvasDraw(x, y) {
    currentPoint = {x: x, y: y};
    if (isMousePressed) {
        canvasContext.beginPath();
        canvasContext.moveTo(previousPoint.x, previousPoint.y);
        canvasContext.lineTo(currentPoint.x, currentPoint.y);
        canvasContext.closePath();
        canvasContext.stroke();
    }

    previousPoint = currentPoint;
}

function canvasSerialize() {
    return canvasContext.canvas.toDataURL();
}

function canvasDeserialize(data) {
    canvasClear();

    const img = new Image();
    img.onload = function () {
        canvasContext.drawImage(img, 0, 0);
    };

    img.src = data;
}

function canvasClear() {
    canvasContext.clearRect(0, 0, canvasContext.canvas.width, canvasContext.canvas.height);
}

function canvasSetColor(color) {
    canvasContext.strokeStyle = color;
}

function canvasSetWidth(width) {
    canvasContext.lineWidth = width;
    canvasContext.font = (width * 5) + "px sans-serif";
}

function canvasTextInput(x, y) {
    const input = document.createElement("input");
    input.type = "text";
    input.style.position = "absolute";
    input.style.left = (x + 10) + "px";
    input.style.top = (y - 10) + "px";
    input.onkeydown = function (e) {
        if (e.keyCode === 13) {
            if (this.value !== "") {
                drawText(this.value, parseInt(this.style.left), parseInt(this.style.top));
            }
            document.getElementById("canvas-div").removeChild(input);
            isHasTextInput = false;
        }
    };
    document.getElementById("canvas-div").appendChild(input);
    input.focus();
    isHasTextInput = true;
}

function drawText(text, x, y) {
    canvasContext.fillText(text, x - 10, y + 10);
    sendWS({type: WS_MESSAGE_UPDATE, data: canvasSerialize()});
}
