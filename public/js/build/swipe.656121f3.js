import{_ as m}from"./openpgp_hi.15f91b1d.js";import{P as l}from"./photoswipe.a7142509.js";import{n as h}from"./app.481e399c.js";import"./@micro-zoe.c2e1472d.js";import"./jquery.44658a49.js";import"./@babel.49d8906a.js";import"./dayjs.a811ba28.js";import"./localforage.1045925b.js";import"./markdown-it.f48c10fc.js";import"./entities.797c3e49.js";import"./uc.micro.39573202.js";import"./mdurl.2f66c031.js";import"./linkify-it.3ecfda1e.js";import"./punycode.c1b51344.js";import"./highlight.js.24fdca15.js";import"./markdown-it-link-attributes.e1d5d151.js";import"./@traptitech.b5c819e2.js";import"./vue.c448ed56.js";import"./vuex.cc7cb26e.js";import"./axios.6ec123f8.js";import"./le5le-store.b40f9152.js";import"./vue-router.2d566cd7.js";import"./vue-clipboard2.6e355525.js";import"./clipboard.7eddb2ef.js";import"./view-design-hi.d2045547.js";import"./vuedraggable.dbf1607a.js";import"./sortablejs.20b8ddfe.js";import"./vue-resize-observer.452c7636.js";import"./element-sea.e89b014c.js";import"./deepmerge.cecf392e.js";import"./resize-observer-polyfill.9f685ce8.js";import"./throttle-debounce.7c3948b2.js";import"./babel-helper-vue-jsx-merge-props.5ed215c3.js";import"./normalize-wheel.2a034b9f.js";import"./async-validator.5f40db32.js";import"./babel-runtime.4773988a.js";import"./core-js.314b4a1d.js";var d=function(){var i=this,t=i.$createElement,r=i._self._c||t;return r("div")},u=[];const c={props:{className:{type:String,default:()=>"preview-image-swipe-"+Math.round(Math.random()*1e4)},urlList:{type:Array,default:()=>[]},initialIndex:{type:Number,default:0}},data(){return{lightbox:null}},beforeDestroy(){var i;(i=this.lightbox)==null||i.destroy()},watch:{urlList:{handler(i){var n;let t=!1,r=!1;(n=this.lightbox)==null||n.destroy();const a=i.map(o=>{if($A.isJson(o)){if(parseInt(o.width)>0&&parseInt(o.height)>0)return o;o=o.src}return r=!0,{html:`<div class="preview-image-swipe"><img src="${o}"/></div>`}});this.lightbox=new l({dataSource:a,escKey:!1,mainClass:this.className+" no-dark-content",showHideAnimationType:"none",pswpModule:()=>m(()=>import("./photoswipe.a7142509.js").then(function(o){return o.p}),["js/build/photoswipe.a7142509.js","js/build/photoswipe.0fb72215.css"])}),this.lightbox.on("change",o=>{!r||$A.loadScript("js/pinch-zoom.umd.min.js").then(f=>{document.querySelector(`.${this.className}`).querySelectorAll(".preview-image-swipe").forEach(e=>{e.getAttribute("data-init-pinch-zoom")!=="init"&&(e.setAttribute("data-init-pinch-zoom","init"),e.querySelector("img").addEventListener("pointermove",p=>{t&&p.stopPropagation()}),new PinchZoom.default(e,{draggableUnzoomed:!1,onDragStart:()=>{t=!0},onDragEnd:()=>{t=!1}}))})})}),this.lightbox.on("close",()=>{this.$emit("on-close")}),this.lightbox.on("destroy",()=>{this.$emit("on-destroy")}),this.lightbox.init(),this.lightbox.loadAndOpen(this.initialIndex)},immediate:!0},initialIndex(i){var t;(t=this.lightbox)==null||t.loadAndOpen(i)}}},s={};var _=h(c,d,u,!1,g,null,null,null);function g(i){for(let t in s)this[t]=s[t]}var ot=function(){return _.exports}();export{ot as default};