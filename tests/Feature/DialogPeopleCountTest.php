<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebSocketDialog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DialogPeopleCountTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $email, int $bot = 0): User
    {
        $user = User::createInstance([
            'email' => $email,
            'userimg' => '',
            'nickname' => 'TestUser',
            'profession' => '',
            'password' => md5('123456'),
            'bot' => $bot,
        ]);
        $user->save();
        return $user;
    }

    public function test_generate_people_ignores_deleted_and_disabled_users(): void
    {
        $owner = $this->makeUser('people-owner@test.local');
        $active = $this->makeUser('people-active@test.local');
        $disabled = $this->makeUser('people-disabled@test.local');
        $deleted = $this->makeUser('people-deleted@test.local');
        $bot = $this->makeUser('people-bot@test.local', 1);
        $dialog = WebSocketDialog::createGroup(
            'People count',
            [$owner->userid, $active->userid, $disabled->userid, $deleted->userid, $bot->userid],
            'user',
            $owner->userid
        );

        $disabled->disable_at = now();
        $disabled->save();
        DB::table('users')->where('userid', $deleted->userid)->delete();

        $this->assertSame([
            'people' => 3,
            'people_user' => 2,
            'people_bot' => 1,
        ], WebSocketDialog::generatePeople($dialog->id));
    }
}
