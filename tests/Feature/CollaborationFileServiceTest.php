<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskUser;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use App\Exceptions\ApiException;
use App\Services\CollaborationFileService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CollaborationFileServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $email): User
    {
        $user = User::createInstance([
            'email' => $email,
            'userimg' => '',
            'nickname' => 'File_' . substr(md5($email), 0, 6),
            'profession' => '',
            'password' => md5('123456'),
        ]);
        $user->save();
        return $user;
    }

    private function makeDialog(string $type, string $groupType, string $name, array $members): WebSocketDialog
    {
        $dialog = WebSocketDialog::createInstance([
            'type' => $type,
            'group_type' => $groupType,
            'name' => $name,
        ]);
        $dialog->save();
        foreach ($members as $userid) {
            WebSocketDialogUser::createInstance([
                'dialog_id' => $dialog->id,
                'userid' => $userid,
            ])->save();
        }
        return $dialog;
    }

    private function makeFile(WebSocketDialog $dialog, User $sender, string $name): WebSocketDialogMsg
    {
        $message = WebSocketDialogMsg::createInstance([
            'dialog_id' => $dialog->id,
            'userid' => $sender->userid,
            'type' => 'file',
            'key' => $name,
            'msg' => json_encode([
                'name' => $name,
                'ext' => pathinfo($name, PATHINFO_EXTENSION),
                'size' => 1024,
                'path' => 'uploads/test/' . $name,
            ], JSON_UNESCAPED_UNICODE),
        ]);
        $message->save();
        return $message;
    }

    public function test_lists_classifies_sources_and_applies_access(): void
    {
        $viewer = $this->makeUser('collaboration-viewer@test.local');
        $sender = $this->makeUser('collaboration-sender@test.local');
        $outsider = $this->makeUser('collaboration-outsider@test.local');

        $private = $this->makeDialog('user', '', '', [$viewer->userid, $sender->userid]);
        $group = $this->makeDialog('group', 'user', '普通工作群', [$viewer->userid, $sender->userid]);
        $hidden = $this->makeDialog('group', 'user', '不可见群', [$sender->userid, $outsider->userid]);

        $projectDialog = $this->makeDialog('group', 'project', '项目群', [$viewer->userid, $sender->userid]);
        $project = Project::createInstance([
            'name' => '协作文件项目',
            'desc' => '',
            'userid' => $sender->userid,
            'dialog_id' => $projectDialog->id,
            'personal' => 0,
        ]);
        $project->save();
        ProjectUser::updateInsert(
            ['project_id' => $project->id, 'userid' => $viewer->userid],
            ['owner' => 0]
        );

        $taskDialog = $this->makeDialog('group', 'task', '任务群', [$viewer->userid, $sender->userid]);
        $task = ProjectTask::createInstance([
            'project_id' => $project->id,
            'parent_id' => 0,
            'name' => '协作文件任务',
            'dialog_id' => $taskDialog->id,
            'userid' => $sender->userid,
            'visibility' => 2,
        ]);
        $task->save();
        ProjectTaskUser::createInstance([
            'project_id' => $project->id,
            'task_id' => $task->id,
            'userid' => $viewer->userid,
            'owner' => 0,
        ])->save();

        $this->makeFile($private, $sender, 'private.pdf');
        $this->makeFile($group, $sender, 'group.docx');
        $this->makeFile($projectDialog, $sender, 'project.xlsx');
        $this->makeFile($taskDialog, $sender, 'task.png');
        $this->makeFile($hidden, $sender, 'hidden.zip');

        $result = CollaborationFileService::lists($viewer, ['take' => 20]);
        $this->assertSame(['task', 'project_chat', 'group', 'private'], array_column($result['list'], 'source_type'));
        $this->assertNotContains('hidden.zip', array_column($result['list'], 'name'));
        $this->assertStringEndsWith('/uploads/test/task.png', $result['list'][0]['image_url']);
        $this->assertSame('', $result['list'][1]['image_url']);

        $conversation = CollaborationFileService::lists($viewer, [
            'scope' => 'conversation',
            'conversation_type' => 'private',
        ]);
        $this->assertSame(['private.pdf'], array_column($conversation['list'], 'name'));

        $projectFiles = CollaborationFileService::lists($viewer, [
            'scope' => 'project',
            'project_id' => $project->id,
            'project_source' => 'task',
        ]);
        $this->assertSame(['task.png'], array_column($projectFiles['list'], 'name'));

        $archivedDialog = $this->makeDialog('group', 'project', '归档项目群', [$viewer->userid, $sender->userid]);
        $archivedProject = Project::createInstance([
            'name' => '已归档协作文件项目',
            'desc' => '',
            'userid' => $sender->userid,
            'dialog_id' => $archivedDialog->id,
            'personal' => 0,
            'archived_at' => now(),
        ]);
        $archivedProject->save();
        ProjectUser::updateInsert(
            ['project_id' => $archivedProject->id, 'userid' => $viewer->userid],
            ['owner' => 0]
        );
        $this->makeFile($archivedDialog, $sender, 'archived-project.zip');

        $hiddenProjectDialog = $this->makeDialog('group', 'project', '不可见项目群', [$sender->userid, $outsider->userid]);
        $hiddenProject = Project::createInstance([
            'name' => '不可见协作文件项目',
            'desc' => '',
            'userid' => $sender->userid,
            'dialog_id' => $hiddenProjectDialog->id,
            'personal' => 0,
        ]);
        $hiddenProject->save();
        ProjectUser::updateInsert(
            ['project_id' => $hiddenProject->id, 'userid' => $sender->userid],
            ['owner' => ProjectUser::OWNER_PRIMARY]
        );
        $this->makeFile($hiddenProjectDialog, $sender, 'hidden-project.pdf');

        $allProjectFiles = CollaborationFileService::lists($viewer, [
            'scope' => 'project',
            'project_id' => 0,
        ]);
        $this->assertSame(['task.png', 'project.xlsx'], array_column($allProjectFiles['list'], 'name'));

        $allProjectTaskFiles = CollaborationFileService::lists($viewer, [
            'scope' => 'project',
            'project_id' => 0,
            'project_source' => 'task',
        ]);
        $this->assertSame(['task.png'], array_column($allProjectTaskFiles['list'], 'name'));
    }

    public function test_lists_excludes_deleted_messages_and_uses_cursor(): void
    {
        $viewer = $this->makeUser('collaboration-page@test.local');
        $dialog = $this->makeDialog('group', 'user', '分页群', [$viewer->userid]);
        $first = $this->makeFile($dialog, $viewer, 'first.pdf');
        $this->makeFile($dialog, $viewer, 'deleted.pdf')->delete();
        $last = $this->makeFile($dialog, $viewer, 'last.png');

        $page = CollaborationFileService::lists($viewer, ['take' => 1]);
        $this->assertSame(['last.png'], array_column($page['list'], 'name'));
        $this->assertTrue($page['has_more']);
        $this->assertSame($last->id, $page['next_cursor']);

        $next = CollaborationFileService::lists($viewer, [
            'cursor' => $page['next_cursor'],
            'file_type' => 'document',
        ]);
        $this->assertSame([$first->id], array_column($next['list'], 'msg_id'));
        $this->assertFalse($next['has_more']);
    }

    public function test_authorize_message_rejects_non_member(): void
    {
        $member = $this->makeUser('collaboration-member@test.local');
        $outsider = $this->makeUser('collaboration-denied@test.local');
        $dialog = $this->makeDialog('group', 'user', '权限群', [$member->userid]);
        $message = $this->makeFile($dialog, $member, 'permission.pdf');

        CollaborationFileService::authorizeMessage($message, $member);
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('无权限访问此文件');
        CollaborationFileService::authorizeMessage($message, $outsider);
    }
}
