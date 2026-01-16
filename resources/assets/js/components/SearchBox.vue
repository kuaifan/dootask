<template>
    <ModalAlive
        v-model="showModal"
        class-name="common-search-box-modal"
        :closable="!isFullscreen"
        :fullscreen="isFullscreen"
        :mask-closable="false"
        :footer-hide="true"
        width="768">

        <div class="search-header">
            <div class="search-input">
                <div class="search-pre">
                    <Loading v-if="loadIng > 0"/>
                    <Icon v-else type="ios-search" />
                </div>
                <Form class="search-form" action="javascript:void(0)" @submit.native.prevent="$A.eeuiAppKeyboardHide">
                    <Input type="search" ref="searchKey" v-model="searchKey" :placeholder="$L('请输入关键字')"/>
                </Form>
                <div v-if="aiSearchAvailable" class="search-ai" @click="toggleAiSearch">
                    <i class="taskfont">&#xe8a1;</i>
                    <span>{{ $L('AI 搜索') }}</span>
                </div>
            </div>
            <div class="search-close" @click="onHide">
                <i class="taskfont">&#xe6e5;</i>
            </div>
        </div>

        <div class="search-body" @touchstart="onTouchstart">
            <div class="search-tags">
                <div
                    v-for="tag in tags"
                    :key="tag.type"
                    class="tag-item"
                    :class="{action: tag.type === action}"
                    @click="onTag(tag.type, $event)">
                    <i class="taskfont" v-html="tag.icon"></i>
                    <span>{{$L(tag.name)}}</span>
                    <i v-if="tag.type === action" class="taskfont tag-close">&#xe747;</i>
                </div>
            </div>
            <template v-if="total === 0">
                <div v-if="(loadIng + loadPre) > 0 || !searchKey.trim()" class="search-empty">
                    <i class="taskfont">&#xe60b;</i>
                    <span>{{ $L((loadIng + loadPre) > 0 ? '正在拼命搜索...' : '请输入关键字搜索') }}</span>
                </div>
                <div v-else class="search-empty">
                    <i class="taskfont">&#xe60b;</i>
                    <span class="empty-label">{{ $L('暂无相关结果') }}</span>
                    <span>{{ $L('未搜到跟「(*)」相关的结果', searchKey) }}</span>
                </div>
            </template>
            <div v-else class="search-list">
                <ul v-for="data in list" :key="data.type">
                    <li v-if="!action" class="item-label">{{$L(data.name)}}</li>
                    <li v-for="item in data.items" @click="onClick(item)">
                        <div class="item-icon">
                            <div v-if="item.icons[0]==='file'" :class="`no-dark-content file-icon ${item.icons[1]}`"></div>
                            <i v-else-if="item.icons[0]==='department'" class="taskfont icon-avatar department">&#xe75c;</i>
                            <i v-else-if="item.icons[0]==='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                            <i v-else-if="item.icons[0]==='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                            <UserAvatar v-else-if="item.icons[0]==='user'" class="user-avatar" :userid="item.icons[1]" :size="38"/>
                            <EAvatar v-else-if="item.icons[0]==='avatar'" class="img-avatar" :src="item.icons[1]" :size="38"/>
                            <Icon v-else-if="item.icons[0]==='people'" class="icon-avatar" type="ios-people" />
                            <Icon v-else class="icon-avatar" type="md-person" />
                        </div>
                        <div class="item-content">
                            <div class="item-title">
                                <div class="title-text" v-html="transformEmojiToHtml(item.title)"></div>
                                <div
                                    v-if="item.activity"
                                    class="title-activity"
                                    :title="item.activity">{{activityFormat(item.activity)}}</div>
                            </div>
                            <div class="item-desc">
                                <span
                                    class="desc-tag"
                                    v-if="item.tags"
                                    v-for="tag in item.tags"
                                    :style="tag.style">{{tag.name}}</span>
                                <span class="desc-text" v-html="transformEmojiToHtml(item.desc)"></span>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

    </ModalAlive>
</template>

