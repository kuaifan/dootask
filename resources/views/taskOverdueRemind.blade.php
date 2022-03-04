<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
</head>
<body>
<p>用户您好， {{env('APP_NAME') }} 任务到期提醒。</p>
@if ($type == 1)
    <p>
        您有一个任务【{{$name}}】还有{{$time}}小时即将超时，请及时处理
    </p>
@else
    <p>
        您的任务【{{$name}}】已经超时{{$time}}小时，请及时处理
    </p>
@endif
</body>
<body>
