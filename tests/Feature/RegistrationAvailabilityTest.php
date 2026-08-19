<?php

namespace Tests\Feature;

use App\Module\Base;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RegistrationAvailabilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_registration_status_reports_closed_registration(): void
    {
        Base::setting('system', ['reg' => 'close'], true);

        $this->getJson('/api/users/reg/needinvite')
            ->assertOk()
            ->assertJsonPath('ret', 1)
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.need', false);
    }

    public function test_registration_status_reports_invite_registration(): void
    {
        Base::setting('system', ['reg' => 'invite'], true);

        $this->getJson('/api/users/reg/needinvite')
            ->assertOk()
            ->assertJsonPath('ret', 1)
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.need', true);
    }
}
