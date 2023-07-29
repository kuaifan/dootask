import{n as o}from"./app.92886a13.js";var r=function(){var t=this,i=t.$createElement,e=t._self._c||i;return e("div",{staticClass:"common-img-update"},[t._l(t.uploadList,function(s){return t.type!=="callback"?e("div",{staticClass:"imgcomp-upload-list"},[s.status==="finished"?[e("div",{staticClass:"imgcomp-upload-img",style:{"background-image":"url("+t.__thumb(s.thumb)+")"}}),e("div",{staticClass:"imgcomp-upload-list-cover"},[e("Icon",{attrs:{type:"ios-eye-outline"},nativeOn:{click:function(a){return t.handleView(s)}}}),e("Icon",{attrs:{type:"ios-trash-outline"},nativeOn:{click:function(a){return t.handleRemove(s)}}})],1)]:[s.showProgress?e("Progress",{attrs:{percent:s.percentage,"hide-info":""}}):t._e()]],2):t._e()}),e("div",{staticClass:"add-box",class:{"callback-add-box":t.type==="callback"}},[e("div",{staticClass:"add-box-icon"},[e("Icon",{attrs:{type:"md-add",size:"32"}})],1),e("div",{staticClass:"add-box-upload"},[e("div",{staticClass:"add-box-item",on:{click:t.browsePicture}},[e("span",[t._v(t._s(t.$L("\u6D4F\u89C8"))),t.type==="callback"?e("em",[t._v(t._s(t.$L("\u56FE\u7247")))]):t._e()])]),e("div",{staticClass:"add-box-item"},[e("Upload",{ref:"upload",attrs:{name:"image",accept:"image/*",action:t.actionUrl,headers:t.uploadHeaders,data:t.uploadParams,"show-upload-list":!1,"max-size":t.maxSize,format:["jpg","jpeg","webp","gif","png"],"default-file-list":t.defaultList,"on-progress":t.handleProgress,"on-success":t.handleSuccess,"on-error":t.handleError,"on-format-error":t.handleFormatError,"on-exceeded-size":t.handleMaxSize,"before-upload":t.handleBeforeUpload,multiple:t.multiple}},[e("span",[t._v(t._s(t.$L("\u4E0A\u4F20"))),t.type==="callback"?e("em",[t._v(t._s(t.$L("\u56FE\u7247")))]):t._e()])])],1)])]),e("Modal",{staticClass:"img-upload-modal",attrs:{title:t.$L("\u6D4F\u89C8\u56FE\u7247\u7A7A\u95F4"),width:"710"},model:{value:t.browseVisible,callback:function(s){t.browseVisible=s},expression:"browseVisible"}},[t.isLoading?e("div",{staticClass:"browse-load"},[t._v(t._s(t.$L("\u52A0\u8F7D\u4E2D...")))]):e("div",{ref:"browselistbox",staticClass:"browse-list",class:t.httpType==="input"?"browse-list-disabled":""},[t.browseList.length<=0?e("div",[t._v(t._s(t.$L("\u65E0\u5185\u5BB9")))]):t._l(t.browseList,function(s){return e("div",{staticClass:"browse-item",on:{click:function(a){return t.browseItem(s)}}},[s.active?e("Icon",{staticClass:"browse-icon",attrs:{type:"ios-checkmark-circle"}}):t._e(),e("div",{staticClass:"browse-img",style:{"background-image":"url("+s.thumb+")"}}),e("div",{staticClass:"browse-title"},[t._v(t._s(s.title))])],1)})],2),e("div",{staticClass:"img-upload-foot",attrs:{slot:"footer"},slot:"footer"},[t.type!=="callback"&&t.http&&t.httpType===""?e("div",{staticClass:"img-upload-foot-input",on:{click:function(s){t.httpType="input"}}},[e("Icon",{attrs:{type:"ios-image",size:"22"}}),e("div",{staticClass:"img-upload-foot-httptitle"},[t._v(t._s(t.$L("\u81EA\u5B9A\u4E49\u56FE\u7247\u5730\u5740")))])],1):t._e(),t.type!=="callback"&&t.http&&t.httpType==="input"?e("div",{staticClass:"img-upload-foot-input"},[e("Input",{attrs:{placeholder:t.$L("\u4EE5 http:// \u6216 https:// \u5F00\u5934"),search:"","enter-button":t.$L("\u786E\u5B9A")},on:{"on-search":t.httpEnter},model:{value:t.httpValue,callback:function(s){t.httpValue=s},expression:"httpValue"}},[e("span",{staticStyle:{cursor:"pointer"},attrs:{slot:"prepend"},on:{click:function(s){t.httpType=""}},slot:"prepend"},[t._v(t._s(t.$L("\u81EA\u5B9A\u4E49\u5730\u5740"))+": ")])])],1):t._e(),t.httpType===""?e("Button",{on:{click:function(s){t.browseVisible=!1}}},[t._v(t._s(t.$L("\u5173\u95ED")))]):t._e(),t.httpType===""?e("Button",{attrs:{type:"primary"},on:{click:function(s){return t.handleCallback(!0)}}},[t._v(t._s(t.$L("\u5B8C\u6210")))]):t._e()],1)]),e("Modal",{staticClass:"img-upload-modal",attrs:{title:t.$L("\u67E5\u770B\u56FE\u7247"),draggable:""},model:{value:t.visible,callback:function(s){t.visible=s},expression:"visible"}},[e("div",{staticStyle:{"max-height":"480px",overflow:"auto"}},[e("a",{attrs:{href:t.imgVisible,target:"_blank"}},[t.visible?e("img",{staticStyle:{"max-width":"100%","max-height":"900px",display:"block",margin:"0 auto"},attrs:{src:t.imgVisible}}):t._e()])])])],2)},n=[];const h={name:"ImgUpload",props:{value:{},num:{},width:{},height:{},whcut:{},type:{},http:{type:Boolean,default:!1},otherParams:{type:Object,default:()=>({})},uploadIng:{type:Number,default:0}},data(){return{actionUrl:$A.apiUrl("system/imgupload"),multiple:this.num>1,visible:!1,browseVisible:!1,isLoading:!1,browseList:[],browseListNext:[],imgVisible:"",defaultList:this.initItems(this.value),uploadList:[],maxNum:Math.min(Math.max($A.runNum(this.num),1),99),httpValue:"",httpType:"",maxSize:2048}},mounted(){this.uploadList=this.$refs.upload.fileList,this.$emit("input",this.uploadList);let t=$A(this.$refs.browselistbox);t.scroll(()=>{let i=t[0].scrollHeight,e=t[0].scrollTop,s=t.height();if(e+s>=i&&this.browseListNext.length>0){let a=this.browseListNext;this.browseListNext=[],this.browsePictureFor(a)}})},watch:{value(t){if(typeof t=="string"){this.$emit("input",this.initItems(t));return}t!==this.$refs.upload.fileList&&(this.$refs.upload.fileList=this.initItems(t),this.uploadList=this.$refs.upload.fileList)},browseVisible(){this.httpType="",this.httpValue=""}},computed:{uploadHeaders(){return{fd:$A.getSessionStorageString("userWsFd"),token:this.userToken}},uploadParams(){let t={width:this.width,height:this.height,whcut:this.whcut};return Object.keys(this.otherParams).length>0?Object.assign(t,this.otherParams):t}},methods:{handleCallback(t){this.type==="callback"&&(t===!0?(this.$emit("on-callback",this.uploadList),this.$refs.upload.fileList=[],this.uploadList=this.$refs.upload.fileList):typeof t=="object"&&this.$emit("on-callback",[t])),this.browseVisible=!1},initItems(t){typeof t=="string"&&(t=[{url:t}]);let i=[];return $A.each(t,(e,s)=>{typeof s=="string"&&(s={url:s}),s.url&&(s.active=!0,s.status="finished",typeof s.path=="undefined"&&(s.path=s.url),typeof s.thumb=="undefined"&&(s.thumb=s.url),i.push(s))}),i},handleView(t){this.$store.dispatch("previewImage",t.url)},handleRemove(t){let i=this.$refs.upload.fileList;this.$refs.upload.fileList.splice(i.indexOf(t),1),this.$emit("input",this.$refs.upload.fileList)},handleProgress(t,i){i._uploadIng===void 0&&(i._uploadIng=!0,this.$emit("update:uploadIng",this.uploadIng+1))},handleSuccess(t,i){this.$emit("update:uploadIng",this.uploadIng-1),t.ret===1?(i.url=t.data.url,i.path=t.data.path,i.thumb=t.data.thumb,this.handleCallback(i)):($A.noticeWarning({title:this.$L("\u4E0A\u4F20\u5931\u8D25"),desc:this.$L("\u6587\u4EF6 "+i.name+" \u4E0A\u4F20\u5931\u8D25 "+t.msg)}),this.$refs.upload.fileList.pop()),this.$emit("input",this.$refs.upload.fileList)},handleError(){this.$emit("update:uploadIng",this.uploadIng-1)},handleFormatError(t){$A.noticeWarning({title:this.$L("\u6587\u4EF6\u683C\u5F0F\u4E0D\u6B63\u786E"),desc:this.$L("\u6587\u4EF6 "+t.name+" \u683C\u5F0F\u4E0D\u6B63\u786E\uFF0C\u8BF7\u4E0A\u4F20 jpg\u3001jpeg\u3001webp\u3001gif\u3001png \u683C\u5F0F\u7684\u56FE\u7247\u3002")})},handleMaxSize(t){$A.noticeWarning({title:this.$L("\u8D85\u51FA\u6587\u4EF6\u5927\u5C0F\u9650\u5236"),desc:this.$L("\u6587\u4EF6 "+t.name+" \u592A\u5927\uFF0C\u4E0D\u80FD\u8D85\u8FC7\uFF1A"+$A.bytesToSize(this.maxSize*1024))})},handleBeforeUpload(){let t=this.uploadList.length<this.maxNum;return!t&&this.uploadList.length==1&&(this.handleRemove(this.uploadList[0]),t=this.uploadList.length<this.maxNum),t||$A.noticeWarning(this.$L("\u6700\u591A\u53EA\u80FD\u4E0A\u4F20 "+this.maxNum+" \u5F20\u56FE\u7247\u3002")),t},handleClick(){this.handleBeforeUpload()&&this.$refs.upload.handleClick()},handleManual(t){this.handleBeforeUpload()&&this.$refs.upload.upload(t)},browsePicture(t){this.browseVisible=!0,this.browseList=[],this.browseListNext=[],this.isLoading=!0,this.$store.dispatch("call",{url:"system/imgview",data:{path:t||""}}).then(({data:i})=>{let e=i.dirs;for(let s=0;s<e.length;s++)this.browseList.push(e[s]);this.browsePictureFor(i.files)}).catch(({msg:i})=>{this.browseVisible=!1,$A.noticeWarning(i)}).finally(i=>{this.isLoading=!1})},browsePictureFor(t){for(let i=0;i<t.length;i++){for(let e=0;e<this.uploadList.length;e++)if(this.uploadList[e].url===t[i].url||this.uploadList[e].url===t[i].path){t[i].active=!0;break}i<100?this.browseList.push(t[i]):this.browseListNext.push(t[i])}},browseItem(t){if(t.type==="dir")this.browsePicture(t.path);else if(t.type==="file"){if(t.active){let i=this.$refs.upload.fileList;this.$refs.upload.fileList.splice(i.indexOf(t),1),t.active=!1}else{if(this.maxNum===1){for(let e=0;e<this.browseList.length;e++)this.browseList[e].active=!1;this.$refs.upload.fileList=[],this.uploadList=this.$refs.upload.fileList}if(!(this.uploadList.length<this.maxNum)){$A.noticeWarning(this.$L("\u6700\u591A\u53EA\u80FD\u9009\u62E9 "+this.maxNum+" \u5F20\u56FE\u7247\u3002"));return}t.active=!0,t.status="finished",this.$refs.upload.fileList.push(t),this.uploadList=this.$refs.upload.fileList}this.$emit("input",this.$refs.upload.fileList)}},__thumb(t){return $A.strExists(t,"?",!1)?t+"&__thumb=true":t+"?__thumb=true"},httpEnter(){this.$emit("input",this.initItems(this.httpValue)),this.browseVisible=!1}}},l={};var u=o(h,r,n,!1,p,null,null,null);function p(t){for(let i in l)this[i]=l[i]}var c=function(){return u.exports}();export{c as I};