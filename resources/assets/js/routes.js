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
                name: 'manage-messenger',
                path: 'messenger',
                component: () => import('./pages/manage/messenger.vue'),
            },
            {
                path: 'setting',
                component: () => import('./pages/manage/setting/index.vue'),
                children: [
                    {
                        name: 'manage-setting',
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
                        name: 'manage-setting-system',
                        path: 'system',
                        component: () => import('./pages/manage/setting/system.vue'),
                    },
                ]
            },
            {
                name: 'manage-project-invite',
                path: 'project/invite',
                component: () => import('./pages/manage/projectInvite.vue'),
            },
            {
                name: 'manage-project',
                path: 'project/:id',
                component: () => import('./pages/manage/project.vue'),
            },
            {
                name: 'manage-file',
                path: 'file',
                component: () => import('./pages/manage/file.vue'),
            },
        ]
    },
    {
        name: 'single-file-msg',
        path: '/single/file/msg/:id',
        component: () => import('./pages/single/fileMsg.vue'),
    },
    {
        name: 'single-file-task',
        path: '/single/file/task/:id',
        component: () => import('./pages/single/fileTask.vue'),
    },
    {
        name: 'single-file',
        path: '/single/file/:id',
        component: () => import('./pages/single/file.vue'),
    },
    {
        name: 'single-task',
        path: '/single/task/:id',
        component: () => import('./pages/single/task.vue'),
    },
    {
        name: 'valid-email',
        path: '/single/valid/email',
        meta: {title: '验证绑定邮箱'},
        component: () => import('./pages/single/validEmail.vue')
    },
    {
        name: 'report-edit',
        path: '/single/report/edit/:id',
        component: () => import('./pages/single/reportEdit.vue')
    },
    {
        name: 'report-detail',
        path: '/single/report/detail/:id',
        component: () => import('./pages/single/reportDetail.vue')
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
