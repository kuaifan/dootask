<table style="border-collapse: collapse; width: 100%;" border="1">
    <thead>
    <tr>
        @foreach($labels as $label)
            <th><strong>{{ $label }}</strong></th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @forelse($datas as $data)
        <tr>
            @foreach($data as $item)
                <td>{!! $item !!}</td>
            @endforeach
        </tr>
    @empty
        <tr>
            @foreach($labels as $label)
                <td>&nbsp;</td>
            @endforeach
        </tr>
    @endforelse
    </tbody>
</table>

