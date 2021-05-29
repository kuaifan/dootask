import Vue from 'vue'
import App from './App.vue'
import routes from './routes'
import VueRouter from 'vue-router'
import ViewUI from 'view-design-hi';
import Language from './common/language/index'
import Mixins from './common/mixins/index'

import './common/functions/index'
import './main'

Vue.use(VueRouter);
Vue.use(ViewUI);
Vue.use(Language);
Vue.use(Mixins);

import PageTitle from './common/components/PageTitle.vue'
import Loading from './common/components/Loading.vue'
import AutoTip from './common/components/AutoTip.vue'
import TableAction from './common/components/TableAction.vue'

Vue.component('PageTitle', PageTitle);
Vue.component('Loading', Loading);
Vue.component('AutoTip', AutoTip);
Vue.component('TableAction', TableAction);


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
    template: '<App/>',
    components: { App }
});

$A.app = app;
