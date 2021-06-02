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

const originalPush = VueRouter.prototype.push
VueRouter.prototype.push = function push(location) {
    return originalPush.call(this, location).catch(err => err)
}
const router = new VueRouter({
    mode: 'history',
    routes
});

//进度条配置
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

//加载函数
Vue.prototype.goForward = function(location, isReplace) {
    if (typeof location === 'string') location = {name: location};
    if (isReplace === true) {
        this.$router.replace(location);
    }else{
        this.$router.push(location);
    }
};

//返回函数
Vue.prototype.goBack = function (number) {
    let history = $A.jsonParse(window.sessionStorage['__history__'] || '{}');
    if ($A.runNum(history['::count']) > 2) {
        this.$router.go(typeof number === 'number' ? number : -1);
    } else {
        this.$router.replace(typeof number === "object" ? number : {path: '/'});
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

$A.app = app;
