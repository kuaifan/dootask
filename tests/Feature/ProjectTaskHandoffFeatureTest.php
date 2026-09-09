<?php

namespace Tests\Feature;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\ProjectTaskHandoffController;
use App\Models\Project;
use App\Models\ProjectFlow;
use App\Models\ProjectFlowItem;
use App\Models\ProjectTask;
use App\Models\ProjectTaskHandoff;
use App\Models\ProjectTaskUser;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\UserDepartment;
use App\Module\Base;
use App\Module\Interface\DooSo;
use App\Module\ProjectTaskHandoffService;
use App\Module\ProjectTaskHandoffRecord;
use App\Services\RequestContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as RequestFacade;
use Mockery;
use Tests\TestCase;

class ProjectTaskHandoffFeatureTest extends TestCase
{
    use DatabaseTransactions;

    private $originalDispatcher;
    private User $leader;
    private User $inside;
    private User $outside;
    private User $receiver;
    private Project $project;
    private ProjectTask $task;
    private UserDepartment $department;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        // Keep tests transactional and suppress unrelated Swoole/search/external hooks.
        $this->originalDispatcher = Model::getEventDispatcher();
        Model::setEventDispatcher(new Dispatcher(app()));
        RequestContext::clean();
        $this->leader = $this->user('leader');
        $this->inside = $this->user('inside');
        $this->outside = $this->user('outside');
        $this->receiver = $this->user('receiver');
        $this->login($this->leader);
        $this->department = UserDepartment::createInstance(['name' => 'Handoff test', 'parent_id' => 0, 'owner_userid' => $this->leader->userid]);
        $this->department->save();
        foreach ([$this->inside, $this->receiver] as $user) {
            $user->department = ',' . $this->department->id . ',';
            $user->save();
        }
        $this->project = Project::createInstance(['name' => 'Handoff test', 'department_owner_view' => 'open']);
        $this->project->save();
        foreach ([$this->inside, $this->outside, $this->receiver] as $user) {
            ProjectUser::createInstance(['project_id' => $this->project->id, 'userid' => $user->userid, 'owner' => $user === $this->outside ? 1 : 0])->save();
        }
        $this->task = ProjectTask::createInstance([
            'project_id' => $this->project->id, 'parent_id' => 0, 'name' => 'Handoff test',
            'userid' => $this->outside->userid, 'visibility' => 1, 'dialog_id' => 0,
        ]);
        $this->task->save();
        foreach ([$this->inside, $this->outside] as $user) {
            ProjectTaskUser::createInstance([
                'project_id' => $this->project->id, 'task_id' => $this->task->id,
                'task_pid' => $this->task->id, 'userid' => $user->userid, 'owner' => 1,
            ])->save();
        }
        ProjectTask::updated(fn ($task) => ProjectTaskHandoffRecord::updated($task));
        Base::setting('system', [
            'project_task_handoff' => 'open', 'project_task_handoff_role' => 'owner',
            'project_task_handoff_candidates' => 'department', 'project_task_handoff_adjust' => 'department',
            'project_task_handoff_note' => 'optional', 'department_owner_project_view' => 'open',
        ], true);
    }

    protected function tearDown(): void
    {
        RequestContext::clean();
        Model::setEventDispatcher($this->originalDispatcher);
        parent::tearDown();
    }

    private function user(string $name): User
    {
        $user = User::createInstance(['email' => uniqid('handoff_' . $name) . '@test.local', 'nickname' => $name, 'identity' => ['normal'], 'bot' => 0]);
        $user->save();
        return $user;
    }

    private function login(User $user): void
    {
        RequestContext::clean();
        $doo = Mockery::mock(DooSo::class);
        $doo->shouldReceive('userId')->andReturn($user->userid);
        $doo->shouldReceive('translate')->andReturnUsing(fn ($value) => $value);
        RequestContext::save('doo_instance', $doo);
        RequestContext::save('auth', $user);
    }

    public function test_department_owner_candidates_and_protected_people(): void
    {
        $policy = ProjectTaskHandoffService::policy(ProjectTaskHandoffService::task($this->task->id));
        $this->assertEqualsCanonicalizing([$this->inside->userid, $this->receiver->userid], $policy['candidates']);
        $this->assertSame([$this->outside->userid], $policy['protected']);
    }

    public function test_project_wide_options_do_not_add_nonmembers(): void
    {
        Base::setting('system', ['project_task_handoff_candidates' => 'project', 'project_task_handoff_adjust' => 'all'], true);
        $policy = ProjectTaskHandoffService::policy(ProjectTaskHandoffService::task($this->task->id));
        $this->assertCount(3, $policy['candidates']);
        $this->assertNotContains($this->leader->userid, $policy['candidates']);
        $this->assertSame([], $policy['protected']);
    }

    public function test_deputy_requires_explicit_authorization(): void
    {
        $deputy = $this->user('deputy');
        DB::table('user_department_owners')->insert(['department_id' => $this->department->id, 'userid' => $deputy->userid]);
        $this->login($deputy);
        $this->expectExceptionMessage('无指派权限');
        ProjectTaskHandoffService::policy(ProjectTaskHandoffService::task($this->task->id));
    }

    public function test_authorized_deputy_can_assign(): void
    {
        $deputy = $this->user('deputy');
        DB::table('user_department_owners')->insert(['department_id' => $this->department->id, 'userid' => $deputy->userid]);
        Base::setting('system', ['project_task_handoff_role' => 'managers'], true);
        $this->login($deputy);
        $this->assertContains($this->receiver->userid, ProjectTaskHandoffService::policy(ProjectTaskHandoffService::task($this->task->id))['candidates']);
    }

    public function test_department_authority_does_not_expose_private_tasks(): void
    {
        $this->task->visibility = 2;
        $this->task->save();
        $this->expectException(ApiException::class);
        ProjectTaskHandoffService::task($this->task->id);
    }

    public function test_switch_off_denies_reads_without_deleting_records(): void
    {
        ProjectTaskHandoffRecord::created($this->task);
        Base::setting('system', ['project_task_handoff' => 'close'], true);
        $this->assertSame(1, ProjectTaskHandoff::where('task_id', $this->task->id)->count());
        $this->expectExceptionMessage('任务流转未开启');
        ProjectTaskHandoffService::task($this->task->id);
    }

    public function test_project_owner_keeps_original_permission_when_department_assignment_is_off(): void
    {
        Base::setting('system', ['project_task_handoff_role' => 'close'], true);
        $this->login($this->outside);
        $policy = ProjectTaskHandoffService::policy(ProjectTaskHandoffService::task($this->task->id));
        $this->assertCount(3, $policy['candidates']);
        $this->assertSame([], $policy['protected']);
    }

    public function test_nested_updates_form_one_event_with_note(): void
    {
        ProjectTaskHandoffRecord::track($this->task, 'assign', function () {
            ProjectTaskHandoffRecord::track($this->task, 'flow', function () {
                ProjectTaskUser::whereTaskId($this->task->id)->whereUserid($this->inside->userid)->update(['userid' => $this->receiver->userid]);
                $this->task->flow_item_id = 123;
                $this->task->flow_item_name = 'progress|Processing|';
                $this->task->save();
            });
        }, 'Instruction');
        $events = ProjectTaskHandoff::where('task_id', $this->task->id)->get();
        $this->assertCount(1, $events);
        $record = $events->first()->record;
        $this->assertSame('Instruction', $record['note']);
        $this->assertContains($this->inside->userid, $record['before']['owners']);
        $this->assertContains($this->receiver->userid, $record['after']['owners']);
        $this->assertSame(123, (int)$record['after']['flow_item_id']);
        $this->assertFalse(ProjectTaskHandoffRecord::active($this->task->id));
    }

    public function test_failed_change_rolls_back_and_clears_recording_context(): void
    {
        try {
            ProjectTaskHandoffRecord::track($this->task, 'assign', function () {
                ProjectTaskUser::whereTaskId($this->task->id)->delete();
                throw new ApiException('Test rollback');
            }, 'Instruction');
            $this->fail('Expected failure');
        } catch (ApiException $e) {
            $this->assertSame('Test rollback', $e->getMessage());
        }
        $this->assertCount(2, ProjectTaskHandoffRecord::owners($this->task->id));
        $this->assertSame(0, ProjectTaskHandoff::where('task_id', $this->task->id)->count());
        $this->assertFalse(ProjectTaskHandoffRecord::active($this->task->id));
    }

    public function test_unrelated_edits_do_not_create_records_and_bulk_archival_does(): void
    {
        $this->task->name = 'Renamed';
        $this->task->save();
        $this->assertSame(0, ProjectTaskHandoff::where('task_id', $this->task->id)->count());
        ProjectTask::whereKey($this->task->id)->change(['archived_at' => now()]);
        $this->assertSame(1, ProjectTaskHandoff::where('task_id', $this->task->id)->count());
    }

    public function test_project_exit_records_owner_removal(): void
    {
        ProjectUser::whereProjectId($this->project->id)->whereUserid($this->inside->userid)->first()->exitProject();
        $event = ProjectTaskHandoff::where('task_id', $this->task->id)->first();
        $this->assertSame('member_exit', $event->source);
        $this->assertSame([$this->outside->userid], $event->record['after']['owners']);
    }

    public function test_account_transfer_records_actual_owners(): void
    {
        ProjectTaskUser::transfer($this->inside->userid, $this->receiver->userid);
        $event = ProjectTaskHandoff::where('task_id', $this->task->id)->first();
        $this->assertSame('transfer', $event->source);
        $this->assertContains($this->receiver->userid, $event->record['after']['owners']);
    }

    public function test_new_request_does_not_inherit_context(): void
    {
        $request = request();
        RequestContext::save('handoff-isolation-test', 'first');
        $this->assertSame('first', RequestContext::get('handoff-isolation-test'));
        app()->instance('request', Request::create('/api/projecttaskhandoff/lists'));
        $this->assertNull(RequestContext::get('handoff-isolation-test'));
        RequestContext::clean();
        app()->instance('request', $request);
        $this->assertSame('first', RequestContext::get('handoff-isolation-test'));
    }

    public function test_assignment_uses_existing_owner_update_and_saves_note_atomically(): void
    {
        $task = ProjectTaskHandoffFixture::find($this->task->id);
        $options = ProjectTaskHandoffService::options(ProjectTaskHandoffService::task($task->id));
        $data = ProjectTaskHandoffService::assign($task, [$this->outside->userid, $this->receiver->userid], $options['version'], 'Please review');
        $this->assertSame($task->id, $data['id']);
        $event = ProjectTaskHandoff::where('task_id', $task->id)->sole();
        $this->assertSame('assign', $event->source);
        $this->assertSame('Please review', $event->record['note']);
        $this->assertEqualsCanonicalizing([$this->outside->userid, $this->receiver->userid], ProjectTaskHandoffRecord::owners($task->id));
        $this->expectExceptionMessage('负责人已发生变化，请刷新后重试');
        ProjectTaskHandoffService::assign($task, [$this->outside->userid, $this->inside->userid], $options['version'], 'Stale');
    }

    public function test_empty_optional_note_passes_controller_validation_after_middleware(): void
    {
        foreach ([[], ['note' => ''], ['note' => '   '], ['note' => null]] as $note) {
            $this->assertAssignmentRequestMessage($note, 'stale', '负责人已发生变化，请刷新后重试');
        }
    }

    public function test_empty_required_note_still_requires_instruction(): void
    {
        Base::setting('system', ['project_task_handoff_note' => 'required'], true);
        $version = ProjectTaskHandoffService::version($this->task);
        $this->assertAssignmentRequestMessage(['note' => ''], $version, '请填写指派留言');
    }

    public function test_non_string_note_is_still_rejected(): void
    {
        foreach ([[], 123, false] as $note) {
            $this->assertAssignmentRequestMessage(['note' => $note], 'stale', '指派参数无效');
        }
    }

    private function assertAssignmentRequestMessage(array $note, string $version, string $message): void
    {
        $original = request();
        $request = Request::create('/api/projecttaskhandoff/assign', 'POST', [], [], [],
            ['CONTENT_TYPE' => 'OPTIONS, application/json'], json_encode(array_merge([
                'task_id' => $this->task->id,
                'owners' => [$this->outside->userid, $this->receiver->userid],
                'version' => $version,
            ], $note)));
        try {
            $result = (new Pipeline(app()))->send($request)->through([
                TrimStrings::class,
                ConvertEmptyStringsToNull::class,
            ])->then(function ($request) {
                RequestFacade::swap($request);
                $this->login($this->leader);
                return (new ProjectTaskHandoffController())->assign();
            });
            $this->assertSame(0, $result['ret']);
            $this->assertSame($message, $result['msg']);
        } catch (ApiException $e) {
            $this->assertSame($message, $e->getMessage());
        } finally {
            RequestFacade::swap($original);
            $this->login($this->leader);
        }
    }

    public function test_original_owner_edit_is_recorded_with_feature_disabled(): void
    {
        Base::setting('system', ['project_task_handoff' => 'close'], true);
        $task = ProjectTaskHandoffFixture::find($this->task->id);
        $task->updateTask(['owner' => [$this->outside->userid, $this->receiver->userid]]);
        $event = ProjectTaskHandoff::where('task_id', $task->id)->sole();
        $this->assertSame('update', $event->source);
        $this->assertSame('', $event->record['note']);
    }

    public function test_archived_task_cannot_be_assigned(): void
    {
        $this->task->archived_at = now();
        $this->task->save();
        $this->expectExceptionMessage('当前任务不可指派');
        ProjectTaskHandoffService::policy(ProjectTaskHandoffService::task($this->task->id));
    }

    public function test_closing_project_department_view_revokes_access(): void
    {
        $this->project->department_owner_view = 'close';
        $this->project->save();
        $this->expectException(ApiException::class);
        ProjectTaskHandoffService::task($this->task->id);
    }

    public function test_workflow_auto_assignment_is_one_record(): void
    {
        $flow = ProjectFlow::createInstance(['project_id' => $this->project->id, 'name' => 'Test flow']);
        $flow->save();
        $node = ProjectFlowItem::createInstance([
            'project_id' => $this->project->id, 'flow_id' => $flow->id,
            'name' => 'Processing', 'status' => 'progress', 'usertype' => 'replace',
            'userids' => [$this->receiver->userid],
        ]);
        $node->save();
        $task = ProjectTaskHandoffFixture::find($this->task->id);
        $task->updateTask(['flow_item_id' => $node->id]);
        $event = ProjectTaskHandoff::where('task_id', $task->id)->sole();
        $this->assertSame('flow', $event->source);
        $this->assertSame([$this->receiver->userid], $event->record['after']['owners']);
        $this->assertSame($node->id, (int)$event->record['after']['flow_item_id']);
    }

    public function test_copy_starts_its_own_history(): void
    {
        ProjectTaskHandoffRecord::created($this->task);
        $copy = $this->task->copyTask();
        $event = ProjectTaskHandoff::where('task_id', $copy->id)->sole();
        $this->assertSame('copy', $event->source);
        $this->assertNull($event->record['before']);
        $this->assertSame(ProjectTaskHandoffRecord::owners($this->task->id), $event->record['after']['owners']);
    }

    public function test_account_transfer_still_handles_deleted_tasks(): void
    {
        $this->task->delete();
        ProjectTaskUser::transfer($this->inside->userid, $this->receiver->userid);
        $this->assertContains($this->receiver->userid, ProjectTaskHandoffRecord::owners($this->task->id));
    }
}

// Exercise database changes while keeping notifications outside these transactional tests.
class ProjectTaskHandoffFixture extends ProjectTask
{
    protected $table = 'project_tasks';

    public function taskPush($userids, int $type, string $suffix = '') {}
    public function syncDialogUser() {}
    public function pushMsg($action, $data = null, $userid = null, $ignoreSelf = true) {}
    public function pushMsgVisibleRemove(array $userids = []) {}
}
