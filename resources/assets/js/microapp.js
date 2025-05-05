import microApp from '@micro-zoe/micro-app'

export default function() {
    microApp.start({
        'iframe': true,
        'keep-alive': true,         // 全局开启保活模式
        'router-mode': 'state',     // 路由设置为state模式
    })
}
