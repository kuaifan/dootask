export default {
    contextKeyPattern: /^\[([a-z][a-z0-9_]*)]\.([\s\S]+)$/,

    /**
     * 语言类型
     */
    languageList: {
        "zh": "简体中文",
        "zh-CHT": "繁體中文",
        "en": "English",
        "ko": "한국어",
        "ja": "日本語",
        "de": "Deutsch",
        "fr": "Français",
        "id": "Indonesia",
        "ru": "Русский язык",
    },

    /**
     * 替换(*)遍历
     * @param text
     * @param objects
     * @returns {*}
     */
    replaceArgumentsLanguage(text, objects) {
        let j = 1;
        return text.replace(/\(\*\)/g, () => this.getArgumentLanguage(objects[j++]));
    },

    /**
     * 获取语言参数
     * @param value
     * @returns {string|*}
     */
    getArgumentLanguage(value) {
        if (value === null || typeof value === "undefined" || typeof value === "object") {
            return '';
        }
        return value;
    },

    /**
     * 规范化参数化语言键
     * @param text
     * @returns {string}
     */
    normalizeArgumentsLanguage(text) {
        return text.replace(/\(%[TM]\d+\)/g, "(*)");
    },

    /**
     * 移除翻译上下文前缀，例如 [weekday].一 -> 一
     */
    stripContextLanguageKey(text) {
        if (typeof text !== "string") {
            return text;
        }
        return text.match(this.contextKeyPattern)?.[2] || text;
    },

    /**
     * 获取语言
     * @returns {string}
     */
    getLanguage() {
        let lang = window.localStorage.getItem("__system:languageName__")
        if (typeof lang === "string" && typeof this.languageList[lang] !== "undefined") {
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
                if (typeof this.languageList[navLang] !== "undefined") {
                    lang = navLang
                }
                break;
        }
        this.saveLanguage(lang)
        return lang
    },

    /**
     * 保存语言
     * @param lang
     */
    saveLanguage(lang) {
        window.localStorage.setItem("__system:languageName__", lang)
    },
}
