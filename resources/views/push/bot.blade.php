@if ($type === '/help')
    您可以通过发送以下命令来控制我：

    @if ($manager)
        <span style="color:#84c56a">/list</span> - 机器人列表
        <span style="color:#84c56a">/newbot {机器人名称}</span> - 创建机器人
    @else
        <span style="color:#84c56a">/info</span> - 查看机器人详情
    @endif

    <b>修改机器人</b>
    <span style="color:#84c56a">/setname {机器人ID} {机器人名称}</span> - 修改机器人名称
    <span style="color:#84c56a">/deletebot {机器人ID}</span> - 删除机器人
    <span style="color:#84c56a">/clearday {机器人ID} {天数}</span> - 设置保留消息时间（默认30天）
    <span style="color:#84c56a">/webhook {机器人ID} [url]</span> - 设置消息Webhook（详情请看 <u>Api接口文档</u>）

    <b>机器人设置</b>
    <span style="color:#84c56a">/token {机器人ID}</span> - 生成Token令牌
    <span style="color:#84c56a">/revoke {机器人ID}</span> - 撤销机器人Token令牌

    <b>会话管理</b>
    <span style="color:#84c56a">/dialog {机器人ID} [搜索关键词]</span> - 查看会话ID

    <b>Api接口文档</b>
    <span style="color:#84c56a">/api</span> - 查看接口列表
@elseif ($type === '/list')
    <b>我的机器人。</b>

    <b>ID | 名称 | 清理时间 | Webhook</b>
    @foreach($data as $item)
        {{$item->userid}} | {{$item->nickname}} | {{$item->clear_day}} | {{$item->webhook_url ? '✅' : '-'}}
    @endforeach
@elseif ($type === '/info')
    <b>机器人详情。</b>

    机器人ID：<span style="color:#84c56a">{{$data->userid}}</span>
    机器人名称：<span style="color:#84c56a">{{$data->nickname}}</span>
    保留消息时间：<span style="color:#84c56a">{{$data->clear_day}}</span>
    最后一次清理时间：<span style="color:#84c56a">{{$data->clear_at ?: '-'}}</span>
    Webhook地址：<span style="color:#84c56a">{{$data->webhook_url ?: '-'}}</span>
    Webhook请求次数：<span style="color:#84c56a">{{$data->webhook_num}}</span>
@elseif ($type === '/newbot')
    <b>创建成功。</b>

    机器人ID：<span style="color:#84c56a">{{$data->userid}}</span>
    机器人名称：<span style="color:#84c56a">{{$data->nickname}}</span>
@elseif ($type === '/setname')
    <b>设置名称成功。</b>

    机器人ID：<span style="color:#84c56a">{{$data->userid}}</span>
    机器人名称：<span style="color:#84c56a">{{$data->nickname}}</span>
@elseif ($type === '/deletebot')
    <b>删除成功。</b>

    机器人ID：<span style="color:#84c56a">{{$data->userid}}</span>
    机器人名称：<span style="color:#84c56a">{{$data->nickname}}</span>
@elseif ($type === '/token')
    <b>生成Token令牌。</b>

    机器人ID：<span style="color:#84c56a">{{$data->userid}}</span>
    机器人名称：<span style="color:#84c56a">{{$data->nickname}}</span>
    Token：<span style="color:#84c56a">{{$data->token}}</span>
@elseif ($type === '/revoke')
    <b>撤销机器人Token令牌。</b>

    机器人ID：<span style="color:#84c56a">{{$data->userid}}</span>
    机器人名称：<span style="color:#84c56a">{{$data->nickname}}</span>
@elseif ($type === '/webhook')
    <b>设置Webhook地址。</b>

    机器人ID：<span style="color:#84c56a">{{$data->userid}}</span>
    机器人名称：<span style="color:#84c56a">{{$data->nickname}}</span>
    Webhook地址：<span style="color:#84c56a">{{$data->webhook_url}}</span>
@elseif ($type === '/clearday')
    <b>设置保留消息时间。</b>

    机器人ID：<span style="color:#84c56a">{{$data->userid}}</span>
    机器人名称：<span style="color:#84c56a">{{$data->nickname}}</span>
    清理周期：<span style="color:#84c56a">{{$data->clear_day}}天</span>
    下次清理：<span style="color:#84c56a">{{$data->clear_at}}</span>
@elseif ($type === '/dialog')
    <b>机器人 <span style="color:#84c56a">{{$data->nickname}} (ID:{{$data->userid}})</span> 已加入的会话：</b>

    <b>会话ID | 会话名称</b>
    @foreach($data->list as $item)
        {{$item->id}} | {{$item->name}}{{$item->type == 'user' ? ' (个人)' : ''}}
    @endforeach
