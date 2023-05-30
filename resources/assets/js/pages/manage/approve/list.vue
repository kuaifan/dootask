<template>
    <div class="approve-list">
        <h2>
            <span class="list-name">{{$L(data.proc_def_name)}}</span> 
            <Tag v-if="data.state == 0" color="cyan">{{$L('待审批')}}</Tag>
            <Tag v-if="data.state == 1" color="cyan">{{$L('审批中')}}</Tag>
            <Tag v-if="data.state == 2" color="green">{{$L('已通过')}}</Tag>
            <Tag v-if="data.state == 3" color="red">{{$L('已拒绝')}}</Tag>
            <Tag v-if="data.state == 4" color="red">{{$L('已撤回')}}</Tag>
        </h2>
        <p v-if="data.var?.type">{{$L('假期类型')}}：<span>{{$L(data.var?.type)}}</span></p>
        <p>{{$L('开始时间')}}：<span>{{data.var?.start_time}}</span></p>
        <p>{{$L('结束时间')}}：<span>{{data.var?.end_time}}</span></p>
        <div class="list-member">
            <span>
                <Avatar :src="data.userimg" size="20"/>
                {{ data.start_user_name }}
            </span>
            <span>
                {{$L('发起时间')}}：{{data.start_time}}
            </span>
        </div>
    </div>
</template>

<script>
export default {
    name: "list",
    props: {
        data: {
            type: Object,
            default() {
                return {};
            }
        }
    },
    data() {
        return {

        }
    }
}


</script>

