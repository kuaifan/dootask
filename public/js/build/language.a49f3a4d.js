import{l as s,m as l,a as i,s as m,n as u}from"./app.5e0461e9.js";var f=function(){var t=this,o=t.$createElement,a=t._self._c||o;return a("div",{staticClass:"setting-item submit"},[a("Form",{ref:"formData",attrs:{model:t.formData,rules:t.ruleData,labelPosition:t.formLabelPosition,labelWidth:t.formLabelWidth},nativeOn:{submit:function(e){e.preventDefault()}}},[a("FormItem",{attrs:{label:t.$L("\u9009\u62E9\u8BED\u8A00"),prop:"language"}},[a("Select",{attrs:{placeholder:t.$L("\u9009\u9879\u8BED\u8A00")},model:{value:t.formData.language,callback:function(e){t.$set(t.formData,"language",e)},expression:"formData.language"}},t._l(t.languageList,function(e,n){return a("Option",{key:n,attrs:{value:n}},[t._v(t._s(e))])}),1)],1)],1),a("div",{staticClass:"setting-footer"},[a("Button",{attrs:{loading:t.loadIng>0,type:"primary"},on:{click:t.submitForm}},[t._v(t._s(t.$L("\u63D0\u4EA4")))]),a("Button",{staticStyle:{"margin-left":"8px"},attrs:{loading:t.loadIng>0},on:{click:t.resetForm}},[t._v(t._s(t.$L("\u91CD\u7F6E")))])],1)],1)},g=[];const c={data(){return{loadIng:0,languageList:s,formData:{language:""},ruleData:{}}},mounted(){this.initData()},computed:{...l(["formLabelPosition","formLabelWidth"])},methods:{initData(){this.$set(this.formData,"language",i),this.formData_bak=$A.cloneJSON(this.formData)},submitForm(){this.$refs.formData.validate(t=>{t&&m(this.formData.language)})},resetForm(){this.formData=$A.cloneJSON(this.formData_bak)}}},r={};var _=u(c,f,g,!1,d,null,null,null);function d(t){for(let o in r)this[o]=r[o]}var p=function(){return _.exports}();export{p as default};