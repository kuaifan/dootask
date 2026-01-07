<template>
    <ModalAlive
        v-model="showModal"
        class-name="common-user-detail-modal"
        :fullscreen="isFullscreen"
        :mask-closable="false"
        :footer-hide="true"
        width="480"
    >
        <div class="user-detail-body">
            <div class="profile-header">
                <div class="cover-photo" :style="{ '--user-cover-photo': `url(${userData.userimg || ''})` }"></div>
                <div class="profile-avatar">
                    <UserAvatar
                        :userid="userData.userid"
                        :size="96"
                        :show-state-dot="false"
                        @on-click="onOpenAvatar"
                    />
                </div>
            </div>
            <div class="profile-content">
                <div class="user-info-top">
                    <h1 class="username">
                        {{ userData.nickname }}
                    </h1>
                    <div class="meta">
                        <span @click="commonDialogShow = true" class="common-dialog">{{ $L(userId == userData.userid ? "我的群组" : "共同群组") }}:<em>{{ $L("(*)个", commonDialog.total) }}</em></span>
                        <span class="separator">|</span>
                        <span>{{ $L("最后在线") }}: {{$A.newDateString( userData.line_at, "YYYY-MM-DD HH:mm") || "-"}}</span>
                    </div>
                </div>

                <div class="profile-actions">
                    <Button @click="onOpenDialog"><i class="taskfont">&#xe6eb;</i>{{ $L("开始聊天") }}</Button>
                    <Button @click="onCreateGroup"><i class="taskfont">&#xe63f;</i>{{ $L("创建群组") }}</Button>
                </div>

                <div v-if="userData.introduction" class="profile-bio">
                    <p>{{ userData.introduction }}</p>
                </div>

                <div class="profile-information">
                    <h2>{{ $L("个人信息") }}</h2>
                    <ul>
                        <li>
                            <Icon type="ios-briefcase-outline" />
                            <span class="label">{{ $L("职位/职称") }}</span>
                            <span class="value">{{userData.profession || "-"}}</span>
                        </li>
                        <li>
                            <Icon type="ios-people-outline" />
                            <span class="label">{{ $L("部门") }}</span>
                            <span class="value">{{userData.department_name || "-"}}</span>
                        </li>
                        <li>
                            <Icon type="ios-mail-outline" />
                            <span class="label">{{ $L("邮箱") }}</span>
                            <span @click="onOpenEmail" class="value" :class="{ 'clickable': userData.email }">{{userData.email || "-"}}</span>
                        </li>
                        <li>
                            <Icon type="ios-call-outline" />
                            <span class="label">{{ $L("电话") }}</span>
                            <span @click="onOpenTel" class="value" :class="{ 'clickable': userData.tel }">{{ userData.tel || "-" }}</span>
                        </li>
                        <li v-if="userData.birthday">
                            <Icon type="ios-calendar-outline" />
                            <span class="label">{{ $L("生日") }}</span>
                            <span class="value">{{userData.birthday || "-"}}</span>
                        </li>
                    </ul>

                    <div class="profile-tags">
                        <div v-if="displayTags.length" class="tags-list">
                            <Button
                                v-for="tag in displayTags"
                                :key="tag.id"
                                :type="tag.recognized ? 'primary' : 'default'"
                                @click="onOpenTagsModal"
                            >
                                {{ tag.name }}
                                <span v-if="tag.recognition_total > 0" class="recognition-total">{{tag.recognition_total}}</span>
                            </Button>
                            <Button
                                type="dashed"
                                class="manage-tags-btn icon"
                                @click="onOpenTagsModal"
                            >
                                <Icon type="ios-settings-outline" /> 管理
                            </Button>
                        </div>
                        <div v-else class="tags-empty">
                            <Button
                                type="dashed"
                                icon="md-add"
                                class="add-tag-btn"
                                @click="onOpenTagsModal"
                                >{{ $L("添加标签") }}</Button
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <UserTagsModal
            v-if="userData.userid"
            v-model="tagModalVisible"
            :userid="userData.userid"
            @updated="onTagsUpdated"
        />

        <CommonDialogModal
            v-model="commonDialogShow"
            :target-user-id="userData.userid"
            :total-count="commonDialog.total || 0"
            @open-chat="onOpenCommonDialogChat"
        />
    </ModalAlive>
</template>

<script>
import emitter from "../../../store/events";
import { mapState } from "vuex";
import transformEmojiToHtml from "../../../utils/emoji";
import UserTagsModal from "./UserTagsModal.vue";
import CommonDialogModal from "./CommonDialogModal.vue";

