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
                component: () => import('./pages/manage/setting/index.vue'),
                children: [
                    {
                        path: '',
                        redirect: 'personal',
                    },
                    {
                        name: 'manage-setting-personal',
                        path: 'personal',
                        component: () => import('./pages/manage/setting/personal.vue'),
                    },
                    {
                        name: 'manage-setting-password',
                        path: 'password',
                        component: () => import('./pages/manage/setting/password.vue'),
                    },
                    {
                        name: 'manage-setting-personal',
                        path: 'system',
                        component: () => import('./pages/manage/setting/system.vue'),
                    },
                    {
                        name: 'manage-setting-personal',
                        path: 'priority',
                        component: () => import('./pages/manage/setting/priority.vue'),
                    },
                ]
            },
            {
                name: 'manage-project',
                path: 'project/:id',
                component: () => import('./pages/manage/project.vue'),
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
