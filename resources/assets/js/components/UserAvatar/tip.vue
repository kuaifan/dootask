<template>
    <ETooltip
        :open-delay="openDelay"
        :disabled="$isEEUIApp || windowTouch || tooltipDisabled || isBot"
        :placement="tooltipPlacement">
        <div v-if="user" slot="content" class="common-avatar-transfer">
            <slot/>
            <p>{{$L('昵称')}}: {{user.nickname}}<em v-if="user.delete_at" class="deleted no-dark-content">{{$L('已删除')}}</em><em v-else-if="user.disable_at" class="disabled no-dark-content">{{$L('已离职')}}</em></p>
            <p class="department-name" :title="user.department_name || ''">{{$L('部门')}}: {{user.department_name || '-'}}</p>
            <p>{{$L('职位/职称')}}: {{user.profession || '-'}}</p>
            <p v-if="user.delete_at"><strong>{{$L('删除时间')}}: {{$A.newDateString(user.delete_at, 'YYYY-MM-DD HH:mm')}}</strong></p>
            <p v-else-if="user.disable_at"><strong>{{$L('离职时间')}}: {{$A.newDateString(user.disable_at, 'YYYY-MM-DD HH:mm')}}</strong></p>
            <slot name="end"/>
        </div>
        <div>
            <UserAvatar
                ref="avatar"
                :userid="userid"
                :size="size"
                :showIcon="showIcon"
                :showName="showName"
                :showStateDot="showStateDot"
                :nameText="nameText"
                :borderWidth="borderWidth"
                :borderColor="borderColor"
                :clickOpenDetail="clickOpenDetail"
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
        isBot() {
            return !!(this.user && this.user.bot);
        },
    },

    methods: {
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
                const now = $A.daytz()
                const line = $A.dayjs(this.user.line_at)
                const seconds = now.unix() - line.unix()
                let stats = '最后在线于很久以前';
                if (seconds < 60) {
                    stats = `最后在线于刚刚`
                } else if (seconds < 3600) {
                    stats = `最后在线于 ${Math.floor(seconds / 60)} 分钟前`
                } else if (seconds < 3600 * 6) {
                    stats = `最后在线于 ${Math.floor(seconds / 3600)} 小时前`
                } else {
                    const nowYmd = now.format('YYYY-MM-DD')
                    const lineYmd = line.format('YYYY-MM-DD')
                    const lineHi = line.format('HH:mm')
                    if (nowYmd === lineYmd) {
                        stats = `最后在线于今天 ${lineHi}`
                    } else if (now.clone().subtract(1, 'day').format('YYYY-MM-DD') === lineYmd) {
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
