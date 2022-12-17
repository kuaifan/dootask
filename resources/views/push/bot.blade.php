@if ($type === '/help')
    您可以通过发送以下命令来控制我：

    <span style="color:#84c56a">/list</span> - 机器人列表
    <span style="color:#84c56a">/newbot {机器人名称}</span> - 创建机器人

    <b>修改机器人</b>
    <span style="color:#84c56a">/setname {机器人ID} {机器人名称}</span> - 修改机器人名称
    <span style="color:#84c56a">/deletebot {机器人ID}</span> - 删除机器人

    <b>机器人设置</b>
    <span style="color:#84c56a">/token {机器人ID}</span> - 生成Token令牌
    <span style="color:#84c56a">/revoke {机器人ID}</span> - 撤销机器人Token令牌

    <b>会话管理</b>
    <span style="color:#84c56a">/dialog {机器人ID} [搜索关键词]</span> - 查看会话ID

    <b>Api接口文档</b>
    <span style="color:#84c56a">/api</span> - 查看接口列表
@elseif ($type === '/list')
    <b>我的机器人。</b>

    <b>机器人ID | 机器人名称</b>
    @foreach($data as $item)
        {{$item->userid}} | {{$item->nickname}}
    @endforeach
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
@elseif ($type === '/dialog')
    <b>机器人 <span style="color:#84c56a">{{$data->nickname}} (ID:{{$data->userid}})</span> 已加入的会话：</b>

    <b>会话ID | 会话名称</b>
    @foreach($data->list as $item)
        {{$item->id}} | {{$item->name}}{{$item->type == 'user' ? ' (个人)' : ''}}
    @endforeach
@elseif ($type === '/api')
    你可以通过执行以下命令来请求我:

    <b>发送文本消息</b>
    curl --request POST '{{url('api/dialog/msg/sendtext')}}' \
    --header 'version: {{ $version }}' \
    --header 'token: <span style="color:#84c56a">{机器人Token}</span>' \
    --form 'dialog_id="<span style="color:#84c56a">{对话ID}</span>"' \
    --form 'text="<span style="color:#84c56a">{消息内容}</span>"'
@elseif ($type === 'notice')
    {{$notice}}
@else
    你好，我是你的机器人助理，你可以发送 <span style="color:#84c56a">/help</span> 查看帮助菜单。
@endif
