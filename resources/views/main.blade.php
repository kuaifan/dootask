<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta name="format-detection" content="telephone=no" />
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $system_alias }}</title>
    <link rel="shortcut icon" href="{{ asset_main('favicon.ico') }}">
    @if($style)
        <link rel="stylesheet" type="text/css" href="{{ $style }}">
    @endif
    <link rel="stylesheet" type="text/css" href="{{ asset_main('css/iview.css') }}?v={{ $version }}">
    <link rel="stylesheet" type="text/css" href="{{ asset_main('css/loading.css') }}?v={{ $version }}">
    <script src="{{ asset_main('js/loading-theme.js') }}?v={{ $version }}"></script>
    <script>
        window.csrfToken = {
            csrfToken: "{{ csrf_token() }}"
        };
        window.systemInfo = {
            title: "{{$system_alias}}",
            debug: "{{config('app.debug') ? 'yes' : 'no'}}",
            version: "{{ $version }}",
            origin: window.location.origin + "/",
            homeUrl: null,
            apiUrl: null
        };
    </script>
</head>
<body>

@extends('ie')
<div id="app" data-preload="init">
    <div class="app-view-loading no-dark-content">
        <div>
            <div>PAGE LOADING</div>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</div>

<script>
    setTimeout(function () {
        if (document.getElementById("app")?.getAttribute("data-preload") === "false") {
            window.location.reload();
        }
    }, 6000);
</script>
<script type="module" src="{{$script}}"></script>
</body>
</html>
