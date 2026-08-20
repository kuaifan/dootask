<template>
    <div class="collaboration-files">
        <div class="collaboration-toolbar">
            <div class="scope-segment">
                <button :class="{active: scope === 'all'}" @click="setScope('all')">{{$L('全部')}}</button>
                <button :class="{active: scope === 'conversation'}" @click="setScope('conversation')">{{$L('会话')}}</button>
                <button :class="{active: scope === 'project'}" @click="setScope('project')">{{$L('项目')}}</button>
            </div>

            <div v-if="scope === 'conversation'" class="sub-segment">
                <button :class="{active: conversationType === 'all'}" @click="setConversationType('all')">{{$L('全部会话')}}</button>
                <button :class="{active: conversationType === 'private'}" @click="setConversationType('private')">{{$L('私聊')}}</button>
                <button :class="{active: conversationType === 'group'}" @click="setConversationType('group')">{{$L('群聊')}}</button>
            </div>

            <template v-if="scope === 'project'">
                <Select
                    v-model="projectId"
                    class="project-select"
                    :placeholder="$L('选择项目')"
                    filterable
                    @on-change="reload"
                    style="max-width:auto;">
                    <Option :value="0">{{$L('全部项目')}}</Option>
                    <Option v-for="project in projects" :key="project.id" :value="project.id">{{project.name}}</Option>
                </Select>
                <div class="sub-segment">
                    <button :class="{active: projectSource === 'all'}" @click="setProjectSource('all')">{{$L('全部')}}</button>
                    <button :class="{active: projectSource === 'project_chat'}" @click="setProjectSource('project_chat')">{{$L('项目群聊')}}</button>
                    <button :class="{active: projectSource === 'task'}" @click="setProjectSource('task')">{{$L('任务')}}</button>
                </div>
            </template>

            <div class="toolbar-full"></div>
            <Select v-model="fileType" class="type-select" @on-change="reload">
                <Option value="all">{{$L('所有类型')}}</Option>
                <Option value="document">{{$L('文档')}}</Option>
                <Option value="sheet">{{$L('表格')}}</Option>
                <Option value="slide">{{$L('演示文稿')}}</Option>
                <Option value="image">{{$L('图片')}}</Option>
                <Option value="video">{{$L('视频')}}</Option>
                <Option value="archive">{{$L('压缩包')}}</Option>
                <Option value="other">{{$L('其他')}}</Option>
            </Select>
            <Select v-model="senderScope" class="sender-select" @on-change="reload">
                <Option value="all">{{$L('所有成员')}}</Option>
                <Option value="mine">{{$L('我发送的')}}</Option>
            </Select>
            <div :class="['view-switch', {table: viewMode === 'list'}]">
                <div :title="$L('宫格')" @click="viewMode='grid'"><i class="taskfont">&#xe60c;</i></div>
                <div :title="$L('列表')" @click="viewMode='list'"><i class="taskfont">&#xe66a;</i></div>
            </div>
        </div>

        <div class="collaboration-summary">
            <div class="summary-icon"><Icon :type="summaryIcon"/></div>
            <div class="summary-text">
                <strong>{{summaryTitle}}</strong>
                <span>{{summarySubtitle}}</span>
            </div>
            <div v-if="items.length" class="loaded-count">{{$L('已加载(*)个文件', items.length)}}</div>
        </div>

        <div ref="scroller" class="collaboration-scroll" @scroll="onScroll">
            <div v-if="initializing || (loading && items.length === 0)" class="initial-loading"><Loading/></div>
            <template v-else-if="items.length">
                <div v-if="viewMode === 'list'" class="collaboration-table">
                    <div class="table-head">
                        <span>{{$L('文件名')}}</span>
                        <span>{{$L('来源')}}</span>
                        <span>{{$L('发送人')}}</span>
                        <span>{{$L('时间')}}</span>
                        <span>{{$L('大小')}}</span>
                        <span>{{$L('操作')}}</span>
                    </div>
                    <div v-for="item in items" :key="itemKey(item)" class="table-row">
                        <div class="file-main" @click="preview(item)">
                            <div class="collaboration-file-preview">
                                <img
                                    v-if="showThumbnail(item)"
                                    class="collaboration-thumbnail"
                                    :src="item.image_url"
                                    alt=""
                                    @error.stop="handleThumbnailError(item)"/>
                                <div v-else :class="['no-dark-content', 'collaboration-file-icon', fileIconType(item)]"></div>
                            </div>
                            <div class="file-text">
                                <AutoTip class="file-title">{{fileName(item)}}</AutoTip>
                                <span>{{fileTypeText(item)}}</span>
                            </div>
                        </div>
                        <div class="source-main" @click="openSource(item)">
                            <div class="source-text">
                                <span :class="['source-type', item.source_type]">{{sourceTypeText(item)}}</span>
                                <span class="source-name" :title="sourcePath(item)">{{item.source_name}}</span>
                            </div>
                        </div>
                        <UserAvatar :userid="item.sender.userid" :size="24" showName/>
                        <span class="time-text">{{formatTime(item.created_at)}}</span>
                        <span class="size-text">{{$A.bytesToSize(item.size)}}</span>
                        <div class="row-actions">
                            <ETooltip :content="$L('打开来源')"><button @click="locateMessage(item)"><CollaborationSourceIcon/></button></ETooltip>
                            <ETooltip :content="localActionTitle(item)">
                                <button
                                    :disabled="localFileStatus(item) === 'downloading'"
                                    @click="handleLocalAction(item)">
                                    <LocalFileStatusIcon :status="localFileStatus(item)"/>
                                </button>
                            </ETooltip>
                        </div>
                    </div>
                </div>

                <div v-else class="collaboration-grid">
                    <div v-for="item in items" :key="itemKey(item)" class="grid-item" @click="preview(item)">
                        <div class="grid-preview">
                            <img
                                v-if="showThumbnail(item)"
                                class="collaboration-thumbnail"
                                :src="item.image_url"
                                alt=""
                                @error.stop="handleThumbnailError(item)"/>
                            <div v-else :class="['no-dark-content', 'collaboration-file-icon', fileIconType(item)]"></div>
                        </div>
                        <AutoTip class="grid-title">{{fileName(item)}}</AutoTip>
                        <div class="grid-source">
                            <span :class="['source-type', item.source_type]">{{sourceTypeText(item)}}</span>
                            <AutoTip class="grid-source-name" :content="sourcePath(item)">{{item.source_name}}</AutoTip>
                        </div>
                        <div class="grid-meta">{{item.sender.nickname || $L('未知成员')}} · {{formatTime(item.created_at)}}</div>
                        <div class="grid-actions" @click.stop>
                            <button :title="$L('打开来源')" @click="locateMessage(item)"><CollaborationSourceIcon/></button>
                            <button
                                :title="localActionTitle(item)"
                                :disabled="localFileStatus(item) === 'downloading'"
                                @click="handleLocalAction(item)">
                                <LocalFileStatusIcon :status="localFileStatus(item)"/>
                            </button>
                        </div>
                    </div>
                </div>
                <div v-if="loading" class="load-more"><Loading/></div>
                <div v-else-if="!hasMore" class="list-end">{{$L('没有更多文件了')}}</div>
            </template>
            <div v-else class="empty-state">
                <Icon type="ios-folder-open-outline"/>
                <p>{{$L('没有找到相关文件')}}</p>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import {openFileInClient} from "../../../utils/file";
