<?php

namespace Tests\Feature;

use App\Models\Gesture;
use App\Models\PracticeAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGestureFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function createGesture(string $slug, string $status = 'published', array $extra = []): Gesture
    {
        return Gesture::create(array_merge([
            'slug' => $slug,
            'word' => ucfirst($slug),
            'indonesian' => ucfirst($slug).' ID',
            'category' => 'alpha',
            'level' => 'abjad',
            'sort_order' => 1,
            'difficulty' => 'Easy',
            'status' => $status,
            'description' => 'Deskripsi '.$slug,
        ], $extra));
    }

    public function test_draft_gestures_are_hidden_from_public_and_visible_in_admin_preview(): void
    {
        $published = $this->createGesture('terbit-1', 'published');
        $draft = $this->createGesture('draft-1', 'draft');

        // Public API
        $response = $this->getJson('/api/gestures');
        $response->assertOk()
            ->assertJsonFragment(['id' => 'terbit-1'])
            ->assertJsonMissing(['id' => 'draft-1']);

        // Public single show
        $this->getJson('/api/gestures/draft-1')->assertNotFound();

        // Admin preview
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/gestures/'.$draft->id.'/preview')
            ->assertOk()
            ->assertSee('draft-1')
            ->assertSee('Draft');

        // Admin toggle status
        $this->actingAs($admin)->post('/admin/gestures/'.$draft->id.'/toggle-status')
            ->assertRedirect();
        $this->assertSame('published', $draft->fresh()->status);
    }

    public function test_content_completeness_dashboard_lists_incomplete_gestures(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Gesture without image and video
        $this->createGesture('incomplete-gesture', 'published', [
            'image_path' => null,
            'video_path' => null,
        ]);

        $response = $this->actingAs($admin)->get('/admin/gestures/completeness');
        $response->assertOk()
            ->assertSee('incomplete-gesture')
            ->assertSee('Belum ada gambar')
            ->assertSee('Belum ada video');
    }

    public function test_gesture_search_is_case_insensitive_in_public_api_and_admin(): void
    {
        $this->createGesture('hello-world', 'published', [
            'word' => 'Hello World',
            'indonesian' => 'Halo Dunia',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->getJson('/api/gestures?q=HELLO')
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', 'hello-world');

        $this->actingAs($admin)->get('/admin/gestures?q=HELLO')
            ->assertSee('Hello World');
    }

    public function test_analytics_displays_hardest_and_popular_gestures(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $g1 = $this->createGesture('huruf-z', 'published');
        $g2 = $this->createGesture('huruf-a', 'published');

        // g1: 3 attempts, 1 correct (33.3% accuracy - harder)
        PracticeAttempt::create(['gesture_id' => $g1->id, 'is_correct' => false]);
        PracticeAttempt::create(['gesture_id' => $g1->id, 'is_correct' => false]);
        PracticeAttempt::create(['gesture_id' => $g1->id, 'is_correct' => true]);

        // g2: 2 attempts, 2 correct (100% accuracy)
        PracticeAttempt::create(['gesture_id' => $g2->id, 'is_correct' => true]);
        PracticeAttempt::create(['gesture_id' => $g2->id, 'is_correct' => true]);

        $response = $this->actingAs($admin)->get('/admin/gestures/analytics');
        $response->assertOk()
            ->assertSee('huruf-z')
            ->assertSee('33.3%');
    }

    public function test_activity_log_records_admin_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/gestures', [
            'slug' => 'gesture-baru',
            'word' => 'Gesture Baru',
            'category' => 'greet',
            'level' => 'kata',
            'difficulty' => 'Easy',
            'status' => 'published',
            'description' => 'Deskripsi gesture baru',
        ])->assertRedirectToRoute('admin.gestures.index');

        $this->assertDatabaseHas('admin_activity_logs', [
            'user_id' => $admin->id,
            'action' => 'create',
        ]);

        $response = $this->actingAs($admin)->get('/admin/logs');
        $response->assertOk()
            ->assertSee('Gesture Baru')
            ->assertSee('CREATE');
    }

    public function test_soft_delete_and_restore_workflow(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $gesture = $this->createGesture('gesture-hapus', 'published');

        // Soft delete
        $this->actingAs($admin)->delete('/admin/gestures/'.$gesture->id)
            ->assertRedirectToRoute('admin.gestures.index');
        $this->assertSoftDeleted('gestures', ['id' => $gesture->id]);

        // Trash page
        $this->actingAs($admin)->get('/admin/gestures/trash')
            ->assertOk()
            ->assertSee('gesture-hapus');

        // Restore
        $this->actingAs($admin)->post('/admin/gestures/'.$gesture->id.'/restore')
            ->assertRedirectToRoute('admin.gestures.trash');
        $this->assertNotSoftDeleted('gestures', ['id' => $gesture->id]);

        // Force delete
        $gesture->delete();
        $this->actingAs($admin)->delete('/admin/gestures/'.$gesture->id.'/force-delete')
            ->assertRedirectToRoute('admin.gestures.trash');
        $this->assertDatabaseMissing('gestures', ['id' => $gesture->id]);
    }

    public function test_non_staff_users_cannot_access_any_admin_feature_routes(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $gesture = $this->createGesture('gesture-test', 'published');

        $this->actingAs($student)->get('/admin')->assertForbidden();
        $this->actingAs($student)->get('/admin/gestures/completeness')->assertForbidden();
        $this->actingAs($student)->get('/admin/gestures/analytics')->assertForbidden();
        $this->actingAs($student)->get('/admin/gestures/trash')->assertForbidden();
        $this->actingAs($student)->get('/admin/logs')->assertForbidden();
        $this->actingAs($student)->post('/admin/gestures/'.$gesture->id.'/toggle-status')->assertForbidden();
    }
}
