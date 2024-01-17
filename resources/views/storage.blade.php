<script>
    function isArray(obj) {
        return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == '[object array]' && typeof obj.length == "number";
    }

    function isJson(obj) {
        return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == "[object object]" && typeof obj.length == "undefined";
    }

    let storages = {!! $value !!};
    if (isArray(storages)) {
        storages.forEach(storage => {
            window.localStorage.setItem(`__system:${storage.key}__`, storage.value);
        })
    } else if (isJson(storages)) {
        for (let key in storages) {
            let value = storages[key];
            window.localStorage.setItem(`__system:${key}__`, value);
        }
    }
</script>