import CollaborationSourceIcon from "./CollaborationSourceIcon.vue";
import LocalFileStatusIcon from "./DialogView/LocalFileStatusIcon.vue";

const CACHE_VERSION = 1;

export default {
    name: "CollaborationFileList",
    components: {CollaborationSourceIcon, LocalFileStatusIcon},
    props: {
        searchKey: {
            type: String,
            default: '',
        },
    },
    data() {
        return {
            scope: 'all',
            conversationType: 'all',
            projectId: 0,
            projectSource: 'all',
            fileType: 'all',
            senderScope: 'all',
            viewMode: this.$store.state.collaborationFileViewMode === 'grid' ? 'grid' : 'list',
            items: [],
            cursor: 0,
            hasMore: false,
            loading: 0,
            initializing: true,
            searchTimer: null,
            requestId: 0,
            localFileStatuses: {},
            removeDownloadListener: null,
        }
    },
    computed: {
        ...mapState(['cacheProjects', 'collaborationFileCache', 'userId']),
        projects() {
            return this.cacheProjects
                .filter(project => !project.deleted_at && !project.archived_at)
                .slice()
                .sort((a, b) => a.name.localeCompare(b.name));
        },
        selectedProject() {
            return this.projects.find(project => project.id == this.projectId);
        },
        summaryIcon() {
            if (this.scope === 'project') return 'ios-briefcase-outline';
            if (this.scope === 'conversation') return 'ios-chatbubbles-outline';
            return 'ios-git-merge';
        },
        summaryTitle() {
            if (this.scope === 'project') {
                const name = this.selectedProject?.name || this.$L('全部项目');
                if (this.projectSource === 'task') return this.$L('(*) · 任务文件', name);
                if (this.projectSource === 'project_chat') return this.$L('(*) · 项目群聊文件', name);
                return this.$L('(*) · 全部文件', name);
            }
            if (this.scope === 'conversation') {
                if (this.conversationType === 'private') return this.$L('个人聊天文件');
                if (this.conversationType === 'group') return this.$L('普通群聊文件');
                return this.$L('全部会话文件');
            }
            return this.$L('全部协作文件');
        },
        summarySubtitle() {
            if (this.scope === 'project') return this.$L('汇总项目群聊和项目下任务中的文件');
            if (this.scope === 'conversation') return this.$L('来自私聊和普通群聊的文件');
            return this.$L('包含会话、项目群聊和任务中的文件');
        },
    },
    watch: {
        searchKey() {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => this.reload(), 400);
        },
        viewMode(value) {
            this.$store.commit('collaboration/file/view/save', value);
        },
    },
    created() {
        if (this.restoreCache(this.collaborationFileCache)) {
            this.initializing = false;
        }
    },
    mounted() {
        this.$store.dispatch('getProjects').catch(() => {});
        if (this.$Electron) {
            this.removeDownloadListener = $A.Electron.listener('downloadItemsChanged', () => {
                this.refreshLocalFileStatuses();
            });
            this.refreshLocalFileStatuses();
        }
        this.refresh();
        this.initializing = false;
    },
    beforeDestroy() {
        clearTimeout(this.searchTimer);
        if (typeof this.removeDownloadListener === 'function') {
            this.removeDownloadListener();
        }
    },
    methods: {
        setScope(scope) {
            if (this.scope === scope) return;
            this.scope = scope;
            this.reload();
        },
        setConversationType(type) {
            if (this.conversationType === type) return;
            this.conversationType = type;
            this.reload();
        },
        setProjectSource(source) {
            if (this.projectSource === source) return;
            this.projectSource = source;
            this.reload();
        },
        reload() {
            this.loadFirstPage(false);
        },
        refresh() {
            this.loadFirstPage(true);
        },
        loadFirstPage(keepItems) {
            this.requestId++;
            this.loading = 0;
            if (!keepItems) {
                this.items = [];
                this.localFileStatuses = {};
            }
            this.cursor = 0;
            this.hasMore = false;
            this.load(true);
        },
        load(replace = false) {
            if (this.loading || (this.cursor && !this.hasMore)) return;
            const requestId = ++this.requestId;
            const requestCursor = replace ? 0 : this.cursor;
            this.loading++;
            this.$store.dispatch('call', {
                url: 'file/collaboration/lists',
                data: {
                    scope: this.scope,
                    conversation_type: this.conversationType,
                    project_id: this.projectId,
                    project_source: this.projectSource,
                    file_type: this.fileType,
                    sender_id: this.senderScope === 'mine' ? this.userId : 0,
                    key: this.searchKey.trim(),
                    cursor: requestCursor,
                    take: 50,
                },
            }).then(({data}) => {
                if (requestId !== this.requestId) return;
                if (replace) {
                    this.items = data.list;
                } else {
                    this.items.push(...data.list);
                }
                this.refreshLocalFileStatuses(data.list);
                this.cursor = data.next_cursor;
                this.hasMore = data.has_more;
                if (replace && !this.searchKey.trim()) {
                    this.saveCache(data);
                }
            }).catch(({msg}) => {
                if (msg) $A.modalError(msg);
            }).finally(() => {
                if (requestId === this.requestId) {
                    this.loading--;
                }
            });
        },
        cacheParams() {
            return {
                scope: this.scope,
                conversationType: this.conversationType,
                projectId: this.projectId,
                projectSource: this.projectSource,
                fileType: this.fileType,
                senderScope: this.senderScope,
            };
        },
        restoreCache(cache) {
            if (!$A.isJson(cache)
                || cache.version !== CACHE_VERSION
                || cache.userId !== this.userId
                || !$A.isJson(cache.params)
                || !$A.isArray(cache.list)) {
                return false;
            }

            const params = cache.params;
            if (!['all', 'conversation', 'project'].includes(params.scope)
                || !['all', 'private', 'group'].includes(params.conversationType)
                || !['all', 'project_chat', 'task'].includes(params.projectSource)
                || !['all', 'document', 'sheet', 'slide', 'image', 'video', 'archive', 'other'].includes(params.fileType)
                || !['all', 'mine'].includes(params.senderScope)) {
                return false;
            }

            this.scope = params.scope;
            this.conversationType = params.conversationType;
            this.projectId = Math.max(0, parseInt(params.projectId) || 0);
            this.projectSource = params.projectSource;
            this.fileType = params.fileType;
            this.senderScope = params.senderScope;
            if (!this.searchKey.trim()) {
                this.items = cache.list;
                this.cursor = cache.next_cursor || 0;
                this.hasMore = cache.has_more === true;
            }
            return true;
        },
        saveCache(data) {
            this.$store.commit('collaboration/file/cache/save', {
                version: CACHE_VERSION,
                userId: this.userId,
                params: this.cacheParams(),
                list: data.list,
                next_cursor: data.next_cursor,
                has_more: data.has_more,
            });
        },
        onScroll(event) {
            const target = event.target;
            if (target.scrollHeight - target.scrollTop - target.clientHeight < 180 && this.hasMore) {
                this.load();
            }
        },
        fileIconType(item) {
            if (item.file_type === 'sheet') return 'excel';
            if (item.file_type === 'slide') return 'ppt';
            if (item.file_type === 'document') return item.ext === 'pdf' ? 'pdf' : 'word';
            if (item.file_type === 'image') return 'picture';
            if (item.file_type === 'video') return 'media';
            if (item.file_type === 'archive') return 'archive';
            return 'file';
        },
        itemKey(item) {
            return item.attachment_id ? `attachment-${item.attachment_id}` : `message-${item.msg_id}`;
        },
        fileReference(item) {
            return {
                key: this.itemKey(item),
                msgId: item.msg_id,
                attachmentId: item.attachment_id,
            };
        },
        localFileStatus(item) {
            if (!this.$Electron) return 'missing';
            return this.localFileStatuses[this.itemKey(item)] || 'missing';
        },
        localActionTitle(item) {
            if (this.localFileStatus(item) === 'available') {
                return this.$L('在文件夹中显示');
            }
            return this.$L('下载');
        },
        async refreshLocalFileStatuses(items = this.items) {
            if (!this.$Electron || !items.length) return;
            const references = items.map(item => this.fileReference(item));
            const batches = [];
            for (let index = 0; index < references.length; index += 500) {
                batches.push(references.slice(index, index + 500));
            }
            try {
                const results = await Promise.all(batches.map(files => $A.Electron.sendAsync('downloadManager', {
                    action: 'fileStatuses',
                    files,
                })));
                this.localFileStatuses = Object.assign({}, this.localFileStatuses, ...results);
            } catch {
                // Keep the download action available when local history cannot be read.
            }
        },
        showThumbnail(item) {
            return !!item.image_url && !item._thumbnailError;
        },
        handleThumbnailError(item) {
            this.$set(item, '_thumbnailError', true);
        },
        fileTypeText(item) {
            const labels = {
                document: '文档',
                sheet: '表格',
                slide: '演示文稿',
                image: '图片',
                video: '视频',
                archive: '压缩包',
                other: '其他',
            };
            return `${this.$L(labels[item.file_type] || '其他')} · ${(item.ext || '').toUpperCase()}`;
        },
        fileName(item) {
            if (item.attachment_source === 'inline_image' && item.generated_name) {
                const time = $A.dayjs(item.created_at).format('YYYY-MM-DD HH:mm');
                return this.$L('聊天图片 (*)', `${time} #${(item.attachment_position || 0) + 1}`);
            }
            return item.name;
        },
        sourceTypeText(item) {
            const labels = {
                private: '私聊',
                group: '群聊',
                project_chat: '项目群聊',
                task: '任务',
            };
            return this.$L(labels[item.source_type] || '群聊');
        },
        sourcePath(item) {
            if (item.source_type === 'task') return `${item.project_name} / ${item.task_name}`;
            if (item.source_type === 'project_chat') return item.project_name;
            return item.source_type === 'private' ? this.$L('个人会话') : this.$L('普通群聊');
        },
        formatTime(time) {
            if (!time) return '-';
            const value = $A.dayjs(time);
            if (value.isSame($A.dayjs(), 'day')) return value.format('HH:mm');
            if (value.isSame($A.dayjs().subtract(1, 'day'), 'day')) return this.$L('昨天 (*)', value.format('HH:mm'));
            return value.format('YYYY-MM-DD HH:mm');
        },
        preview(item) {
            if (item.attachment_source === 'inline_image' && item.image_url) {
                this.$store.dispatch('previewImage', item.image_url);
                return;
            }
            openFileInClient(this, item, {
                path: `/single/file/msg/${item.msg_id}`,
                windowName: `file-msg-${item.msg_id}`,
            });
        },
        openSource(item) {
            if (item.source_type === 'task' && item.task_id) {
                this.$store.dispatch('openTask', {id: item.task_id, project_id: item.project_id});
                return;
            }
            this.locateMessage(item);
        },
        locateMessage(item) {
            this.$store.dispatch('openDialog', {
                dialog_id: item.dialog_id,
                search_msg_id: item.msg_id,
            }).catch(({msg}) => msg && $A.modalError(msg));
        },
        downloadUrl(item) {
            if (item.attachment_source === 'file_message') {
                return `dialog/msg/download?msg_id=${item.msg_id}`;
            }
            return item.attachment_id
                ? `file/collaboration/download?attachment_id=${item.attachment_id}`
                : `dialog/msg/download?msg_id=${item.msg_id}`;
        },
        async handleLocalAction(item) {
            if (!this.$Electron) {
                this.download(item);
                return;
            }
            const status = this.localFileStatus(item);
            if (status === 'downloading') return;
            if (status === 'available') {
                try {
                    const shown = await $A.Electron.sendAsync('downloadManager', {
                        action: 'showFile',
                        file: this.fileReference(item),
                    });
                    if (shown) return;
                } catch {
                    // Refresh the action when the local file cannot be revealed.
                }
                this.$set(this.localFileStatuses, this.itemKey(item), 'missing');
                return;
            }
            this.$set(this.localFileStatuses, this.itemKey(item), 'downloading');
            this.$store.dispatch('downUrl', $A.apiUrl(this.downloadUrl(item)));
            setTimeout(() => this.refreshLocalFileStatuses([item]), 500);
        },
        download(item) {
            $A.modalConfirm({
                language: false,
                title: this.$L('下载文件'),
                okText: this.$L('立即下载'),
                content: `${this.fileName(item)} (${$A.bytesToSize(item.size)})`,
                onOk: () => this.$store.dispatch('downUrl', $A.apiUrl(this.downloadUrl(item))),
            });
        },
    },
}
</script>

