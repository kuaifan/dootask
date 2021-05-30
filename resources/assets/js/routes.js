export default [
    {
        name: 'index',
        path: '/',
        component: () => import('./pages/index.vue')
    },
    {
        name: 'dashboard',
        path: '/dashboard',
        component: () => import('./pages/dashboard/index.vue'),
    },
    {
        name: 'project-detail',
        path: '/project/:id',
        component: () => import('./pages/project/detail.vue'),
    },
    {
        name: 'users-login',
        path: '/users/login',
        component: () => import('./pages/users/login.vue'),
    },
    {
        name: '404',
        path: '*',
        component: () => import('./pages/404.vue')
    },
]
