export default {
    /**
     * 语言类型
     */
    languageTypes: {
        "zh": "简体中文",
        "zh-CHT": "繁體中文",
        "en": "English",
        "ko": "한국어",
        "ja": "日本語",
        "de": "Deutsch",
        "fr": "Français",
        "id": "Indonesia",
    },

    /**
     * 替换(*)遍历
     * @param text
     * @param objects
     * @returns {*}
     */
    replaceArgumentsLanguage(text, objects) {
        let j = 1;
        while (text.indexOf("(*)") !== -1) {
            if (typeof objects[j] === "object") {
                text = text.replace("(*)", "");
            } else {
                text = text.replace("(*)", objects[j]);
            }
            j++;
        }
        return text;
    },

    /**
     * 译文转义
     * @param val
     * @returns {string|*}
     */
    replaceEscape(val) {
        if (!val || val == '') {
            return '';
        }
        return val.replace(/\(\*\)/g, "~%~").replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&').replace(/~%~/g, '(.*?)');
    },

    /**
     * 获取语言
     * @returns {string}
     */
    getLanguage() {
        let lang = window.localStorage.getItem("__language:type__")
        if (typeof lang === "string" && typeof this.languageTypes[lang] !== "undefined") {
            return lang;
        }
        lang = 'en';
        let navLang = ((window.navigator.language || navigator.userLanguage) + "").toLowerCase();
        switch (navLang) {
            case "zh":
            case "cn":
            case "zh-cn":
                lang = 'zh'
                break;
            case "zh-tw":
            case "zh-tr":
            case "zh-hk":
            case "zh-cnt":
            case "zh-cht":
                lang = 'zh-CHT'
                break;
            default:
                if (typeof this.languageTypes[navLang] !== "undefined") {
                    lang = navLang
                }
                break;
        }
        window.localStorage.setItem("__language:type__", lang)
        return lang
    }
}
