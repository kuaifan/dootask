<template>
    <ETooltip
        :open-delay="openDelay"
        :disabled="$isEEUiApp || windowTouch || tooltipDisabled || isBot"
        :placement="tooltipPlacement">
        <div v-if="user" slot="content" class="common-avatar-transfer">
            <slot/>
            <p>{{$L('昵称')}}: {{user.nickname}}<em v-if="user.delete_at" class="deleted no-dark-content">{{$L('已删除')}}</em><em v-else-if="user.disable_at" class="disabled no-dark-content">{{$L('已离职')}}</em></p>
            <p class="department-name" :title="user.department_name || ''">{{$L('部门')}}: {{user.department_name || '-'}}</p>
            <p>{{$L('职位/职称')}}: {{user.profession || '-'}}</p>
            <p v-if="user.delete_at"><strong>{{$L('删除时间')}}: {{user.delete_at}}</strong></p>
            <p v-else-if="user.disable_at"><strong>{{$L('离职时间')}}: {{user.disable_at}}</strong></p>
            <slot name="end"/>
            <div v-if="showMenu" class="avatar-icons">
                <Icon type="ios-chatbubbles" @click="onOpenDialog"/>
            </div>
        </div>
        <div>
            <UserAvatar
                ref="avatar"
                :userid="userid"
                :size="size"
                :showIcon="showIcon"
                :showName="showName"
                :nameText="nameText"
                :borderWitdh="borderWitdh"
                :borderColor="borderColor"
                :clickOpenDialog="clickOpenDialog"
                :userResult="onUserResult"/>
        </div>
    </ETooltip>
</template>

<script>
import mixin from './mixin';

export default {
    name: 'UserAvatarTip',
    mixins: [mixin],
    props: {
        tooltipDisabled: {
            type: Boolean,
            default: false
        },
        showIconMenu: {
            type: Boolean,
            default: false
        },
        tooltipPlacement: {
            type: String,
            default: 'bottom'
        },
        openDelay: {
            type: Number,
            default: 600
        },
    },

    data() {
        return {
            user: null,
        }
    },

    mounted() {
        if (this.$listeners['update:online']) {
            this.$watch('userid', () => {
                this.updateOnline()
            })
            this.$watch('user.online', () => {
                this.updateOnline()
            })
            this.updateOnline()
        }
    },

    computed: {
        showMenu() {
            if (this.$store.state.userId == this.userid) {
                return false
            }
            if (this.user.delete_at || this.user.disable_at) {
                return false
            }
            return this.showIconMenu
        },

        isBot() {
            return !!(this.user && this.user.bot);
        },
    },

    methods: {
        onOpenDialog() {
            this.$refs.avatar.openDialog();
        },

        onUserResult(info) {
            if (typeof this.userResult === "function") {
                this.userResult(info);
            }
            this.user = info;
        },

        updateOnline() {
            if (!this.user) {
                return
            }
            if (this.user.online || this.$store.state.userId === this.userid) {
                this.$emit('update:online', true)
            } else {
                const now = $A.Time()
                const line = $A.Time(this.user.line_at)
                const seconds = now - line
                let stats = '最后在线于很久以前';
                if (seconds < 60) {
                    stats = `最后在线于刚刚`
                } else if (seconds < 3600) {
                    stats = `最后在线于 ${Math.floor(seconds / 60)} 分钟前`
                } else if (seconds < 3600 * 6) {
                    stats = `最后在线于 ${Math.floor(seconds / 3600)} 小时前`
                } else {
                    const nowYmd = $A.formatDate('Y-m-d', now)
                    const lineYmd = $A.formatDate('Y-m-d', line)
                    const lineHi = $A.formatDate('H:i', line)
                    if (nowYmd === lineYmd) {
                        stats = `最后在线于今天 ${lineHi}`
                    } else if ($A.formatDate('Y-m-d', now - 86400) === lineYmd) {
                        stats = `最后在线于昨天 ${lineHi}`
                    } else if (seconds < 3600 * 24 * 365) {
                        stats = `最后在线于 ${lineYmd}`
                    }
                }
                this.$emit('update:online', this.$L(stats))
            }
        }
    }
}
</script>
