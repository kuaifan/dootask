function cacheKey(userId) {
    return `aiAssistant.floatButtonVisible.${userId}`;
}

export function loadFloatButtonVisible(userId) {
    if (userId <= 0) {
        return Promise.resolve(true);
    }
    return $A.IDBBoolean(cacheKey(userId), true).catch(() => true);
}

export function saveFloatButtonVisible(userId, visible) {
    if (userId <= 0) {
        return Promise.resolve();
    }
    return $A.IDBSet(cacheKey(userId), visible);
}
