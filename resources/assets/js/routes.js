export default [
    {
        name: 'index',
        path: '/',
        component: () => import('./pages/index.vue')
    },
    {
        name: 'manage',
        path: '/manage',
        meta: {
            slide: false
        },
        component: () => import('./pages/manage.vue'),
        children: [
            {
                name: 'manage-dashboard',
                path: 'dashboard',
                component: () => import('./pages/manage/dashboard.vue'),
            },
            {
                name: 'manage-calendar',
                path: 'calendar',
                component: () => import('./pages/manage/calendar.vue'),
            },
            {
                name: 'manage-setting',
                path: 'setting',
                component: () => import('./pages/manage/setting.vue'),
            },
            {
                name: 'manage-project-detail',
                path: 'project/:id',
                component: () => import('./pages/manage/project-detail.vue'),
            },
        ]
    },
    {
        name: 'login',
        path: '/login',
        component: () => import('./pages/login.vue'),
    },
    {
        name: '404',
        path: '*',
        component: () => import('./pages/404.vue')
    },
]
