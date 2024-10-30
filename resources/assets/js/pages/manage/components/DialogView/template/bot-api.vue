<template>
    <div>
        <p>{{$L("你可以通过执行以下命令来请求我")}}:</p>
        <p>&nbsp;</p>

        <p><b>{{$L("发送文本消息")}}：</b></p>
        <p>curl --request POST '{{ $A.apiUrl('dialog/msg/sendtext') }}' \</p>
        <p>--header 'version: {{ msg.version }}' \</p>
        <p>--header 'token: <span class="mark-color">{{$L("机器人Token")}}</span>' \</p>
        <p>--form 'dialog_id="<span class="mark-color">{{$L("对话ID")}}</span>"' \</p>
        <p>--form 'text="<span class="mark-color">{{$L("消息内容")}}</span>"'</p>
        <p>--form 'text_type="<span class="mark-color">[html|md]</span>"'</p>
        <p>--form 'key="<span class="mark-color">{{$L("搜索词 (留空自动生成)")}}</span>"'</p>
        <p>--form 'silence="<span class="mark-color">[yes|no]</span>"'</p>
        <p>--form 'reply_id="<span class="mark-color">{{$L("回复指定消息ID")}}</span>"'</p>
        <p>&nbsp;</p>

        <p><b>{{$L("Webhook说明")}}：</b></p>
        <p>{{$L("机器人收到消息后会将消息POST推送到Webhook地址，请求超时为10秒，请求参数如下")}}：</p>
        <p><span class="mark-color">text</span>: {{$L("消息文本")}}</p>
        <p><span class="mark-color">token</span>: {{$L("机器人Token")}}</p>
        <p><span class="mark-color">dialog_id</span>: {{$L("对话ID")}}</p>
        <p><span class="mark-color">dialog_type</span>: {{$L("对话类型")}}</p>
        <p><span class="mark-color">msg_id</span>: {{$L("消息ID")}}</p>
        <p><span class="mark-color">msg_uid</span>: {{$L("消息发送人ID")}}</p>
        <p><span class="mark-color">mention</span>: {{$L("是否被@到")}}</p>
        <p><span class="mark-color">bot_uid</span>: {{$L("机器人ID")}}</p>
        <p><span class="mark-color">version</span>: {{$L("系统版本")}}</p>
    </div>
</template>

<script>
export default {
    props: {
        msg: Object,
    },
    data() {
        return {};
    },
    computed: {},
    methods: {},
}
</script>