@elseif ($type === '/api')
    你可以通过执行以下命令来请求我:

    <b>发送文本消息：</b>
    curl --request POST '{{url('api/dialog/msg/sendtext')}}' \
    --header 'version: {{ $version }}' \
    --header 'token: <span style="color:#84c56a">{机器人Token}</span>' \
    --form 'dialog_id="<span style="color:#84c56a">{对话ID}</span>"' \
    --form 'text="<span style="color:#84c56a">{消息内容}</span>"'
    --form 'text_type="<span style="color:#84c56a">[html|md]</span>"'
    --form 'silence="<span style="color:#84c56a">[yes|no]</span>"'
    --form 'reply_id="<span style="color:#84c56a">[回复指定消息ID]</span>"'

    <b>Webhook说明：</b>
    机器人收到消息后会将消息POST推送到Webhook地址，请求超时为10秒，请求参数如下：
    <span style="color:#84c56a">text</span>: 消息文本
    <span style="color:#84c56a">token</span>: 机器人Token
    <span style="color:#84c56a">dialog_id</span>: 对话ID
    <span style="color:#84c56a">dialog_type</span>: 对话类型
    <span style="color:#84c56a">msg_id</span>: 消息ID
    <span style="color:#84c56a">msg_uid</span>: 消息发送人ID
    <span style="color:#84c56a">mention</span>: 是否被@到
    <span style="color:#84c56a">bot_uid</span>: 机器人ID
    <span style="color:#84c56a">version</span>: 系统版本
@elseif ($type === 'notice')
    {{$notice}}
@elseif ($type === 'approve_reviewer')
    <span class="open-approve-details" data-id="{{$data->id}}"><b>{{$data->nickname}}提交的「{{$data->proc_def_name}}」待你审批</b>
    <div class="cause"><span>申请人：<span style="color:#84c56a">{{'@'}}{{$data->nickname}}</span> {{$data->department}}</span>
        <b>详情</b>
        @if ($data->type)
        <span>假期类型：{{$data->type}}</span>
        @endif
        <span>开始时间：{{$data->start_time}} ({{ $data->start_day_of_week }})</span>
        <span>结束时间：{{$data->end_time}} ({{ $data->end_day_of_week }})</span>
        <span>事由：{{$data->description}}</span>
    </div><div class="btn-raw no-dark-content">
        @if ($action === 'pass')
            <button class="ivu-btn ivu-btn-grey">已同意</button>
        @elseif ($action === 'refuse')
            <button class="ivu-btn ivu-btn-grey">已拒绝</button>
        @elseif ($action === 'withdraw')
            <button class="ivu-btn ivu-btn-grey">已撤销</button>
        @else
            <button class="ivu-btn ivu-btn-primary">同意</button>
            <button class="ivu-btn ivu-btn-error">拒绝</button>
        @endif
    </div></span>
@elseif ($type === 'approve_notifier')
    <span class="open-approve-details" data-id="{{$data->id}}"><b>抄送{{$data->nickname}}提交的「{{$data->proc_def_name}}」记录</b>
    <div class="cause"><span>申请人：<span style="color:#84c56a">{{'@'}}{{$data->nickname}}</span> {{$data->department}}</span>
        <b>详情</b>
        @if ($data->type)
        <span>假期类型：{{$data->type}}</span>
        @endif
        <span>开始时间：{{$data->start_time}} ({{ $data->start_day_of_week }})</span>
        <span>结束时间：{{$data->end_time}} ({{ $data->end_day_of_week }})</span>
        <span>事由：{{$data->description}}</span>
    </div><div class="btn-raw no-dark-content">
        @if ($is_finished == 1)
        <button class="ivu-btn ivu-btn-grey">已同意</button>
        @else
        <button class="ivu-btn ivu-btn-grey">查看详情</button>
        @endif
    </div></span>
@elseif ($type === 'approve_comment_notifier')
    <span class="open-approve-details" data-id="{{$data->id}}"><b>{{$data->comment_nickname}}评论了此审批</b>
    <div class="cause"><span>申请人：<span style="color:#84c56a">{{'@'}}{{$data->nickname}}</span> {{$data->department}}</span>
        <b>评论内容</b>
        <span>{{$data->comment_content}}</span>
        @if ($data->comment_pictures)
        <span>[图片]</span>
        @endif
    </div><div class="btn-raw no-dark-content">
        @if ($is_finished == 1)
        <button class="ivu-btn ivu-btn-grey">已同意</button>
        @else
        <button class="ivu-btn ivu-btn-grey">查看详情</button>
        @endif
    </div></span>
@elseif ($type === 'approve_submitter')
    <span class="open-approve-details" data-id="{{$data->id}}"><b>@if ($action === 'pass')您发起的「{{$data->proc_def_name}}」已通过 @else您发起的「{{$data->proc_def_name}}」被{{$data->nickname}}拒绝 @endif</b>
    <div class="cause"><span>申请人：<span style="color:#84c56a">{{'@'}}{{$data->start_nickname}}</span> {{$data->department}}</span>
        <b>详情</b>
        @if ($data->type)
        <span>假期类型：{{$data->type}}</span>
        @endif
        <span>开始时间：{{$data->start_time}} ({{ $data->start_day_of_week }})</span>
        <span>结束时间：{{$data->end_time}} ({{ $data->end_day_of_week }})</span>
        <span>事由：{{$data->description}}</span>
    </div><div class="btn-raw no-dark-content">
    @if ($action === 'pass')
            <button class="ivu-btn ivu-btn-grey">已同意</button>
        @elseif ($action === 'refuse')
            <button class="ivu-btn ivu-btn-grey">已拒绝</button>
        @elseif ($action === 'withdraw')
            <button class="ivu-btn ivu-btn-grey">已撤销</button>
        @endif
    </div></span>
@else
    你好，我是你的机器人助理，你可以发送 <span style="color:#84c56a">/help</span> 查看帮助菜单。
@endif
