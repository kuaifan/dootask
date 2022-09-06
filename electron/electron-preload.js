const {contextBridge, ipcRenderer} = require("electron")

let reqId = 1
let reqInfo = {}
let msgChangedListeners = {}
let fileChangedListeners = {}

ipcRenderer.on('mainResp', (event, resp) => {
    let callbacks = reqInfo[resp.reqId]

    if (resp.error) {
        callbacks.error(resp.msg, resp.e)
    } else {
        callbacks.callback(resp.data)
    }

    delete reqInfo[resp.reqId]
})

ipcRenderer.on('fileChanged', (event, resp) => {
    let listener = fileChangedListeners[resp.path]

    if (listener) {
        listener(resp.curr, resp.prev)
    }
})

contextBridge.exposeInMainWorld(
    'electron', {
        request: (msg, callback, error) => {
            msg.reqId = reqId++
            reqInfo[msg.reqId] = {callback: callback, error: error}

            if (msg.action == 'watchFile') {
                fileChangedListeners[msg.path] = msg.listener
                delete msg.listener
            }

            ipcRenderer.send('rendererReq', msg)
        },
        registerMsgListener: (action, callback) => {
            msgChangedListeners[action] = (event, args) => {
                callback(args)
            }
            ipcRenderer.on(action, msgChangedListeners[action])
        },
        registerMsgListenOnce: (action, callback) => {
            msgChangedListeners[action] = (event, args) => {
                callback(args)
            }
            ipcRenderer.once(action, msgChangedListeners[action])
        },
        removeMsgListener: (action) => {
            if (typeof msgChangedListeners[action] === "function") {
                ipcRenderer.removeListener(action, msgChangedListeners[action])
                delete msgChangedListeners[action]
            }
        },
        sendMessage: (action, args) => {
            ipcRenderer.send(action, args)
        },
        sendSyncMessage: (action, args) => {
            ipcRenderer.sendSync(action, args)
        }
    }
)

contextBridge.exposeInMainWorld(
    'process', {
        type: process.type,
        versions: process.versions
    }
)
