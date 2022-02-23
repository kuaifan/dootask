const {
    contextBridge,
    ipcRenderer
} = require("electron");

let reqId = 1;
let reqInfo = {};
let fileChangedListeners = {};

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
            reqInfo[msg.reqId] = {callback: callback, error: error};

            if (msg.action == 'watchFile') {
                fileChangedListeners[msg.path] = msg.listener;
                delete msg.listener;
            }

            ipcRenderer.send('rendererReq', msg);
        },
        registerMsgListener: (action, callback) => {
            ipcRenderer.on(action, (event, args) => {
                callback(args);
            });
        },
        listenOnce: (action, callback) => {
            ipcRenderer.once(action, (event, args) => {
                callback(args);
            });
        },
        sendMessage: (action, args) => {
            ipcRenderer.send(action, args);
        },
        sendSyncMessage: (action, args) => {
            ipcRenderer.sendSync(action, args);
        }
    }
);

contextBridge.exposeInMainWorld(
    'process', {
        type: process.type,
        versions: process.versions
    }
);
