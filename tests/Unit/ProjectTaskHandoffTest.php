<?php

namespace Tests\Unit;

use App\Exceptions\ApiException;
use App\Module\ProjectTaskHandoffService;
use App\Module\ProjectTaskHandoffSettings;
use PHPUnit\Framework\TestCase;

class ProjectTaskHandoffTest extends TestCase
{
    private function policy(array $changes = []): array
    {
        return array_replace([
            'owners' => [1, 2], 'protected' => [2], 'candidates' => [1, 3],
            'note_required' => false, 'version' => 'v1',
        ], $changes);
    }

    public function test_defaults_are_disabled_and_department_scoped(): void
    {
        $settings = ProjectTaskHandoffSettings::normalize([]);
        $this->assertSame('close', $settings['project_task_handoff']);
        $this->assertSame('owner', $settings['project_task_handoff_role']);
        $this->assertSame('department', $settings['project_task_handoff_candidates']);
        $this->assertSame('department', $settings['project_task_handoff_adjust']);
        $this->assertSame('optional', $settings['project_task_handoff_note']);
    }

    public function test_invalid_options_fall_back_without_losing_other_settings(): void
    {
        $settings = ProjectTaskHandoffSettings::normalize(['project_task_handoff' => true, 'project_task_handoff_role' => 'everyone', 'other' => 'kept']);
        $this->assertSame('close', $settings['project_task_handoff']);
        $this->assertSame('owner', $settings['project_task_handoff_role']);
        $this->assertSame('kept', $settings['other']);
        foreach (ProjectTaskHandoffSettings::OPTIONS as $key => $options) {
            foreach ($options as $option) {
                $this->assertSame($option, ProjectTaskHandoffSettings::normalize([$key => $option])[$key]);
            }
        }
    }

    public function test_assignment_preserves_outside_owner(): void
    {
        $this->assertSame([2, 3], ProjectTaskHandoffService::validateOwners(['3', '2', '3'], $this->policy(), 'v1', ''));
    }

    public function test_protected_owner_cannot_be_removed(): void
    {
        $this->expectExceptionMessage('不能移除管理范围外的负责人');
        ProjectTaskHandoffService::validateOwners([3], $this->policy(), 'v1', '');
    }

    public function test_candidate_must_be_authorized(): void
    {
        $this->expectExceptionMessage('所选负责人不在可指派范围内');
        ProjectTaskHandoffService::validateOwners([2, 4], $this->policy(), 'v1', '');
    }

    public function test_existing_ineligible_owner_can_be_retained(): void
    {
        $this->assertSame([1, 2, 3], ProjectTaskHandoffService::validateOwners([1, 2, 3], $this->policy(['candidates' => [3]]), 'v1', ''));
    }

    public function test_all_adjustment_allows_replacing_outside_owner(): void
    {
        $this->assertSame([3], ProjectTaskHandoffService::validateOwners([3], $this->policy(['protected' => []]), 'v1', ''));
    }

    public function test_stale_assignment_is_rejected(): void
    {
        $this->expectExceptionMessage('负责人已发生变化，请刷新后重试');
        ProjectTaskHandoffService::validateOwners([2, 3], $this->policy(), 'old', '');
    }

    public function test_comment_alone_is_not_an_assignment(): void
    {
        $this->expectExceptionMessage('负责人未发生变化');
        ProjectTaskHandoffService::validateOwners([2, 1], $this->policy(), 'v1', '留言');
    }

    public function test_required_note_is_enforced(): void
    {
        $this->expectExceptionMessage('请填写指派留言');
        ProjectTaskHandoffService::validateOwners([2, 3], $this->policy(['note_required' => true]), 'v1', '');
    }

    public function test_ten_owner_limit_includes_protected_owners(): void
    {
        $this->expectException(ApiException::class);
        ProjectTaskHandoffService::validateOwners(range(1, 11), $this->policy(['candidates' => range(1, 11)]), 'v1', '');
    }
}
