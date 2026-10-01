<template>
    <div class="setting-item submit">
        <div class="system-update-box">
            <!-- 更新进行中 / 本次更新结果 -->
            <!-- 更新进行中 / 本次更新结果 -->
            <div v-if="taskMode" :class="['update-view', `is-${task.status || 'running'}`]">
                <div class="view-head">
                    <div class="view-title">{{$L(taskTitle)}}</div>
                    <div class="view-desc">
                        <span v-if="task.from_version && task.to_version">v{{task.from_version}} → v{{task.to_version}}</span>
                        <span v-if="taskDuration">{{$L('用时 (*)', taskDuration)}}</span>
                    </div>
                </div>

                <ul class="task-steps">
                    <li v-for="item in stages" :key="item.key" :class="stageClass(item.key)">
                        <div class="step-dot">
                            <Loading v-if="stageClass(item.key) === 'process'"/>
                            <Icon v-else-if="stageClass(item.key) === 'finish'" type="md-checkmark"/>
                            <Icon v-else-if="stageClass(item.key) === 'error'" type="md-close"/>
                        </div>
                        <span class="step-label">{{$L(item.label)}}</span>
                    </li>
                </ul>

                <div v-if="task.status === 'running' && pollFailed" class="view-notice warning">{{$L('服务重启中，正在等待恢复...')}}</div>
                <div v-else-if="task.status === 'success'" class="view-notice success">
                    {{$L('系统已更新到 v(*)，请刷新页面以使用新版本', task.to_version || task.current_version)}}
                </div>
                <div v-else-if="task.status === 'failed'" class="view-notice error">
                    <div v-if="task.error" class="notice-title">{{task.error}}</div>
                    <div v-if="task.backup_file">{{$L('更新前的数据库备份：(*)', task.backup_file)}}</div>
                    <div>{{$L('请根据日志在服务器上处理，必要时执行 (*) 回滚代码后重新更新', rollbackCommand)}}</div>
                </div>

                <div class="view-panel">
                    <div class="panel-head">{{$L('更新日志')}}</div>
                    <pre ref="log" class="panel-body is-log">{{taskLog || $L('等待日志输出...')}}</pre>
                </div>
            </div>

            <!-- 检查更新 -->
            <div v-else :class="['update-view', checkStatus]">
                <div class="view-head">
                    <div class="view-title">{{$L(checkTitle)}}</div>
                    <div class="view-desc">
                        <span v-if="info && info.has_update">v{{currentVersion}} → v{{info.latest_version}}</span>
                        <span v-else>{{$L('当前版本')}} v{{currentVersion}}</span>
                        <span v-if="info && info.checked_at">{{$L('上次检查：(*)', checkedTime)}}</span>
                    </div>
                </div>

                <div v-if="lastFailed" class="view-notice error">
                    <div class="notice-title">{{$L('上次系统更新失败')}}</div>
                    <div>
                        <span v-if="lastFailed.error">{{lastFailed.error}}</span>
                        <a href="javascript:void(0)" @click="viewLastTask">{{$L('查看日志')}}</a>
                    </div>
                </div>

                <template v-if="info && !info.supported">
                    <div class="view-notice warning">
                        <div class="notice-title">{{$L(reasonText)}}</div>
                        <div>{{$L(reasonHint, './cmd update')}}</div>
                    </div>
                </template>

                <template v-else-if="info && info.has_update">
                    <div v-if="info.has_local_changes" class="view-notice error">
                        <div class="notice-title">{{$L('服务器存在本地修改，无法在线更新')}}</div>
                        <div>{{$L('请在服务器上处理本地修改后，执行 (*) 更新系统', './cmd update')}}</div>
                    </div>
                    <div v-else class="view-notice warning">
                        <div>{{$L('更新期间系统将暂停服务（通常 1~3 分钟），所有在线成员会短暂断开连接')}}</div>
                        <div>{{$L(info.has_db_changes ? '更新前将自动备份数据库（本次更新包含数据库变更）' : '更新前将自动备份数据库')}}</div>
                    </div>
                    <div class="view-panel">
                        <div class="panel-head">{{$L('更新内容')}}</div>
                        <div class="panel-body is-markdown">
                            <VMPreview v-if="info.changelog" :value="info.changelog"/>
                            <div v-else class="panel-empty">{{$L('暂无更新介绍！')}}</div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- 底部按钮：与其他设置页一致固定在底部 -->
        <div v-if="taskMode && task.status !== 'running'" class="setting-footer">
            <Button v-if="task.status === 'success'" type="primary" @click="reloadPage">{{$L('刷新页面')}}</Button>
            <Button @click="backToCheck">{{$L('返回')}}</Button>
        </div>
        <div v-else-if="!taskMode" class="setting-footer">
            <Button
                v-if="info && info.has_update && !info.has_local_changes"
                type="primary"
                :disabled="checkIng"
                @click="confirmShow = true">{{$L('立即更新系统')}}</Button>
            <Button :loading="checkIng" @click="check(true)">{{$L('检查更新')}}</Button>
        </div>

        <!-- 确认更新 -->
        <Modal
            v-model="confirmShow"
            :title="$L('更新系统')"
            :mask-closable="false"
            @on-visible-change="onConfirmVisible">
            <div class="system-update-confirm">
                <p>{{$L('更新期间系统将暂停服务（通常 1~3 分钟），所有在线成员会短暂断开连接。更新前会自动备份数据库。确定立即更新吗？')}}</p>
                <Input
                    ref="password"
                    v-model="password"
                    type="password"
                    password
                    :placeholder="$L('请输入登录密码')"
                    @on-enter="start"/>
            </div>
            <div slot="footer" class="adaption">
                <Button type="default" @click="confirmShow = false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="startIng" @click="start">{{$L('立即更新')}}</Button>
            </div>
        </Modal>
    </div>
