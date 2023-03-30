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
        'hideload',
        'timerange',
    ])).toString())
    const callData = state.callAt.find(item => item.key === callKey) || {}
    if (typeof callData.key === "undefined") {
        callData.key = callKey
        callData.updated = 0
        callData.deleted = 0
        state.callAt.push(callData)
        $A.IDBSet("callAt", state.callAt).then(_ => {})
    }

    /**
     * @returns {*}
     */
    this.get = () => {
        requestData.timerange = requestData.timerange || `${callData.updated}-${callData.deleted}`
        return requestData
    }

    /**
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

    /**
     * @returns {boolean}
     */
    this.showLoad = () => {
        return !requestData.hideload
    }

    return this
}

export function $callData(key, requestData, state) {
    return new __callData(key, requestData, state)
}

export function $urlSafe(value, encode = true) {
    if (value) {
        if (encode) {
            value = String(value).replace(/\+/g, "-").replace(/\//g, "_")
        } else {
            value = String(value).replace(/-/g, "+").replace(/_/g, "/")
        }
    }
    return value
}
