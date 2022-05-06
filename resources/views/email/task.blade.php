<p>{{ $user->nickname }} 您好：</p>
@if ($type === 'before')
    <p>您有一个任务【{{ $task->name }}】还有{{ $setting['task_remind_hours'] }}小时即将超时，请及时处理。</p>
@elseif ($type === 'after')
    <p>您的任务【{{ $task->name }}】已经超时{{ $setting['task_remind_hours2'] }}小时，请及时处理。</p>
@else
    <p>您有一个新任务【{{ $task->name }}】已开始，请及时处理。</p>
@endif