export default {
    name: "UserDetail",

    components: { UserTagsModal, CommonDialogModal },

    data() {
        return {
            userData: {
                userid: 0,
            },
            showModal: false,
            tagModalVisible: false,
            commonDialog: {
                userid: null,
                total: null,
                list: [],
                page: 1,
                has_more: false,
            },
            commonDialogShow: false,
            commonDialogLoading: 0,
        };
    },

    mounted() {
        emitter.on("openUser", this.onShow);
    },

    beforeDestroy() {
        emitter.off("openUser", this.onShow);
    },

    watch: {
        ...mapState(["cacheUserBasic"]),

        commonDialogShow() {
            if (!this.commonDialogShow || this.commonDialog.list.length > 0) {
                return;
            }
            this.loadCommonDialogList(false);
        },
    },

    computed: {
        isFullscreen({ windowWidth }) {
            return windowWidth < 576;
        },

        displayTags() {
            return Array.isArray(this.userData.personal_tags)
                ? this.userData.personal_tags
                : [];
        },

        personalTagTotal() {
            if (typeof this.userData.personal_tags_total === "number") {
                return this.userData.personal_tags_total;
            }
            return this.displayTags.length;
        },

        commonDialogList() {
            return this.commonDialog.list || [];
        },
    },

    methods: {
        transformEmojiToHtml,
        onShow(userid) {
            if (!/^\d+$/.test(userid)) {
                return;
            }
            this.$store.dispatch("showSpinner", 600);
            Promise.all([
                this.$store.dispatch("getUserData", userid).catch(() => null),
                this.$store.dispatch("getUserExtra", userid).catch(() => null),
            ])
                .then(([user, extra]) => {
                    const baseData = $A.isJson(user) ? user : {};
                    const extraData = $A.isJson(extra) ? extra : {};
                    this.userData = Object.assign({}, baseData, extraData);
                    this.ensureTagDefaults();
                    this.showModal = true;
                    this.loadCommonDialogCount();
                })
                .finally((_) => {
                    this.$store.dispatch("hiddenSpinner");
                });
        },

        onHide() {
            this.showModal = false;
            this.tagModalVisible = false;
            this.commonDialogShow = false;
        },

        onOpenAvatar() {
            this.$store.dispatch("previewImage", this.userData.userimg);
        },

        onOpenDialog() {
            this.$store
                .dispatch("openDialogUserid", this.userData.userid)
                .then((_) => {
                    this.onHide();
                })
                .catch(({ msg }) => {
                    $A.modalError(msg);
                });
        },

        onCreateGroup() {
            const userids = [this.$store.state.userId];
            if (this.userData.userid && this.$store.state.userId != this.userData.userid) {
                userids.push(this.userData.userid);
            }
            emitter.emit('createGroup', userids);
            this.onHide();
        },

        ensureTagDefaults() {
            if (!Array.isArray(this.userData.personal_tags)) {
                this.$set(this.userData, "personal_tags", []);
            }
            if (typeof this.userData.personal_tags_total !== "number") {
                this.$set(
                    this.userData,
                    "personal_tags_total",
                    this.userData.personal_tags.length
                );
            }
        },

        onOpenTagsModal() {
            if (!this.userData.userid) {
                return;
            }
            this.tagModalVisible = true;
        },

        onTagsUpdated({ top, total }) {
            this.$set(
                this.userData,
                "personal_tags",
                Array.isArray(top) ? top : []
            );
            this.$set(
                this.userData,
                "personal_tags_total",
                typeof total === "number"
                    ? total
                    : this.userData.personal_tags.length
            );
        },

        loadCommonDialogCount() {
            const target_userid = this.userData.userid;
            const previousUserId = this.commonDialog.userid;
            if (!target_userid) {
                this.commonDialog = {
                    ...this.commonDialog,
                    userid: target_userid || null,
                    total: null,
                    list: [],
                    page: 1,
                    has_more: false,
                };
                return;
            }

            if (previousUserId !== target_userid) {
                this.commonDialog = {
                    ...this.commonDialog,
                    userid: target_userid,
                    total: null,
                    list: [],
                    page: 1,
                    has_more: false,
                };
            }

            const cacheMap = this.$store.state.dialogCommonCountCache || {};
            const cached = cacheMap[String(target_userid)];
            if (cached && typeof cached.total !== "undefined") {
                this.commonDialog = {
                    ...this.commonDialog,
                    total: cached.total,
                };
            }

            this.$store
                .dispatch("call", {
                    url: "dialog/common/list",
                    data: {
                        target_userid,
                        only_count: "yes",
                    },
                })
                .then(({ data }) => {
                    if (target_userid !== this.userData.userid) {
                        return;
                    }
                    const parsedTotal = Number(data.total);
                    const total = Number.isNaN(parsedTotal) ? 0 : parsedTotal;
                    this.commonDialog = {
                        ...this.commonDialog,
                        userid: target_userid,
                        total,
                        list: [],
                        page: 1,
                        has_more: false,
                    };
                    this.$store.commit("common/dialog/count/save", {
                        userid: target_userid,
                        total,
                    });
                });
        },

        loadCommonDialogList(loadMore = false) {
            this.commonDialogLoading++;
            const target_userid = this.userData.userid;
            this.$store
                .dispatch("call", {
                    url: "dialog/common/list",
                    data: {
                        target_userid,
                        page: loadMore ? this.commonDialog.page + 1 : 1,
                    },
                })
                .then(({ data }) => {
                    if (target_userid !== this.userData.userid) {
                        return;
                    }
                    this.commonDialog = {
                        ...this.commonDialog,
                        list: loadMore
                            ? [...this.commonDialog.list, ...data.data]
                            : data.data,
                        total: data.total,
                        page: data.current_page,
                        has_more: !!data.next_page_url,
                    };
                })
                .catch(({ msg }) => {
                    $A.modalError(msg || this.$L("加载失败"));
                })
                .finally(() => {
                    this.commonDialogLoading--;
                });
        },

        onOpenCommonDialogChat(dialog) {
            this.$store
                .dispatch("openDialog", dialog.id)
                .then(() => {
                    this.onHide();
                })
                .catch(({ msg }) => {
                    $A.modalError(msg);
                });
        },

        onOpenEmail() {
            if (!this.userData.email) {
                return;
            }
            $A.modalConfirm({
                content: `是否发送邮件给 ${this.userData.nickname}？`,
                onOk: () => {
                    window.open(`mailto:${this.userData.email}`);
                }
            });
        },

        onOpenTel() {
            if (!this.userData.tel) {
                return;
            }
            $A.modalConfirm({
                content: `是否拨打电话给 ${this.userData.nickname}？`,
                onOk: () => {
                    if ($A.isEEUIApp()) {
                        $A.eeuiAppSendMessage({
                            action: 'callTel',
                            tel: this.userData.tel
                        });
                    } else {
                        window.open(`tel:${this.userData.tel}`);
                    }
                }
            });
        },
    },
};
</script>