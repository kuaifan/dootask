<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
</head>
<body>

<p>用户您好， {{env('APP_NAME') }} 任务到期。</p>
<p>若需要重新设定账号密码，请点击下方链接：</p>
<div style="display: flex; justify-content: left;">
    <a href="{{$url}}">{{$url}}</a>
</div>
<p>如果连接无法点击，请复制此URL然后贴入到您浏览器的地址栏中。</p>

</body>
<body>
