<template>
    <div>
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
        <Modal :title="$L('浏览图片空间的图片')" v-model="browseVisible" class="img-upload-modal" class-name="simple-modal" width="710">
            <div class="browse-load" v-if="isLoading">{{$L('加载中...')}}</div>
            <div class="browse-list" :class="httpType==='input'?'browse-list-disabled':''" ref="browselistbox">
                <div class="browse-item" v-for="item in browseList" @click="browseItem(item)">
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
        <Modal :title="$L('查看图片')" v-model="visible" class="img-upload-modal" class-name="simple-modal" draggable>
            <div style="max-height:480px;overflow:auto;">
                <a :href="imgVisible" target="_blank"><img :src="imgVisible" v-if="visible" style="max-width:100%;max-height:900px;display:block;margin:0 auto"></a>
            </div>
        </Modal>
    </div>
</template>

<style lang="scss">
:global {
    .img-upload-modal {
        .ivu-modal-mask {
            z-index: 1001;
        }

        .ivu-modal-no-mask {
            background-color: rgba(55, 55, 55, .2);
        }

        .ivu-modal-wrap {
            z-index: 1001;
        }
    }

    .imgcomp-upload-list {
        display: inline-block;
        width: 60px;
        height: 60px;
        text-align: center;
        line-height: 60px;
        border: 1px solid transparent;
        border-radius: 4px;
        overflow: hidden;
        background: #fff;
        position: relative;
        box-shadow: 0 1px 1px rgba(0, 0, 0, .2);
        margin-right: 4px;
        vertical-align: top;

        .imgcomp-upload-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-position: center;
            background-size: cover;
        }

        .imgcomp-upload-list-cover {
            display: none;
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, .6);
        }

        .imgcomp-upload-list-cover i {
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            vertical-align: middle;
            margin: 0;
            transition: all .2s;
        }

        .imgcomp-upload-list-cover i:hover {
            font-size: 28px;
        }

        .ivu-progress-outer {
            background-color: rgba(0, 0, 0, 0.68);

            .ivu-progress-inner {
                width: 88%;
            }
        }
    }

    .imgcomp-upload-list:hover .imgcomp-upload-list-cover {
        display: block;
    }

    .img-upload-foot {
        display: flex;
        align-items: center;
        justify-content: flex-end;

        .img-upload-foot-input {
            flex: 1;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: flex-end;

            .img-upload-foot-httptitle {
                cursor: pointer;
                padding-left: 3px;
                margin-right: 22px;
            }
        }
    }

    .add-box {
        width: 60px;
        height: 60px;
        line-height: 60px;
        display: inline-block;
        background: #fff;
        border: 1px dashed #dddee1;
        border-radius: 4px;
        text-align: center;
        position: relative;
        overflow: hidden;
        vertical-align: top;

        .add-box-icon {
            i {
                vertical-align: middle;
                padding-bottom: 2px;
            }
        }

        .add-box-upload {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            color: #ffffff;
            padding-top: 9px;
            background: rgba(0, 0, 0, 0.6);

            .add-box-item {
                height: 22px;
                line-height: 22px;
                cursor: pointer;

                .ivu-upload-drag, .ivu-upload-drag:hover {
                    background: transparent;
                    border: 0;
                    border-radius: 0;
                }

                span {
                    transition: all .2s;
                    font-size: 12px;
                }
            }

            .add-box-item:hover {
                span {
                    font-size: 14px;
                }
            }
        }

        em {
            font-style: normal;
        }
    }

    .add-box:hover {
        border-color: rgba(0, 0, 0, .6);

        .add-box-upload {
            display: block;
        }
    }

    .callback-add-box {
        display: block;
        width: auto;
        height: 25px;
        line-height: 25px;
        border: 0;
        background: transparent;

        .add-box-icon {
            display: none;
        }

        .add-box-upload {
            display: block;
            width: auto;
            background: transparent;
            color: #333;
            padding: 0;

            > div {
                display: inline-block;
                padding-right: 10px;
            }
        }
    }

    .browse-load {
        margin: 20px;
        text-align: center;
    }

    .browse-list {
        max-height: 540px;
        overflow: auto;

        .browse-item {
            margin: 10px 15px;
            display: inline-block;
            text-align: center;
            cursor: pointer;
            position: relative;

            .browse-img {
                width: 64px;
                height: 64px;
                background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAKoAAABxCAAAAABg5GeyAAACW0lEQVR4Ae3XVRLjMBAE0L3/rSwKM3OcSNPyLVYOLvM6UD0Bd03LVe9XH+RlhlRSSSWVVFJJJZVUUkkllVRSSSWVVFJJJZVUUkl9WyqppJJKKqmkkgpURP17xngOAR5NxW5wlJ9MaLQh83F4NHWmd/gZtdVBaOldfDB1bq5UpJFbFOC6LKnYrkRO209PAw+hIuzWB8Ep5es8HvYo4z4tE1X8UeRwlMM2D5Bzkc7kj6Bi3VTKDDwEeUcrMxrUvGDXTnHa6kK69SDN9sgq1clxKSbNHqqnYmdri81Q9QHf1JPt1Frncaib2XbiTKL2GkHaurnY9LOulMV0O7G6Kw+g9sw2ohhm62KezVJaaufjWC1TnOkr1exilJ7Ji0vxCCqO9V4UwV4PYr9+apouhGYLKfnahdpqegjmeoXOpXgANe70pKT6Zhu19qkY2nC0PZS527lQOyInqr8Uvc5jqfUb1X+PGh5IhW90S2quh3FQC2XRcF66TUkTXPcLKm5FtdR9RJq+2hWII7UpFtmsQLEyzsdJtkxxpr6gLotbUSlV9yeT0Trmzk2XPdUThLYarUbWOY9j04xXQ2u+pMZLYSumGmNUH3HbM9qOAwSHodN2Pks25F2j3aI7+IxqNsB+YLWb16ukSjiW4xNB0gMoMfApBS/XZQgi3p9/5RsiKNKZEOwYFVIF5VyTyD19sbyjIJiNJRZxpNbx2S8sGKvGZNHJBniBu9Wy5WxjGuQFqIAcBHiRGyt4ua5gSCWVVFJJJZVUUkkllVRSSSWVVFJJJZVUUkkllVRSSSWVVFI/AgO0SXIVYHeGAAAAAElFTkSuQmCC);
                background-position: center;
                background-repeat: no-repeat;
                background-size: cover;
            }

            .browse-title {
                display: block;
                width: 64px;
                margin-top: 5px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .browse-icon {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 64px;
                font-size: 36px;
                padding-top: 15px;
                color: #ffffff;
                background-color: rgba(0, 0, 0, 0.5);
            }
        }
    }

    .browse-list-disabled {
        position: relative;
    }

    .browse-list-disabled:after {
        position: absolute;
        content: '';
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.9);
        z-index: 1;
    }
}
</style>
<script>
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
                    token: $A.getToken(),
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
                let lists = [];
                $A.each(items, (index, item)=>{
                    if (typeof item === 'string') item = {'url': item};
                    if (item.url) {
                        item.active = true;
                        item.status = 'finished';
                        if (typeof item.path === 'undefined') item.path = item.url;
                        if (typeof item.thumb === 'undefined') item.thumb = item.url;
                        lists.push(item);
                    }
                });
                return lists;
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
            handleProgress() {
                //开始上传
                this.$emit('update:uploadIng', this.uploadIng + 1);
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
                    desc: this.$L('文件 ' + file.name + ' 太大，不能超过' + $A.bytesToSize(this.maxSize * 1024))
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
                    token: $A.getToken(),
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
                $A.apiAjax({
                    url: 'system/imgview',
                    data: { path: path?path:'' },
                    beforeSend: true,
                    complete: true,
                    error: true,
                    success: (res) => {
                        this.isLoading = false;
                        if (res.ret === 1) {
                            let dirs = res.data['dirs'];
                            for (let i = 0; i < dirs.length; i++) {
                                this.browseList.push(dirs[i]);
                            }
                            this.browsePictureFor(res.data['files']);
                        }else if (res.ret === -2) {
                            this.browseVisible = false;
                            $A.noticeWarning(res.msg);
                        }
                    }
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
                    //目录
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
