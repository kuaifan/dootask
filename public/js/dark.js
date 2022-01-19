;(function () {
    'use strict';

    let util = {
        getValue(name) {
            return GM_getValue(name);
        },

        setValue(name, value) {
            GM_setValue(name, value);
        },

        addStyle(id, tag, css) {
            tag = tag || 'style';
            let doc = document, styleDom = doc.getElementById(id);
            if (styleDom) return;
            let style = doc.createElement(tag);
            style.rel = 'stylesheet';
            style.id = id;
            tag === 'style' ? style.innerHTML = css : style.href = css;
            document.head.appendChild(style);
        },

        removeElementById(eleId) {
            let ele = document.getElementById(eleId);
            ele && ele.parentNode.removeChild(ele);
        },

        hasElementById(eleId) {
            return document.getElementById(eleId);
        },

        filter: '-webkit-filter: url(#dark-mode-filter) !important; filter: url(#dark-mode-filter) !important;',
        reverseFilter: '-webkit-filter: url(#dark-mode-reverse-filter) !important; filter: url(#dark-mode-reverse-filter) !important;',
        noneFilter: '-webkit-filter: none !important; filter: none !important;',
    };

    let main = {
        /**
         * 配置默认值
         */
        initValue() {
            let value = [{
                name: 'dark_mode',
                value: 'light'
            }, {
                name: 'button_position',
                value: 'right'
            }, {
                name: 'button_size',
                value: 30
            }, {
                name: 'exclude_list',
                value: ['youku.com', 'v.youku.com', 'www.douyu.com', 'www.iqiyi.com', 'vip.iqiyi.com', 'mail.qq.com', 'live.kuaishou.com']
            }];

            value.forEach((v) => {
                util.getValue(v.name) === undefined && util.setValue(v.name, v.value);
            });
        },

        addExtraStyle() {
            try {
                return darkModeRule;
            } catch (e) {
                return '';
            }
        },

        createDarkFilter() {
            if (util.hasElementById('dark-mode-svg')) return;
            let svgDom = '<svg id="dark-mode-svg" style="height: 0; width: 0;"><filter id="dark-mode-filter" x="0" y="0" width="99999" height="99999"><feColorMatrix type="matrix" values="0.283 -0.567 -0.567 0.000 0.925 -0.567 0.283 -0.567 0.000 0.925 -0.567 -0.567 0.283 0.000 0.925 0.000 0.000 0.000 1.000 0.000"></feColorMatrix></filter><filter id="dark-mode-reverse-filter" x="0" y="0" width="99999" height="99999"><feColorMatrix type="matrix" values="0.333 -0.667 -0.667 0.000 1.000 -0.667 0.333 -0.667 0.000 1.000 -0.667 -0.667 0.333 0.000 1.000 0.000 0.000 0.000 1.000 0.000"></feColorMatrix></filter></svg>';
            let div = document.createElementNS('http://www.w3.org/1999/xhtml', 'div');
            div.innerHTML = svgDom;
            let frag = document.createDocumentFragment();
            while (div.firstChild)
                frag.appendChild(div.firstChild);
            document.head.appendChild(frag);
        },

        createDarkStyle() {
            util.addStyle('dark-mode-style', 'style', `
                @media screen {
                    html {
                        ${util.filter}
                    }

                    /* Default Reverse rule */
                    img,
                    video,
                    iframe,
                    canvas,
                    :not(object):not(body) > embed,
                    object,
                    svg image,
                    [style*="background:url"],
                    [style*="background-image:url"],
                    [style*="background: url"],
                    [style*="background-image: url"],
                    [background],
                    twitterwidget,
                    .sr-reader,
                    .no-dark-mode,
                    .sr-backdrop {
                        ${util.reverseFilter}
                    }

                    [style*="background:url"] *,
                    [style*="background-image:url"] *,
                    [style*="background: url"] *,
                    [style*="background-image: url"] *,
                    input,
                    [background] *,
                    img[src^="https://s0.wp.com/latex.php"],
                    twitterwidget .NaturalImage-image {
                        ${util.noneFilter}
                    }

                    /* Text contrast */
                    html {
                        text-shadow: 0 0 0 !important;
                    }

                    /* Full screen */
                    .no-filter,
                    :-webkit-full-screen,
                    :-webkit-full-screen *,
                    :-moz-full-screen,
                    :-moz-full-screen *,
                    :fullscreen,
                    :fullscreen * {
                        ${util.noneFilter}
                    }

                    ::-webkit-scrollbar {
                        background-color: #202324;
                        color: #aba499;
                    }
                    ::-webkit-scrollbar-thumb {
                        background-color: #454a4d;
                    }
                    ::-webkit-scrollbar-thumb:hover {
                        background-color: #575e62;
                    }
                    ::-webkit-scrollbar-thumb:active {
                        background-color: #484e51;
                    }
                    ::-webkit-scrollbar-corner {
                        background-color: #181a1b;
                    }

                    /* Page background */
                    html {
                        background: #fff !important;
                    }
                    ${this.addExtraStyle()}
                }

                @media print {
                    .no-print {
                        display: none !important;
                    }
                }`);
        },

        enableDarkMode() {
            if (this.isFullScreen()) return;
            this.createDarkFilter();
            this.createDarkStyle();
        },

        disableDarkMode() {
            util.removeElementById('dark-mode-svg');
            util.removeElementById('dark-mode-style');
        },

        addButton() {
            if (this.isTopWindow()) {
                let lightIcon = `<div style="background: #000;display: flex;align-items: center;justify-content: center;width: ${util.getValue('button_size')}px;height: ${util.getValue('button_size')}px;border-radius: 50%"><svg style="position: static;margin: 0;padding: 0;" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" width="${util.getValue('button_size') / 1.5}" height="${util.getValue('button_size') / 1.5}"><path d="M522.88 874.667A21.333 21.333 0 0 1 544.213 896v85.333a21.333 21.333 0 0 1-21.333 21.334h-21.333a21.333 21.333 0 0 1-21.334-21.334V896a21.333 21.333 0 0 1 21.334-21.333h21.333zm268.416-107.52l60.352 60.352a21.333 21.333 0 0 1 0 30.165l-15.083 15.083a21.333 21.333 0 0 1-30.186 0l-60.331-60.352a21.333 21.333 0 0 1 0-30.166l15.083-15.082a21.333 21.333 0 0 1 30.165 0zm-527.957 0l15.082 15.082a21.333 21.333 0 0 1 0 30.166l-60.352 60.352a21.333 21.333 0 0 1-30.165 0l-15.083-15.083a21.333 21.333 0 0 1 0-30.165l60.331-60.352a21.333 21.333 0 0 1 30.187 0zM512 277.333c141.376 0 256 114.624 256 256s-114.624 256-256 256-256-114.624-256-256 114.624-256 256-256zm0 64a192 192 0 1 0 0 384 192 192 0 0 0 0-384zm448.213 160a21.333 21.333 0 0 1 21.334 21.334V544a21.333 21.333 0 0 1-21.334 21.333H874.88A21.333 21.333 0 0 1 853.547 544v-21.333a21.333 21.333 0 0 1 21.333-21.334h85.333zm-810.666 0a21.333 21.333 0 0 1 21.333 21.334V544a21.333 21.333 0 0 1-21.333 21.333H64.213A21.333 21.333 0 0 1 42.88 544v-21.333a21.333 21.333 0 0 1 21.333-21.334h85.334zm687.04-307.413l15.082 15.083a21.333 21.333 0 0 1 0 30.165l-60.352 60.352a21.333 21.333 0 0 1-30.165 0l-15.083-15.083a21.333 21.333 0 0 1 0-30.165L806.4 193.92a21.333 21.333 0 0 1 30.187 0zm-618.496 0l60.352 60.352a21.333 21.333 0 0 1 0 30.165L263.36 299.52a21.333 21.333 0 0 1-30.187 0l-60.352-60.373a21.333 21.333 0 0 1 0-30.166l15.083-15.082a21.333 21.333 0 0 1 30.165 0zM522.9 64a21.333 21.333 0 0 1 21.334 21.333v85.334A21.333 21.333 0 0 1 522.9 192h-21.333a21.333 21.333 0 0 1-21.333-21.333V85.333A21.333 21.333 0 0 1 501.568 64h21.333z" fill="#fff"/></svg></div>`,
                    darkIcon = `<div style="background: #333;display: flex;align-items: center;justify-content: center;width: ${util.getValue('button_size')}px;height: ${util.getValue('button_size')}px;border-radius: 50%"><svg style="position: static;margin: 0;padding: 0;" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" width="${util.getValue('button_size') / 1.5}" height="${util.getValue('button_size') / 1.5}"><path d="M513.173 128A255.061 255.061 0 0 0 448 298.667c0 141.376 114.624 256 256 256a255.36 255.36 0 0 0 189.803-84.203A392.855 392.855 0 0 1 896 512c0 212.075-171.925 384-384 384S128 724.075 128 512c0-209.707 168.107-380.16 376.96-383.936l8.192-.064zM395.35 213.93l-3.52 1.409C274.645 262.827 192 377.77 192 512c0 176.725 143.275 320 320 320 145.408 0 268.16-96.981 307.115-229.803l1.536-5.504-1.6.64a319.51 319.51 0 0 1-106.496 21.227l-8.555.107c-176.725 0-320-143.275-320-320 0-28.48 3.755-56.406 10.944-83.2l.405-1.536z" fill="#adbac7"/></svg></div>`;

                let o = document.createElement('div'),
                    buttonPostion = util.getValue('button_position');
                o.style.position = 'fixed';
                o.style[buttonPostion] = '25px';
                o.style.bottom = '25px';
                o.style.cursor = 'pointer';
                o.style.zIndex = '2147483999';
                o.style.userSelect = 'none';
                o.className = 'no-print';
                o.id = 'darkBtn';
                this.isDarkMode() ? o.innerHTML = lightIcon : o.innerHTML = darkIcon;
                document.body.appendChild(o);

                o.addEventListener("click", () => {
                    if (this.isDarkMode()) { //黑暗模式变为正常模式
                        util.setValue('dark_mode', 'light');
                        o.innerHTML = darkIcon;
                        this.disableDarkMode();
                    } else {
                        util.setValue('dark_mode', 'dark');
                        o.innerHTML = lightIcon;
                        this.enableDarkMode();
                    }
                });
            }
        },

        registerMenuCommand() {
            if (this.isTopWindow()) {
                let whiteList = util.getValue('exclude_list');
                let host = location.host;
                if (whiteList.includes(host)) {
                    GM_registerMenuCommand('💡 当前网站：❌', () => {
                        let index = whiteList.indexOf(host);
                        whiteList.splice(index, 1);
                        util.setValue('exclude_list', whiteList);
                        history.go(0);
                    });
                } else {
                    GM_registerMenuCommand('💡 当前网站：✔️', () => {
                        whiteList.push(host);
                        util.setValue('exclude_list', Array.from(new Set(whiteList)));
                        history.go(0);
                    });
                }

                GM_registerMenuCommand('⚙️ 设置', () => {
                    let style = `
                                .darkmode-popup { font-size: 14px !important; }
                                .darkmode-center { display: flex;align-items: center; }
                                .darkmode-setting-label { display: flex;align-items: center;justify-content: space-between;padding-top: 15px; }
                                .darkmode-setting-label-col { display: flex;align-items: flex-start;;padding-top: 15px;flex-direction:column }
                                .darkmode-setting-radio { width: 16px;height: 16px; }
                                .darkmode-setting-textarea { width: 100%; margin: 14px 0 0; height: 100px; resize: none; border: 1px solid #bbb; box-sizing: border-box; padding: 5px 10px; border-radius: 5px; color: #666; line-height: 1.2; }
                                .darkmode-setting-input { border: 1px solid #bbb; box-sizing: border-box; padding: 5px 10px; border-radius: 5px; width: 100px}
                            `;
                    util.addStyle('darkmode-style', 'style', style);
                    util.addStyle('swal-pub-style', 'style', GM_getResourceText('swalStyle'));
                    let excludeListStr = util.getValue('exclude_list').join('\n');

                    let dom = `<div style="font-size: 1em;">
                              <label class="darkmode-setting-label">按钮位置 <div id="S-Dark-Position" class="darkmode-center"><input type="radio" name="buttonPosition" ${util.getValue('button_position') === 'left' ? 'checked' : ''} class="darkmode-setting-radio" value="left">左 <input type="radio" name="buttonPosition" style="margin-left: 30px;" ${util.getValue('button_position') === 'right' ? 'checked' : ''} class="darkmode-setting-radio" value="right">右</div></label>
                              <label class="darkmode-setting-label">按钮大小（默认：30）<small id="currentSize">当前：${util.getValue('button_size')}</small>
                              <input id="S-Dark-Size" type="range" class="darkmode-setting-range" min="20" max="50" step="2" value="${util.getValue('button_size')}">
                              </label>
                              <label class="darkmode-setting-label-col">排除下列网址 <textarea placeholder="列表中的域名将不开启夜间模式，一行一个，例如：v.youku.com" id="S-Dark-Exclude" class="darkmode-setting-textarea">${excludeListStr}</textarea></label>
                            </div>`;
                    Swal.fire({
                        title: '夜间模式配置',
                        html: dom,
                        icon: 'info',
                        showCloseButton: true,
                        confirmButtonText: '保存',
                        footer: '<div style="text-align: center;font-size: 1em;">点击查看 <a href="https://www.youxiaohou.com/tool/install-darkmode.html" target="_blank">使用说明</a>，助手免费开源，<a href="https://www.youxiaohou.com/darkmode.user.js">检查更新</a><svg viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" width="14" height="14"><path d="M445.956 138.812L240.916 493.9c-11.329 19.528-12.066 44.214 0 65.123 12.067 20.909 33.898 32.607 56.465 32.607h89.716v275.044c0 31.963 25.976 57.938 57.938 57.938h134.022c32.055 0 57.938-25.975 57.938-57.938V591.63h83.453c24.685 0 48.634-12.803 61.806-35.739 13.172-22.844 12.343-50.016 0-71.386l-199.42-345.693c-13.633-23.58-39.24-39.516-68.44-39.516-29.198 0-54.897 15.935-68.438 39.516z" fill="#d81e06"/></svg></div>',
                        customClass: {
                            popup: 'darkmode-popup',
                        },
                    }).then((res) => {
                        res.isConfirmed && history.go(0);
                    });

                    document.getElementById('S-Dark-Position').addEventListener('click', (e) => {
                        e.target.tagName === "INPUT" && util.setValue('button_position', e.target.value);
                    });
                    document.getElementById('S-Dark-Size').addEventListener('change', (e) => {
                        util.setValue('button_size', e.currentTarget.value);
                        document.getElementById('currentSize').innerText = '当前：' + e.currentTarget.value;
                    });
                    document.getElementById('S-Dark-Exclude').addEventListener('change', (e) => {
                        util.setValue('exclude_list', Array.from(new Set(e.currentTarget.value.split('\n').filter(Boolean))));
                    });
                });
            }
        },

        isTopWindow() {
            return window.self === window.top;
        },

        addListener() {
            document.addEventListener("fullscreenchange", (e) => {
                if (this.isFullScreen()) {
                    //进入全屏
                    this.disableDarkMode();
                } else {
                    //退出全屏
                    this.isDarkMode() && this.enableDarkMode();
                }
            });
        },

        isDarkMode() {
            return util.getValue('dark_mode') === 'dark';
        },

        isInExcludeList() {
            return util.getValue('exclude_list').includes(location.host);
        },

        isFullScreen() {
            return document.fullscreenElement;
        },

        firstEnableDarkMode() {
            if (document.head) {
                this.isDarkMode() && this.enableDarkMode();
            }
            const headObserver = new MutationObserver(() => {
                this.isDarkMode() && this.enableDarkMode();
            });
            headObserver.observe(document.head, {childList: true, subtree: true});

            if (document.body) {
                this.addButton();
            } else {
                const bodyObserver = new MutationObserver(() => {
                    if (document.body) {
                        bodyObserver.disconnect();
                        this.addButton();
                    }
                });
                bodyObserver.observe(document, {childList: true, subtree: true});
            }
        },

        init() {
            this.initValue();
            this.registerMenuCommand();
            if (this.isInExcludeList()) return;
            this.addListener();
            this.firstEnableDarkMode();
        }
    };
    main.init();
})();
