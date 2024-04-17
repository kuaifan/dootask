<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta name="format-detection" content="telephone=no"/>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $name }} - {{ config('app.name', 'WebPage') }}</title>
    <link rel="shortcut icon" href="{{ asset_main('favicon.ico') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-size: 14px;
        }

        .down {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #000;
            color: #fff;
            padding: 16px 18px;
            border-radius: 12px;;
        }

        .title {
            font-size: 18px;
        }

        .link {
            margin-top: 10px;
            display: block;
            height: 30px;
            line-height: 30px;
            padding: 0 12px;
            text-align: center;
            background-color: #f60;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }

        .dark {
            .down {
                background-color: #ffffff;
                color: #555;
            }
        }
    </style>
</head>
<body>
<div class="down">
    <div class="title">{{ $name }}</div>
    <a class="link" href="{{ $url }}" target="_blank">{{$button}} ({{ $size }})</a>
</div>

<script>
    let themeConf = window.localStorage.getItem("__system:themeConf__");
    if (themeConf === 'auto') {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            themeConf = 'dark';
        }
    }
    if (themeConf === 'dark') {
        document.body.classList.add("dark");
    }
    //
    const isEEUiApp = window && window.navigator && /eeui/i.test(window.navigator.userAgent);
    if (isEEUiApp) {
        document.querySelector(".link").addEventListener('click', function (e) {
            e.preventDefault();
            window.top.postMessage({
                action: "eeuiAppSendMessage",
                data: [
                    {
                        action: 'openUrl',
                        url: "{{ $url }}",
                    }
                ]
            }, "*")
        });
    }
</script>
</body>
</html>
