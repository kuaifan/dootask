<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
    <meta name="renderer" content="webkit">
    <meta name="format-detection" content="telephone=no" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'WebPage') }}</title>
    <link rel="shortcut icon" href="{{ asset_main('favicon.ico') }}">
    <link rel="stylesheet" type="text/css" href="{{ mix('css/app.css') }}?v={{ $version }}-{{ $hash }}">
    <link rel="stylesheet" type="text/css" href="{{ asset_main('css/iview.css') }}">
    <script src="{{ asset_main('js/language.all.js') }}"></script>
    <script src="{{ asset_main('js/scroll-into-view.min.js') }}"></script>
    <script>
        window.csrfToken = { csrfToken : "{{ csrf_token() }}" };
        window.systemInfo = { title: "{{config('app.name', 'WebPage')}}", version : "{{ $version }}", origin: window.location.origin + "/", homeUrl: null, apiUrl: null };
    </script>
</head>
<body>

@extends('ie')
<div id="app">
    <div class="app-view-loading">
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

<script src="{{ mix('js/app.js') }}?v={{ $version }}-{{ $hash }}"></script>

</body>
</html>
