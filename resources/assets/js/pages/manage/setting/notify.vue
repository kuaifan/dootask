<template>
    <div class="setting-item submit">
        <Tabs v-model="tabAction">
            <TabPane :label="$L('推送规则')" name="rule">
                <NotifyRule :eventTypes="eventTypes" :modeLists="modeLists"/>
            </TabPane>
            <TabPane :label="$L('参数配置')" name="config">
                <NotifyConfig :modeLists="modeLists"/>
            </TabPane>
        </Tabs>
    </div>
</template>

<script>
import NotifyRule from "./components/NotifyRule";
import NotifyConfig from "./components/NotifyConfig";

export default {
    components: {NotifyConfig, NotifyRule},
    data() {
        return {
            tabAction: 'rule',

            eventTypes: [
                {
                    event: 'taskExpireBefore',
                    label: '任务到期前',
                    vars: ['name', 'hour'],
                    example: '您有一个任务【{name}】还有{hour}小时即将超时，请及时处理'
                }, {
                    event: 'taskExpireAfter',
                    label: '任务超期后',
                    vars: ['name', 'hour'],
                    example: '您的任务【{name}】已经超时{hour}小时，请及时处理'
                }, {
                    event: 'taskStateEnd',
                    label: '任务状态修改（结束状态）',
                    vars: ['name', 'oldState', 'newState'],
                    example: '您的任务【{name}】已结束，状态：{newState}'
                }, {
                    event: 'taskStateChange',
                    label: '任务状态修改（不含结束状态）',
                    vars: ['name', 'oldState', 'newState'],
                    example: '您的任务【{name}】状态发生改变，状态：{oldState} => {newState}'
                }, {
                    event: 'taskAdd',
                    label: '添加任务',
                    vars: ['name', 'projectName', 'time'],
                    example: '收到新的任务【{name}】，来自项目：{projectName}，任务时间：{time}'
                }, {
                    event: 'taskDelete',
                    label: '删除任务',
                    vars: ['name', 'projectName'],
                    example: '您的任务【{name}】已删除，来自项目：{projectName}'
                }, {
                    event: 'taskArchived',
                    label: '任务归档',
                    vars: ['name', 'projectName'],
                    example: '您的任务【{name}】已归档，来自项目：{projectName}'
                }, {
                    event: 'taskEditTime',
                    label: '任务时间发生变化',
                    vars: ['name', 'oldTime', 'newTime'],
                    example: '您的任务【{name}】时间发生改变，原计划时间：{oldTime}，新计划时间：{newTime}'
                }, {
                    event: 'taskJoinOwner',
                    label: '加入负责人',
                    vars: ['name', 'projectName', 'time'],
                    example: '加入新的任务【{name}】，来自项目：{projectName}，任务时间：{time}'
                }, {
                    event: 'taskRemoveOwner',
                    label: '移出负责人',
                    vars: ['name', 'projectName'],
                    example: '已移出任务【{name}】负责人，来自项目：{projectName}'
                },
            ],

            modeLists: [
                {
                    mode: 'mail',
                    label: '邮件',
                    receiver: '任务负责人',
                    contentType: 'html',
                    events: [
                        'taskExpireBefore',
                        'taskExpireAfter',
                        'taskStateEnd',
                        'taskStateChange',
                        'taskAdd',
                        'taskDelete',
                        'taskArchived',
                        'taskEditTime',
                        'taskJoinOwner',
                        'taskRemoveOwner',
                    ],
                    params: [
                        {key: 'server', label: 'SMTP服务器'},
                        {key: 'port', label: '端口'},
                        {key: 'account', label: '用户名'},
                        {key: 'password', label: '密码', inputType: 'password'},
                    ],
                }, {
                    mode: 'dingding',
                    label: '钉钉群机器人',
                    receiver: '群内所有人',
                    contentType: 'md',
                    events: [
                        'taskExpireBefore',
                        'taskExpireAfter',
                        'taskStateEnd',
                        'taskStateChange',
                        'taskAdd',
                        'taskDelete',
                        'taskArchived',
                        'taskEditTime',
                        'taskJoinOwner',
                        'taskRemoveOwner',
                    ],
                    params: [
                        {key: 'token', label: 'Token'},
                        {key: 'secret', label: 'Secret'},
                    ],
                }, {
                    mode: 'feishu',
                    label: '飞书群机器人',
                    receiver: '群内所有人',
                    contentType: 'md',
                    events: [
                        'taskExpireBefore',
                        'taskExpireAfter',
                        'taskStateEnd',
                        'taskStateChange',
                        'taskAdd',
                        'taskDelete',
                        'taskArchived',
                        'taskEditTime',
                        'taskJoinOwner',
                        'taskRemoveOwner',
                    ],
                    params: [
                        {key: 'token', label: 'Token'},
                        {key: 'secret', label: 'Secret'},
                    ],
                }, {
                    mode: 'wework',
                    label: '企业微信群机器人',
                    receiver: '群内所有人',
                    contentType: 'md',
                    events: [
                        'taskExpireBefore',
                        'taskExpireAfter',
                        'taskStateEnd',
                        'taskStateChange',
                        'taskAdd',
                        'taskDelete',
                        'taskArchived',
                        'taskEditTime',
                        'taskJoinOwner',
                        'taskRemoveOwner',
                    ],
                    params: [
                        {key: 'token', label: 'Token'},
                    ],
                }, {
                    mode: 'xizhi',
                    label: '息知',
                    receiver: 'Token主人',
                    contentType: 'text',
                    events: [
                        'taskExpireBefore',
                        'taskExpireAfter',
                        'taskStateEnd',
                        'taskStateChange',
                        'taskAdd',
                        'taskDelete',
                        'taskArchived',
                        'taskEditTime',
                        'taskJoinOwner',
                        'taskRemoveOwner',
                    ],
                    params: [
                        {key: 'token', label: 'Token'},
                    ],
                }, {
                    mode: 'telegram',
                    label: 'Telegram',
                    receiver: '任务负责人',
                    contentType: 'md',
                    events: [
                        'taskExpireBefore',
                        'taskExpireAfter',
                        'taskStateEnd',
                        'taskStateChange',
                        'taskAdd',
                        'taskDelete',
                        'taskArchived',
                        'taskEditTime',
                        'taskJoinOwner',
                        'taskRemoveOwner',
                    ],
                    params: [
                        {key: 'token', label: 'Token'},
                    ],
                }, {
                    mode: 'gitter',
                    label: 'Gitter',
                    receiver: '房间内所有人',
                    contentType: 'text',
                    events: [
                        'taskExpireBefore',
                        'taskExpireAfter',
                        'taskStateEnd',
                        'taskStateChange',
                        'taskAdd',
                        'taskDelete',
                        'taskArchived',
                        'taskEditTime',
                        'taskJoinOwner',
                        'taskRemoveOwner',
                    ],
                    params: [
                        {key: 'token', label: 'Token'},
                        {key: 'roomid', label: 'RoomId'},
                    ],
                }, {
                    mode: 'googlechat',
                    label: 'Google Chat',
                    receiver: '群内所有人',
                    contentType: 'text',
                    events: [
                        'taskExpireBefore',
                        'taskExpireAfter',
                        'taskStateEnd',
                        'taskStateChange',
                        'taskAdd',
                        'taskDelete',
                        'taskArchived',
                        'taskEditTime',
                        'taskJoinOwner',
                        'taskRemoveOwner',
                    ],
                    params: [
                        {key: 'token', label: 'Token'},
                        {key: 'key', label: 'Key'},
                        {key: 'space', label: 'Space'},
                    ],
                }, {
                    mode: 'webhook',
                    label: 'Webhook',
                    receiver: null,
                    contentType: 'json',
                    events: [
                        'taskExpireBefore',
                        'taskExpireAfter',
                        'taskStateEnd',
                        'taskStateChange',
                        'taskAdd',
                        'taskDelete',
                        'taskArchived',
                        'taskEditTime',
                        'taskJoinOwner',
                        'taskRemoveOwner',
                    ],
                    params: [],
                }
            ]
        }
    },
}
</script>