</template>


<script>
import VMPreview from "../../../components/VMEditor/preview.vue";

const STAGES = [
    {key: 'preparing', label: '准备更新'},
    {key: 'fetching', label: '获取更新'},
    {key: 'backup', label: '备份数据库'},
    {key: 'pulling', label: '更新代码'},
    {key: 'composer', label: '安装依赖'},
    {key: 'migrating', label: '迁移数据库'},
    {key: 'restarting', label: '重启服务'},
    {key: 'verifying', label: '检查服务'},
];

const REASONS = {
    disabled: ['在线更新已关闭', '如需开启，请联系运维人员调整服务器配置；也可在服务器上执行 (*) 更新系统'],
    not_installed: ['系统尚未完成安装', '请在服务器上执行 (*) 更新系统'],
    no_git: ['当前部署方式不支持在线更新', '请在服务器上执行 (*) 更新系统'],
    detached_head: ['当前代码不在任何分支上，无法在线更新', '请在服务器上执行 (*) 更新系统'],
    fetch_failed: ['无法连接代码仓库，请检查服务器网络或仓库地址', '也可在服务器上执行 (*) 更新系统'],
    appstore_unavailable: ['无法连接应用商店服务', '请检查应用商店服务是否正常运行，或在服务器上执行 (*) 更新系统'],
    appstore_outdated: ['应用商店服务版本过低，暂不支持在线更新', '请先在服务器上执行一次 (*)，之后即可在线更新'],
};

