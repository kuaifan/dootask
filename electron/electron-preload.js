const {
    contextBridge,
    ipcRenderer
} = require("electron");

let reqId = 1;
let reqInfo = {};
let fileChangedListeners = {};
let onlyChangedListeners = {};

ipcRenderer.on('mainResp', (event, resp) => {
    let callbacks = reqInfo[resp.reqId];

    if (resp.error) {
        callbacks.error(resp.msg, resp.e);
    } else {
        callbacks.callback(resp.data);
    }

    delete reqInfo[resp.reqId];
});

ipcRenderer.on('fileChanged', (event, resp) => {
    let listener = fileChangedListeners[resp.path];

    if (listener) {
        listener(resp.curr, resp.prev);
    }
});

contextBridge.exposeInMainWorld(
    'electron', {
        request: (msg, callback, error) => {
            msg.reqId = reqId++;
            if (typeof callback !== "function") {
                callback = function () {};
            }
            if (typeof error !== "function") {
                error = function () {};
            }
            reqInfo[msg.reqId] = {callback, error};
            if (msg.action == 'watchFile') {
                fileChangedListeners[msg.path] = msg.listener;
                delete msg.listener;
            }
            ipcRenderer.send('rendererReq', msg);
        },

        sendMessage: function (action, args) {
            ipcRenderer.send(action, args);
        },
        sendAsync: function (action, args) {
            return ipcRenderer.invoke(action, args)
        },

        listener: function (action, callback) {
            ipcRenderer.on(action, function (event, args) {
                callback(args);
            });
        },
        listenOnce: function (action, callback) {
            ipcRenderer.once(action, function (event, args) {
                callback(args);
            });
        },
        listenerOnly: function (action, callback) {
            if (typeof onlyChangedListeners[action] === "function") {
                ipcRenderer.removeListener(action, onlyChangedListeners[action])
                delete onlyChangedListeners[action]
            }
            onlyChangedListeners[action] = (event, args) => {
                callback(args)
            }
            ipcRenderer.on(action, onlyChangedListeners[action])
        },
    }
);

contextBridge.exposeInMainWorld(
    'process', {
        type: process.type,
        versions: process.versions,
        platform: process.platform,
    }
);