<style lang="scss" scoped>
@import "../../../../sass/var";

.collaboration-files {
    flex: 1;
    height: 0;
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.collaboration-toolbar {
    min-height: 58px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    margin: 0 32px;
    padding: 10px 0;
    box-sizing: border-box;
    border-bottom: 1px solid #e8eaec;
    .toolbar-full { flex: 1; }
    .project-select { width: 180px; }
    .type-select { width: 118px; }
    .sender-select { width: 118px; }
    ::v-deep .project-select .ivu-select-selection {
        height: 32px;
        min-height: 32px;
    }
    ::v-deep .project-select .ivu-select-selected-value,
    ::v-deep .project-select .ivu-select-placeholder {
        height: 30px;
        line-height: 30px;
    }
}
.scope-segment {
    display: flex;
    gap: 4px;
    width: max-content;
    height: 32px;
    padding: 2px;
    box-sizing: border-box;
    border-radius: 7px;
    background: #f5f6f7;
    button {
        min-width: 58px;
        height: 28px;
        padding: 0 12px;
        border: 0;
        border-radius: 5px;
        white-space: nowrap;
        color: $primary-text-color;
        background: transparent;
        cursor: pointer;
        &.active {
            color: $primary-color;
            background: #fff;
            box-shadow: 0 1px 5px rgba(31, 44, 58, 0.12);
        }
    }
}
.sub-segment {
    flex-shrink: 0;
    display: flex;
    gap: 8px;
    button {
        flex-shrink: 0;
        height: 30px;
        padding: 0 11px;
        border: 1px solid transparent;
        border-radius: 15px;
        white-space: nowrap;
        color: $primary-text-color;
        background: rgba(0, 0, 0, 0.03);
        cursor: pointer;
        &.active {
            color: $primary-color;
            border-color: rgba($primary-color, 0.45);
            background: rgba($primary-color, 0.07);
        }
    }
}
.view-switch {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    position: relative;
    border-radius: 6px;
    background: #fff;
    transition: box-shadow 0.2s;
    &:hover { box-shadow: 0 0 10px #e6ecfa; }
    &:before {
        content: "";
        width: 50%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        z-index: 0;
        border: 1px solid $primary-color;
        border-radius: 6px;
        background: rgba($primary-color, 0.1);
        transition: left 0.2s;
    }
    > div {
        z-index: 1;
        width: 32px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        color: $primary-text-color;
        cursor: pointer;
        &:first-child { color: $primary-color; }
        i { font-size: 17px; }
    }
    &.table {
        &:before { left: 50%; }
        > div:first-child { color: $primary-text-color; }
        > div:last-child { color: $primary-color; }
    }
}
.collaboration-summary {
    min-height: 70px;
    display: flex;
    align-items: center;
    margin: 0 32px;
    border-bottom: 1px solid #e8eaec;
    .summary-icon {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        border-radius: 7px;
        color: $primary-color;
        background: rgba($primary-color, 0.1);
        i { font-size: 20px; }
    }
    .summary-text {
        min-width: 0;
        display: flex;
        flex-direction: column;
        strong { font-size: 16px; color: $primary-title-color; }
        span { margin-top: 4px; font-size: 12px; color: $primary-desc-color; }
    }
    .loaded-count { margin-left: auto; color: $primary-desc-color; font-size: 12px; }
}
.collaboration-scroll {
    flex: 1;
    min-height: 0;
    overflow: auto;
    position: relative;
}
.initial-loading {
    width: 100%;
    height: 100%;
    min-height: 260px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.collaboration-table {
    margin: 0 32px;
}
.table-head,
.table-row {
    display: grid;
    grid-template-columns: minmax(260px, 1.7fr) minmax(210px, 1.2fr) 130px 150px 90px 80px;
    align-items: center;
    column-gap: 16px;
    padding: 0 12px;
}
.table-head {
    height: 44px;
    color: $primary-desc-color;
    font-size: 12px;
    border-bottom: 1px solid #e8eaec;
    span:last-child { text-align: right; }
}
.table-row {
    min-height: 70px;
    border-bottom: 1px solid #e8eaec;
    transition: background 0.15s;
    &:hover { background: rgba($primary-color, 0.025); }
}
.file-main,
.source-main {
    min-width: 0;
    display: flex;
    align-items: center;
    cursor: pointer;
}
.collaboration-file-preview {
    width: 38px;
    height: 38px;
    flex: none;
    margin-right: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 5px;
    .collaboration-file-icon {
        width: 32px;
        height: 32px;
    }
}
.collaboration-thumbnail {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    border-radius: 5px;
}
.collaboration-file-icon {
    flex: none;
    background: center / contain no-repeat url("/images/file/light/other.svg");
    &.archive { background-image: url("/images/file/light/archive.svg"); }
    &.excel { background-image: url("/images/file/light/excel.svg"); }
    &.media { background-image: url("/images/file/light/media.svg"); }
    &.pdf { background-image: url("/images/file/light/pdf.svg"); }
    &.picture { background-image: url("/images/file/light/picture.svg"); }
    &.ppt { background-image: url("/images/file/light/ppt.svg"); }
    &.word { background-image: url("/images/file/light/word.svg"); }
}
.file-text,
.source-text {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    .file-title { color: $primary-title-color; font-weight: 500; }
    > span { margin-top: 4px; color: $primary-desc-color; font-size: 11px; }
}
.source-text {
    max-height: 40px;
    overflow: hidden;
    .source-type,
    .source-name {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .source-type {
        margin-top: 0;
        color: #5276b3;
        font-size: 11px;
        line-height: 16px;
        &.group { color: #705bb1; }
        &.project_chat { color: #aa6d2e; }
        &.task { color: $primary-color; }
    }
    .source-name {
        margin-top: 0;
        color: $primary-title-color;
        font-size: 13px;
        line-height: 20px;
    }
}
.time-text,
.size-text { color: $primary-text-color; font-size: 12px; }
.row-actions {
    display: flex;
    justify-content: flex-end;
    button,
    .ivu-tooltip-rel button {
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 5px;
        color: $primary-text-color;
        background: transparent;
        cursor: pointer;
        &:hover { color: $primary-color; background: rgba($primary-color, 0.08); }
        &:disabled { cursor: default; }
        .common-loading { width: 16px; height: 16px; }
    }
}
.collaboration-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 12px;
    margin: 0 32px;
    padding: 16px 0;
}
.grid-item {
    min-height: 164px;
    padding: 14px;
    position: relative;
    border: 1px solid #e8eaec;
    border-radius: 7px;
    cursor: pointer;
    &:hover {
        border-color: rgba($primary-color, 0.55);
        .grid-actions { opacity: 1; }
    }
    .grid-preview {
        width: 42px;
        height: 42px;
        overflow: hidden;
        border-radius: 5px;
        .collaboration-file-icon {
            width: 38px;
            height: 38px;
        }
    }
    .grid-title { display: block; margin-top: 12px; color: $primary-title-color; font-weight: 500; }
    .grid-source {
        min-width: 0;
        margin-top: 8px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        .source-type {
            color: #5276b3;
            font-size: 11px;
            line-height: 16px;
            &.group { color: #705bb1; }
            &.project_chat { color: #aa6d2e; }
            &.task { color: $primary-color; }
        }
        .grid-source-name { max-width: 100%; color: $primary-text-color; line-height: 20px; }
    }
    .grid-meta { margin-top: 8px; color: $primary-desc-color; font-size: 11px; }
    .grid-actions {
        opacity: 0;
        position: absolute;
        top: 10px;
        right: 8px;
        display: flex;
        button {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            color: $primary-text-color;
            background: transparent;
            cursor: pointer;
            &:disabled { cursor: default; }
            .common-loading { width: 16px; height: 16px; }
        }
    }
}
.empty-state {
    height: 100%;
    min-height: 260px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    color: $primary-desc-color;
    i { font-size: 64px; }
    p { margin-top: 14px; }
}
.load-more,
.list-end {
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: $primary-desc-color;
    font-size: 12px;
}
@media (max-width: 900px) {
    .collaboration-toolbar {
        margin: 0 16px;
    }
    .collaboration-summary,
    .collaboration-table,
    .collaboration-grid { margin-right: 16px; margin-left: 16px; }
    .toolbar-full { display: none; }
    .project-select { flex: 1; min-width: 160px; }
    .table-head { display: none; }
    .table-row {
        grid-template-columns: minmax(0, 1fr) 72px;
        gap: 6px 10px;
        padding: 12px 4px;
        .source-main { grid-column: 1; padding-left: 49px; }
        > .avatar-wrapper,
        > .time-text,
        > .size-text { display: none; }
        .row-actions { grid-column: 2; grid-row: 1 / 3; }
    }
    .collaboration-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
    .loaded-count { display: none; }
}
</style>