export default {
    components: {VMPreview},
    data() {
        return {
            stages: STAGES,

            info: null,
            checkIng: false,
            currentVersion: window.systemInfo.version,

            confirmShow: false,
            password: '',
            startIng: false,

            taskMode: false,
            task: {},
            taskId: '',
            taskLog: '',
            taskOffset: 0,
            lastFailed: null,

            pollTimer: null,
            pollFailed: false,

            nowTime: Date.now(),
            clockTimer: null,
        }
    },

    computed: {
        reasonText() {
            return (REASONS[this.info?.reason] || ['当前环境不支持在线更新'])[0];
        },

        reasonHint() {
            return (REASONS[this.info?.reason] || [null, '请在服务器上执行 (*) 更新系统'])[1];
        },

        checkedTime() {
            return $A.daytz(this.info.checked_at * 1000).format('YYYY-MM-DD HH:mm:ss');
        },

        checkTitle() {
            if (!this.info) {
                return this.checkIng ? '正在检查更新...' : '检查更新失败';
            }
            if (!this.info.supported) {
                return '暂不支持在线更新';
            }
            return this.info.has_update ? '发现系统新版本' : '系统已是最新版本';
        },

        checkStatus() {
            if (!this.info) {
                return 'is-loading';
            }
            if (!this.info.supported) {
                return 'is-unsupported';
            }
            return this.info.has_update ? 'is-update' : 'is-latest';
        },

        taskTitle() {
            switch (this.task.status) {
                case 'success':
                    return '系统更新完成';
                case 'failed':
                    return '系统更新失败';
                default:
                    return '系统更新中...';
            }
        },

        taskDuration() {
            const start = parseInt(this.task.started_at) || 0;
            if (!start) {
                return '';
            }
            const end = parseInt(this.task.finished_at) || Math.floor(this.nowTime / 1000);
            const seconds = Math.max(0, end - start);
            return `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        },

        rollbackCommand() {
            return this.task.from_commit ? `git reset --hard ${this.task.from_commit.substring(0, 12)}` : 'git reset --hard <commit>';
        },
    },

    mounted() {
        this.init();
    },

    beforeDestroy() {
        this.stopPoll();
        clearInterval(this.clockTimer);
    },

    methods: {
        async init() {
            const state = await this.fetchStatus(0).catch(_ => null);
            if (state && state.status === 'running') {
                this.enterTask(state.id);
                this.applyStatus(state);
                this.startPoll();
                return;
            }
            if (state && state.status === 'failed') {
                this.lastFailed = state;
            }
            this.check(false);
        },

        check(force) {
            this.checkIng = true;
            this.$store.dispatch("call", {
                url: 'system/update/check',
                data: {force: force ? 1 : 0},
            }).then(({data}) => {
                this.info = data;
                this.currentVersion = data.current_version || this.currentVersion;
                this.$store.state.systemNewVersion = data.has_update ? data.latest_version : null;
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.checkIng = false;
            });
        },

        onConfirmVisible(visible) {
            if (visible) {
                this.password = '';
                this.$nextTick(_ => this.$refs.password?.focus());
            }
        },

        start() {
            if (!this.password) {
                $A.messageWarning('请输入登录密码');
                return;
            }
            this.startIng = true;
            this.$store.dispatch("call", {
                url: 'system/update/start',
                method: 'post',
                data: {
                    password: this.password,
                    version: this.info?.latest_version || '',
                },
            }).then(({data}) => {
                this.confirmShow = false;
                this.lastFailed = null;
                this.enterTask(data.id);
                this.task = {
                    status: 'running',
                    stage: 'preparing',
                    from_version: data.from_version,
                    to_version: data.to_version,
                    started_at: Math.floor(Date.now() / 1000),
                };
                this.startPoll();
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.startIng = false;
            });
        },

        enterTask(id) {
            clearInterval(this.clockTimer);
            this.clockTimer = setInterval(_ => {
                this.nowTime = Date.now();
            }, 1000);
            this.taskMode = true;
            this.taskId = id || '';
            this.taskLog = '';
            this.taskOffset = 0;
            this.pollFailed = false;
        },

        fetchStatus(offset) {
            return this.$store.dispatch("call", {
                url: 'system/update/status',
                data: {offset},
                checkNetwork: false,
                networkFailureRetry: false,
            }).then(({data}) => data);
        },

        applyStatus(state) {
            // 新批次的状态文件尚未写入时仍是上一次的结果，忽略
            if (this.taskId && state.id && state.id !== this.taskId) {
                return;
            }
            const {log, offset, ...rest} = state;
            this.task = Object.assign({}, this.task, rest);
            if (log) {
                this.taskLog += log;
                this.$nextTick(_ => {
                    const el = this.$refs.log;
                    el && (el.scrollTop = el.scrollHeight);
                });
            }
            this.taskOffset = offset || this.taskOffset;
            if (state.status === 'success') {
                this.$store.state.systemNewVersion = null;
            }
        },

        startPoll() {
            this.stopPoll();
            this.pollTimer = setTimeout(async _ => {
                try {
                    const state = await this.fetchStatus(this.taskOffset);
                    this.pollFailed = false;
                    this.applyStatus(state);
                } catch (e) {
                    // php 重启期间请求失败属正常，继续等待恢复
                    this.pollFailed = true;
                }
                if (this.task.status === 'running') {
                    this.startPoll();
                } else if (this.taskOffset > 0) {
                    // 结束后补拉剩余日志
                    this.fetchStatus(this.taskOffset).then(this.applyStatus).catch(_ => {});
                }
            }, 2000);
        },

        stopPoll() {
            this.pollTimer && clearTimeout(this.pollTimer);
            this.pollTimer = null;
        },

        viewLastTask() {
            const last = this.lastFailed;
            this.enterTask(last.id);
            this.task = Object.assign({}, last);
            this.fetchStatus(0).then(this.applyStatus).catch(_ => {});
        },

        backToCheck() {
            this.stopPoll();
            clearInterval(this.clockTimer);
            this.taskMode = false;
            this.task = {};
            this.check(false);
        },

        stageClass(key) {
            const index = STAGES.findIndex(item => item.key === key);
            const current = this.task.stage === 'done' ? STAGES.length : STAGES.findIndex(item => item.key === this.task.stage);
            if (index < current) {
                return 'finish';
            }
            if (index === current) {
                if (this.task.status === 'failed') {
                    return 'error';
                }
                return this.task.status === 'running' ? 'process' : 'finish';
            }
            return 'wait';
        },

        reloadPage() {
            $A.reloadUrl();
        },
    }
}
</script>