<script>
import {mapState} from "vuex";
import emitter from "../store/events";
import transformEmojiToHtml from "../utils/emoji";
import {SEARCH_AI_SYSTEM_PROMPT, withLanguagePreferencePrompt} from "../utils/ai";

export default {
    name: 'SearchBox',
    props: {
        //
    },

    data() {
        return {
            loadPre: 0,
            loadIng: 0,

            searchKey: '',
            searchResults: [],
            searchTimer: null,

            showModal: false,

            tags: [
                {type: 'task', name: '任务', icon: '&#xe6f4;'},
                {type: 'project', name: '项目', icon: '&#xe6f9;'},
                {type: 'message', name: '消息', icon: '&#xe6eb;'},
                {type: 'contact', name: '联系人', icon: '&#xe6b2;'},
                {type: 'file', name: '文件', icon: '&#xe6f3;'},
            ],
            action: '',
        }
    },

    mounted() {
        emitter.on('openSearch', this.onShow);
    },

    beforeDestroy() {
        emitter.off('openSearch', this.onShow);
    },

    watch: {
        searchKey() {
            this.preSearch()
        },

        action() {
            this.preSearch()
        },

        showModal(v) {
            $A.eeuiAppSetScrollDisabled(v)
        }
    },

    computed: {
        ...mapState([
            'themeName',
            'keyboardShow',
            'microAppsIds'
        ]),

        aiSearchAvailable() {
            return this.microAppsIds 
                && this.microAppsIds.includes('search') 
                && this.microAppsIds.includes('ai')
        },

        isFullscreen({windowWidth}) {
            return windowWidth < 576
        },

        items({searchKey, searchResults, action}) {
            return searchResults.filter(item => item.key === searchKey && (!action || item.type === action))
        },

        total() {
            return this.items.length
        },

        list({action, tags}) {
            const groups = new Map();
            const maxItems = action ? Infinity : 10;

            for (let i = 0; i < this.items.length; i++) {
                const item = this.items[i];
                const type = item.type;
                if (!groups.has(type)) {
                    groups.set(type, []);
                }
                const group = groups.get(type);
                if (group.length < maxItems) {
                    group.push(item);
                }
            }

            return tags.reduce((result, tag) => {
                if (groups.has(tag.type)) {
                    result.push({
                        ...tag,
                        items: groups.get(tag.type)
                    });
                }
                return result;
            }, []);
        },
    },

    methods: {
        transformEmojiToHtml,
        activityFormat(date) {
            const local = $A.daytz(),
                time = $A.dayjs(date);
            if (local.format("YYYY/MM/DD") === time.format("YYYY/MM/DD")) {
                return time.format("HH:mm")
            }
            if (local.year() === time.year()) {
                return time.format("MM/DD")
            }
            return time.format("YYYY/MM/DD") || '';
        },

        onClick(item) {
            switch (item.type) {
                case 'task':
                    this.$store.dispatch('openTask', item.rawData)
                    this.onHide()
                    break;

                case 'project':
                    if (item.rawData.archived_at) {
                        $A.modalWarning("项目已归档，无法查看")
                        return
                    }
                    this.goForward({name: 'manage-project', params: {projectId: item.id}})
                    this.onHide()
                    break;

                case 'message':
                    this.$store.dispatch("openDialog", item.id).then(_ => {
                        this.$store.state.dialogSearchMsgId = /^\d+$/.test(item.rawData.search_msg_id) ? item.rawData.search_msg_id : 0
                        if (this.routeName === 'manage-messenger') {
                            this.onHide()
                        }
                    }).catch(({msg}) => {
                        $A.modalError(msg || this.$L('打开会话失败'))
                    })
                    break;

                case 'contact':
                    this.$store.dispatch("openDialogUserid", item.id).then(_ => {
                        if (this.routeName === 'manage-messenger') {
                            this.onHide()
                        }
                    }).catch(({msg}) => {
                        $A.modalError(msg || this.$L('打开会话失败'))
                    });
                    break;

                case 'file':
                    this.goForward({name: 'manage-file', params: {folderId: item.rawData.pid, fileId: null, shakeId: item.id}})
                    this.$store.state.fileShakeId = item.id
                    setTimeout(() => {
                        this.$store.state.fileShakeId = 0
                    }, 600)
                    this.onHide()
                    break;
            }
        },

        onTouchstart() {
            $A.eeuiAppKeyboardHide();
        },

        onTag(type, e) {
            this.action = this.action !== type ? type : '';
            $A.scrollToView(e.target, {
                block: 'nearest',
                inline: 'nearest',
                behavior: 'smooth'
            })
        },

        onShow() {
            const autoFocus = this.total === 0 || this.showModal || !this.windowTouch   // 这几种情况下显示完后自动获取焦点（无展示结果、现在已经显示了、不是触摸设备）
            this.showModal = true
            autoFocus && this.$nextTick(() => {
                const $el = this.$refs.searchKey?.$refs?.input;
                if ($el) {
                    $el.style.caretColor = 'transparent';
                    $el.focus()
                    setTimeout(() => {
                        const len = $el.value.length;
                        $el.setSelectionRange(len, len);
                        $el.style.caretColor = null
                    }, 300)
                }
            })
        },

        onHide() {
            this.showModal = false
        },

        preSearch() {
            if (!this.searchKey.trim()) {
                return;
            }
            if (this.searchTimer) {
                clearTimeout(this.searchTimer)
                this.searchTimer = null;
                this.loadPre--;
            }
            this.loadPre++;
            this.searchTimer = setTimeout(() => {
                if (this.searchKey.trim()) {
                    this.onSearch()
                }
                this.searchTimer = null;
                this.loadPre--;
            }, 500)
        },

        onSearch() {
            if (this.action) {
                this.distSearch(this.action)
                return;
            }
            this.tags.forEach(({type}) => this.distSearch(type))
        },

        distSearch(type) {
            const func = this[`search${type.charAt(0).toUpperCase()}${type.slice(1)}`]
            if (typeof func === 'function') {
                func(this.searchKey)
                return true
            }
            return false
        },

        echoSearch(items, key = 'id') {
            items.forEach(item => {
                const index = this.searchResults.findIndex(result => {
                    return result[key] === item[key] && result.type === item.type
                })
                if (index > -1) {
                    this.searchResults.splice(index, 1, item)
                } else {
                    this.searchResults.push(item)
                }
            })
        },

        searchTask(key) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'search/task',
                data: {
                    key,
                    search_type: 'text',
                    take: this.action ? 50 : 10,
                },
            }).then(({data}) => {
                const nowTime = $A.dayjs().unix()
                const items = data.map(item => {
                    const tags = [];
                    if (item.complete_at) {
                        tags.push({
                            name: this.$L('已完成'),
                            style: 'background-color:#0bc037',
                        })
                    } else if (item.overdue) {
                        tags.push({
                            name: this.$L('超期'),
                            style: 'background-color:#f00',
                        })
                    } else if (item.end_at && $A.dayjs(item.end_at).unix() - nowTime < 86400) {
                        tags.push({
                            name: this.$L('即将到期'),
                            style: 'background-color:#f80',
                        })
                    }
                    if (item.archived_at) {
                        tags.push({
                            name: this.$L('已归档'),
                            style: 'background-color:#ccc',
                        })
                    }
                    return {
                        key,
                        type: 'task',
                        icons: ['task', null],
                        tags,

                        id: item.id,
                        title: item.name,
                        desc: item.desc,
                        activity: item.end_at,

                        rawData: item,
                    };
                });
                this.echoSearch(items)
            }).finally(_ => {
                this.loadIng--;
            })
        },

        searchProject(key) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'search/project',
                data: {
                    key,
                    search_type: 'text',
                    take: this.action ? 50 : 10,
                },
            }).then(({data}) => {
                const items = data.map(item => {
                    const tags = [];
                    if (item.owner) {
                        tags.push({
                            name: this.$L('负责人'),
                            style: 'background-color:#0bc037',
                        })
                    }
                    if (item.archived_at) {
                        tags.push({
                            name: this.$L('已归档'),
                            style: 'background-color:#ccc',
                        })
                    }
                    return {
                        key,
                        type: 'project',
                        icons: ['project', null],
                        tags,

                        id: item.id,
                        title: item.name,
                        desc: item.desc || '',
                        activity: item.updated_at,

                        rawData: item,
                    };
                })
                this.echoSearch(items)
            }).finally(_ => {
                this.loadIng--;
            })
        },

        searchMessage(key) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'search/message',
                data: {
                    key,
                    search_type: 'text',
                    mode: 'dialog',
                    take: this.action ? 50 : 10,
                },
            }).then(({data}) => {
                const items = data.data.map(item => {
                    let icon = 'person';
                    let desc = null;
                    if (item.type == 'group') {
                        if (item.avatar) {
                            icon = 'avatar';
                            desc = item.avatar;
                        } else if (item.group_type == 'department') {
                            icon = 'department';
                        } else if (item.group_type == 'project') {
                            icon = 'project';
                        } else if (['task', 'okr'].includes(item.group_type)) {
                            icon = 'task';
                        } else {
                            icon = 'people';
                        }
                    } else if (item.dialog_user) {
                        icon = 'user';
                        desc = item.dialog_user.userid
                    }
                    return {
                        key,
                        type: 'message',
                        icons: [icon, desc],
                        tags: [],

                        id: item.id,
                        title: item.name,
                        desc: $A.getMsgSimpleDesc(item.last_msg),
                        activity: item.last_at,

                        searchMsgId: item.search_msg_id,
                        rawData: item,
                    };
                })
                this.echoSearch(items, 'searchMsgId')
            }).finally(_ => {
                this.loadIng--;
            })
        },

        searchContact(key) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'search/contact',
                data: {
                    key,
                    search_type: 'text',
                    take: this.action ? 50 : 10,
                },
            }).then(({data}) => {
                const items = data.map(item => {
                    // 构建标签：显示匹配搜索关键词的标签
                    const tags = [];
                    if (item.search_tags && item.search_tags.length > 0) {
                        const keyLower = key.toLowerCase();
                        item.search_tags.forEach(tagName => {
                            if (tagName.toLowerCase().includes(keyLower)) {
                                tags.push({
                                    name: tagName,
                                    style: 'background-color:#2d8cf0',
                                });
                            }
                        });
                    }
                    return {
                        key,
                        type: 'contact',
                        icons: ['user', item.userid],
                        tags,

                        id: item.userid,
                        title: item.nickname,
                        desc: item.profession || '',
                        activity: item.line_at,

                        rawData: item,
                    };
                })
                this.echoSearch(items)
            }).finally(_ => {
                this.loadIng--;
            })
        },

        searchFile(key) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'search/file',
                data: {
                    key,
                    search_type: 'text',
                    take: this.action ? 50 : 10,
                },
            }).then(({data}) => {
                const items = data.map(item => {
                    const tags = [];
                    if (item.share) {
                        tags.push({
                            name: this.$L(item.userid == this.userId ? '已共享' : '共享'),
                            style: 'background-color:#0bc037',
                        })
                    }
                    return {
                        key,
                        type: 'file',
                        icons: ['file', item.type],
                        tags,

                        id: item.id,
                        title: item.name,
                        desc: item.type === 'folder' ? '' : $A.bytesToSize(item.size),
                        activity: item.updated_at,

                        rawData: item,
                    };
                })
                this.echoSearch(items)
            }).finally(_ => {
                this.loadIng--;
            })
        },

        toggleAiSearch() {
            const keyword = this.searchKey.trim();
            emitter.emit('openAIAssistant', {
                sessionKey: 'ai-search',
                resumeSession: 86400,
                title: this.$L('AI 搜索'),
                value: keyword,
                placeholder: this.$L('请描述你想搜索的内容...'),
                loadingText: this.$L('搜索中...'),
                showApplyButton: false,
                autoSubmit: !!keyword,
                onBeforeSend: this.handleAISearchBeforeSend,
            });
            this.onHide();
        },

        handleAISearchBeforeSend(context = []) {
            const prepared = [
                ['system', withLanguagePreferencePrompt(SEARCH_AI_SYSTEM_PROMPT)]
            ];

            if (context.length > 0) {
                prepared.push(...context);
            }

            return prepared;
        }
    }
};
</script>
