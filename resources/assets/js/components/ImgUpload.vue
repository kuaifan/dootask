<template>
    <div class="common-img-update">
        <div v-if="type !== 'callback'" class="imgcomp-upload-list" v-for="item in uploadList">
            <template v-if="item.status === 'finished'">
                <div class="imgcomp-upload-img" v-bind:style="{ 'background-image': 'url(' + __thumb(item.thumb) + ')' }"></div>
                <div class="imgcomp-upload-list-cover">
                    <Icon type="ios-eye-outline" @click.native="handleView(item)"></Icon>
                    <Icon type="ios-trash-outline" @click.native="handleRemove(item)"></Icon>
                </div>
            </template>
            <template v-else>
                <Progress v-if="item.showProgress" :percent="item.percentage" hide-info></Progress>
            </template>
        </div>
        <div class="add-box" v-bind:class="{ 'callback-add-box': type === 'callback' }">
            <div class="add-box-icon">
                <Icon type="md-add" size="32"></Icon>
            </div>
            <div class="add-box-upload">
                <div class="add-box-item" @click="browsePicture">
                    <span>{{$L('浏览')}}<em v-if="type === 'callback'">{{$L('图片')}}</em></span>
                </div>
                <div class="add-box-item">
                    <Upload
                            name="image"
                            ref="upload"
                            accept="image/*"
                            :action="actionUrl"
                            :headers="uploadHeaders"
                            :data="uploadParams"
                            :show-upload-list="false"
                            :max-size="maxSize"
                            :format="['jpg', 'jpeg', 'gif', 'png']"
                            :default-file-list="defaultList"
                            :on-progress="handleProgress"
                            :on-success="handleSuccess"
                            :on-error="handleError"
                            :on-format-error="handleFormatError"
                            :on-exceeded-size="handleMaxSize"
                            :before-upload="handleBeforeUpload"
                            :multiple=multiple>
                        <span>{{$L('上传')}}<em v-if="type === 'callback'">{{$L('图片')}}</em></span>
                    </Upload>
                </div>
            </div>
        </div>
        <Modal :title="$L('浏览图片空间')" v-model="browseVisible" class="img-upload-modal" width="710">
            <div class="browse-load" v-if="isLoading">{{$L('加载中...')}}</div>
            <div class="browse-list" :class="httpType==='input'?'browse-list-disabled':''" ref="browselistbox">
                <div v-if="browseList.length <= 0">{{$L('无内容')}}</div>
                <div v-else class="browse-item" v-for="item in browseList" @click="browseItem(item)">
                    <Icon v-if="item.active" class="browse-icon" type="ios-checkmark-circle"></Icon>
                    <div class="browse-img" v-bind:style="{ 'background-image': 'url(' + item.thumb + ')' }"></div>
                    <div class="browse-title">{{item.title}}</div>
                </div>
            </div>
            <div slot="footer" class="img-upload-foot">
                <div v-if="type !== 'callback' && http && httpType===''" class="img-upload-foot-input" @click="httpType='input'">
                    <Icon type="ios-image" size="22"/>
                    <div class="img-upload-foot-httptitle">{{$L('自定义图片地址')}}</div>
                </div>
                <div v-if="type !== 'callback' && http && httpType==='input'" class="img-upload-foot-input">
                    <Input v-model="httpValue" :placeholder="$L('以 http:// 或 https:// 开头')" @on-search="httpEnter" search :enter-button="$L('确定')">
                        <span slot="prepend" @click="httpType=''" style="cursor:pointer">{{$L('自定义地址')}}: </span>
                    </Input>
                </div>
                <Button v-if="httpType===''" @click="browseVisible=false">{{$L('关闭')}}</Button>
                <Button v-if="httpType===''" type="primary" @click="handleCallback(true)">{{$L('完成')}}</Button>
            </div>
        </Modal>
        <Modal :title="$L('查看图片')" v-model="visible" class="img-upload-modal" draggable>
            <div style="max-height:480px;overflow:auto;">
                <a :href="imgVisible" target="_blank"><img :src="imgVisible" v-if="visible" style="max-width:100%;max-height:900px;display:block;margin:0 auto"></a>
            </div>
        </Modal>
    </div>
</template>

