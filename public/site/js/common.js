const menuBtn = document.getElementById('menuBtn'); // 768模式下的菜单按钮
const drawer = document.querySelector('.drawer'); // 768模式下的菜单
const theme_light = document.querySelectorAll('.theme_light');
const theme_dark = document.querySelectorAll('.theme_dark');
/* 主题切换更换图片 */
const themeSwitch = (val1,val2)=>{
  theme_light.forEach(item=>{
    item.style.display = val1
  })
  theme_dark.forEach(item=>{
    item.style.display = val2
  })
}
/* 更换图片 */
const changeImageSrc = (img, src) => {
  const imgDom = document.querySelectorAll(img);
  const url = window.location.href   // 获取当前浏览器 URL
  if (imgDom.length > 0) {
    imgDom.forEach(item=>{
      item.src = url.includes('site/i') ? `./img/${src}` : `../img/${src}`;
    })
  }
};
/* 设置默认语言 */
if(!localStorage.getItem('lang')){
  localStorage.setItem('lang','zh')
}
/* 设置默认主题 */
const localStorageTheme = localStorage.getItem('theme')
if(!localStorageTheme){
  setTheme('light')
  localStorage.setItem('theme', 'light')
}else{
  setTheme(localStorageTheme)
}

/* 切换主题函数 */
function setTheme(theme) {
  const root = document.documentElement;
  const lang = localStorage.getItem('lang')
  changeImageSrc('#logo', `${theme}/logo.svg`)
  changeImageSrc('#home_pic', `${theme}/${lang}_home_pic1.png`)
  changeImageSrc('#home_pic2', `${theme}/${lang}_home_pic2.png`)
  changeImageSrc('#home_pic3', `${theme}/${lang}_home_pic3.png`)
  changeImageSrc('#home_pic4', `${theme}/${lang}_home_pic4.png`)
  changeImageSrc('#home_pic5', `${theme}/${lang}_home_pic5.png`)
  changeImageSrc('#home_pic6', `${theme}/${lang}_home_pic6.png`)
  changeImageSrc('#solution_pic1', `${theme}/${lang}_solution_pic1.png`)
  changeImageSrc('#solution_pic2', `${theme}/${lang}_solution_pic2.png`)
  changeImageSrc('#solution_pic3', `${theme}/${lang}_solution_pic3.png`)
  changeImageSrc('#dow_pic1', `${theme}/${lang}_dow_pic1.png`)
  changeImageSrc('#solution_pic4', `${theme}/solution_pic4.png`)
  changeImageSrc('#about_pic1', `${theme}/about_pic1.png`)
  changeImageSrc('#home_icon1', `${theme}/home_icon1.png`)
  changeImageSrc('#home_icon2', `${theme}/home_icon2.png`)
  changeImageSrc('#home_icon3', `${theme}/home_icon3.png`)
  changeImageSrc('#home_icon4', `${theme}/home_icon4.png`)
  changeImageSrc('#home_pic7', `${theme}/home_pic7.svg`)
  changeImageSrc('#home_pic7_768', `${theme}/home_pic7_768.svg`)
  changeImageSrc('#help_pic2', `${theme}/help_pic2.png`)
  changeImageSrc('#help_pic3', `${theme}/help_pic3.png`)
  root.style.setProperty('--bg-pic7-url', `url(../img/${theme}/home_pic7.svg)`);
  root.style.setProperty('--bg-pic7-768-url', `url(../img/${theme}/home_pic7-768.svg)`);
  root.style.setProperty(`--bg-pic1-url`, `url(../img/${theme}/${lang}_dow_pic1.png)`);
  root.style.setProperty(`--bg-pic2-url`, `url(../img/${theme}/help_pic1.svg)`);
  root.style.setProperty(`--bg-1-url`, `url(../img/${theme}/bg1.png)`);
  root.style.setProperty(`--bg-2-url`, `url(../img/${theme}/bg2.png)`);
  root.style.setProperty(`--bg-3-url`, `url(../img/${theme}/bg3.png)`);
  root.style.setProperty(`--bg-4-url`, `url(../img/${theme}/bg4.png)`);
  root.style.setProperty(`--bg-5-url`, `url(../img/${theme}/bg5.png)`);
  root.style.setProperty(`--bg-6-url`, `url(../img/${theme}/bg6.png)`);
  root.style.setProperty(`--bg-7-url`, `url(../img/${theme}/bg7.png)`);
  root.style.setProperty(`--bg-8-url`, `url(../img/${theme}/bg8.png)`);
  root.style.setProperty(`--bg-9-url`, `url(../img/${theme}/bg9.png)`);
  root.style.setProperty(`--bg-10-url`, `url(../img/${theme}/bg10.png)`);
  root.style.setProperty(`--bg-11-url`, `url(../img/${theme}/bg11.png)`);
  root.style.setProperty(`--bg-768-3-url`, `url(../img/${theme}/bg3_768.png)`);
  root.style.setProperty(`--bg-768-4-url`, `url(../img/${theme}/bg4_768.png)`);
  root.style.setProperty(`--bg-768-5-url`, `url(../img/${theme}/bg5_768.png)`);
  root.style.setProperty(`--bg-768-6-url`, `url(../img/${theme}/bg6_768.png)`);
  root.style.setProperty(`--bg-768-7-url`, `url(../img/${theme}/bg7_768.png)`);
  root.style.setProperty(`--bg-768-8-url`, `url(../img/${theme}/bg8_768.png)`);
  root.style.setProperty(`--bg-768-9-url`, `url(../img/${theme}/bg9_768.png)`);
  root.style.setProperty('--bg-color', theme === 'dark' ? '#1E1E1E' :'#fff');
  root.style.setProperty('--text-color', theme === 'dark' ? '#fff' : '#000');
  root.style.setProperty('--txt-gray-color', theme === 'dark' ? '#888C8A':'#727570');
  root.style.setProperty('--txt-4ca5', theme === 'dark' ? '#A5ACA9':'#4C4E4B');
  root.style.setProperty('--txt-theme-color', theme === 'dark' ? '#58A738':'#8BCF70');
  root.style.setProperty('--bg-hover-color', theme === 'dark' ? '#2C312E':'#F2F3F1');
  root.style.setProperty('--btn-hover-color', theme === 'dark' ? '#5EB939':'#98d87f');
  root.style.setProperty('--choose-bg-hover-color', theme === 'dark' ? '#2E3533':'#fff');
  root.style.setProperty('--box-shadow-color', theme === 'dark' ? 'rgba(0, 0, 0, 0.12)':'rgba(234, 236, 242, 0.5)');
  root.style.setProperty('--border-color', theme === 'dark' ? '#2F3329':'#E7E9E4');
  root.style.setProperty('--pop-bg-color', theme === 'dark' ? '#202124':'#fff');
  root.style.setProperty('--bg-fa-color', theme === 'dark' ? '#262B2A':'#fafafa');
  root.style.setProperty('--txt-191a15-color', theme === 'dark' ? '#fff':'#191a15');
  root.style.setProperty('--bg-rec-color', theme === 'dark' ? '#D6B300':'#FFDD33');
  root.style.setProperty('--bg-292c2f-color', theme === 'dark' ? '#292C2F':'#fff');
  root.style.setProperty('--pop-box-shadow', theme === 'dark' ? '0px 4px 16px 8px rgba(0, 0, 0, 0.12)':'0px 0px 8px #F3F2F5');
  root.style.setProperty('--code-bg-color', theme === 'dark' ? '#2E3533':'#000');
  theme === 'dark'?themeSwitch('none','block'):themeSwitch('block','none')
  localStorage.setItem('theme', theme)
  drawer.classList.remove('open-drawer');
}
/* 导航选中激活 */
const url = window.location.pathname;
const currentTabName = url.split('/')[url.split('/').length - 1].split('.')[0]
if(currentTabName
  && currentTabName != 'index'
  && currentTabName != 'help'
  && currentTabName != 'download'
  && currentTabName != 'log'){
  const currentTab = document.querySelector(`.nav-${currentTabName}`)
  currentTab.style.backgroundColor = 'var(--bg-hover-color)';
  currentTab.style.color = 'var(--text-color)';
  currentTab.style.borderRadius = '6px';
}
/* 导航下拉菜单函数 */
const submenuPopDom = document.querySelector('#submenu-pop');
const dropDownSvgDom = document.querySelector('#drop-down-svg');
const showMenuPopHandle = ()=>{
  submenuPopDom.style.display = 'block';
  dropDownSvgDom.style.transform = 'rotate(180deg)'
}
const changeMenu = (type)=>{
  submenuPopDom.style.display = 'none';
  dropDownSvgDom.style.transform = ''
}
document.addEventListener('click', function (event) {
  if (!event.target.closest('#support-txt')) {
    submenuPopDom.style.display = 'none';
    dropDownSvgDom.style.transform = ''
  }
});
/* 语言切换函数 */
const langPopDom = document.querySelector('#lang-pop');
const shouLangPopHandle = ()=>{
  langPopDom.style.display = 'block';
}
const changeLanguage = (type)=>{
  const str = window.location.pathname
  const index = str.lastIndexOf('/');
  const newStr = str.slice(index + 1);
  const lang = localStorage.getItem('lang')
  if(type != lang){
    if (str.includes('site')){
      window.location.href = `/site/${type}/${newStr}`
    }else{
      window.location.href = `/site/${type}/index.html`
    }
  }else{
    if (str.includes('site')){
      window.location.href = `/site/${lang}/${newStr}`
    }else{
      window.location.href = `/site/${lang}/index.html`
    }
  }
  langPopDom.style.display = 'none';
  localStorage.setItem('lang',type)
  drawer.classList.remove('open-drawer');
}
document.addEventListener('click', function (event) {
  if (!event.target.closest('#lang-img')) {
    langPopDom.style.display = 'none';
  }
});
/* 当屏幕宽度低于768px时显示菜单的抽屉的相关操作逻辑 */
menuBtn.addEventListener('click', () => {
  drawer.classList.add('open-drawer');
});
const closeDraweHandle = ()=>{
  drawer.classList.remove('open-drawer');
}
window.addEventListener('click', (e) => {
  if (!drawer.contains(e.target) && e.target !== menuBtn) {
    drawer.classList.remove('open-drawer');
  }
});
const expandMenuHandle = (val)=>{
  const actives = document.querySelector(`#${val}`);
  const dropDownSvgDom = document.querySelector(`#drawer-down-${val}-svg`);
  if(actives.style.display == 'none') {
    actives.style.display = 'block'
    dropDownSvgDom.style.transform = 'rotate(180deg)'
  }else{
    dropDownSvgDom.style.transform = ''
    actives.style.display = 'none'
  }
}
/* 导航栏悬浮函数 */
const navbar = document.querySelector('.nav');
window.addEventListener('scroll', () => {
  if (window.scrollY >= 30) {
    navbar.classList.add('navbar-white');
  } else {
    navbar.classList.remove('navbar-white');
  }
});
const openInNewTab = (url)=> {
  const win = window.open(url, '_blank');
  win.focus();
}


