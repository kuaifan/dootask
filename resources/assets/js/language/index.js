const utils = require('./utils')

const languageList = utils.languageList
const languageName = utils.getLanguage()
const languageCache = new Map();
const languageRegex = [];

if (typeof window.LANGUAGE_DATA === "undefined") {
    window.LANGUAGE_DATA = {}
}

/**
 * 初始化语言，需在加载完语言文件后调用
 */
function initLanguage() {
    if (typeof window.LANGUAGE_DATA === "undefined" || typeof window.LANGUAGE_DATA["key"] === "undefined") {
        return
    }
    const keys = window.LANGUAGE_DATA['key'] || []
    delete window.LANGUAGE_DATA['key'];
    //
    keys.forEach((key, index) => {
        if (/\(%[TM]\d+\)/.test(key)) {
            // 处理复杂的键值
            const _m = {};
            const translation = {
                key: new RegExp("^" + utils.replaceEscape(key) + "$"),
            }
            for (let language in window.LANGUAGE_DATA) {
                if (typeof languageList[language] === "undefined") {
                    continue
                }
                translation[language] = window.LANGUAGE_DATA[language][index]
                    ?.replace(/\(%([TM])(\d+)\)/g, function (_, type, index) {
                        if (type === 'M') {
                            _m[index] = index;
                        }
                        return "$" + index;
                    });
            }
            translation._m = Object.keys(_m);
            languageRegex.push(translation)
        } else {
            // 缓存简单的键值
            for (let language in window.LANGUAGE_DATA) {
                if (typeof languageList[language] === "undefined") {
                    continue
                }
                const result = window.LANGUAGE_DATA[language][index] || key
                languageCache.set(`${key}-${language}`, result);
            }
        }
    })
}

/**
 * 添加语言数据
 * @param data
 */
function addLanguage(data) {
    if (!$A.isArray(data)) {
        return
    }
    data.forEach(item => {
        const {key, general} = item
        if (!key) {
            return
        }
        if (general) {
            for (let language in window.LANGUAGE_DATA) {
                if (typeof languageList[language] === "undefined") {
                    continue
                }
                languageCache.set(`${key}-${language}`, general);
            }
        }
        for (let language in item) {
            if (language === 'key' || language === 'general') {
                continue
            }
            languageCache.set(`${key}-${language}`, item[language]);
        }
    })
}

/**
 * 设置语言
 * @param language
 * @param silence
 */
function setLanguage(language, silence = false) {
    if (language === undefined) {
        return
    }
    if (silence) {
        utils.saveLanguage(language);
        (async () => {
            await $A.IDBDel("callAt")
            $A.reloadUrl()
        })()
    } else {
        $A.modalConfirm({
            content: '切换语言需要刷新后生效，是否确定刷新？',
            cancelText: '取消',
            okText: '确定',
            onOk: () => setLanguage(language, true)
        })
    }
}

/**
 * 获取最新语言
 */
function getLanguage() {
    return utils.getLanguage();
}

/**
 * 转换语言
 * @param inputString
 * @returns {string|*}
 */
function switchLanguage(inputString) {
    if (typeof arguments[1] !== "undefined") {
        inputString = utils.replaceArgumentsLanguage(inputString, arguments)
    }
    if (typeof inputString !== "string" || !inputString) {
        return inputString
    }

    // 读取缓存
    const cacheKey = `${inputString}-${languageName}`;
    if (languageCache.has(cacheKey)) {
        return languageCache.get(cacheKey);
    }

    // 正则匹配
    for (const translation of languageRegex) {
        const { key, _m } = translation;
        const match = key.exec(inputString);
        if (match) {
            if (translation[languageName]) {
                const result = translation[languageName].replace(/\$(\d+)/g, (_, index) => {
                    if (_m.includes(index)) {
                        return switchLanguage(match[index]);
                    }
                    return match[index] || '';
                });
                languageCache.set(cacheKey, result);
                return result;
            }
            languageCache.set(cacheKey, inputString);
            return inputString;
        }
    }

    // 开发模式下，未翻译的文本自动添加到语言文件
    if (window.systemInfo.debug === "yes") {
        setTimeout(_ => {
            try {
                let cacheKey = '__language:Undefined__'
                let languageTmp = JSON.parse(window.localStorage.getItem(cacheKey) || '[]')
                if (!$A.isArray(languageTmp)) {
                    languageTmp = []
                }
                if (languageTmp.findIndex(item => item == inputString) === -1) {
                    languageTmp.push(inputString)
                    window.localStorage.setItem(cacheKey, JSON.stringify(languageTmp))
                }
            } catch (e) { }
        }, 10)
    }

    // 未匹配返回原始字符串
    languageCache.set(cacheKey, inputString);
    return inputString;
}

export {languageName, languageList, addLanguage, setLanguage, initLanguage, getLanguage, switchLanguage}
