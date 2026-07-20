const utils = require('./utils')

const languageList = utils.languageList
const languageName = utils.getLanguage()
const languageCache = new Map();
const languageTemplateCache = new Map();

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
            // 缓存参数化键值
            const template = utils.normalizeArgumentsLanguage(key)
            const translateArguments = new Set();
            key.replace(/\(%M(\d+)\)/g, (_, index) => {
                translateArguments.add(index)
            })
            for (let language in window.LANGUAGE_DATA) {
                if (typeof languageList[language] === "undefined") {
                    continue
                }
                languageTemplateCache.set(`${template}-${language}`, {
                    text: window.LANGUAGE_DATA[language][index],
                    translateArguments,
                });
            }
        } else {
            // 缓存简单的键值
            for (let language in window.LANGUAGE_DATA) {
                if (typeof languageList[language] === "undefined") {
                    continue
                }
                const result = window.LANGUAGE_DATA[language][index]
                if (result || utils.stripContextLanguageKey(key) === key) {
                    languageCache.set(`${key}-${language}`, result || key);
                }
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
            $A.Electron?.sendMessage('recreatePreloadPool');
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
    if (typeof inputString !== "string" || !inputString) {
        return inputString
    }

    const fallbackString = utils.stripContextLanguageKey(inputString);
    const lookupStrings = fallbackString === inputString ? [inputString] : [inputString, fallbackString];

    if (arguments.length > 1) {
        for (const lookupString of lookupStrings) {
            const templateKey = `${lookupString}-${languageName}`;
            const template = languageTemplateCache.get(templateKey);
            if (!template?.text) {
                continue;
            }
            return template.text.replace(/\(%[TM](\d+)\)/g, (_, index) => {
                const value = utils.getArgumentLanguage(arguments[index]);
                return template.translateArguments.has(index) ? switchLanguage(String(value)) : value;
            });
        }
        inputString = utils.replaceArgumentsLanguage(fallbackString, arguments)
    } else {
        for (const lookupString of lookupStrings) {
            const result = languageCache.get(`${lookupString}-${languageName}`);
            if (result) {
                return result;
            }
        }
        inputString = fallbackString;
    }

    // 读取缓存
    const cacheKey = `${inputString}-${languageName}`;
    if (languageCache.has(cacheKey)) {
        return languageCache.get(cacheKey);
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
