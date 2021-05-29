export default [
    {
        name: 'index',
        path: '/',
        component: resolve => require(['./pages/index.vue'], resolve)
    },
    {
        name: 'dashboard',
        path: '/dashboard',
        component: resolve => require(['./pages/dashboard/index.vue'], resolve),
    },
    {
        name: 'project-detail',
        path: '/project/:id',
        component: resolve => require(['./pages/project/detail.vue'], resolve),
    },
    {
        name: 'users-login',
        path: '/users/login',
        component: resolve => require(['./pages/users/login.vue'], resolve),
    },
    {
        name: '404',
        path: '*',
        component: resolve => require(['./pages/404.vue'], resolve),
    },
]
