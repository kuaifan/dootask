<?php

namespace App\Module;

class ProjectTaskHandoffSettings
{
    // 每项的第一个值为默认值，未配置的旧系统也使用该值。
    public const OPTIONS = [
        'project_task_handoff' => ['close', 'open'],
        'project_task_handoff_role' => ['owner', 'close', 'managers'],
        'project_task_handoff_candidates' => ['department', 'project'],
        'project_task_handoff_adjust' => ['department', 'all'],
        'project_task_handoff_note' => ['optional', 'required'],
    ];

    public static function normalize(array $settings): array
    {
        foreach (self::OPTIONS as $key => $options) {
            if (!in_array($settings[$key] ?? null, $options, true)) {
                $settings[$key] = $options[0];
            }
        }
        return $settings;
    }

    public static function get(): array
    {
        return self::normalize(Base::setting('system'));
    }
}
