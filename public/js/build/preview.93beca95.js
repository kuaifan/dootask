import{V as t,d as s,a,b as n,c as l,_ as u,e as _,v as c}from"./@kangc.a7dcf9a0.js";import{P as v}from"./prismjs.86ef8ba0.js";import{l as o,n as d}from"./app.fe1cd649.js";import{p as f}from"./index.40a8e116.js";import"./@babel.43d8d0a5.js";import"./vue.296078bd.js";import"./copy-to-clipboard.a53c061d.js";import"./toggle-selection.d2487283.js";import"./@micro-zoe.c2e1472d.js";import"./jquery.f44c3c79.js";import"./dayjs.aaefe451.js";import"./localforage.bd4872f5.js";import"./markdown-it.6846e2b0.js";import"./entities.797c3e49.js";import"./uc.micro.700527ef.js";import"./mdurl.95c1032c.js";import"./linkify-it.96515e28.js";import"./punycode.50f384b0.js";import"./highlight.js.ab8aeea4.js";import"./markdown-it-link-attributes.e1d5d151.js";import"./@traptitech.64308959.js";import"./vuex.cc7cb26e.js";import"./openpgp_hi.15f91b1d.js";import"./axios.6ec123f8.js";import"./le5le-store.d4b5b622.js";import"./vue-router.2d566cd7.js";import"./vue-clipboard2.ad7135d3.js";import"./clipboard.3f21bed6.js";import"./view-design-hi.609f8897.js";import"./vuedraggable.6ea348a4.js";import"./sortablejs.982d79d6.js";import"./vue-resize-observer.e788af6d.js";import"./element-sea.c283d284.js";import"./deepmerge.cecf392e.js";import"./resize-observer-polyfill.37526d89.js";import"./throttle-debounce.7c3948b2.js";import"./babel-helper-vue-jsx-merge-props.5ed215c3.js";import"./normalize-wheel.2a034b9f.js";import"./async-validator.e7e40052.js";import"./babel-runtime.4773988a.js";import"./core-js.314b4a1d.js";var h=function(){var e=this,r=e.$createElement,i=e._self._c||r;return i("div",{staticClass:"vmpreview-wrapper",on:{click:e.handleClick}},[i("v-md-preview",{attrs:{text:e.value}})],1)},x=[];o==="zh"||o==="zh-CHT"?t.lang.use("zh-CN",s):t.lang.use("en-US",a);t.use(n());t.use(l());t.use(u());t.use(_());const w={mixins:[f],components:{[t.name]:t},created(){t.use(c,{Prism:v,extend(e){}})},methods:{handleClick({target:e}){if(e.nodeName==="IMG"){const r=[...this.$el.querySelectorAll("img").values()].map(p=>p.src);if(r.length===0)return;const i=Math.max(0,r.indexOf(e.src));this.$store.dispatch("previewImage",{index:i,list:r})}}}},m={};var g=d(w,h,x,!1,$,"6ef45f6c",null,null);function $(e){for(let r in m)this[r]=m[r]}var ne=function(){return g.exports}();export{ne as default};