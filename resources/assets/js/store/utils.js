/**
 * @param key
 * @param requestData
 * @param state
 * @returns {$callData}
 */
function __callData(key, requestData, state) {
    if (!$A.isJson(requestData)) {
        requestData = {}
    }
    const callKey = key + "::" + encodeURIComponent(new URLSearchParams($.sortObject(requestData, [
        'page',
        'pagesize',
        'timerange',
    ])).toString())
    const callData = state.callAt.find(item => item.key === callKey) || {}
    if (typeof callData.key === "undefined") {
        callData.key = callKey
        callData.updated = 0
        callData.deleted = 0
        state.callAt.push(callData)
        $A.IDBSet("callAt", state.callAt).then(_ => {
        })
    }

    /**
     * @returns {*}
     */
    this.get = () => {
        requestData.timerange = requestData.timerange || `${callData.updated || 0},${callData.deleted || 0}`
        return requestData
    }

    /**
     * @param total
     * @param current_page
     * @param deleted_id
     * @returns {Promise<unknown>}
     */
    this.save = ({total, current_page, deleted_id}) => {
        return new Promise(resolve => {
            if (current_page === 1) {
                let hasUpdate = false
                const time = $A.Time()
                if (total > 0) {
                    callData.updated = time
                    hasUpdate = true
                }
                if ($A.isArray(deleted_id) && deleted_id.length > 0) {
                    callData.deleted = time
                    hasUpdate = true
                } else {
                    deleted_id = []
                }
                if (hasUpdate) {
                    $A.IDBSet("callAt", state.callAt).then(_ => resolve(deleted_id))
                } else {
                    resolve(deleted_id)
                }
            }
        })
    }

    return this
}

export function $callData(key, requestData, state) {
    return new __callData(key, requestData, state)
}

export function $urlSafe(value, encode = true) {
    if (value) {
        if (encode) {
            value = String(value).replace(/\+/g, "-").replace(/\//g, "_").replace(/\n/g, '$')
        } else {
            value = String(value).replace(/\-/g, "+").replace(/\_/g, "/").replace(/\$/g, '\n')
        }
    }
    return value
}

/**
 * EventSource
 */
const SSEDefaultOptions = {
    retry: 5,
    interval: 3 * 1000,
}

export class SSEClient {
    constructor(url, options = SSEDefaultOptions) {
        this.url = url;
        this.es = null;
        this.options = options;
        this.retry = options.retry;
        this.timer = null;
    }

    _onOpen() {
        if (window.systemInfo.debug === "yes") {
            console.log("SSE open: " + this.url);
        }
    }

    _onMessage(type, handler) {
        return (event) => {
            this.retry = this.options.retry;
            if (typeof handler === "function") {
                handler(type, event);
            }
        };
    }

    _onError(type, handler) {
        return () => {
            if (window.systemInfo.debug === "yes") {
                console.log("SSE retry: " + this.url);
            }
            if (this.es) {
                this._removeAllEvent(type, handler);
                this.unsunscribe();
            }

            if (this.retry > 0) {
                this.retry--;
                this.timer = setTimeout(() => {
                    this.subscribe(type, handler);
                }, this.options.interval);
            }
        };
    }

    _removeAllEvent(type, handler) {
        type = $A.isArray(type) ? type : [type]
        this.es.removeEventListener("open", this._onOpen);
        type.some(item => {
            this.es.removeEventListener(item, this._onMessage(item, handler));
        })
        this.es.removeEventListener("error", this._onError(type, handler));
    }

    subscribe(type, handler) {
        type = $A.isArray(type) ? type : [type]
        this.es = new EventSource(this.url);
        this.es.addEventListener("open", this._onOpen);
        type.some(item => {
            this.es.addEventListener(item, this._onMessage(item, handler));
        })
        this.es.addEventListener("error", this._onError(type, handler));
    }

    unsunscribe() {
        if (this.es) {
            this.es.close();
            this.es = null;
        }
        if (this.timer) {
            clearTimeout(this.timer);
        }
        if (window.systemInfo.debug === "yes") {
            console.log("SSE cancel: " + this.url);
        }
    }
}
