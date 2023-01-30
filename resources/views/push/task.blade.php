@if ($type === 'before')
    您的任务 <span class="mention task" data-id="{{ $task->id }}">#{{ $task->name }}</span> 即将超时，请及时处理。
@elseif ($type === 'after')
    您的任务 <span class="mention task" data-id="{{ $task->id }}">#{{ $task->name }}</span> 已经超时，请及时处理。
@else
    您有一个新任务 <span class="mention task" data-id="{{ $task->id }}">#{{ $task->name }}</span>。
@endif
