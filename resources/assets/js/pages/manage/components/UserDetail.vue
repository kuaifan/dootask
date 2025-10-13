<template>
    <ModalAlive
        v-model="showModal"
        class-name="common-user-detail-modal"
        :fullscreen="isFullscreen"
        :mask-closable="false"
        :footer-hide="true"
        width="420">
        <div class="user-detail-body">
            <div class="profile-header">
                <div class="cover-photo"></div>
                <div class="profile-avatar">
                    <UserAvatar
                        :userid="userData.userid"
                        :size="80"
                        :show-state-dot="false"
                        @on-click="onOpenAvatar"/>
                </div>
            </div>
            <div class="profile-content">
                <div class="user-info-top">
                    <span class="username">@{{userData.nickname || 'Arnoldy'}}</span>
                    <h1 class="fullname">{{userData.nickname || 'Arnoldy Chafe'}}</h1>
                    <div class="meta">
                        <span>{{userData.address || 'Bandung'}}</span>
                        <span class="separator">|</span>
                        <span>Joined {{$A.newDateString(userData.line_at, 'MMM YYYY')}}</span>
                    </div>
                </div>

                <div class="profile-actions">
                    <Button type="primary" icon="ios-person-add-outline">{{$L('Follow')}}</Button>
                    <Button icon="ios-chatbubble-outline">{{$L('Message')}}</Button>
                    <Button icon="ios-more"></Button>
                </div>

                <div class="profile-bio">
                    <p>{{userData.introduction || 'CEO System D, Because your satisfaction is everything & Standing out from the rest, and that\'s what we want you to be as well.'}}</p>
                </div>

                <div class="profile-information">
                    <h2>{{$L('Information')}}</h2>
                    <ul>
                        <li>
                            <Icon type="ios-globe-outline" />
                            <span class="label">{{$L('Website')}}</span>
                            <span class="value">www.Arnoldy.com</span>
                        </li>
                        <li>
                            <Icon type="ios-mail-outline" />
                            <span class="label">{{$L('Email')}}</span>
                            <span class="value">Hello@adalahreza.com</span>
                        </li>
                        <li>
                            <Icon type="ios-call-outline" />
                            <span class="label">{{$L('Phone')}}</span>
                            <span class="value">+62 517 218 92 00</span>
                        </li>
                        <li>
                            <Icon type="ios-calendar-outline" />
                            <span class="label">{{$L('Joined')}}</span>
                            <span class="value">{{$A.newDateString(userData.line_at, 'DD MMMM, YYYY')}}</span>
                        </li>
                    </ul>
                </div>

                <div class="profile-tags" @click="onOpenTagsModal">
                    <div v-if="displayTags.length" class="tags-list">
                        <Tag
                            v-for="tag in displayTags"
                            :key="tag.id"
                            :color="tag.recognized ? 'primary' : 'default'"
                            class="tag-pill">{{tag.name}}</Tag>
                        <Tag v-if="!displayTags.length" class="tag-pill">UI Designer</Tag>
                        <Tag v-if="!displayTags.length" class="tag-pill">UX Designer</Tag>
                        <Tag v-if="!displayTags.length" class="tag-pill">Design System</Tag>
                        <Tag v-if="!displayTags.length" class="tag-pill">Product</Tag>
                        <Tag v-if="!displayTags.length" class="tag-pill">Succesfull</Tag>
                    </div>
                    <span v-else class="tags-empty">{{$L('暂无个性标签')}}</span>
                </div>
            </div>
        </div>
        <UserTagsModal
            v-if="userData.userid"
            v-model="tagModalVisible"
            :userid="userData.userid"
            @updated="onTagsUpdated"/>
    </ModalAlive>
</template>

<script>
import emitter from "../../../store/events";
import {mapState} from "vuex";
import UserTagsModal from "./UserTagsModal.vue";

export default {
    name: 'UserDetail',

    components: {UserTagsModal},

    data() {
        return {
            userData: {
                userid: 0
            },
            showModal: false,
            tagModalVisible: false,
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
    },

    computed: {
        isFullscreen({windowWidth}) {
            return windowWidth < 576
        },

        displayTags() {
            return Array.isArray(this.userData.personal_tags) ? this.userData.personal_tags : [];
        },

        personalTagTotal() {
            if (typeof this.userData.personal_tags_total === 'number') {
                return this.userData.personal_tags_total;
            }
            return this.displayTags.length;
        }
    },

    methods: {
        onShow(userid) {
            if (!/^\d+$/.test(userid)) {
                return
            }
            this.$store.dispatch("showSpinner", 600)
            this.$store.dispatch('getUserData', userid).then(user => {
                this.userData = user;
                this.ensureTagDefaults();
                this.showModal = true;
            }).finally(_ => {
                this.$store.dispatch("hiddenSpinner")
            });
        },

        onHide() {
            this.showModal = false;
            this.tagModalVisible = false;
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

        ensureTagDefaults() {
            if (!Array.isArray(this.userData.personal_tags)) {
                this.$set(this.userData, 'personal_tags', []);
            }
            if (typeof this.userData.personal_tags_total !== 'number') {
                this.$set(this.userData, 'personal_tags_total', this.userData.personal_tags.length);
            }
        },

        onOpenTagsModal() {
            if (!this.userData.userid) {
                return;
            }
            this.tagModalVisible = true;
        },

        onTagsUpdated({top, total}) {
            this.$set(this.userData, 'personal_tags', Array.isArray(top) ? top : []);
            this.$set(this.userData, 'personal_tags_total', typeof total === 'number' ? total : this.userData.personal_tags.length);
        },
    }
};
</script>

<style lang="scss" scoped>
// The styles will be moved to the SCSS file as requested.
// This scoped style block can be removed if not needed for specific overrides.
</style>
