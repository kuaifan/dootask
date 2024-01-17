let themeName = window.localStorage.getItem('__system:themeConf__')
if (!['dark', 'light'].includes(themeName)) {
    let isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
    if (/eeui/i.test(window.navigator.userAgent)) {
        isDark = /system_theme\/dark;/i.test(window.navigator.userAgent)
    }
    themeName = isDark ? 'dark' : 'light'
}
if (themeName === 'dark') {
    let style = document.createElement('style');
    style.rel = 'stylesheet';
    style.innerHTML = '.app-view-loading{background-color:#131313}'
    document.head.appendChild(style);
}
