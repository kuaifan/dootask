import{n as i}from"./app.fe1cd649.js";import"./@micro-zoe.c2e1472d.js";import"./jquery.f44c3c79.js";import"./@babel.43d8d0a5.js";import"./dayjs.aaefe451.js";import"./localforage.bd4872f5.js";import"./markdown-it.6846e2b0.js";import"./entities.797c3e49.js";import"./uc.micro.700527ef.js";import"./mdurl.95c1032c.js";import"./linkify-it.96515e28.js";import"./punycode.50f384b0.js";import"./highlight.js.ab8aeea4.js";import"./markdown-it-link-attributes.e1d5d151.js";import"./@traptitech.64308959.js";import"./vue.296078bd.js";import"./vuex.cc7cb26e.js";import"./openpgp_hi.15f91b1d.js";import"./axios.6ec123f8.js";import"./le5le-store.d4b5b622.js";import"./vue-router.2d566cd7.js";import"./vue-clipboard2.ad7135d3.js";import"./clipboard.3f21bed6.js";import"./view-design-hi.609f8897.js";import"./vuedraggable.6ea348a4.js";import"./sortablejs.982d79d6.js";import"./vue-resize-observer.e788af6d.js";import"./element-sea.c283d284.js";import"./deepmerge.cecf392e.js";import"./resize-observer-polyfill.37526d89.js";import"./throttle-debounce.7c3948b2.js";import"./babel-helper-vue-jsx-merge-props.5ed215c3.js";import"./normalize-wheel.2a034b9f.js";import"./async-validator.e7e40052.js";import"./babel-runtime.4773988a.js";import"./core-js.314b4a1d.js";var m=function(){var t=this,o=t.$createElement,r=t._self._c||o;return r("div",{staticClass:"token-transfer"},[r("Loading")],1)},n=[];const p={mounted(){this.goNext1()},methods:{goNext1(){const t=$A.urlParameterAll();t.token&&this.$store.dispatch("call",{url:"users/info",header:{token:t.token}}).then(o=>{this.$store.dispatch("saveUserInfo",o.data),this.goNext2()}).catch(o=>{this.goForward({name:"login"},!0)})},goNext2(){let t=decodeURIComponent($A.getObject(this.$route.query,"from"));t?window.location.replace(t):this.goForward({name:"manage-dashboard"},!0)}}},e={};var a=i(p,m,n,!1,s,"5df16c44",null,null);function s(t){for(let o in e)this[o]=e[o]}var K=function(){return a.exports}();export{K as default};