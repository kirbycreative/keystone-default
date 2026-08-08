<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_owner_can_request_and_use_a_single_use_password_link(): void
    {
        config([
            'app.url' => 'https://client.keystone.test',
            'services.keystone.url' => 'https://kirbycreative.co/api',
            'services.keystone.token' => 'test-api-token',
        ]);

        Http::fake(['*' => Http::response(['sent' => true])]);
        $user = User::factory()->create();

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('account-login-card')
            ->assertSee('<input-text name="email"', false);

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

        $resetUrl = null;
        Http::assertSent(function (Request $request) use (&$resetUrl, $user): bool {
            $resetUrl = $request->data()['reset_url'] ?? null;

            return $request->url() === 'https://kirbycreative.co/api/client-mail/password-reset'
                && $request->data()['email'] === $user->email
                && is_string($resetUrl);
        });

        $this->get($resetUrl)
            ->assertOk()
            ->assertSee('account-login-card')
            ->assertSee('<input-text name="password"', false)
            ->assertSee('<input-text name="password_confirmation"', false);

        $token = basename((string) parse_url($resetUrl, PHP_URL_PATH));
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect(route('login'));

        $this->assertCredentials(['email' => $user->email, 'password' => 'new-secure-password']);
    }

    public function test_health_endpoint_checks_the_application_database(): void
    {
        $this->getJson(route('health'))->assertOk()->assertJson(['status' => 'ok']);
    }
}
