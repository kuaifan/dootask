let themeName = window.localStorage.getItem('__system:themeConf__')
if (!['dark', 'light'].includes(themeName)) {
    let isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
    themeName = isDark ? 'dark' : 'light'
}
if (themeName === 'dark') {
    let style = document.createElement('style');
    style.rel = 'stylesheet';
    style.innerHTML = '.app-view-loading{background-color:#0D0D0D}'
    document.head.appendChild(style);
}
