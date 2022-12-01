import {languageDefaultData} from "./data";

let languageUtils = {
    /**
     * 是否数组
     * @param obj
     * @returns {boolean}
     */
    isArray(obj) {
        return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == '[object array]' && typeof obj.length == "number";
    },

    /**
     * 转换Ascii编码
     * @param value
     * @returns {string}
     */
    convertAscii(value) {
        if (typeof value !== "string") {
            value = String(value)
        }
        let nativecode = value.split("");
        let ascii = "";
        for (let i = 0; i < nativecode.length; i++) {
            let code = Number(nativecode[i].charCodeAt(0));
            if (code > 127) {
                let charAscii = code.toString(16);
                charAscii = String("0000").substring(charAscii.length, 4) + charAscii;
                ascii += "\\u" + charAscii;
            } else {
                ascii += nativecode[i];
            }
        }
        return ascii
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
        if (typeof window.localStorage['__language:type__'] === "string") {
            return window.localStorage['__language:type__'];
        }
        let lang = 'EN';
        let navLang = ((window.navigator.language || navigator.userLanguage) + "").toUpperCase();
        switch (navLang) {
            case "EN":
            case "KM":
            case "TH":
            case "KO":
            case "JA":
                lang = navLang
                break;
            case "ZH-CN":
            case "ZH":
                lang = 'CN'
                break;
            case "ZH-TW":
            case "ZH-HK":
                lang = 'TC'
                break;
        }
        return window.localStorage['__language:type__'] = lang;
    }
};
let languageInit = false;
let languageData = [];
let languageType = languageUtils.getLanguage();
let languageList = {
    "EN": "English",
    "KM": "ភាសាខ្មែរ",
    "TH": "ภาษาไทย",
    "KO": "한국어",
    "JA": "日本語",
    "CN": "简体中文",
    "TC": "繁體中文",
};
let languageAsciis = {};

/**
 * 添加语言数据
 * @param data
 */
function addLanguage(data) {
    if (!languageUtils.isArray(data)) {
        return;
    }
    languageData.unshift(...data);
}

/**
 * 设置语言
 * @param language
 */
function setLanguage(language) {
    if (language === undefined) {
        return
    }
    $A.modalConfirm({
        content: '切换语言需要刷新后生效，是否确定刷新？',
        cancelText: '取消',
        okText: '确定',
        onOk: () => {
            window.localStorage['__language:type__'] = language;
            $A.reloadUrl()
        }
    });
}

/**
 * 获取当前语言
 * @returns {string}
 */
function getLanguage() {
    return languageType;
}

/**
 * 转换语言
 * @param text
 * @returns {string|*}
 */
function switchLanguage(text) {
    if (typeof arguments[1] !== "undefined") {
        return switchLanguage(languageUtils.replaceArgumentsLanguage(text, arguments));
    }
    if (typeof text !== "string" || !text) {
        return text;
    }
    //
    if (languageInit === false) {
        languageInit = true;
        addLanguage(languageDefaultData);
        addLanguage(window.languageData);
    }
    //
    const ascii = languageUtils.convertAscii(text)
    if (typeof languageAsciis[ascii] === "undefined") {
        let tmpKey = null;
        let tmpRege = null;
        let tmpData = languageData.find((obj) => {
            tmpKey = `${obj._ || obj.CN}`
            if (tmpKey.indexOf("(*)") === -1) {
                tmpRege = null;
                return text == tmpKey
            } else {
                tmpRege = new RegExp("^" + languageUtils.replaceEscape(tmpKey) + "$", "g");
                return !!text.match(tmpRege);
            }
        });
        languageAsciis[ascii] = {rege: tmpRege, data: tmpData};
    }
    const {rege, data} = languageAsciis[ascii];
    if (data) {
        let value = data[languageType];
        if (value) {
            if (rege === null) {
                return value
            }
            let index = 0;
            value = value.replace(/\(\*\)/g, function () {
                return "$" + (++index);
            });
            return text.replace(rege, value);
        }
    }
    //
    if (window.systemInfo.debug === "yes") {
        setTimeout(_ => {
            try {
                let key = '__language:Undefined__';
                let languageTmp = JSON.parse(window.localStorage[key] || '[]');
                if (!languageUtils.isArray(languageTmp)) {
                    languageTmp = [];
                }
                let tmpRege = null;
                let tmpData = languageTmp.find((val) => {
                    tmpRege = new RegExp("^" + val.replace(/\(\*\)/g, "(.*?)") + "$", "g");
                    return !!text.match(tmpRege);
                });
                if (!tmpData) {
                    languageTmp.push(text);
                    window.localStorage[key] = JSON.stringify(languageTmp);
                }
            } catch (e) { }
        }, 10)
    }
    //
    return text;
}

export { languageType, languageList, addLanguage, setLanguage, getLanguage, switchLanguage };
