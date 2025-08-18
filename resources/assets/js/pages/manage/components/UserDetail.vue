<template>
    <ModalAlive
        v-model="showModal"
        class-name="common-user-detail-modal"
        :fullscreen="isFullscreen"
        :mask-closable="false"
        :footer-hide="true"
        width="600">
        <div class="user-detail-body">
            <UserAvatar
                :userid="userData.userid"
                :size="120"
                :show-state-dot="false"
                @on-click="onOpenAvatar"/>
            <ul class="user-select-auto">
                <li class="user-name">
                    <h1>{{userData.nickname}}</h1>
                    <em v-if="userData.delete_at" class="deleted no-dark-content">{{$L('已删除')}}</em>
                    <em v-else-if="userData.disable_at" class="disabled no-dark-content">{{$L('已离职')}}</em>
                </li>
                <li v-if="userData.userid != userId && commonDialog.total !== null">
                    <span>{{$L('共同群聊')}}: </span>
                    <a href="javascript:void(0)" @click="commonDialogShow=true">{{ $L('(*)个', commonDialog.total) }}</a>
                </li>
                <template v-if="!userData.bot">
                    <li>
                        <span>{{$L('部门')}}: </span>
                        {{userData.department_name || '-'}}
                    </li>
                    <li>
                        <span>{{$L('职位/职称')}}: </span>
                        {{userData.profession || '-'}}
                    </li>
                    <li>
                        <span>{{$L('最后在线')}}: </span>
                        {{$A.newDateString(userData.line_at, 'YYYY-MM-DD HH:mm') || '-'}}
                    </li>
                    <li v-if="userData.delete_at">
                        <strong><span>{{$L('删除时间')}}: </span>{{$A.newDateString(userData.delete_at, 'YYYY-MM-DD HH:mm')}}</strong>
                    </li>
                    <li v-else-if="userData.disable_at">
                        <strong><span>{{$L('离职时间')}}: </span>{{$A.newDateString(userData.disable_at, 'YYYY-MM-DD HH:mm')}}</strong>
                    </li>
                </template>
            </ul>
            <Button icon="md-chatbubbles" :disabled="!!userData.delete_at" @click="onOpenDialog">{{ $L('开始聊天') }}</Button>
        </div>

        <!-- 共同群组 -->
        <Modal v-model="commonDialogShow" :title="$L('共同群组') + ' (' + $L('(*)个', commonDialog.total) + ')'" :footer-hide="true" width="500">
            <div class="common-dialog-content">
                <div v-if="commonDialogLoading > 0 && commonDialog.list.length === 0" class="loading-wrapper">
                    <Loading/>
                </div>
                <div v-else-if="commonDialogList.length === 0" class="empty-wrapper">
                    <div class="empty-content">
                        <Icon type="ios-people-outline" size="48"/>
                        <p>{{$L('暂无共同群组')}}</p>
                    </div>
                </div>
                <div v-else class="dialog-list">
                    <div
                        v-for="dialog in commonDialogList"
                        :key="dialog.id"
                        class="dialog-item"
                        @click="onOpenCommonDialogChat(dialog)">
                        <div class="dialog-avatar">
                            <EAvatar v-if="dialog.avatar" :src="dialog.avatar" :size="42"></EAvatar>
                            <i v-else-if="dialog.group_type=='department'" class="taskfont icon-avatar department">&#xe75c;</i>
                            <i v-else-if="dialog.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                            <i v-else-if="dialog.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                            <i v-else-if="dialog.group_type=='okr'" class="taskfont icon-avatar task">&#xe6f4;</i>
                            <Icon v-else class="icon-avatar" type="ios-people" />
                        </div>
                        <div class="dialog-info">
                            <div class="dialog-name" v-html="transformEmojiToHtml(dialog.name)"></div>
                            <div class="dialog-meta">
                                <span class="member-count">{{$L('(*)人', dialog.people || 0)}}</span>
                                <span v-if="dialog.last_at" class="last-time">{{$A.timeFormat(dialog.last_at)}}</span>
                            </div>
                        </div>
                        <Icon class="enter-icon" type="ios-arrow-forward" />
                    </div>
                    <div v-if="commonDialog.has_more" class="load-more-wrapper">
                        <Button type="primary" @click="loadCommonDialogList(true)" :loading="commonDialogLoading > 0">{{$L('加载更多')}}</Button>
                    </div>
                </div>
            </div>
        </Modal>
    </ModalAlive>
