<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
</head>
<body>

您好，您正在绑定 {{env('APP_NAME') }} 的邮箱，请于24小时之内点击该链接完成验证 :
<div style="display: flex; justify-content: center;">
    <a href="{{$url}}">{{$url}}</a>
</div>

</body>
<body>
