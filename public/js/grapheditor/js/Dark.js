var Dark = {
    utils: {
        filter: '-webkit-filter: url(#dark-mode-filter) !important; filter: url(#dark-mode-filter) !important;',
        reverseFilter: '-webkit-filter: url(#dark-mode-reverse-filter) !important; filter: url(#dark-mode-reverse-filter) !important;',
        noneFilter: '-webkit-filter: none !important; filter: none !important;',

        addExtraStyle() {
            try {
                return '';
            } catch (e) {
                return '';
            }
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

        getClassList(node) {
            return node.classList || [];
        },

        addClass(node, name) {
            this.getClassList(node).add(name);
            return this;
        },

        removeClass(node, name) {
            this.getClassList(node).remove(name);
            return this;
        },

        hasClass(node, name) {
            return this.getClassList(node).contains(name);
        },

        hasElementById(eleId) {
            return document.getElementById(eleId);
        },

        removeElementById(eleId) {
            let ele = document.getElementById(eleId);
            ele && ele.parentNode.removeChild(ele);
        },
    },

    createDarkFilter() {
        if (this.utils.hasElementById('dark-mode-svg')) return;
        let svgDom = '<svg id="dark-mode-svg" style="height: 0; width: 0;"><filter id="dark-mode-filter" x="0" y="0" width="99999" height="99999"><feColorMatrix type="matrix" values="0.283 -0.567 -0.567 0.000 0.925 -0.567 0.283 -0.567 0.000 0.925 -0.567 -0.567 0.283 0.000 0.925 0.000 0.000 0.000 1.000 0.000"></feColorMatrix></filter><filter id="dark-mode-reverse-filter" x="0" y="0" width="99999" height="99999"><feColorMatrix type="matrix" values="0.333 -0.667 -0.667 0.000 1.000 -0.667 0.333 -0.667 0.000 1.000 -0.667 -0.667 0.333 0.000 1.000 0.000 0.000 0.000 1.000 0.000"></feColorMatrix></filter></svg>';
        let div = document.createElementNS('http://www.w3.org/1999/xhtml', 'div');
        div.innerHTML = svgDom;
        let frag = document.createDocumentFragment();
        while (div.firstChild)
            frag.appendChild(div.firstChild);
        document.head.appendChild(frag);
    },

    createDarkStyle() {
        this.utils.addStyle('dark-mode-style', 'style', `
                @media screen {
                    html {
                        ${this.utils.filter}
                    }

                    /* Default Reverse rule */
                    img,
                    video,
                    iframe,
                    canvas,
                    :not(object):not(body) > embed,
                    object,
                    svg image {
                        ${this.utils.reverseFilter}
                    }

                    [style*="background:url"] *,
                    [style*="background-image:url"] *,
                    [style*="background: url"] *,
                    [style*="background-image: url"] *,
                    input,
                    [background] *,
                    twitterwidget .NaturalImage-image {
                        ${this.utils.noneFilter}
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
                        ${this.utils.noneFilter}
                    }

                    /* Page background */
                    html {
                        background: #fff !important;
                    }
                    ${this.utils.addExtraStyle()}
                }

                @media print {
                    .no-print {
                        display: none !important;
                    }
                }`);
    },

    enableDarkMode() {
        if (this.isDarkEnabled()) {
            return
        }
        this.createDarkFilter();
        this.createDarkStyle();
        this.utils.addClass(document.body, "dark-mode-reverse")
    },

    disableDarkMode() {
        if (!this.isDarkEnabled()) {
            return
        }
        this.utils.removeElementById('dark-mode-svg');
        this.utils.removeElementById('dark-mode-style');
        this.utils.removeClass(document.body, "dark-mode-reverse")
    },

    autoDarkMode() {
        let darkScheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
        if (darkScheme) {
            this.enableDarkMode()
        } else {
            this.disableDarkMode()
        }
    },

    isDarkEnabled() {
        return this.utils.hasClass(document.body, "dark-mode-reverse")
    },
};

switch (window.mxTheme) {
    case 'dark':
        Dark.enableDarkMode();
        break;
    case 'light':
        Dark.disableDarkMode();
        break;
    default:
        Dark.autoDarkMode();
        break;
}