</template>

<script>
import emitter from "../../../store/events";
import transformEmojiToHtml from "../../../utils/emoji";
import {mapState} from "vuex";

export default {
    name: 'UserDetail',

    data() {
        return {
            userData: {
                userid: 0
            },

            showModal: false,

            commonDialog: {
                total: null,
                list: [],
                page: 1,
                has_more: false,
            },
            commonDialogShow: false,
            commonDialogLoading: 0,
        }
    },

    mounted() {
        emitter.on('openUser', this.onShow);
    },

    beforeDestroy() {
        emitter.off('openUser', this.onShow);
    },

    watch: {
        ...mapState(['cacheUserBasic']),

        commonDialogShow() {
            if (!this.commonDialogShow || this.commonDialog.list.length > 0) {
                return;
            }
            this.loadCommonDialogList(false);
        }
    },

    computed: {
        isFullscreen({windowWidth}) {
            return windowWidth < 576
        },

        commonDialogList() {
            return this.commonDialog.list || [];
        },
    },

    methods: {
        transformEmojiToHtml,

        onShow(userid) {
            if (!/^\d+$/.test(userid)) {
                return
            }
            this.$store.dispatch("showSpinner", 600)
            this.$store.dispatch('getUserData', userid).then(user => {
                this.userData = user;
                this.showModal = true;
                this.loadCommonDialogCount()
            }).finally(_ => {
                this.$store.dispatch("hiddenSpinner")
            });
        },

        onHide() {
            this.commonDialogShow = false;
            this.showModal = false
        },

        onOpenAvatar() {
            this.$store.dispatch("previewImage", this.userData.userimg)
        },

        onOpenDialog() {
            this.$store.dispatch("openDialogUserid", this.userData.userid).then(_ => {
                this.onHide()
            }).catch(({msg}) => {
                $A.modalError(msg)
            });
        },

        loadCommonDialogCount() {
            const target_userid = this.userData.userid;
            if (this.commonDialog.userid !== target_userid) {
                this.commonDialog.total = null;
            }
            this.$store.dispatch('call', {
                url: 'dialog/common/list',
                data: {
                    target_userid,
                    only_count: 'yes'
                }
            }).then(({data}) => {
                if (target_userid !== this.userData.userid) {
                    return
                }
                this.commonDialog = Object.assign(data, {
                    userid: target_userid,
                    list: [],
                    has_more: false,
                });
            });
        },

        loadCommonDialogList(loadMore = false) {
            this.commonDialogLoading++;
            const target_userid = this.userData.userid;
            this.$store.dispatch('call', {
                url: 'dialog/common/list',
                data: {
                    target_userid,
                    page: loadMore ? this.commonDialog.page + 1 : 1
                }
            }).then(({data}) => {
                if (target_userid !== this.userData.userid) {
                    return;
                }
                this.commonDialog = {
                    ...this.commonDialog,
                    list: loadMore ? [...this.commonDialog.list, ...data.data] : data.data,
                    total: data.total,
                    page: data.current_page,
                    has_more: !!data.next_page_url
                }
            }).catch(({msg}) => {
                $A.modalError(msg || this.$L('加载失败'));
            }).finally(() => {
                this.commonDialogLoading--;
            });
        },

        onOpenCommonDialogChat(dialog) {
            this.$store.dispatch("openDialog", dialog.id).then(() => {
                this.onHide();
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },
    }
};
</script>
