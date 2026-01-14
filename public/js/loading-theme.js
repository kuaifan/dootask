let themeName = window.localStorage.getItem('__system:themeConf__')
if (!['dark', 'light'].includes(themeName)) {
    let isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
    themeName = isDark ? 'dark' : 'light'
}
if (themeName === 'dark') {
    let style = document.createElement('style');
    style.rel = 'stylesheet';
    style.innerHTML = '.app-view-loading{background-color:#202124}'
    if (document.head) {
        document.head.appendChild(style);
    } else {
        document.body.appendChild(style);
    }
}
