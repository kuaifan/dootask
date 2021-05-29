<?php
$uarowser = Request::server('HTTP_USER_AGENT');
if(strstr($uarowser, 'MSIE 6') || strstr($uarowser, 'MSIE 7') || strstr($uarowser, 'MSIE 8')){
?>
<style type="text/css">
    #browser_ie {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 100000;
        background-color: #f6f6b4;
        height: auto;
        color: #000;
        font-size: 15px
    }

    #browser_ie .brower_info {
        margin: 0 auto;
        width: 820px;
        max-width: 100%;
        padding: 15px 0;
        text-align: center;
    }

    #browser_ie .brower_info .notice_info {
        position: relative;
        display: inline-block;
    }

    #browser_ie .brower_info .notice_info p {
        text-align: left;
        line-height: 25px;
        max-width: 360px;
        display: inline-block
    }

    #browser_ie .browser_list {
        position: relative;
        display: inline-block;
    }

    #browser_ie .browser_list img {
        width: 40px;
        height: 40px
    }

    #browser_ie .browser_list span {
        text-align: center;
        width: 85px;
        vertical-align: bottom;
        margin-bottom: -8px;
        display: inline-block
    }
</style>
<div id="browser_ie">
    <div class="brower_info">
        <div class="notice_info">
            <p>你的浏览器版本过低，可能导致网站不能正常访问！<br>为了你能正常使用网站功能，请使用这些浏览器。</p></div>
        <div class="browser_list">
            <span>
                <a href="http://www.google.cn/chrome/browser/desktop/" target="_blank">
                <img src="{{ asset('images/browser/chrome.png') }}"><br>chrome
                </a>
            </span>
            <span>
                <a href="http://www.firefox.com.cn/download/" target="_blank">
                    <img src="{{ asset('images/browser/firefox.png') }}"><br>firefox
                </a>
            </span>
            <span>
                <a href="https://www.apple.com/cn/safari/" target="_blank">
                    <img src="{{ asset('images/browser/safari.png') }}"><br>safari
                </a>
            </span>
            <span>
                <a href="http://chrome.360.cn/" target="_blank">
                    <img src="{{ asset('images/browser/360.png') }}"><br>360浏览器
                </a>
            </span>
            <span>
                <a href="http://ie.microsoft.com/" target="_blank">
                    <img src="{{ asset('images/browser/ie.png') }}"><br>ie9及以上
                </a>
            </span>
        </div>
    </div>
</div>
<?php
}
?>
