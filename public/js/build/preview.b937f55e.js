import{V as t,d as p,a as s,b as a,c as n,_ as l,e as u,v as _}from"./@kangc.0e27bf4b.js";import{P as c}from"./prismjs.e9e594e5.js";import{l as o,n as v}from"./app.091fe77b.js";import{p as d}from"./index.40a8e116.js";import"./@babel.49d8906a.js";import"./vue.c448ed56.js";import"./copy-to-clipboard.a53c061d.js";import"./toggle-selection.d2487283.js";import"./@micro-zoe.c2e1472d.js";import"./jquery.df8c857a.js";import"./dayjs.fe6fa4c2.js";import"./localforage.ac776967.js";import"./markdown-it.f48c10fc.js";import"./entities.797c3e49.js";import"./uc.micro.39573202.js";import"./mdurl.2f66c031.js";import"./linkify-it.3ecfda1e.js";import"./punycode.c1b51344.js";import"./highlight.js.24fdca15.js";import"./markdown-it-link-attributes.e1d5d151.js";import"./@traptitech.b5c819e2.js";import"./vuex.cc7cb26e.js";import"./openpgp_hi.15f91b1d.js";import"./axios.6ec123f8.js";import"./le5le-store.b40f9152.js";import"./vue-router.2d566cd7.js";import"./vue-clipboard2.6e355525.js";import"./clipboard.7eddb2ef.js";import"./view-design-hi.d2045547.js";import"./vuedraggable.dbf1607a.js";import"./sortablejs.20b8ddfe.js";import"./vue-resize-observer.452c7636.js";import"./element-sea.e89b014c.js";import"./deepmerge.cecf392e.js";import"./resize-observer-polyfill.9f685ce8.js";import"./throttle-debounce.7c3948b2.js";import"./babel-helper-vue-jsx-merge-props.5ed215c3.js";import"./normalize-wheel.2a034b9f.js";import"./async-validator.5f40db32.js";import"./babel-runtime.4773988a.js";import"./core-js.314b4a1d.js";var f=function(){var e=this,r=e.$createElement,i=e._self._c||r;return i("div",{staticClass:"vmpreview-wrapper",on:{click:e.handleClick}},[i("v-md-preview",{attrs:{text:e.value}})],1)},h=[];o==="zh"||o==="zh-CHT"?t.lang.use("zh-CN",p):t.lang.use("en-US",s);t.use(a());t.use(n());t.use(l());t.use(u());const x={mixins:[d],components:{[t.name]:t},created(){t.use(_,{Prism:c,extend(e){}})},methods:{handleClick({target:e}){if(e.nodeName==="IMG"){const r=[...this.$el.querySelectorAll("img").values()].map(i=>i.src);if(r.length===0)return;this.$store.dispatch("previewImage",{index:e.src,list:r})}}}},m={};var w=v(x,f,h,!1,g,"835a8a7a",null,null);function g(e){for(let r in m)this[r]=m[r]}var ae=function(){return w.exports}();export{ae as default};