<?php

namespace Tests\Feature;

use App\Models\Gesture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_account_becomes_admin_and_setup_closes(): void
    {
        $this->get('/setup')->assertOk();

        $this->post('/setup', [
            'name' => 'Site Admin',
            'email' => 'admin@example.test',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ])->assertRedirectToRoute('admin.dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.test',
            'role' => 'admin',
        ]);
        $this->assertAuthenticated();
        $this->get('/setup')->assertNotFound();
    }

    public function test_editor_can_manage_gestures_but_cannot_delete_or_manage_users(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editor);
        $this->get('/admin/gestures')->assertOk();

        $this->post('/admin/gestures', $this->gestureData())
            ->assertRedirectToRoute('admin.gestures.index');

        $gesture = Gesture::where('slug', 'hello')->firstOrFail();

        $this->put('/admin/gestures/'.$gesture->id, $this->gestureData([
            'word' => 'Hi there',
            'slug' => 'hello',
        ]))->assertRedirectToRoute('admin.gestures.index');

        $this->assertDatabaseHas('gestures', [
            'id' => $gesture->id,
            'word' => 'Hi there',
        ]);

        $this->delete('/admin/gestures/'.$gesture->id)->assertForbidden();
        $this->post('/admin/users', [
            'name' => 'Another Editor',
            'email' => 'editor@example.test',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ])->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->delete('/admin/gestures/'.$gesture->id)
            ->assertRedirectToRoute('admin.gestures.index');

        $this->assertSoftDeleted('gestures', ['id' => $gesture->id]);
    }

    public function test_admin_can_create_editor_accounts_and_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirectToRoute('login');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'New Editor',
                'email' => 'editor@example.test',
                'password' => 'secure-password-123',
                'password_confirmation' => 'secure-password-123',
            ])
            ->assertRedirectToRoute('admin.users.index');

        $this->assertDatabaseHas('users', [
            'email' => 'editor@example.test',
            'role' => 'editor',
        ]);

        $editor = User::where('email', 'editor@example.test')->firstOrFail();
        $this->patch('/admin/users/'.$editor->id.'/role', ['role' => 'admin'])
            ->assertRedirectToRoute('admin.users.index');

        $this->assertDatabaseHas('users', [
            'id' => $editor->id,
            'role' => 'admin',
        ]);
    }

    public function test_login_authenticates_an_existing_user(): void
    {
        $user = User::factory()->create([
            'email' => 'editor@example.test',
            'password' => 'secure-password-123',
            'role' => 'editor',
        ]);

        $this->post('/login', [
            'email' => 'editor@example.test',
            'password' => 'secure-password-123',
        ])->assertRedirectToRoute('admin.dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_gesture_form_rejects_missing_required_fields(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->actingAs($editor)
            ->post('/admin/gestures', [])
            ->assertSessionHasErrors(['slug', 'word', 'description']);

        $this->assertDatabaseCount('gestures', 0);
    }

    /**
     * @return array<string, string|null>
     */
    private function gestureData(array $overrides = []): array
    {
        return array_merge([
            'slug' => 'hello',
            'word' => 'Hello',
            'indonesian' => 'Halo',
            'category' => 'greet',
            'level' => 'kata',
            'emoji' => '👋',
            'difficulty' => 'Easy',
            'description' => 'A greeting gesture.',
            'hand_position' => null,
            'movement' => null,
            'common_mistake' => null,
            'visual_key' => null,
            'video_url' => null,
        ], $overrides);
    }
}
