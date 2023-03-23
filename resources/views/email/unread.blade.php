@if ($type === 'head')
    <table style="box-sizing:border-box;border-spacing:0;border-collapse:collapse;padding:0;vertical-align:top;text-align:left;margin:0 auto 24px;width:580px">
        <tbody>
        <tr style="box-sizing:border-box;padding:0;vertical-align:top;text-align:left">
            <td style="box-sizing:border-box;word-break:break-word;border-collapse:collapse;padding:0 0 10px;vertical-align:top;text-align:left;color:#202020;font-weight:normal;margin:0;line-height:19px;font-size:14px">
                <p>{{ $nickname }} 您好：</p>
                <p>您有（{{ $count }}）条未读{{ $msgType }}消息，请及时处理。</p>
                <hr style='box-sizing:border-box;color:#d9d9d9;background-color:#d9d9d9;height:1px;border:none;margin-top:32px'>
            </td>
        </tr>
        </tbody>
    </table>
@else
    <table style="box-sizing:border-box;border-spacing:0;border-collapse:collapse;padding:0;vertical-align:top;text-align:left;margin:0 auto;width:580px">
        <tbody>
        <tr style="box-sizing:border-box;padding:0;vertical-align:top;text-align:left">
            <td style="box-sizing:border-box;word-break:break-word;border-collapse:collapse;padding:0 0 10px;vertical-align:top;text-align:left;color:#202020;font-weight:normal;margin:0;line-height:19px;font-size:14px">
                <h2 style="box-sizing:border-box;color:#202020;font-weight:normal;padding:0 0 8px 0;margin:0;text-align:left;line-height:1.3;word-break:normal;font-size:20px">
                    {{ $dialogName }}
                </h2>
                <h4 style="box-sizing:border-box;color:#202020;font-weight:normal;padding:0;margin:0;text-align:left;line-height:1.3;word-break:normal;font-size:14px">
                    {{ $unread }}条未读信息
                </h4>
                <br style="box-sizing:border-box">
                @foreach($items as $item)
                <table style="box-sizing:border-box;border-spacing:0;border-collapse:collapse;padding:0;vertical-align:top;text-align:left;margin-top:4px">
                    <tbody>
                    <tr style="box-sizing:border-box;padding:0;vertical-align:top;text-align:left">
                        <td style="box-sizing:border-box;word-break:break-word;border-collapse:collapse;vertical-align:middle;text-align:left;padding: 0 10px 10px 0;min-width:24px;color:#202020;font-weight:normal;margin:0;line-height:19px;font-size:14px;border-radius:4px">
                            <img data-imagetype="External" src="{{$item->userInfo?->userimg}}" style="box-sizing:border-box;outline:none;text-decoration:none;width:24px;max-width:100%;float:left;clear:both;display:block;border-radius:4px;height:24px">
                        </td>
                        <td style="box-sizing:border-box;word-break:break-word;border-collapse:collapse;padding:0 0 10px;vertical-align:middle;text-align:left;color:#202020;font-weight:normal;margin:0;line-height:19px;font-size:14px">
                            <strong style="box-sizing:border-box">
                                {{$item->userInfo?->nickname}}
                            </strong>
                        </td>
                    </tr>
                    <tr style="box-sizing:border-box;padding:0;vertical-align:top;text-align:left">
                        <td style="box-sizing:border-box;word-break:break-word;border-collapse:collapse;padding:0 0 10px;vertical-align:top;text-align:left;color:#202020;font-weight:normal;margin:0;line-height:19px;font-size:14px"></td>
                        <td style="box-sizing:border-box;word-break:break-word;border-collapse:collapse;padding:0 0 10px;vertical-align:top;text-align:left;color:#202020;font-weight:normal;margin:0;line-height:19px;font-size:14px">
                            {!! $item->preview !!}
                            <div style="font-size:12px;opacity:0.3;padding-top:4px">{{ $item->created_at }}</div>
                        </td>
                    </tr>
                    </tbody>
                </table>
                @endforeach
                @if (!empty($dialogUrl))
                    <br style="box-sizing:border-box">
                    <table style="box-sizing:border-box; border-spacing:0; border-collapse:collapse; padding:0; vertical-align:top; text-align:left">
                        <tbody>
                        <tr style="box-sizing:border-box; padding:0; vertical-align:top; text-align:left">
                            <td style="box-sizing:border-box; word-break:break-word; border-collapse:collapse; padding:0 0 10px; vertical-align:top; text-align:left; color:#202020; font-weight:normal; margin:0; line-height:19px; font-size:14px">
                                <a style="text-decoration:none; box-sizing:border-box; color:white; background-color:#46bc99; padding:8px 16px; border-radius:4px; font-size:10px; text-transform:uppercase; font-weight:bold" href="{{ $dialogUrl }}" target="_blank">回复消息</a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                @endif
                <br style="box-sizing:border-box">
                <hr style="box-sizing:border-box;color:#d9d9d9;background-color:#d9d9d9;height:1px;border:none">
                <br style="box-sizing:border-box">
            </td>
        </tr>
        </tbody>
    </table>
@endif
