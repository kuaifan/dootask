const state = {
    setStorage(key, value) {
        return this._storage(key, value);
    },

    getStorage(key, def = null) {
        let value = this._storage(key);
        return value || def;
    },

    getStorageString(key, def = '') {
        let value = this._storage(key);
        return typeof value === "string" || typeof value === "number" ? value : def;
    },

    getStorageNumber(key, def = 0) {
        let value = this._storage(key);
        return typeof value === "number" ? value : def;
    },

    getStorageBoolean(key, def = false) {
        let value = this._storage(key);
        return typeof value === "boolean" ? value : def;
    },

    _storage(key, value) {
        let keyName = 'state';
        if (typeof value === 'undefined') {
            return this._loadFromlLocal('__::', key, '', '__' + keyName + '__');
        } else {
            this._savaToLocal('__::', key, value, '__' + keyName + '__');
        }
    },

    _savaToLocal(id, key, value, keyName) {
        try {
            if (typeof keyName === 'undefined') keyName = '__seller__';
            let seller = window.localStorage[keyName];
            if (!seller) {
                seller = {};
                seller[id] = {};
            } else {
                seller = JSON.parse(seller);
                if (!seller[id]) {
                    seller[id] = {};
                }
            }
            seller[id][key] = value;
            window.localStorage[keyName] = JSON.stringify(seller);
        } catch (e) {
        }
    },

    _loadFromlLocal(id, key, def, keyName) {
        try {
            if (typeof keyName === 'undefined') keyName = '__seller__';
            let seller = window.localStorage[keyName];
            if (!seller) {
                return def;
            }
            seller = JSON.parse(seller)[id];
            if (!seller || typeof seller[key] === 'undefined') {
                return def;
            }
            return seller[key];
        } catch (e) {
            return def;
        }
    },
};

export default Object.assign(state, {
    projectChatShow: state.getStorageBoolean('projectChatShow', true),
})
