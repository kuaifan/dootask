export default {
    install(Vue) {
        Vue.mixin({
            data() {
                return {
                    mixinId: 0,
                    //用户信息
                    userLogin: false,
                    userInfo: {},
                    userName: '',
                    userId: 0,
                    //浏览器宽度≤768返回true
                    windowMax768: window.innerWidth <= 768,
                }
            },

            mounted() {
                if (typeof window.__mixinId != "number") window.__mixinId = 0;
                this.mixinId = window.__mixinId++;
                //
                this.userLogin = $A.getToken() !== false;
                this.userInfo = $A.getUserInfo();
                this.userName = this.userInfo.username || '';
                this.userId = parseInt(this.userInfo.userid);
                $A.setOnUserInfoListener('mixins_' + this.mixinId, (data, isLogin) => {
                    this.userLogin = isLogin;
                    this.userInfo = data;
                    this.userName = this.userInfo.username || '';
                    this.userId = parseInt(this.userInfo.userid);
                });
                //
                window.addEventListener('resize', this.windowMax768Listener);
            },

            beforeDestroy() {
                $A.removeUserInfoListener('mixins_' + this.mixinId);
                window.removeEventListener('resize', this.windowMax768Listener);
            },

            methods: {
                isArray(obj) {
                    return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == '[object array]' && typeof obj.length == "number";
                },

                isJson(obj) {
                    return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == "[object object]" && typeof obj.length == "undefined";
                },

                windowMax768Listener() {
                    this.windowMax768 = window.innerWidth <= 768
                }
            }
        });
    }
}
