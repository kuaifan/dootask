import{m as h}from"./vuex.cc7cb26e.js";import{n as p}from"./app.fe1cd649.js";import"./@micro-zoe.c2e1472d.js";import"./jquery.f44c3c79.js";import"./@babel.43d8d0a5.js";import"./dayjs.aaefe451.js";import"./localforage.bd4872f5.js";import"./markdown-it.6846e2b0.js";import"./entities.797c3e49.js";import"./uc.micro.700527ef.js";import"./mdurl.95c1032c.js";import"./linkify-it.96515e28.js";import"./punycode.50f384b0.js";import"./highlight.js.ab8aeea4.js";import"./markdown-it-link-attributes.e1d5d151.js";import"./@traptitech.64308959.js";import"./vue.296078bd.js";import"./openpgp_hi.15f91b1d.js";import"./axios.6ec123f8.js";import"./le5le-store.d4b5b622.js";import"./vue-router.2d566cd7.js";import"./vue-clipboard2.ad7135d3.js";import"./clipboard.3f21bed6.js";import"./view-design-hi.609f8897.js";import"./vuedraggable.6ea348a4.js";import"./sortablejs.982d79d6.js";import"./vue-resize-observer.e788af6d.js";import"./element-sea.c283d284.js";import"./deepmerge.cecf392e.js";import"./resize-observer-polyfill.37526d89.js";import"./throttle-debounce.7c3948b2.js";import"./babel-helper-vue-jsx-merge-props.5ed215c3.js";import"./normalize-wheel.2a034b9f.js";import"./async-validator.e7e40052.js";import"./babel-runtime.4773988a.js";import"./core-js.314b4a1d.js";const l={name:"AceEditor",props:{value:{default:""},options:{type:Object,default:()=>({})},theme:{type:String,default:"auto"},ext:{type:String,default:"txt"},height:{type:Number||null,default:null},width:{type:Number||null,default:null},wrap:{type:Boolean,default:!1},readOnly:{type:Boolean,default:!1}},render(e){return e("div",{class:"no-dark-content"})},data:()=>({code:"",editor:null,cursorPosition:{row:0,column:0},supportedModes:{Apache_Conf:["^htaccess|^htgroups|^htpasswd|^conf|htaccess|htgroups|htpasswd"],BatchFile:["bat|cmd"],C_Cpp:["cpp|c|cc|cxx|h|hh|hpp|ino"],CSharp:["cs"],CSS:["css"],Dockerfile:["^Dockerfile"],golang:["go|golang"],HTML:["html|htm|xhtml|vue|we|wpy"],Java:["java"],JavaScript:["js|jsm|jsx"],JSON:["json"],JSP:["jsp"],LESS:["less"],Lua:["lua"],Makefile:["^Makefile|^GNUmakefile|^makefile|^OCamlMakefile|make"],Markdown:["md|markdown"],MySQL:["mysql"],Nginx:["nginx|conf"],INI:["ini|conf|cfg|prefs"],ObjectiveC:["m|mm"],Perl:["pl|pm"],Perl6:["p6|pl6|pm6"],pgSQL:["pgsql"],PHP_Laravel_blade:["blade.php"],PHP:["php|inc|phtml|shtml|php3|php4|php5|phps|phpt|aw|ctp|module"],Powershell:["ps1"],Python:["py"],R:["r"],Ruby:["rb|ru|gemspec|rake|^Guardfile|^Rakefile|^Gemfile"],Rust:["rs"],SASS:["sass"],SCSS:["scss"],SH:["sh|bash|^.bashrc"],SQL:["sql"],SQLServer:["sqlserver"],Swift:["swift"],Text:["txt"],Typescript:["ts|typescript|str"],VBScript:["vbs|vb"],Verilog:["v|vh|sv|svh"],XML:["xml|rdf|rss|wsdl|xslt|atom|mathml|mml|xul|xbl|xaml|plist"],YAML:["yaml|yml"],Compress:["tar|zip|7z|rar|gz|arj|z"],images:["icon|jpg|jpeg|webp|png|bmp|gif|tif|emf"]}}),mounted(){$A.loadScriptS(["js/ace/ace.js","js/ace/mode-json.js"]).then(e=>{this.setSize(this.$el,{height:this.height,width:this.width}),this.editor=window.ace.edit(this.$el,{wrap:this.wrap,showPrintMargin:!1,readOnly:this.readOnly,keyboardHandler:"vscode"}),this.editor.session.setMode(`ace/mode/${this.getFileMode()}`),this.$emit("mounted",this.editor),this.editor.session.$worker&&this.editor.session.$worker.addEventListener("annotate",this.workerMessage,!1),this.setValue(this.value),this.editor.setOptions(this.options),this.editTheme&&this.editor.setTheme(`ace/theme/${this.editTheme}`),this.editor.commands.addCommand({name:"\u4FDD\u5B58\u6587\u4EF6",bindKey:{win:"Ctrl-S",mac:"Command-S"},exec:()=>{this.$emit("saveData")},readOnly:!1}),this.editor.getSession().on("change",()=>{this.code=this.editor.getValue(),this.$emit("input",this.code)})})},methods:{workerMessage({data:e}){this.cursorPosition=this.editor.selection.getCursor();const[t]=e;t&&t.type==="error"?this.$emit("validationFailed",t):this.$emit("change",this.editor.getValue())},setSize(e,{width:t=this.width,height:i=this.height}){e.style.width=t&&typeof t=="number"?`${t}px`:"100%",e.style.height=i&&typeof i=="number"?`${i}px`:"100%",this.$nextTick(()=>this.editor&&this.editor.resize())},setValue(e){typeof e=="string"&&this.editor&&(this.editor.setValue(e),this.editor.clearSelection())},getFileMode(){var e=this.ext||"text";for(var t in this.supportedModes)for(var i=this.supportedModes[t],r=i[0].split("|"),a=t.toLowerCase(),s=0;s<r.length;s++)if(e==r[s])return a;return"text"}},computed:{...h(["themeName"]),editTheme(){return this.theme=="auto"?this.themeName==="dark"?"dracula-dark":"chrome":this.theme}},watch:{options(e){e&&typeof e=="object"&&this.editor&&this.editor.setOptions(e)},editTheme(e){e&&typeof e=="string"&&this.editor&&this.editor.setTheme(`ace/theme/${e}`)},ext(e){e&&typeof e=="string"&&this.editor&&this.editor.session.setMode(`ace/mode/${this.getFileMode()}`)},width(e){this.setSize(this.el,{width:e})},height(e){this.setSize(this.el,{height:e})},readOnly(e){typeof e=="boolean"&&this.editor&&this.editor.setReadOnly(e)},value(e){if(!this.editor||e==this.code)return;this.setValue(e);const{row:t,column:i}=this.cursorPosition;this.editor.selection.moveCursorTo(t,i)}},beforeDestroy(){this.editor&&(this.editor.session.$worker&&this.editor.session.$worker.removeEventListener("message",this.workerMessage,!1),this.editor.destroy(),this.editor.container.remove())}};let m,d;const o={};var n=p(l,m,d,!1,c,null,null,null);function c(e){for(let t in o)this[t]=o[t]}var X=function(){return n.exports}();export{X as default};