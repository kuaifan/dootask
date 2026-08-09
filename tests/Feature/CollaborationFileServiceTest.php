<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\ProjectTaskUser;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgAttachment;
use App\Models\WebSocketDialogMsgAttachmentBackfill;
use App\Models\WebSocketDialogUser;
use App\Exceptions\ApiException;
use App\Services\CollaborationFileService;
use App\Services\MessageAttachmentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CollaborationFileServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        WebSocketDialogMsgAttachmentBackfill::query()->update([
            'status' => WebSocketDialogMsgAttachmentBackfill::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

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
        MessageAttachmentService::sync($message);
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

        // The browser sends cursor=0 on the initial request.
        $page = CollaborationFileService::lists($viewer, ['take' => 1, 'cursor' => '0']);
        $this->assertSame(['last.png'], array_column($page['list'], 'name'));
        $this->assertTrue($page['has_more']);
        $this->assertStringStartsWith("a:{$last->id}:0:", $page['next_cursor']);

        $next = CollaborationFileService::lists($viewer, [
            'cursor' => $page['next_cursor'],
            'file_type' => 'document',
        ]);
        $this->assertSame([$first->id], array_column($next['list'], 'msg_id'));
        $this->assertFalse($next['has_more']);
    }

    public function test_lists_paginates_each_inline_image_in_one_message(): void
    {
        $viewer = $this->makeUser('collaboration-inline@test.local');
        $dialog = $this->makeDialog('user', '', '', [$viewer->userid]);
        $message = WebSocketDialogMsg::createInstance([
            'dialog_id' => $dialog->id,
            'userid' => $viewer->userid,
            'type' => 'text',
            'key' => '',
            'msg' => json_encode([
                'text' => '<p><img class="browse" src="uploads/test/first.png" alt="第一张"/>'
                    . '<img class="browse" src="uploads/test/second.png" alt="第二张"/></p>',
            ], JSON_UNESCAPED_UNICODE),
        ]);
        $message->save();
        MessageAttachmentService::sync($message);

        $firstPage = CollaborationFileService::lists($viewer, ['take' => 1]);
        $this->assertSame(['第一张'], array_column($firstPage['list'], 'name'));
        $this->assertSame('inline_image', $firstPage['list'][0]['attachment_source']);
        $this->assertSame($message->id, $firstPage['list'][0]['msg_id']);
        $this->assertTrue($firstPage['has_more']);

        $secondPage = CollaborationFileService::lists($viewer, [
            'take' => 1,
            'cursor' => $firstPage['next_cursor'],
        ]);
        $this->assertSame(['第二张'], array_column($secondPage['list'], 'name'));
        $this->assertSame($message->id, $secondPage['list'][0]['msg_id']);
        $this->assertNotSame($firstPage['list'][0]['attachment_id'], $secondPage['list'][0]['attachment_id']);
        $this->assertFalse($secondPage['has_more']);

        $legacySecondPage = CollaborationFileService::lists($viewer, [
            'take' => 1,
            'cursor' => substr($firstPage['next_cursor'], 2),
        ]);
        $this->assertSame(['第二张'], array_column($legacySecondPage['list'], 'name'));
    }

    public function test_lists_falls_back_to_messages_until_index_is_ready(): void
    {
        $viewer = $this->makeUser('collaboration-fallback@test.local');
        $dialog = $this->makeDialog('group', 'user', '回填中群聊', [$viewer->userid]);
        $message = $this->makeFile($dialog, $viewer, 'fallback.pdf');
        WebSocketDialogMsgAttachment::whereMsgId($message->id)->delete();
        WebSocketDialogMsgAttachmentBackfill::query()->update([
            'status' => WebSocketDialogMsgAttachmentBackfill::STATUS_PENDING,
            'completed_at' => null,
        ]);

        $result = CollaborationFileService::lists($viewer, []);

        $this->assertSame(['fallback.pdf'], array_column($result['list'], 'name'));
        $this->assertSame(0, $result['list'][0]['attachment_id']);
        $this->assertSame('file_message', $result['list'][0]['attachment_source']);
    }

    public function test_message_cursor_keeps_fallback_source_after_backfill_completes(): void
    {
        $viewer = $this->makeUser('collaboration-transition@test.local');
        $dialog = $this->makeDialog('group', 'user', '回填切换群聊', [$viewer->userid]);
        $older = $this->makeFile($dialog, $viewer, 'older.pdf');
        $newer = $this->makeFile($dialog, $viewer, 'newer.pdf');
        WebSocketDialogMsgAttachmentBackfill::query()->update([
            'status' => WebSocketDialogMsgAttachmentBackfill::STATUS_PENDING,
            'completed_at' => null,
        ]);

        $firstPage = CollaborationFileService::lists($viewer, ['take' => 1, 'cursor' => '0']);
        $this->assertSame([$newer->id], array_column($firstPage['list'], 'msg_id'));
        $this->assertSame("m:{$newer->id}", $firstPage['next_cursor']);

        WebSocketDialogMsgAttachmentBackfill::query()->update([
            'status' => WebSocketDialogMsgAttachmentBackfill::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
        $secondPage = CollaborationFileService::lists($viewer, [
            'take' => 1,
            'cursor' => $firstPage['next_cursor'],
        ]);

        $this->assertSame([$older->id], array_column($secondPage['list'], 'msg_id'));
        $this->assertSame(0, $secondPage['list'][0]['attachment_id']);

        $legacySecondPage = CollaborationFileService::lists($viewer, [
            'take' => 1,
            'cursor' => (string)$newer->id,
        ]);
        $this->assertSame([$older->id], array_column($legacySecondPage['list'], 'msg_id'));
        $this->assertSame(0, $legacySecondPage['list'][0]['attachment_id']);
    }

    public function test_resolve_local_attachment_path_rejects_traversal(): void
    {
        $directory = public_path('uploads/tmp/collaboration-file-test');
        $file = $directory . '/download.txt';
        $link = $directory . '/escape.env';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        file_put_contents($file, 'download');
        symlink(base_path('.env'), $link);

        try {
            $this->assertSame(realpath($file), CollaborationFileService::resolveLocalAttachmentPath(
                'uploads/tmp/collaboration-file-test/download.txt'
            ));
            $this->assertNull(CollaborationFileService::resolveLocalAttachmentPath('../.env'));
            $this->assertNull(CollaborationFileService::resolveLocalAttachmentPath('uploads/../index.php'));
            $this->assertNull(CollaborationFileService::resolveLocalAttachmentPath(
                'uploads/tmp/collaboration-file-test/escape.env'
            ));
            $this->assertNull(CollaborationFileService::resolveLocalAttachmentPath('https://example.com/file.pdf'));
        } finally {
            @unlink($link);
            @unlink($file);
            @rmdir($directory);
        }
    }

    public function test_authorize_attachment_rejects_non_member(): void
    {
        $member = $this->makeUser('collaboration-member@test.local');
        $outsider = $this->makeUser('collaboration-denied@test.local');
        $dialog = $this->makeDialog('group', 'user', '权限群', [$member->userid]);
        $message = $this->makeFile($dialog, $member, 'permission.pdf');
        $attachment = WebSocketDialogMsgAttachment::whereMsgId($message->id)->firstOrFail();

        CollaborationFileService::authorizeAttachment($attachment, $member);
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('无权限访问此文件');
        CollaborationFileService::authorizeAttachment($attachment, $outsider);
    }
}