<script>
    import {mapState} from "vuex";

    export default {
        name: 'ImgUpload',
        props: {
            value: {
            },
            num: {
            },
            width: {
            },
            height: {
            },
            type: {
            },
            http: {
                type: Boolean,
                default: false
            },
            otherParams: {
                type: Object,
                default: () => {
                    return {};
                }
            },
            uploadIng: {
                type: Number,
                default: 0
            }
        },
        data () {
            return {
                actionUrl: $A.apiUrl('system/imgupload'),
                params: {
                    width: this.width,
                    height: this.height
                },
                multiple: this.num > 1,
                visible: false,
                browseVisible: false,
                isLoading: false,
                browseList: [],
                browseListNext: [],
                imgVisible: '',
                defaultList: this.initItems(this.value),
                uploadList: [],
                maxNum: Math.min(Math.max($A.runNum(this.num), 1), 99),
                httpValue: '',
                httpType: '',
                maxSize: 2048
            }
        },
        mounted () {
            this.uploadList = this.$refs.upload.fileList;
            this.$emit('input', this.uploadList);
            //
            let browseBox = $A(this.$refs.browselistbox);
            browseBox.scroll(()=>{
                let nHight = browseBox[0].scrollHeight;
                let nTop = browseBox[0].scrollTop;
                let boxHight = browseBox.height();
                if(nTop + boxHight >= nHight) {
                    //到底了
                    if (this.browseListNext.length > 0) {
                        let tmpNext = this.browseListNext;
                        this.browseListNext = [];
                        this.browsePictureFor(tmpNext);
                    }
                }
            });
        },
        watch: {
            value (val) {
                if (typeof val === 'string') {
                    this.$emit('input', this.initItems(val));
                    return;
                }
                if (val === this.$refs.upload.fileList) {
                    return;
                }
                this.$refs.upload.fileList = this.initItems(val);
                this.uploadList = this.$refs.upload.fileList;
            },
            browseVisible() {
                this.httpType = '';
                this.httpValue = '';
            }
        },
        computed: {
            ...mapState(['userToken']),

            uploadHeaders() {
                return {
                    fd: $A.getStorageString("userWsFd"),
                    token: this.userToken,
                }
            },

            uploadParams() {
                if (Object.keys(this.otherParams).length > 0) {
                    return Object.assign(this.params, this.otherParams);
                } else {
                    return this.params;
                }
            }
        },
        methods: {
            handleCallback(file) {
                if (this.type === 'callback') {
                    if (file === true) {
                        this.$emit('on-callback', this.uploadList);
                        this.$refs.upload.fileList = [];
                        this.uploadList = this.$refs.upload.fileList;
                    }else if (typeof file === "object") {
                        this.$emit('on-callback', [file]);
                    }
                }
                this.browseVisible = false;
            },
            initItems(items) {
                //数据初始化
                if (typeof items === 'string') {
                    items = [{'url': items}];
                }
                let list = [];
                $A.each(items, (index, item)=>{
                    if (typeof item === 'string') item = {'url': item};
                    if (item.url) {
                        item.active = true;
                        item.status = 'finished';
                        if (typeof item.path === 'undefined') item.path = item.url;
                        if (typeof item.thumb === 'undefined') item.thumb = item.url;
                        list.push(item);
                    }
                });
                return list;
            },
            handleView (item) {
                //查看
                this.visible = true;
                this.imgVisible = item.url;
            },
            handleRemove (item) {
                //删除
                let fileList = this.$refs.upload.fileList;
                this.$refs.upload.fileList.splice(fileList.indexOf(item), 1);
                this.$emit('input', this.$refs.upload.fileList);
            },
            handleProgress(event, file) {
                //开始上传
                if (file._uploadIng === undefined) {
                    file._uploadIng = true;
                    this.$emit('update:uploadIng', this.uploadIng + 1);
                }
            },
            handleSuccess (res, file) {
                //上传完成
                this.$emit('update:uploadIng', this.uploadIng - 1);
                if (res.ret === 1) {
                    file.url = res.data.url;
                    file.path = res.data.path;
                    file.thumb = res.data.thumb;
                    this.handleCallback(file);
                }else{
                    $A.noticeWarning({
                        title: this.$L('上传失败'),
                        desc: this.$L('文件 ' + file.name + ' 上传失败 ' + res.msg),
                    });
                    this.$refs.upload.fileList.pop();
                }
                this.$emit('input', this.$refs.upload.fileList);
            },
            handleError() {
                //上传错误
                this.$emit('update:uploadIng', this.uploadIng - 1);
            },
            handleFormatError (file) {
                //上传类型错误
                $A.noticeWarning({
                    title: this.$L('文件格式不正确'),
                    desc: this.$L('文件 ' + file.name + ' 格式不正确，请上传 jpg、jpeg、gif、png 格式的图片。')
                });
            },
            handleMaxSize (file) {
                //上传大小错误
                $A.noticeWarning({
                    title: this.$L('超出文件大小限制'),
                    desc: this.$L('文件 ' + file.name + ' 太大，不能超过：' + $A.bytesToSize(this.maxSize * 1024))
                });
            },
            handleBeforeUpload () {
                //上传前判断
                let check = this.uploadList.length < this.maxNum;
                if (!check && this.uploadList.length == 1) {
                    this.handleRemove(this.uploadList[0]);
                    check = this.uploadList.length < this.maxNum;
                }
                if (!check) {
                    $A.noticeWarning(this.$L('最多只能上传 ' + this.maxNum + ' 张图片。'));
                }
                this.params = {
                    width: this.width,
                    height: this.height
                };
                return check;
            },
            handleClick() {
                //手动上传
                if (this.handleBeforeUpload()) {
                    this.$refs.upload.handleClick()
                }
            },
            handleManual(file) {
                //手动传file
                if (this.handleBeforeUpload()) {
                    this.$refs.upload.upload(file);
                }
            },
            browsePicture(path) {
                //获取图片空间
                this.browseVisible = true;
                this.browseList = [];
                this.browseListNext = [];
                this.isLoading = true;
                this.$store.dispatch("call", {
                    url: 'system/imgview',
                    data: {path: path ? path : ''},
                }).then(({data}) => {
                    this.isLoading = false;
                    let dirs = data['dirs'];
                    for (let i = 0; i < dirs.length; i++) {
                        this.browseList.push(dirs[i]);
                    }
                    this.browsePictureFor(data['files']);
                }).catch(({msg}) => {
                    this.isLoading = false;
                    this.browseVisible = false;
                    $A.noticeWarning(msg);
                });
            },

            browsePictureFor(files) {
                for (let o = 0; o < files.length; o++) {
                    for (let j = 0; j < this.uploadList.length; j++) {
                        if (this.uploadList[j]['url'] === files[o]['url']
                            || this.uploadList[j]['url'] === files[o]['path']) {
                            files[o]['active'] = true;
                            break;
                        }
                    }
                    if (o < 100) {
                        this.browseList.push(files[o]);
                    }else{
                        this.browseListNext.push(files[o]);
                    }
                }
            },

            browseItem(item) {
                //点击选择图片
                if (item.type === 'dir') {
                    //文件夹
                    this.browsePicture(item.path);
                }else if (item.type === 'file') {
                    //文件
                    if (item.active) {
                        let fileList = this.$refs.upload.fileList;
                        this.$refs.upload.fileList.splice(fileList.indexOf(item), 1);
                        item.active = false;
                    }else{
                        if (this.maxNum === 1) {
                            for (let i = 0; i < this.browseList.length; i++) {
                                this.browseList[i].active = false;
                            }
                            this.$refs.upload.fileList = [];
                            this.uploadList = this.$refs.upload.fileList;
                        }
                        let check = this.uploadList.length < this.maxNum;
                        if (!check) {
                            $A.noticeWarning(this.$L('最多只能选择 ' + this.maxNum + ' 张图片。'));
                            return;
                        }
                        item.active = true;
                        item.status = 'finished';
                        this.$refs.upload.fileList.push(item);
                        this.uploadList = this.$refs.upload.fileList;
                    }
                    this.$emit('input', this.$refs.upload.fileList);
                }
            },

            __thumb(url) {
                if ($A.strExists(url, "?", false)) {
                    return url + "&__thumb=true";
                }else{
                    return url + "?__thumb=true";
                }
            },

            httpEnter() {
                this.$emit('input', this.initItems(this.httpValue));
                this.browseVisible = false;
            }
        }
    }
</script>
