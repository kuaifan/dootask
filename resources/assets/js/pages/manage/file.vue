<template>
    <div class="page-file">
        <PageTitle :title="$L('文件')"/>

        <div class="file-wrapper" ref="fileWrapper">

            <div class="file-head">
                <div class="file-nav">
                    <div class="common-nav-back portrait" @click="goForward({name: 'manage-application'},true)"><i class="taskfont">&#xe676;</i></div>
                    <h1>{{$L('文件')}}</h1>
                    <div v-if="loadIng == 0" class="file-refresh" @click="getFileList"><i class="taskfont">&#xe6ae;</i></div>
                </div>
                <div v-if="uploadList.length > 0" class="file-status" @click="[uploadShow=true, packShow=false]">
                    <Loading v-if="uploadList.find(({status}) => status !== 'finished')"/>
                    <Button v-else shape="circle" icon="md-arrow-round-up"></Button>
                </div>
                <div v-if="packList.length > 0" class="file-status" @click="[packShow=true, uploadShow=false]">
                    <Loading v-if="packList.find(({status}) => status !== 'finished')"/>
                    <Button v-else shape="circle" icon="md-arrow-round-down"></Button>
                </div>
                <div :class="['file-search', searchKey ? 'has-value' : '']" @click="onSearchFocus" @mouseenter="onSearchFocus">
                    <Input v-model="searchKey" ref="searchInput" suffix="ios-search" @on-change="onSearchChange" :placeholder="$L('搜索名称')"/>
                </div>
                <div class="file-add">
                    <Button shape="circle" icon="md-add" @click.stop="handleRightClick($event, null, true)"></Button>
                </div>
            </div>

            <div class="file-navigator">
                <ul class="scrollbar-hidden" v-show="showBtnText || (!selectIds.length && !shearFirst)">
                    <li @click="browseFolder(0)">
                        <span>{{$L('全部文件')}}</span>
                    </li>
                    <li v-if="searchKey">{{$L('搜索')}} "{{searchKey}}"</li>
                    <li v-else v-for="item in navigator" :ref="`nav_${item.id}`" @click="browseFolder(item.id)">
                        <i v-if="item.share" class="taskfont">&#xe63f;</i>
                        <span :title="item.name">{{item.name}}</span>
                        <span v-if="item.share && item.permission == 0" class="readonly">{{$L('只读')}}</span>
                    </li>
                </ul>
                <template v-if="shearFirst">
                    <Button :disabled="shearFirst.pid == pid" size="small" type="primary" @click="shearTo" :style="{marginLeft: showBtnText ? '12px' : 0}">
                        <div class="file-shear">
                            <span>{{$L('粘贴')}}</span>
                            <template v-show="showBtnText">"<em>{{shearFirst.name}}</em>"</template>
                            <span v-if="shearIds.length > 1">{{ $L(`等${shearIds.length}个文件`) }}</span>
                        </div>
                    </Button>
                    <Button type="primary" size="small" @click="clearShear">{{ $L('取消剪切') }}</Button>
                </template>
                <template v-else-if="selectIds.length > 0">
                    <Button size="small" type="info" @click="handleContextClick('shearSelect')" :style="{marginLeft: showBtnText ? '12px' : 0}">
                        <div class="tool-box">
                            <Icon type="ios-cut" />
                            <span v-show="showBtnText">{{$L('剪切')}}</span>
                        </div>
                    </Button>
                    <Button :disabled="compressedSownloadDisabled" size="small" type="info" @click="downloadZipFile(selectIds)">
                        <div class="tool-box">
                            <Icon type="ios-download" />
                            <span v-show="showBtnText">{{$L('打包下载')}}</span>
                        </div>
                    </Button>
                    <Button size="small" type="error" @click="deleteFile(selectIds)">
                        <div class="tool-box">
                            <Icon type="ios-trash" />
                            <span v-show="showBtnText">{{$L('删除')}}</span>
                        </div>
                    </Button>
                    <Button type="primary" size="small" @click="clearSelect">
                        {{showBtnText ? $L('取消选择') : $L('取消')}}
                    </Button>
                </template>
                <div v-if="loadIng > 0" class="nav-load"><Loading/></div>
                <div class="flex-full"></div>
                <div v-if="hasShareFile" class="only-checkbox">
                    <Checkbox v-model="hideShared">
                        {{showBtnText ? $L('仅显示我的') : $L('仅我的')}}
                    </Checkbox>
                </div>
                <div :class="['switch-button', tableMode]">
                    <div @click="tableMode='block'"><i class="taskfont">&#xe60c;</i></div>
                    <div @click="tableMode='table'"><i class="taskfont">&#xe66a;</i></div>
                </div>
            </div>

            <div
                class="file-drag"
                @drop.prevent="filePasteDrag($event, 'drag')"
                @dragover.prevent="fileDragOver(true, $event)"
                @dragleave.prevent="fileDragOver(false, $event)">
                <div v-if="tableMode === 'table'" class="file-table" @contextmenu.prevent="handleContextmenu">
                    <Table
                        :columns="columns"
                        :data="fileList"
                        :height="tableHeight"
                        :no-data-text="$L('没有任何文件')"
                        @on-cell-click="clickRow"
                        @on-contextmenu="handleContextMenu"
                        @on-select="handleTableSelect"
                        @on-select-cancel="handleTableSelect"
                        @on-select-all-cancel="handleTableSelect"
                        @on-select-all="handleTableSelect"
                        @on-sort-change="handleTableSort"
                        context-menu
                        stripe/>
                </div>
                <template v-else>
                    <div v-if="fileList.length == 0 && loadIng == 0" class="file-no" @contextmenu.prevent="handleContextmenu">
                        <i class="taskfont">&#xe60b;</i>
                        <p>{{$L('没有任何文件')}}</p>
                    </div>
                    <div v-else class="file-list" @contextmenu.prevent="handleContextmenu">
                        <ul>
                            <li v-for="item in fileList">
                                <div
                                    class="file-item"
                                    :class="{
                                        shear: shearIds.includes(item.id),
                                        highlight: selectIds.includes(item.id),
                                        operate: contextMenuVisible && item.id === contextMenuItem.id,
                                    }"
                                    :data-id="item.id"
                                    v-longpress="handleLongpress"
                                    @click="dropFile(item, 'openCheckMenu')">
                                    <div class="file-check" :class="{'file-checked':selectIds.includes(item.id)}" @click.stop="dropFile(item, 'select')">
                                        <Checkbox :value="selectIds.includes(item.id)"/>
                                    </div>
                                    <div class="file-menu" @click.stop="handleRightClick($event, item)">
                                        <Icon type="ios-more" />
                                    </div>
                                    <div :class="`no-dark-before file-icon ${item.type}${item.share ? ' share' : ''}`">
                                        <template v-if="item.share">
                                            <UserAvatarTip v-if="item.userid != userId" :userid="item.userid" class="share-avatar" :size="20">
                                                <p>{{$L('共享权限')}}: {{$L(item.permission == 1 ? '读/写' : '只读')}}</p>
                                            </UserAvatarTip>
                                            <div v-else class="share-icon no-dark-content">
                                                <i class="taskfont">&#xe757;</i>
                                            </div>
                                        </template>
                                        <template v-else-if="isParentShare">
                                            <UserAvatarTip :userid="item.created_id" class="share-avatar" :size="20">
                                                <p v-if="item.created_id != item.userid"><strong>{{$L('成员创建于')}}: {{item.created_at}}</strong></p>
                                                <p v-else>{{$L('所有者创建于')}}: {{item.created_at}}</p>
                                            </UserAvatarTip>
                                        </template>
                                    </div>
                                    <div v-if="item._edit" class="file-input">
                                        <Input
                                            :ref="'input_' + item.id"
                                            v-model="item.newname"
                                            size="small"
                                            :disabled="!!item._load"
                                            :parser="onParser"
                                            @on-blur="onBlur(item)"
                                            @on-keyup="onKeyup($event, item)"/>
                                        <div v-if="item._load" class="file-load"><Loading/></div>
                                    </div>
                                    <div v-else class="file-name" :title="item.name">{{$A.getFileName(item)}}</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </template>
                <div v-if="dialogDrag" class="drag-over" @click="dialogDrag=false">
                    <div class="drag-text">{{$L('拖动到这里发送')}}</div>
                </div>
            </div>

            <div class="file-menu" :style="contextMenuStyles">
                <Dropdown
                    trigger="custom"
                    :visible="contextMenuVisible"
                    transfer-class-name="page-file-dropdown-menu"
                    @on-click="handleContextClick"
                    @on-clickoutside="handleClickContextMenuOutside"
                    @on-visible-change="handleVisibleChangeMenu"
                    transfer>
                    <DropdownMenu slot="list">
                        <template v-if="contextMenuItem.id">
                            <DropdownItem name="open" class="item-open">
                                {{$L('打开')}}
                                <div class="open-name">“{{contextMenuItem.name}}”</div>
                            </DropdownItem>
                            <DropdownItem v-if="searchKey" name="upperFolder" class="item-open">
                                {{$L('在上层文件夹中显示')}}
                            </DropdownItem>

                            <DropdownItem name="select">{{$L(selectIds.includes(contextMenuItem.id) ? '取消选择' : '选择')}}</DropdownItem>

                            <Dropdown placement="right-start" transfer>
                                <DropdownItem divided @click.native.stop="" name="new:">
                                    <div class="arrow-forward-item">{{$L('新建')}}<Icon type="ios-arrow-forward"></Icon></div>
                                </DropdownItem>
                                <DropdownMenu slot="list" class="page-file-dropdown-menu">
                                    <DropdownItem
                                        v-for="(type, key) in types"
                                        v-if="type.label"
                                        :key="key"
                                        :divided="!!type.divided"
                                        :name="`new:${type.value}`">
                                        <div :class="`no-dark-before file-item file-icon ${type.value}`">{{$L(type.label)}}</div>
                                    </DropdownItem>
                                </DropdownMenu>
                            </Dropdown>

                            <DropdownItem name="rename" divided>{{$L('重命名')}}</DropdownItem>
                            <DropdownItem name="copy" :disabled="contextMenuItem.type == 'folder'">{{$L('复制')}}</DropdownItem>
                            <DropdownItem name="shear" :disabled="contextMenuItem.userid != userId">{{$L('剪切')}}</DropdownItem>

                            <DropdownItem v-if="contextMenuItem.userid == userId" name="share" divided>{{$L('共享')}}</DropdownItem>
                            <DropdownItem v-else-if="contextMenuItem.share" name="outshare" divided>{{$L('退出共享')}}</DropdownItem>
                            <DropdownItem name="send" :disabled="contextMenuItem.type == 'folder'">{{$L('发送')}}</DropdownItem>
                            <DropdownItem name="link" :divided="contextMenuItem.userid != userId && !contextMenuItem.share" :disabled="contextMenuItem.type == 'folder'">{{$L('链接')}}</DropdownItem>
                            <DropdownItem name="download" :disabled="contextMenuItem.ext == '' || (contextMenuItem.userid != userId && contextMenuItem.permission == 0)">{{$L('下载')}}</DropdownItem>
                            <DropdownItem name="downloadzip" :disabled="contextMenuItem.userid != userId && contextMenuItem.permission == 0">{{$L('打包下载')}}</DropdownItem>

                            <DropdownItem name="delete" divided style="color:red">{{$L('删除')}}</DropdownItem>
                        </template>
                        <template v-else>
                            <DropdownItem
                                v-for="(type, key) in types"
                                v-if="type.label"
                                :key="key"
                                :divided="!!type.divided"
                                :name="`new:${type.value}`">
                                <div :class="`no-dark-before file-item file-icon ${type.value}`">{{$L(type.label)}}</div>
                            </DropdownItem>
                        </template>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </div>

        <div v-if="uploadShow && uploadList.length > 0" class="file-upload-list">
            <div class="upload-wrap">
                <div class="title">
                    {{$L('上传列表')}} ({{uploadList.length}})
                    <em v-if="uploadList.find(({status}) => status === 'finished')" @click="uploadClear">{{$L('清空已完成')}}</em>
                </div>
                <ul class="content">
                    <li v-for="(item, index) in uploadList" :key="index" v-if="index < 100" @click="uploadClick(item)">
                        <AutoTip class="file-name">
                            <span v-html="uploadName(item)"></span>
                        </AutoTip>
                        <AutoTip v-if="item.status === 'finished' && item.response && item.response.ret !== 1" class="file-error">{{item.response.msg}}</AutoTip>
                        <Progress v-else :percent="uploadPercentageParse(item.percentage)" :stroke-width="5" />
                        <Icon class="file-close" type="ios-close-circle-outline" @click="uploadList.splice(index, 1)"/>
                    </li>
                </ul>
                <Icon class="close" type="md-close" @click="uploadShow=false"/>
            </div>
        </div>

        <div v-if="packShow && packList.length > 0" class="file-upload-list">
            <div class="upload-wrap">
                <div class="title">
                    <span>{{$L('打包列表')}}({{packList.length}})</span>
                    <em v-if="packList.find(({status}) => status === 'finished')" @click="packClear">{{$L('清空已完成')}}</em>
                </div>
                <ul class="content">
                <li v-for="(item, index) in packList" :key="index" v-if="index < 100">
                    <AutoTip class="file-name">
                        <span v-if="item.status !== 'finished'">{{item.name}}</span>
                        <a v-else :href="item.url" target="_blank">{{item.name}}</a>
                    </AutoTip>
                    <AutoTip v-if="item.status === 'finished' && item.response && item.response.ret !==1" class="file-error">{{item.response.msg}}</AutoTip>
                    <Progress v-else :percent="packPercentageParse(item.percentage)" :stroke-width="5" />
                    <Icon class="file-close" type="ios-close-circle-outline" @click="packList.splice(index, 1)"/>
                </li>
                </ul>
                <Icon class="close" type="md-close" @click="packShow=false"/>
            </div>
        </div>

        <!--上传文件-->
        <Upload
            name="files"
            ref="fileUpload"
            v-show="false"
            :action="actionUrl"
            :headers="headers"
            :multiple="true"
            :webkitdirectory="false"
            :format="uploadFormat"
            :accept="uploadAccept"
            :show-upload-list="false"
            :max-size="maxSize"
            :on-progress="handleProgress"
            :on-success="handleSuccess"
            :on-error="handleError"
            :on-format-error="handleFormatError"
            :on-exceeded-size="handleMaxSize"
            :before-upload="handleBeforeUpload"/>

        <!--上传文件夹-->
        <Upload
            name="files"
            ref="dirUpload"
            v-show="false"
            :action="actionUrl"
            :headers="headers"
            :multiple="true"
            :webkitdirectory="true"
            :format="uploadFormat"
            :accept="uploadAccept"
            :show-upload-list="false"
            :max-size="maxSize"
            :on-progress="handleProgress"
            :on-success="handleSuccess"
            :on-error="handleError"
            :on-format-error="handleFormatError"
            :on-exceeded-size="handleMaxSize"
            :before-upload="handleBeforeUpload"/>

        <!--共享设置-->
        <Modal
            v-model="shareShow"
            :title="$L('共享设置')"
            :mask-closable="false"
            footer-hide>
            <Form class="page-file-share-form" :model="shareInfo" @submit.native.prevent inline>
                <FormItem prop="userids" class="share-userid">
                    <RadioGroup v-model="shareInfo.type">
                        <Radio label="all">{{$L('所有人')}}</Radio>
                        <Radio label="custom">{{$L('指定成员')}}</Radio>
                    </RadioGroup>
                    <UserSelect
                        v-if="shareInfo.type === 'custom'"
                        v-model="shareInfo.userids"
                        :disabledChoice="shareAlready"
                        :multiple-max="100"
                        :placeholder="$L('选择共享成员')"
                        :avatar-size="24"
                        border>
                    </UserSelect>
                </FormItem>
                <FormItem>
                    <Select v-model="shareInfo.permission" :placeholder="$L('权限')">
                        <Option :value="1">{{$L('读/写')}}</Option>
                        <Option :value="0">{{$L('只读')}}</Option>
                    </Select>
                </FormItem>
                <FormItem>
                    <Button type="primary" :loading="shareLoad > 0" @click="onShare">{{$L('共享')}}</Button>
                </FormItem>
            </Form>
            <div v-if="shareList.length > 0" class="page-file-share-items">
                <div class="page-file-share-title">{{ $L('已共享成员') }}:</div>
                <ul class="page-file-share-list">
                    <li v-for="item in shareList">
                        <div v-if="item.userid == 0" class="all-avatar">
                            <EAvatar class="avatar-text" icon="el-icon-s-custom"/>
                            <span class="avatar-name">{{$L('所有人')}}</span>
                        </div>
                        <UserAvatar v-else :size="32" :userid="item.userid" showName/>
                        <Select v-model="item.permission" :placeholder="$L('权限')" @on-change="upShare(item)">
                            <Option :value="1">{{ $L('读/写') }}</Option>
                            <Option :value="0">{{ $L('只读') }}</Option>
                            <Option :value="-1" class="delete">{{ $L('删除') }}</Option>
                        </Select>
                    </li>
                </ul>
            </div>
        </Modal>

        <!-- 文件发送 -->
        <UserSelect
            ref="sendFile"
            v-model="sendData"
            :multiple-max="50"
            :title="$L('发送文件')"
            :before-submit="onSendFile"
            :show-select-all="false"
            show-dialog
            module/>

        <!--文件链接-->
        <Modal
            v-model="linkShow"
            :title="$L('文件链接')"
            :mask-closable="false">
            <div>
                <div style="margin:-10px 0 8px">{{$L('文件名称')}}: {{linkData.name}}</div>
                <Input ref="linkInput" v-model="linkData.url" type="textarea" :rows="3" @on-focus="linkFocus" readonly/>
                <div class="form-tip" style="padding-top:6px">
                    {{$L('可通过此链接浏览文件。')}}
                    <Poptip
                        confirm
                        placement="bottom"
                        :ok-text="$L('确定')"
                        :cancel-text="$L('取消')"
                        @on-ok="linkGet(true)"
                        transfer>
                        <div slot="title">
                            <p><strong>{{$L('注意：刷新将导致原来的链接失效！')}}</strong></p>
                        </div>
                        <a href="javascript:void(0)">{{$L('刷新链接')}}</a>
                    </Poptip>
                </div>
            </div>
            <div slot="footer" class="adaption">
                <Button type="default" @click="linkShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="linkLoad > 0" @click="linkCopy">{{$L('复制')}}</Button>
            </div>
        </Modal>

        <!--查看/修改文件-->
        <DrawerOverlay
            v-model="fileShow"
            class="page-file-drawer"
            :beforeClose="fileBeforeClose"
            :mask-closable="false">
            <FilePreview v-if="isPreview" :file="fileInfo"/>
            <FileContent v-else ref="fileContent" v-model="fileShow" :file="fileInfo"/>
        </DrawerOverlay>

        <!--拖动上传提示-->
        <Modal
            v-model="pasteShow"
            :title="$L(pasteTitle)"
            :cancel-text="$L('取消')"
            :ok-text="$L('立即上传')"
            :enter-ok="true"
            @on-ok="pasteSend">
            <ul class="dialog-wrapper-paste" :class="pasteWrapperClass">
                <li v-for="item in pasteItem">
                    <img v-if="item.type == 'image'" :src="item.result"/>
                    <div v-else>{{$L('文件')}}: {{item.name}} ({{$A.bytesToSize(item.size)}})</div>
                </li>
            </ul>
        </Modal>
    </div>
