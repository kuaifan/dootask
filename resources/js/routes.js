export default [
    {
        path: '/',
        name: 'index',
        component: resolve => require(['./pages/index.vue'], resolve)
    },
    {
        path: '*',
        name: '404',
        component: resolve => require(['./pages/404.vue'], resolve),
    },
    {
        path: '/login',
        name: 'login',
        component: resolve => require(['./pages/login/index.vue'], resolve),
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: resolve => require(['./pages/dashboard/index.vue'], resolve),
    },
]
