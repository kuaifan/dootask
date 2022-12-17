@if ($type === 'before')
    您有一个任务 <span class="mention task" data-id="{{ $task->id }}">#{{ $task->name }}</span> {{ $setting['task_remind_hours'] > 0 ? "还有{$setting['task_remind_hours']}小时即将超时" : "已超时" }}，请及时处理。
@elseif ($type === 'after')
    您的任务 <span class="mention task" data-id="{{ $task->id }}">#{{ $task->name }}</span> 已经超时{{ $setting['task_remind_hours2'] > 0 ? "{$setting['task_remind_hours2']}小时" : "" }}，请及时处理。
@else
    您有一个新任务 <span class="mention task" data-id="{{ $task->id }}">#{{ $task->name }}</span> 已开始。
@endif
