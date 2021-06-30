//hljs体积过大，多数为解决代码高亮显示的问题,所以只引入部分语言，如果需要可自行加载

import hljs from './highlight'

import javascript from './languages/javascript'
import java from './languages/java';
import css from './languages/css';
import less from './languages/less';
import go from './languages/go';
import markdown from './languages/markdown';
import php from './languages/php';
import python from './languages/python';
import typescript from './languages/typescript';
import xml from './languages/xml';
import autohotkey from './languages/autohotkey';
import auto from './languages/autoit';

const languages = {
    javascript,
    java,
    css,
    less,
    markdown,
    go,
    php,
    python,
    typescript,
    xml,
    autohotkey,
    auto
}
Object.keys(languages).forEach(key => {
    hljs.registerLanguage(key, languages[key])
})

export default hljs;
