import Vue from 'vue'
import Vuex from 'vuex'
import App from './App.vue'
import routes from './routes'
import VueRouter from 'vue-router'
import ViewUI from 'view-design-hi';
import Language from './language/index'
import store from './store/index'

import './functions/common'
import './functions/web'

Vue.use(Vuex);
Vue.use(ViewUI);
Vue.use(VueRouter);
Vue.use(Language);

import PageTitle from './components/PageTitle.vue'
import Loading from './components/Loading.vue'
import AutoTip from './components/AutoTip.vue'
import TagInput from './components/TagInput.vue'
import TableAction from './components/TableAction.vue'
import UserAvatar from './components/UserAvatar.vue'

Vue.component('PageTitle', PageTitle);
Vue.component('Loading', Loading);
Vue.component('AutoTip', AutoTip);
Vue.component('TagInput', TagInput)
Vue.component('TableAction', TableAction);
Vue.component('UserAvatar', UserAvatar);

import {
    Avatar,
    Tooltip,
    Popover,
    Dropdown,
    DropdownMenu,
    DropdownItem,
} from 'element-ui';
Vue.component('EAvatar', Avatar);
Vue.component('ETooltip', Tooltip);
Vue.component('EPopover', Popover);
Vue.component('EDropdown', Dropdown);
Vue.component('EDropdownMenu', DropdownMenu);
Vue.component('EDropdownItem', DropdownItem);

const originalPush = VueRouter.prototype.push
VueRouter.prototype.push = function push(location) {
    return originalPush.call(this, location).catch(err => err)
}
const router = new VueRouter({
    mode: 'history',
    routes
});

// 进度条配置
ViewUI.LoadingBar.config({
    color: '#3fcc25',
    failedColor: '#ff0000'
});
router.beforeEach((to, from, next) => {
    ViewUI.LoadingBar.start();
    next();
});
router.afterEach((to, from, next) => {
    ViewUI.LoadingBar.finish();
});

// 加载函数
Vue.prototype.goForward = function(location, isReplace) {
    if (typeof location === 'string') location = {name: location};
    if (isReplace === true) {
        app.$router.replace(location).then(() => {});
    } else {
        app.$router.push(location).then(() => {});
    }
};

// 返回函数
Vue.prototype.goBack = function (number) {
    let history = $A.jsonParse(window.sessionStorage['__history__'] || '{}');
    if ($A.runNum(history['::count']) > 2) {
        app.$router.go(typeof number === 'number' ? number : -1);
    } else {
        app.$router.replace(typeof number === "object" ? number : {path: '/'}).then(() => {});
    }
};

Vue.prototype.$A = $A;
Vue.config.productionTip = false;

const app = new Vue({
    el: '#app',
    router,
    store,
    template: '<App/>',
    components: { App }
});


$A.goForward = app.goForward;
$A.goBack = app.goBack;
$A.getLanguage = app.getLanguage;
$A.Message = app.$Message;
$A.Notice = app.$Notice;
$A.Modal = app.$Modal;
$A.store = app.$store;
$A.L = app.$L;
