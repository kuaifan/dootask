import microApp from '@micro-zoe/micro-app'
import DialogWrapper from "./pages/manage/components/DialogWrapper.vue";

export default function() {
    let urls  = "";
    let route  = "/microapp/";
    let modules = {};

    let obj = {
        loader(code,url) {
            if (process.env.NODE_ENV === 'development') {
                const match = /^https?:\/\/([^:/]+)(?::(\d+))?/.exec(url);
                if( match && match[0] && url.indexOf('@vite/client') !== -1 ){
                    urls = url.replace("@vite/client","");
                    route = urls.replace(match[0].replace("@vite/client",""),"");
                }
                // 这里 /basename/ 需要和子应用vite.config.js中base的配置保持一致
                code = code.replace( eval(`/(from|import)(\\s*['"])(${route.replace(/\//g,"\\/")})/g`) , all => {
                    return all.replace(route, urls)
                })
            }
            return code
        }
    }
    
    // 微应用名称
    modules["micro-app"] = [obj]

    // 微应用
    microApp.start({
        plugins: {
            global: [DialogWrapper],
            modules: modules
        }
    })
    
}