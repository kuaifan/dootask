<?php

namespace Tests\Feature;

use App\Module\Base;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SystemBrandingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_system_setting_exposes_login_branding(): void
    {
        Base::setting('system', [
            'system_alias' => 'Example Team',
            'login_logo' => 'uploads/user/picture/1/202609/example.png',
        ], true);

        $response = $this->getJson('/api/system/setting')
            ->assertOk()
            ->assertJsonPath('ret', 1)
            ->assertJsonPath('data.system_alias', 'Example Team');

        $this->assertStringEndsWith(
            '/uploads/user/picture/1/202609/example.png',
            $response->json('data.login_logo')
        );

        $page = $this->get('/login')
            ->assertOk()
            ->assertSee('Example Team', false);

        $this->assertStringContainsString(
            '/uploads/user/picture/1/202609/example.png',
            str_replace('\/', '/', $page->getContent())
        );
    }
}
