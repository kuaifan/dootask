<template>
    <div class="content-text content-word-vote no-dark-content">
        <div class="vote-msg-head">
            <i class="taskfont">&#xe7fd;</i>
            <em>{{ $L('投票') }}</em>
            <span>{{ msg.multiple == 1 ? $L('多选') : $L('单选') }}</span>
            <span>{{ msg.anonymous == 1 ? $L('匿名') : $L('实名') }}</span>
        </div>
        <pre v-html="$A.formatTextMsg(msg.text, userId)"></pre>
        <template v-if="(msg.votes || []).filter(h=>h.userid == userId).length == 0">
            <RadioGroup v-if="msg.multiple == 0" v-model="voteData[msg.uuid]" vertical>
                <Radio v-for="(item,index) in (msg.list || [])" :label="item.id" :key="index">
                    {{ item.text }}
                </Radio>
            </RadioGroup>
            <CheckboxGroup v-else v-model="voteData[msg.uuid]">
                <Checkbox v-for="(item,index) in (msg.list || [])" :label="item.id" :key="index">
                    {{ item.text }}
                </Checkbox>
            </CheckboxGroup>
            <div class="btn-row">
                <Button v-if="(voteData[msg.uuid] || []).length == 0" class="ivu-btn-grey" disabled>{{ $L("请选择后投票") }}</Button>
                <Button v-else type="warning" :loading="msg._loadIng > 0" class="no-dark-content" @click="onVote('vote')">{{ $L("立即投票") }}</Button>
            </div>
        </template>
        <template v-else>
            <div class="vote-result-body">
                <ul>
                    <li v-for="item in (msg.list || [])">
                        <div class="vote-option-title">{{ item.text }}</div>
                        <div class="ticket-num">
                            <span>{{ getVoteProgress(msg, item.id).num }}{{ $L('票') }}</span>
                            <span>{{ getVoteProgress(msg, item.id).progress + '%' }}</span>
                        </div>
                        <Progress :percent="Number(getVoteProgress(msg,item.id).progress)" :stroke-width="5" hide-info/>
                        <div v-if="msg.anonymous == 0" class="avatar-row">
                            <template v-for="votes in (msg.votes || []).filter(h=>h.votes.indexOf(item.id) != -1)">
                                <UserAvatar :userid="votes.userid" :size="18"/>
                            </template>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="btn-row" v-if="msg.state == 1 && msg.userid == userId">
                <Button type="warning" :loading="msg._loadIng > 0" @click="onVote('again')">{{ $L("再次发送") }}</Button>
                <Button type="warning" :loading="msg._loadIng > 0" @click="onVote('finish')">{{ $L("结束投票") }}</Button>
            </div>
        </template>
    </div>
</template>

<script>
export default {
    props: {
        msg: Object,
        voteData: Object,
    },
    methods: {
        getVoteProgress(data, id) {
            const num = data.votes.filter(h => (h.votes || '').indexOf(id) != -1).length
            const progress = !num ? '0.00' : (num / data.votes.length * 100).toFixed(2);
            return {num, progress};
        },
        onVote(type) {
            this.$emit('onVote', type);
        },
    },
}
</script>
