<?php

use App\Models\User;
use Database\Seeders\MarketplaceReferenceSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(MarketplaceReferenceSeeder::class);
});

function gmRegistration(array $overrides = []): array
{
    return array_replace(['name' => 'A Student', 'email' => 'student@mail.ugm.ac.id', 'password' => 'SecurePass123', 'password_confirmation' => 'SecurePass123', 'faculty' => 'FT', 'safety_agreement' => true], $overrides);
}

test('registration normalizes email and ignores injected authority', function () {
    $this->postJson('/gm/api/register', gmRegistration(['email' => 'STUDENT@MAIL.UGM.AC.ID', 'role' => 'admin', 'status' => 'suspended', 'email_verified_at' => now()]))->assertOk();
    $user = User::first();
    expect($user->email)->toBe('student@mail.ugm.ac.id')->and($user->role)->toBe('student')->and($user->status)->toBe('active')->and($user->email_verified_at)->toBeNull()->and(Hash::check('SecurePass123', $user->password))->toBeTrue();
    $this->postJson('/gm/api/register', gmRegistration())->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('registration rejects non campus emails and missing agreement or confirmation', function () {
    $this->postJson('/gm/api/register', gmRegistration(['email' => 'student@example.com']))->assertUnprocessable();
    $this->postJson('/gm/api/register', gmRegistration(['safety_agreement' => false]))->assertUnprocessable();
    $this->postJson('/gm/api/register', gmRegistration(['password_confirmation' => 'bad']))->assertUnprocessable();
});

test('database credentials authenticate and logout ends session', function () {
    $this->postJson('/gm/api/register', gmRegistration())->assertOk();
    $this->postJson('/gm/api/login', ['email' => 'STUDENT@MAIL.UGM.AC.ID', 'password' => 'SecurePass123', 'remember' => true])->assertOk()->assertJsonPath('state.currentUserId', (string) User::first()->id);
    $this->get('/gm/settings.html')->assertOk();
    $this->postJson('/gm/api/logout')->assertOk()->assertJsonPath('state.currentUserId', null);
    $this->get('/gm/settings.html')->assertRedirect('/gm/login.html');
});

test('only admins can view admin page and moderate', function () {
    $student = User::factory()->create();
    $this->get('/gm/admin.html')->assertRedirect('/gm/login.html');
    $this->actingAs($student)->get('/gm/admin.html')->assertForbidden();
    $this->postJson('/gm/api/action/moderate', ['id' => 1, 'decision' => 'approved'])->assertForbidden();
    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get('/gm/admin.html')->assertOk();
});

test('suspended accounts cannot login or use existing sessions', function () {
    $user = User::factory()->create(['status' => 'suspended', 'email' => 'suspended@ugm.ac.id', 'password' => 'password']);
    $this->postJson('/gm/api/login', ['email' => $user->email, 'password' => 'password'])->assertForbidden();
    $this->actingAs($user)->postJson('/gm/api/profile', ['id' => $user->id, 'patch' => ['name' => 'Changed']])->assertForbidden();
});

test('profile updates are owned and public snapshots omit private information', function () {
    $a = User::factory()->create(['email' => 'a@ugm.ac.id']);
    $b = User::factory()->create(['email' => 'b@ugm.ac.id', 'phone' => 'secret phone', 'faculty_code' => 'FT', 'show_faculty' => false]);
    $this->actingAs($a)->postJson('/gm/api/profile', ['id' => $b->id, 'patch' => ['name' => 'Hacked']])->assertForbidden();
    $this->postJson('/gm/api/profile', ['id' => $a->id, 'patch' => ['name' => 'Updated', 'role' => 'admin']])->assertOk();
    expect($a->fresh()->role)->toBe('student')->and($a->fresh()->name)->toBe('Updated');
    $state = $this->getJson('/gm/api/state')->assertOk()->json('state');
    $other = collect($state['users'])->firstWhere('id', (string) $b->id);
    expect($other['email'])->toBe('')->and($other['phone'])->toBe('')->and($other['faculty'])->toBe('')->and($other)->not->toHaveKey('password');
});

test('password changes validate current password and invalidate old password', function () {
    $user = User::factory()->create(['password' => 'OldPassword123']);
    $this->actingAs($user)->postJson('/gm/api/password', ['oldPw' => 'incorrect', 'newPw' => 'NewPassword123', 'newPw_confirmation' => 'NewPassword123'])->assertUnprocessable();
    $this->postJson('/gm/api/password', ['oldPw' => 'OldPassword123', 'newPw' => 'NewPassword123', 'newPw_confirmation' => 'NewPassword123'])->assertOk();
    expect(Hash::check('NewPassword123', $user->fresh()->password))->toBeTrue();
});

test('public and private pages render database bootstraps', function () {
    foreach (['main', 'marketplace', 'item', 'bidding', 'looking-for', 'scam-watch', 'profile', 'meetup-map', 'login', 'logout', 'admin-login'] as $page) {
        $this->get('/gm/'.$page.'.html')->assertOk()->assertSee('GM_BOOTSTRAP', false);
    }
    $this->actingAs(User::factory()->create());
    foreach (['list-item', 'my-listings', 'wishlist', 'settings', 'messages', 'notifications'] as $page) {
        $this->get('/gm/'.$page.'.html')->assertOk();
    }
});

test('mutation endpoints require csrf outside the test bypass', function () {
    $this->app['env'] = 'local';
    $this->withSession(['_token' => 'known-local-test-token']);
    $this->postJson('/gm/api/register', gmRegistration())->assertStatus(419);
    $this->withHeader('X-CSRF-TOKEN', 'known-local-test-token')->postJson('/gm/api/register', gmRegistration())->assertOk();
});

test('login attempts are rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/gm/api/login', ['email' => 'missing@mail.ugm.ac.id', 'password' => 'incorrect'])->assertUnprocessable();
    }
    $this->postJson('/gm/api/login', ['email' => 'missing@mail.ugm.ac.id', 'password' => 'incorrect'])->assertStatus(429);
});
