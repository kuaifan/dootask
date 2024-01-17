const utils = require('./utils')

const languageList = utils.languageList
const languageName = utils.getLanguage()
const languageRege = {}

if (typeof window.LANGUAGE_DATA === "undefined") {
    window.LANGUAGE_DATA = {}
}

/**
 * 添加语言数据
 * @param data
 */
function addLanguage(data) {
    if (!$A.isArray(data)) {
        return
    }
    const keys = Object.assign(Object.keys(languageList))
    data.some(item => {
        let index = -1;
        item.key && keys.some(key => {
            const value = item[key] || item['general'] || null
            if (value && typeof window.LANGUAGE_DATA[key] !== "undefined") {
                index = window.LANGUAGE_DATA[key].push(value) - 1
            }
        })
        if (index > -1) {
            window.LANGUAGE_DATA['key'][item.key] = index
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
        utils.saveLanguage(language)
        $A.reloadUrl()
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
 * @param text
 * @returns {string|*}
 */
function switchLanguage(text) {
    if (typeof arguments[1] !== "undefined") {
        return switchLanguage(utils.replaceArgumentsLanguage(text, arguments))
    }
    if (typeof text !== "string" || !text) {
        return text
    }
    //
    if (typeof window.LANGUAGE_DATA === "undefined"
        || typeof window.LANGUAGE_DATA["key"] === "undefined"
        || typeof window.LANGUAGE_DATA[languageName] === "undefined") {
        return text
    }
    const index = window.LANGUAGE_DATA["key"][text] || -1
    if (index > -1) {
        return window.LANGUAGE_DATA[languageName][index] || text
    }
    if (typeof languageRege[text] === "undefined") {
        languageRege[text] = false
        for (let key in window.LANGUAGE_DATA["key"]) {
            if (key.indexOf("(*)") > -1) {
                const rege = new RegExp("^" + utils.replaceEscape(key) + "$", "g")
                if (rege.test(text)) {
                    let j = 0
                    const index = window.LANGUAGE_DATA["key"][key]
                    const value = (window.LANGUAGE_DATA[languageName][index] || key)?.replace(/\(\*\)/g, function () {
                        return "$" + (++j)
                    })
                    languageRege[text] = {rege, value}
                    break
                }
            }
        }
    }
    if (languageRege[text]) {
        return text.replace(languageRege[text].rege, languageRege[text].value)
    }
    if (window.systemInfo.debug === "yes") {
        setTimeout(_ => {
            try {
                let key = '__language:Undefined__'
                let languageTmp = JSON.parse(window.localStorage.getItem(key) || '[]')
                if (!$A.isArray(languageTmp)) {
                    languageTmp = []
                }
                let tmpRege = null
                let tmpData = languageTmp.find((val) => {
                    tmpRege = new RegExp("^" + val.replace(/\(\*\)/g, "(.*?)") + "$", "g")
                    return !!text.match(tmpRege)
                })
                if (!tmpData) {
                    languageTmp.push(text)
                    window.localStorage.setItem(key, JSON.stringify(languageTmp))
                }
            } catch (e) {
            }
        }, 10)
    }
    return text
}

export {languageName, languageList, addLanguage, setLanguage, getLanguage, switchLanguage}