</template>

<script>
import {mapState} from "vuex";
import {sortBy} from "lodash";
import DrawerOverlay from "../../components/DrawerOverlay";
import longpress from "../../directives/longpress";
import UserSelect from "../../components/UserSelect.vue";
import UserAvatarTip from "../../components/UserAvatar/tip.vue";

const FilePreview = () => import('./components/FilePreview');
const FileContent = () => import('./components/FileContent');
const FileObject = {sort: null, mode: null, shared: null};

export default {
    components: {UserAvatarTip, UserSelect, FilePreview, DrawerOverlay, FileContent},
    directives: {longpress},
    data() {
        return {
            packList: [],
            packShow: false,

            loadIng: 0,
            searchKey: '',
            searchTimeout: null,

            types: [
                {
                    "value": "folder",
                    "label": "新建文件夹",
                    "name": "文件夹",
                },
                {
                    "value": "upload",
                    "label": "上传文件",
                    "name": null,
                    "divided": true
                },
                {
                    "value": "updir",
                    "label": "上传文件夹",
                    "name": null,
                },
                {
                    "value": "document",
                    "label": "文本",
                    "name": "文本",
                    "divided": true
                },
                {
                    "value": "drawio",
                    "label": "图表",
                    "name": "图表",
                },
                {
                    "value": "mind",
                    "label": "思维导图",
                    "name": "导图",
                },
                {
                    "value": "word",
                    "label": "Word 文档",
                    "name": "Word",
                    "divided": true
                },
                {
                    "value": "excel",
                    "label": "Excel 工作表",
                    "name": "Excel",
                },
                {
                    "value": "ppt",
                    "label": "PPT 演示文稿",
                    "name": "PPT",
                }
            ],

            tableMode: "",
            hideShared: false,
            columns: [],

            shareShow: false,
            shareInfo: {id: 0, type: 'all', userid: 0, permission: 1},
            shareList: [],
            shareLoad: 0,

            sendFileId: 0,
            sendData: [],

            linkShow: false,
            linkData: {},
            linkLoad: 0,

            fileShow: false,
            fileInfo: {permission: -1},

            uploadDir: false,
            uploadIng: 0,
            uploadShow: false,
            uploadList: [],
            uploadFormat: [],   // 不限制上传文件类型
            uploadAccept: '',
            uploadCover: false,

            contextMenuItem: {},
            contextMenuVisible: false,
            contextMenuStyles: {
                top: 0,
                left: 0
            },

            shearIds: [],
            selectIds: [],

            dialogDrag: false,
            pasteShow: false,
            pasteFile: [],
            pasteItem: [],
        }
    },

    async beforeRouteEnter(to, from, next) {
        FileObject.sort = await $A.IDBJson("cacheFileSort")
        FileObject.mode = await $A.IDBString("fileTableMode")
        FileObject.shared = await $A.IDBBoolean("fileHideShared")
        next()
    },


    created() {
        this.tableMode = FileObject.mode
        this.hideShared = FileObject.shared
        this.columns = [
            {
                type: 'selection',
                width: 50,
                align: 'right'
            },
            {
                title: this.$L('文件名'),
                key: 'name',
                minWidth: 300,
                sortable: true,
                render: (h, {row}) => {
                    let array = [];
                    let isCreate = !/^\d+$/.test(row.id);
                    if (isCreate) {
                        // 新建
                        array.push(h('Input', {
                            props: {
                                elementId: 'input_' + row.id,
                                value: row.newname,
                                autofocus: true,
                                disabled: !!row._load,
                                parser: this.onParser
                            },
                            style: {
                                width: 'auto'
                            },
                            on: {
                                'on-change': (event) => {
                                    row.newname = event.target.value;
                                },
                                'on-blur': () => {
                                    const file = this.fileLists.find(({id}) => id == row.id);
                                    if (file) {
                                        file.newname = row.newname;
                                        this.onBlur(file)
                                    }
                                },
                                'on-enter': () => {
                                    const file = this.fileLists.find(({id}) => id == row.id);
                                    if (file) {
                                        file.newname = row.newname;
                                        this.onEnter(file)
                                    }
                                }
                            }
                        }))
                        return h('div', {
                            class: 'file-nbox'
                        }, [
                            h('div', {
                                class: `no-dark-before file-name file-icon ${row.type}`,
                            }, array),
                        ]);
                    } else {
                        // 编辑、查看
                        array.push(h('QuickEdit', {
                            props: {
                                value: row.name,
                                autoEdit: !!row._edit,
                                clickOutSide: false,
                                parser: this.onParser
                            },
                            on: {
                                'on-edit-change': (b) => {
                                    const file = this.fileLists.find(({id}) => id == row.id);
                                    if (file) {
                                        setTimeout(() => {
                                            this.setEdit(file.id, b)
                                        }, 100);
                                    }
                                },
                                'on-update': (val, cb) => {
                                    const file = this.fileLists.find(({id}) => id == row.id);
                                    if (file && file._edit === true) {
                                        file.newname = val
                                        this.onEnter(file);
                                    }
                                    cb();
                                }
                            }
                        }, [
                            h('AutoTip', {
                                props: {
                                    placement: 'top'
                                }
                            }, $A.getFileName(row))
                        ]));
                        //
                        const iconArray = [];
                        if (row.share) {
                            if (row.userid != this.userId) {
                                iconArray.push(h('UserAvatar', {
                                    props: {
                                        userid: row.userid,
                                        size: 20
                                    },
                                }))
                            } else {
                                iconArray.push(h('i', {
                                    class: 'taskfont',
                                    domProps: {
                                        innerHTML: '&#xe757;'
                                    },
                                }))
                            }
                        } else if (this.isParentShare) {
                            iconArray.push(h('UserAvatar', {
                                props: {
                                    userid: row.created_id,
                                    size: 20
                                },
                            }, [
                                row.created_id != row.userid ? h('p', [h('strong', this.$L('成员创建于') + ": " + row.created_at)]) : h('p', this.$L('所有者创建于') + ": " + row.created_at)
                            ]))
                        }
                        const shearClass = this.shearIds.includes(row.id) ? ' shear' : '';
                        const shareClass = row.share ? ' share' : '';
                        return h('div', {
                            class: `file-nbox${shearClass}`,
                            attrs: {
                                'data-id': row.id
                            }
                        }, [
                            h('div', {
                                class: `no-dark-before file-name file-icon ${row.type}${shareClass}`,
                            }, array),
                            iconArray
                        ]);
                    }
                }
            },
            {
                title: this.$L('大小'),
                key: 'size',
                width: 110,
                resizable: true,
                sortable: true,
                render: (h, {row}) => {
                    if (row.type == 'folder') {
                        return h('div', '-')
                    }
                    return h('AutoTip', $A.bytesToSize(row.size));
                }
            },
            {
                title: this.$L('类型'),
                key: 'type',
                width: 110,
                resizable: true,
                sortable: true,
                render: (h, {row}) => {
                    let type = this.types.find(({value, name}) => value == row.type && name);
                    if (type) {
                        return h('AutoTip', this.$L(type.name));
                    } else {
                        return h('div', (row.ext || row.type).toUpperCase())
                    }
                }
            },
            {
                title: this.$L('所有者'),
                key: 'userid',
                width: 130,
                resizable: true,
                sortable: true,
                render: (h, {row}) => {
                    return h('UserAvatar', {
                        props: {
                            size: 18,
                            userid: row.userid,
                            showIcon: false,
                            showName: true,
                        }
                    });
                }
            },
            {
                title: this.$L('最后修改'),
                key: 'updated_at',
                width: 168,
                resizable: true,
                sortable: true,
            },
        ].map(item => {
            if (item.key === FileObject.sort.key) {
                item.sortType = FileObject.sort.order
            }
            return item;
        });
    },

    mounted() {
        this.uploadAccept = this.uploadFormat.map(item => {
            return '.' + item
        }).join(",");
    },

    activated() {
        this.getFileList();
    },

    computed: {
        ...mapState(['systemConfig', 'userIsAdmin', 'userInfo', 'fileLists', 'wsOpenNum', 'windowWidth', 'filePackLists']),

        pid() {
            const {folderId} = this.$route.params;
            return parseInt(/^\d+$/.test(folderId) ? folderId : 0);
        },

        fid() {
            const {fileId} = this.$route.params;
            return parseInt(/^\d+$/.test(fileId) ? fileId : 0);
        },

        actionUrl() {
            return $A.apiUrl('file/content/upload?pid=' + this.pid + '&cover=' + (this.uploadCover ? 1 : 0))
        },

        headers() {
            return {
                fd: $A.getSessionStorageString("userWsFd"),
                token: this.userToken,
            }
        },

        shareAlready() {
            let data = this.shareList ? this.shareList.map(({userid}) => userid) : [];
            if (this.shareInfo.userid) {
                data.push(this.shareInfo.userid);
            }
            return data
        },

        fileList() {
            const {fileLists, searchKey, hideShared, pid, selectIds, userId} = this;
            const list = $A.cloneJSON(sortBy(fileLists.filter(file => {
                if (hideShared && file.userid != userId && file.created_id != userId) {
                    return false
                }
                if (searchKey) {
                    return file.name.indexOf(searchKey) !== -1;
                }
                return file.pid == pid;
            }), file => {
                return (file.type == 'folder' ? 'a' : 'b') + file.name;
            }));
            return list.map(item => {
                item._checked = selectIds.includes(item.id)
                return item;
            })
        },

        hasShareFile() {
            const {fileLists, userId} = this;
            return fileLists.findIndex(file => file.share && file.userid != userId) !== -1
        },

        shearFirst() {
            const {fileLists, shearIds} = this;
            if (shearIds.length === 0) {
                return null;
            }
            return fileLists.find(item => item.id == shearIds[0])
        },

        navigator() {
            let {pid, fileLists} = this;
            let array = [];
            while (pid > 0) {
                let file = fileLists.find(({id, permission}) => id == pid && permission > -1);
                if (file) {
                    array.unshift(file);
                    pid = file.pid;
                } else {
                    pid = 0;
                }
            }
            return array;
        },

        isPreview() {
            return (this.windowPortrait && this.fileInfo.type!='document') || this.fileInfo.permission === 0
        },

        isParentShare() {
            const {navigator} = this;
            return !!navigator.find(({share}) => share);
        },

        pasteTitle() {
            const {pasteItem} = this;
            let hasImage = pasteItem.find(({type}) => type == 'image')
            let hasFile = pasteItem.find(({type}) => type != 'image')
            if (hasImage && hasFile) {
                return '上传文件/图片'
            } else if (hasImage) {
                return '上传图片'
            }
            return '上传文件'
        },

        pasteWrapperClass() {
            if (this.pasteItem.find(({type}) => type !== 'image')) {
                return ['multiple'];
            }
            return [];
        },

        tableHeight() {
            return this.windowHeight - 150
        },

        compressedSownloadDisabled() {
            return !!this.fileList?.find((res) => res._checked && res.permission < 1)
        },

        maxSize() {
            if(this.systemConfig?.file_upload_limit){
                return this.systemConfig.file_upload_limit * 1024
            }
            return 1024000
        },

        showBtnText(){
            return this.windowWidth > 600;
        }
    },

    watch: {
        pid() {
            this.searchKey = '';
            this.selectIds = [];
            this.getFileList();
        },

        fid() {
            this.openFileJudge();
        },

        tableMode(val) {
            $A.IDBSave("fileTableMode", val)
        },

        hideShared(val) {
            $A.IDBSave("fileHideShared", val)
        },

        fileShow(val) {
            if (!val) {
                this.browseFile(0)
                if (this.windowPortrait) {
                    $A.onBlur(true)
                }
            }
        },

        navigator: {
            handler() {
                this.$nextTick(_ => {
                    if (this.$refs[`nav_${this.pid}`]) {
                        $A.scrollToView(this.$refs[`nav_${this.pid}`][0], false)
                    }
                });
            },
            immediate: true
        },

        selectIds: {
            handler(ids) {
                if (ids.length > 0) {
                    this.shearIds = [];
                }
            },
            deep: true
        },

        shearIds: {
            handler(list) {
                if (list.length > 0) {
                    this.selectIds = [];
                }
            },
            deep: true
        },

        wsOpenNum(num) {
            if (num <= 1) return
            this.wsOpenTimeout && clearTimeout(this.wsOpenTimeout)
            this.wsOpenTimeout = setTimeout(() => {
                this.$route.name == 'manage-file' && this.getFileList();
            }, 5000)
        },


        filePackLists: {
            handler() {
                this.updatePackProgress()
            },
            deep: true
        },
    },

    methods: {
        getFileList() {
            if (this.$route.name !== 'manage-file') {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("getFiles", this.pid).then(async () => {
                this.loadIng--;
                this.openFileJudge()
                this.shakeFile(this.$route.params.shakeId);
                await $A.IDBSet("fileFolderId", this.pid)
            }).catch(({msg}) => {
                this.loadIng--;
                $A.modalError({
                    content: msg,
                    onOk: () => {
                        this.browseFolder(0);
                    }
                });
            });
        },

        addFile(command) {
            if (!command) {
                return;
            } else if (command == 'upload') {
                this.uploadDir = false
                this.$refs.fileUpload.handleClick();
                return;
            } else if (command == 'updir') {
                this.uploadDir = true
                this.$refs.dirUpload.handleClick();
                return;
            }
            let id = $A.randomString(8);
            this.fileLists.push({
                _edit: true,
                pid: this.pid,
                id: id,
                type: command,
                name: '',
                userid: this.userId,
                newname: this.$L('未命名')
            });
            this.autoBlur(id)
        },

        handleLongpress(event, el) {
            const fileId = $A.getAttr(el, 'data-id')
            const fileItem = this.fileList.find(item => item.id == fileId)
            if (!fileItem) {
                return
            }
            this.handleRightClick(event, fileItem)
        },

        handleContextmenu(event) {
            if (this.windowLandscape) {
                this.handleRightClick(event)
            }
        },

        handleRightClick(event, item, isAddButton) {
            this.contextMenuItem = $A.isJson(item) ? item : {};
            if (this.contextMenuVisible) {
                this.handleClickContextMenuOutside();
            }
            this.$nextTick(() => {
                const fileWrap = this.$refs.fileWrapper;
                const fileBounding = fileWrap.getBoundingClientRect();
                this.contextMenuStyles = {
                    left: `${event.clientX - fileBounding.left}px`,
                    top: `${event.clientY - fileBounding.top}px`
                };
                if (isAddButton === true) {
                    this.contextMenuStyles.top = `${event.target.clientHeight + event.target.offsetTop - 5}px`
                }
                this.contextMenuVisible = true;
            })
        },

        browseFolder(id, shakeId = null) {
            if (id > 0) {
                if (this.pid == id && this.fid == 0 && shakeId) {
                    this.shakeFile(shakeId);
                    return;
                }
                this.goForward({name: 'manage-file', params: {folderId: id, fileId: null, shakeId}});
            } else {
                this.searchKey = '';
                this.goForward({name: 'manage-file'});
            }
        },

        browseFile(id) {
            if (id > 0) {
                this.goForward({name: 'manage-file', params: {folderId: this.pid, fileId: id}});
            } else {
                this.browseFolder(this.pid);
            }
        },

        openFileJudge() {
            if (this.$route.name !== 'manage-file') {
                this.fileShow = false;
                return;
            }
            if (this.fid <= 0) {
                this.fileShow = false;
                return;
            }
            const item = this.fileList.find(({id}) => id === this.fid)
            if (!item) {
                this.fileShow = false;
                return;
            }
            // 客户端打开独立窗口
            if (this.$Electron || this.$isEEUiApp) {
                this.openFileSingle(item);
                return;
            }
            // 正常显示弹窗
            this.fileInfo = item;
            this.fileShow = true;
        },

        openFileSingle(item) {
            const path = `/single/file/${item.id}`;
            if (this.$Electron) {
                this.$store.dispatch('openChildWindow', {
                    name: `file-${item.id}`,
                    path: path,
                    userAgent: "/hideenOfficeTitle/",
                    force: false, // 如果窗口已存在不重新加载
                    config: {
                        title: $A.getFileName(item),
                        titleFixed: true,
                        parent: null,
                        width: Math.min(window.screen.availWidth, 1440),
                        height: Math.min(window.screen.availHeight, 900),
                    },
                    webPreferences: {
                        nodeIntegrationInSubFrames: item.type === 'drawio'
                    },
                });
            } else if (this.$isEEUiApp) {
                this.$store.dispatch('openAppChildPage', {
                    pageType: 'app',
                    pageTitle: $A.getFileName(item),
                    url: 'web.js',
                    params: {
                        titleFixed: true,
                        allowAccess: true,
                        url: $A.rightDelete(window.location.href, window.location.hash) + `#${path}`
                    },
                });
            } else {
                window.open($A.mainUrl(path.substring(1)))
            }
            this.browseFile(0);
        },

        clickRow(row, column) {
            if (this.contextMenuVisible) {
                this.handleClickContextMenuOutside();
                return;
            }
            if (column.type == "selection") {
                this.dropFile(row, 'select');
            } else {
                this.dropFile(row, 'open');
            }
        },

        handleContextMenu(row, event) {
            this.handleRightClick(event, this.fileLists.find(({id}) => id === row.id) || {});
        },

        handleContextClick(command) {
            if ($A.leftExists(command, "new:")) {
                this.addFile($A.leftDelete(command, "new:"))
            } else {
                this.dropFile(this.contextMenuItem, command)
            }
        },

        handleClickContextMenuOutside() {
            this.contextMenuVisible = false;
        },

        handleVisibleChangeMenu(visible) {
            let file = this.fileLists.find(({_highlight}) => !!_highlight)
            if (file) {
                this.$set(file, '_highlight', false);
            }
            if (visible && this.contextMenuItem.id) {
                this.$set(this.contextMenuItem, '_highlight', true);
            }
        },

        dropFile(item, command) {
            switch (command) {
                case 'open':
                case 'openCheckMenu':
                    if (command === 'openCheckMenu' && this.contextMenuVisible) {
                        return;
                    }
                    if (this.fileList.findIndex((file) => file._edit === true) > -1) {
                        return;
                    }
                    if (item._load) {
                        return;
                    }
                    if (item.type == 'folder') {
                        this.browseFolder(item.id)
                        return;
                    }
                    if (item.image_url) {
                        // 图片直接浏览
                        const list = this.fileList.filter(({image_url}) => !!image_url)
                        if (list.length > 0) {
                            const index = list.findIndex(({id}) => item.id === id)
                            const array = list.map(item => {
                                if (item.image_width) {
                                    return {
                                        src: item.image_url,
                                        width: item.image_width,
                                        height: item.image_height,
                                    }
                                }
                                return item.image_url;
                            })
                            this.$store.dispatch("previewImage", {index, list: array})
                            return;
                        }
                    }
                    this.browseFile(item.id)
                    break;

                case 'upperFolder':
                    this.searchKey = '';
                    this.browseFolder(item.pid, item.id)
                    break;

                case 'select':
                    let index = this.selectIds.findIndex(id => id == item.id)
                    if (index > -1) {
                        this.selectIds.splice(index, 1)
                    } else {
                        this.selectIds.push(item.id)
                    }
                    break;

                case 'rename':
                    this.setEdit(item.id, true)
                    this.autoBlur(item.id)
                    break;

                case 'copy':
                    this.$store.dispatch("call", {
                        url: 'file/copy',
                        data: {
                            id: item.id,
                        },
                    }).then(({data, msg}) => {
                        $A.messageSuccess(msg);
                        this.$store.dispatch("saveFile", data);
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    });
                    break;

                case 'shear':
                    this.shearIds = [item.id];
                    break;

                case 'shearSelect':
                    this.shearIds = $A.cloneJSON(this.selectIds);
                    break;

                case 'send':
                    this.sendFileId = item.id;
                    this.sendData = [];
                    this.$refs.sendFile.onSelection()
                    break;

                case 'share':
                    this.shareInfo = {
                        id: item.id,
                        type: 'all',
                        userid: item.userid,
                        permission: 1,
                    };
                    this.shareList = [];
                    this.shareShow = true;
                    this.getShare();
                    break;

                case 'outshare':
                    $A.modalConfirm({
                        content: '你确定要退出【' + item.name + '】共享成员吗？',
                        loading: true,
                        onOk: () => {
                            return new Promise((resolve, reject) => {
                                this.$store.dispatch("call", {
                                    url: 'file/share/out',
                                    data: {
                                        id: item.id,
                                    },
                                }).then(({msg}) => {
                                    resolve(msg);
                                    this.$store.dispatch("forgetFile", item.id);
                                }).catch(({msg}) => {
                                    reject(msg);
                                });
                            })
                        }
                    });
                    break;

                case 'link':
                    this.linkData = {
                        id: item.id,
                        name: item.name
                    };
                    this.linkShow = true;
                    this.linkGet()
                    break;

                case 'download':
                    if (!item.ext) {
                        return;
                    }
                    $A.modalConfirm({
                        title: '下载文件',
                        content: `${item.name}.${item.ext} (${$A.bytesToSize(item.size)})`,
                        okText: '立即下载',
                        onOk: () => {
                            this.$store.dispatch('downUrl', $A.apiUrl(`file/content?id=${item.id}&down=yes`))
                        }
                    });
                    break;

                case 'downloadzip':
                    this.downloadZipFile([item.id])
                    break;

                case 'delete':
                    this.deleteFile([item.id])
                    break;
            }
        },

        onSendFile() {
            return new Promise((resolve, reject) => {
                if (this.sendData.length === 0) {
                    $A.messageError("请选择转发对话或成员");
                    reject();
                    return
                }
                const dialogids = this.sendData.filter(value => $A.leftExists(value, 'd:')).map(value => value.replace('d:', ''));
                const userids = this.sendData.filter(value => !$A.leftExists(value, 'd:'));
                this.$store.dispatch("call", {
                    url: 'dialog/msg/sendfileid',
                    data: {
                        dialogids,
                        userids,
                        file_id: this.sendFileId
                    }
                }).then(({data, msg}) => {
                    this.$store.dispatch("saveDialogMsg", data.msgs);
                    this.$store.dispatch("updateDialogLastMsg", data.msgs);
                    $A.messageSuccess(msg);
                    resolve();
                }).catch(({msg}) => {
                    $A.modalError(msg);
                    reject();
                });
            })
        },

        linkGet(refresh) {
            this.linkLoad++;
            this.$store.dispatch("call", {
                url: 'file/link',
                data: {
                    id: this.linkData.id,
                    refresh: refresh === true ? 'yes' : 'no'
                },
            }).then(({data}) => {
                this.linkData = Object.assign(data, {
                    id: this.linkData.id,
                    name: this.linkData.name
                });
                this.linkCopy();
            }).catch(({msg}) => {
                this.linkShow = false
                $A.modalError(msg);
            }).finally(_ => {
                this.linkLoad--;
            });
        },

        linkCopy() {
            if (!this.linkData.url) {
                return;
            }
            this.linkFocus();
            this.copyText(this.linkData.url);
        },

        linkFocus() {
            this.$nextTick(_ => {
                this.$refs.linkInput.focus({cursor:'all'});
            });
        },

        shearTo() {
            if (this.shearIds.length == 0) {
                return;
            }
            if (this.isParentShare) {
                const tmpFile = this.fileLists.find(({id, share}) => share && this.shearIds.includes(id));
                if (tmpFile) {
                    $A.modalError(`${tmpFile.name} 当前正在共享，无法移动到另一个共享文件夹内`)
                    return;
                }
            }
            this.$store.dispatch("call", {
                url: 'file/move',
                data: {
                    ids: this.shearIds,
                    pid: this.pid,
                },
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.shearIds = [];
                this.$store.dispatch("saveFile", data);
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        deleteFile(ids) {
            if (ids.length === 0) {
                return
            }
            const firstFile = this.fileLists.find(item => item.id == ids[0]) || {};
            const allFolder = !ids.find(id => {
                return this.fileLists.find(item => item.type != 'folder' && item.id == id)
            });
            let typeName = allFolder ? "文件夹" : "文件"
            let fileName = `【${firstFile.name}】等${ids.length}个${typeName}`
            if (ids.length === 1) {
                fileName = `【${firstFile.name}】${typeName}`
            }
            $A.modalConfirm({
                title: '删除' + typeName,
                content: '你确定要删除' + fileName + '吗？',
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'file/remove',
                            data: {
                                ids,
                            },
                        }).then(({msg}) => {
                            resolve(msg);
                            this.$store.dispatch("forgetFile", ids);
                            this.selectIds = this.selectIds.filter(id => !ids.includes(id))
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                }
            });
        },

        /********************文件打包下载部分************************/

        packPercentageParse(val) {
            return parseInt(val, 10);
        },

        packClear() {
            this.packList = this.packList.filter(item => item.status !== 'finished');
            this.packShow = false;
        },

        async startPack(data) {
            this.packList.push(Object.assign(data, {
                status: 'packing',
                percentage: 0
            }));
            this.uploadShow = false; // 隐藏上传列表
            this.packShow = true; // 显示打包列表
        },

        updatePackProgress() {
            this.packList.forEach(file => {
                const pack = this.filePackLists.find(({name}) => name == file.name)
                if (pack) {
                    if (typeof file.percentage === "number" && file.percentage >= 100) {
                        return
                    }
                    file.percentage = Math.max(1, pack.progress);
                    if (file.percentage >= 100) {
                        file.status = 'finished';
                    }
                }
            })
        },

        downloadZipFile(ids){
            if (ids.length === 0) {
                return
            }
            const firstFile = this.fileLists.find(({ id }) => id === ids[0]) || {};
            const allFolder = !ids.some(id => this.fileLists.some(({ type, id: itemId }) => type !== 'folder' && itemId === id));
            const typeName = allFolder ? "文件夹" : "文件";
            const fileName = ids.length === 1 ? `【${firstFile.name}】${typeName}` : `【${firstFile.name}】等${ids.length}个${typeName}`;

            $A.modalConfirm({
                title: '打包下载',
                content: `你确定要打包下载${fileName}吗？`,
                okText: '确定',
                onOk: () => {
                    if (this.packList.find(({status}) => status === 'packing')) {
                        $A.messageWarning("请等待打包完成");
                        return;
                    }
                    const name = this.$L(`打包下载${fileName}`)
                    this.$store.dispatch("call", {
                        url: 'file/download/pack',
                        data: {ids, name},
                    }).then(({data}) => {
                        this.startPack(data);
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    });
                }
            });
        },

        autoBlur(id) {
            this.$nextTick(() => {
                if (this.$refs['input_' + id]) {
                    this.$refs['input_' + id][0].focus({
                        cursor: 'all'
                    })
                } else if (document.getElementById('input_' + id)) {
                    const el = document.getElementById('input_' + id);
                    const len = el.value.length;
                    el.focus();
                    el.setSelectionRange(0, len);
                }
            })
        },

        onParser(val) {
            return val.replace(/[\\\/:*?\"<>|]/g, '')
        },

        onBlur(item) {
            if (this.fileLists.find(({id, _edit}) => id == item.id && !_edit)) {
                return;
            }
            this.onEnter(item);
        },

        onKeyup(e, item) {
            if (e.keyCode === 13) {
                this.onEnter(item);
            } else if (e.keyCode === 27) {
                const isCreate = !/^\d+$/.test(item.id);
                if (isCreate) {
                    item.newname = ''
                    this.$store.dispatch("forgetFile", item.id);
                } else {
                    this.setLoad(item.id, false)
                    this.setEdit(item.id, false)
                }
            }
        },

        onEnter(item) {
            const isCreate = !/^\d+$/.test(item.id);
            if (!item.newname) {
                if (isCreate) {
                    this.$store.dispatch("forgetFile", item.id);
                } else {
                    this.setEdit(item.id, false)
                }
                return;
            }
            if (item.newname == item.name) {
                this.setEdit(item.id, false)
                return;
            }
            if (item._load) {
                return;
            }
            this.setLoad(item.id, true)
            this.$store.dispatch("call", {
                url: 'file/add',
                data: {
                    id: isCreate ? 0 : item.id,
                    pid: item.pid,
                    name: item.newname,
                    type: item.type,
                },
                spinner: 2000
            }).then(({data, msg}) => {
                $A.messageSuccess(msg)
                this.setLoad(item.id, false)
                this.setEdit(item.id, false)
                this.$store.dispatch("saveFile", data);
                if (isCreate) {
                    this.$store.dispatch("forgetFile", item.id);
                    this.shakeFile(data.id);
                }
            }).catch(({msg}) => {
                $A.modalError(msg)
                this.setLoad(item.id, false)
                if (isCreate) {
                    this.$store.dispatch("forgetFile", item.id);
                }
            })
        },

        setEdit(fileId, is) {
            const item = this.$store.state.fileLists.find(({id}) => id == fileId)
            if (item) {
                this.$set(item, '_edit', is);
                if (is) {
                    this.$set(item, 'newname', item.name);
                }
            }
        },

        setLoad(fileId, is) {
            const item = this.$store.state.fileLists.find(({id}) => id == fileId)
            if (item) {
                this.$set(item, '_load', is);
            }
        },

        onSearchFocus() {
            this.$nextTick(() => {
                this.$refs.searchInput.focus({
                    cursor: "end"
                });
            })
        },

        onSearchChange() {
            this.searchTimeout && clearTimeout(this.searchTimeout);
            if (this.searchKey.trim() != '') {
                this.searchTimeout = setTimeout(() => {
                    this.loadIng++;
                    this.$store.dispatch("searchFiles", this.searchKey.trim()).then(() => {
                        this.loadIng--;
                    }).catch(() => {
                        this.loadIng--;
                    });
                }, 600)
            }
        },

        getShare() {
            this.shareLoad++;
            this.$store.dispatch("call", {
                url: 'file/share',
                data: {
                    id: this.shareInfo.id
                },
            }).then(({data}) => {
                if (data.id == this.shareInfo.id) {
                    this.shareList = data.list.map(item => {
                        item._permission = item.permission;
                        return item;
                    });
                }
            }).catch(({msg}) => {
                this.shareShow = false;
                $A.modalError(msg)
            }).finally(_ => {
                this.shareLoad--;
            })
        },

        onShare(force = false) {
            if (this.shareInfo.type === 'all') {
                this.shareInfo.userids = [0];
            }
            if (this.shareInfo.userids.length == 0) {
                $A.messageWarning("请选择共享成员")
                return;
            }
            this.shareLoad++;
            this.$store.dispatch("call", {
                url: 'file/share/update',
                data: Object.assign(this.shareInfo, {
                    force: force === true ? 1 : 0
                }),
            }).then(({data, msg}) => {
                $A.messageSuccess(msg)
                this.$store.dispatch("saveFile", data);
                this.$set(this.shareInfo, 'userids', []);
                this.getShare();
            }).catch(({ret, msg}) => {
                if (ret === -3001) {
                    $A.modalConfirm({
                        content: '此文件夹内已有共享文件夹，子文件的共享状态将被取消，是否继续？',
                        onOk: () => {
                            this.onShare(true)
                        }
                    })
                } else {
                    $A.modalError(msg)
                }
            }).finally(_ => {
                this.shareLoad--;
            })
        },

        upShare(item, force = false) {
            if (item.loading === true) {
                return;
            }
            item.loading = true;
            //
            this.$store.dispatch("call", {
                url: 'file/share/update',
                data: {
                    id: this.shareInfo.id,
                    userids: [item.userid],
                    permission: item.permission,
                    force: force === true ? 1 : 0
                },
            }).then(({data, msg}) => {
                item.loading = false;
                item._permission = item.permission;
                $A.messageSuccess(msg);
                this.$store.dispatch("saveFile", data);
                if (item.permission === -1) {
                    let index = this.shareList.findIndex(({userid}) => userid == item.userid);
                    if (index > -1) {
                        this.shareList.splice(index, 1)
                    }
                }
            }).catch(({ret, msg}) => {
                item.loading = false;
                if (ret === -3001) {
                    $A.modalConfirm({
                        content: '此文件夹内已有共享文件夹，子文件的共享状态将被取消，是否继续？',
                        onOk: () => {
                            this.upShare(item, true)
                        },
                        onCancel: () => {
                            item.permission = item._permission;
                        }
                    })
                } else {
                    item.permission = item._permission;
                    $A.modalError(msg)
                }
            })
        },

        uploadData(item) {
            const data = $A.getObject(item, 'response.data')
            if ($A.isArray(data)) {
                return data[0]
            } else if ($A.isJson(data)) {
                return data
            }
        },

        uploadName(item) {
            const data = this.uploadData(item)
            if (!data) {
                return item.name
            }
            const fullName = data.full_name || item.name
            return data.overwrite ? `<em class="overwrite">[${this.$L('替换')}]</em> ${fullName}` : fullName
        },

        uploadClick(item) {
            const data = this.uploadData(item)
            if (!data) {
                return
            }
            this.browseFolder(data.pid, data.id)
        },

        handleTableSort({key, order}) {
            $A.IDBSave("cacheFileSort", ['asc', 'desc'].includes(order) ? {key, order} : {});
        },

        handleTableSelect(selection) {
            this.selectIds = selection.map(item => item.id);
        },

        clearSelect() {
            this.selectIds = [];
        },

        clearShear() {
            this.shearIds = [];
        },

        shakeFile(fileId) {
            if (!fileId) {
                return
            }
            this.$nextTick(_ => {
                const dom = $A(this.$el).find(`[data-id="${fileId}"]`)
                if (dom.length > 0) {
                    $A.scrollIntoViewIfNeeded(dom[0])
                    $A(dom[0]).addClass("common-shake")
                    setTimeout(_ => $A(dom[0]).removeClass("common-shake"), 800)
                }
            })
        },

        /********************拖动上传部分************************/

        pasteDragNext(e, type) {
            let files = type === 'drag' ? e.dataTransfer.files : e.clipboardData.files;
            files = Array.prototype.slice.call(files);
            if (files.length > 0) {
                e.preventDefault();
                //
                this.pasteFile = [];
                this.pasteItem = [];
                files.some(file => {
                    const item = {
                        type: $A.getMiddle(file.type, null, '/'),
                        name: file.name,
                        size: file.size,
                        result: null
                    }
                    if (item.type === 'image') {
                        const reader = new FileReader();
                        reader.readAsDataURL(file);
                        reader.onload = ({target}) => {
                            item.result = target.result
                            this.pasteFile.push(file)
                            this.pasteItem.push(item)
                            this.pasteShow = true
                        }
                    } else {
                        this.pasteFile.push(file)
                        this.pasteItem.push(item)
                        this.pasteShow = true
                    }
                });
            }
        },

        filePasteDrag(e, type) {
            this.dialogDrag = false;
            if ($A.dataHasFolder(type === 'drag' ? e.dataTransfer : e.clipboardData)) {
                e.preventDefault();
                $A.modalWarning(`暂不支持${type === 'drag' ? '拖拽' : '粘贴'}文件夹，请手动上传文件夹。`)
                return;
            }
            this.pasteDragNext(e, type);
        },

        fileDragOver(show, e) {
            let random = (this.__dialogDrag = $A.randomString(8));
            if (!show) {
                setTimeout(() => {
                    if (random === this.__dialogDrag) {
                        this.dialogDrag = show;
                    }
                }, 150);
            } else {
                if (e.dataTransfer.effectAllowed === 'move') {
                    return;
                }
                this.dialogDrag = true;
            }
        },

        pasteSend() {
            if (this.__paste_send_index) {
                return;
            }
            this.__paste_send_index = 1;
            setTimeout(() => {
                this.__paste_send_index = 0;
            }, 300)
            const names = []
            this.pasteFile.some(file => {
                if (!names.find(name => name === file.name)) {
                    names.push(file.name)
                    this.$refs.fileUpload.upload(file)
                }
            });
        },

        fileBeforeClose() {
            return new Promise(resolve => {
                if (!this.$refs.fileContent) {
                    resolve();
                    return;
                }
                if (this.$refs.fileContent.equalContent) {
                    resolve()
                    return
                }
                $A.modalConfirm({
                    content: '修改的内容尚未保存，确定要放弃修改吗？',
                    cancelText: '取消',
                    okText: '放弃',
                    onOk: () => {
                        resolve()
                    }
                });
            })
        },

        /********************文件上传部分************************/

        uploadUpdate(fileList) {
            fileList.forEach(file => {
                let index = this.uploadList.findIndex(({uid}) => uid == file.uid)
                if (index > -1) {
                    this.uploadList.splice(index, 1, file)
                } else {
                    this.uploadList.unshift(file)
                }
            })
        },

        uploadClear() {
            this.uploadList = this.uploadList.filter(({status}) => status !== 'finished')
            this.$refs.fileUpload.clearFiles();
            this.$refs.dirUpload.clearFiles();
        },

        uploadPercentageParse(val) {
            return parseInt(val, 10);
        },

        handleProgress(event, file, fileList) {
            //开始上传
            if (file._uploadIng === undefined) {
                file._uploadIng = true;
                this.uploadIng++;
            }
            this.uploadUpdate(fileList);
        },

        handleSuccess(res, file, fileList) {
            //上传完成
            this.uploadIng--;
            this.uploadUpdate(fileList);
            if (res.ret === 1) {
                this.$store.dispatch("saveFile", res.data);
            } else {
                $A.modalWarning({
                    title: '上传失败',
                    content: '文件 ' + file.name + ' 上传失败，' + res.msg
                });
            }
        },

        handleError(error, file, fileList) {
            //上传错误
            this.uploadIng--;
            this.uploadUpdate(fileList);
        },

        handleFormatError(file) {
            //上传类型错误
            if (this.uploadDir) {
                return;
            }
            $A.modalWarning({
                title: '文件格式不正确',
                content: '文件 ' + file.name + ' 格式不正确，仅支持上传：' + this.uploadFormat.join(',')
            });
        },

        handleMaxSize(file) {
            //上传大小错误
            $A.modalWarning({
                title: '超出文件大小限制',
                content: '文件 ' + file.name + ' 太大，不能超过：' + $A.bytesToSize(this.maxSize * 1024) + '。'
            });
        },

        handleBeforeUpload(file) {
            //上传前判断
            this.uploadCover = false
            if (this.uploadDir) {
                this.handleUploadNext();
                return true;
            }
            return new Promise(resolve => {
                if (this.fileList.findIndex(item => $A.getFileName(item) === file.name) > -1) {
                    $A.modalConfirm({
                        wait: true,
                        title: '文件已存在',
                        content: '文件 ' + file.name + ' 已存在，是否替换？',
                        cancelText: '保留两者',
                        okText: '替换',
                        closable: true,
                        onOk: () => {
                            this.uploadCover = true
                            this.handleUploadNext();
                            resolve();
                        },
                        onCancel: (isButton) => {
                            if (isButton) {
                                this.handleUploadNext();
                                resolve();
                            }
                        }
                    });
                } else {
                    this.handleUploadNext();
                    resolve();
                }
            })
        },

        handleUploadNext() {
            this.uploadShow = true;
            this.packShow = false;
        }
    }
}
</script>
