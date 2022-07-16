const languageTypeLists = {
    "EN": "English",
    "KM": "ភាសាខ្មែរ",
    "TH": "ภาษาไทย",
    "KO": "한국어",
    "JA": "日本語",
    "CN": "简体中文",
    "TC": "繁體中文",
};
const languageCachesObjects = {};
const languageListenerObjects = [];

export default {
    install(Vue) {
        Vue.mixin({
            data() {
                return {
                    languageInit: false,
                    languageData: [],
                    languageType: window.localStorage['__language:type__'] || this.__getNavigatorLanguage(),
                    languageList: languageTypeLists,
                }
            },

            watch: {
                languageType: {
                    handler(type) {
                        if (type && typeof this.initLanguage === "function") {
                            this.initLanguage();
                        }
                    },
                    immediate: true
                },
            },

            methods: {
                /**
                 * 获取浏览器默认语言
                 * @returns {string}
                 * @private
                 */
                __getNavigatorLanguage() {
                    let lang = 'EN';
                    let navLang = (navigator.language || navigator.userLanguage + "").toUpperCase();
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
                },

                /**
                 * 初始化语言数据
                 * @private
                 */
                __initLanguageData() {
                    if (this.languageInit === undefined) {
                        this.languageInit = false;
                        this.languageData = [];
                        this.languageType = window.localStorage['__language:type__'] || this.__getNavigatorLanguage();
                        this.languageList = languageTypeLists;
                    }
                    if (this.languageInit === false) {
                        this.languageInit = true;
                        //
                        this.addLanguageData(require("./language.js").default);
                        this.addLanguageData(window.languageData);
                        //
                        languageListenerObjects.push((lang) => {
                            this.languageType = lang;
                        });
                    }
                },

                /**
                 * 是否数组
                 * @param obj
                 * @returns {boolean}
                 * @private
                 */
                __isArray(obj) {
                    return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == '[object array]' && typeof obj.length == "number";
                },

                /**
                 * 转换Ascii编码
                 * @param value
                 * @returns {string}
                 * @private
                 */
                __convertAscii(value) {
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
                 * 监听语言变化
                 * @param callback
                 */
                setLanguageListener(callback) {
                    if (typeof callback === 'function') {
                        languageListenerObjects.push((lang) => {
                            callback(lang);
                        });
                    }
                },

                /**
                 * 语言包数据
                 * @param data
                 */
                addLanguageData(data) {
                    if (!this.__isArray(data)) {
                        return;
                    }
                    this.__initLanguageData();
                    this.languageData.unshift(...data);
                },

                /**
                 * 变化语言
                 * @param language
                 */
                setLanguage(language) {
                    if (language === undefined) {
                        return
                    }
                    this.__initLanguageData();
                    setTimeout(() => {
                        window.localStorage['__language:type__'] = language;
                        languageListenerObjects.forEach((call) => {
                            if (typeof call === 'function') {
                                call(language);
                            }
                        });
                    }, 10)
                },

                /**
                 * 获取语言
                 * @returns {*}
                 */
                getLanguage() {
                    this.__initLanguageData();
                    return this.languageType;
                },

                /**
                 * 替换(*)遍历
                 * @param text
                 * @param objects
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
                 * 显示语言
                 * @return {string}
                 */
                $L(text) {
                    if (typeof arguments[1] !== "undefined") {
                        return this.$L(this.replaceArgumentsLanguage(text, arguments));
                    }
                    if (typeof text !== "string" || !text) {
                        return text;
                    }
                    this.__initLanguageData();
                    //
                    const ascii = this.__convertAscii(text)
                    if (typeof languageCachesObjects[ascii] === "undefined") {
                        let tmpKey = null;
                        let tmpRege = null;
                        let tmpData = this.languageData.find((obj) => {
                            tmpKey = `${obj._ || obj.CN}`
                            if (tmpKey.indexOf("(*)") === -1) {
                                tmpRege = null;
                                return text == tmpKey
                            } else {
                                tmpRege = new RegExp("^" + this.replaceEscape(tmpKey) + "$", "g");
                                return !!text.match(tmpRege);
                            }
                        });
                        languageCachesObjects[ascii] = {rege: tmpRege, data: tmpData};
                    }
                    const {rege, data} = languageCachesObjects[ascii];
                    if (data) {
                        let value = data[this.languageType];
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
                    if (window.systemInfo.debug === "yes" && this.languageType == "CN") {
                        setTimeout(_ => {
                            try {
                                let key = '__language:Undefined__';
                                let languageTmp = JSON.parse(window.localStorage[key] || '[]');
                                if (!this.__isArray(languageTmp)) {
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
            }
        });
    }
}
