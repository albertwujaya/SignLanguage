<?php

namespace Tests\Feature;

use App\Models\Gesture;
use App\Models\GestureProgress;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserProgressTest extends TestCase
{
    use RefreshDatabase;

    private function createGesture(string $slug, string $level = 'abjad', string $status = 'published'): Gesture
    {
        return Gesture::create([
            'slug' => $slug,
            'word' => ucfirst($slug),
            'indonesian' => ucfirst($slug).' ID',
            'category' => 'alpha',
            'level' => $level,
            'sort_order' => 1,
            'difficulty' => 'Easy',
            'status' => $status,
            'description' => 'Contoh deskripsi '.$slug,
        ]);
    }

    public function test_admin_can_view_users_list_with_progress_and_scores(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['name' => 'Budi Belajar', 'email' => 'budi@example.test']);

        $g1 = $this->createGesture('huruf-a', 'abjad');
        $g2 = $this->createGesture('huruf-b', 'abjad');

        // Budi learned 1 gesture
        GestureProgress::create([
            'user_id' => $student->id,
            'gesture_id' => $g1->id,
            'learned_at' => now(),
        ]);

        // Budi has a practice session
        PracticeSession::create([
            'user_id' => $student->id,
            'level' => 'abjad',
            'total' => 10,
            'correct' => 8,
            'score' => 80,
            'duration_seconds' => 60,
        ]);

        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertOk()
            ->assertSee('Budi Belajar')
            ->assertSee('budi@example.test')
            ->assertSee('50%') // 1 of 2 gestures = 50%
            ->assertSee('80%'); // avg score
    }

    public function test_admin_user_search_is_case_insensitive(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['name' => 'Siti Pintar', 'email' => 'siti@example.test']);

        $this->actingAs($admin)->get('/admin/users?q=SITI')
            ->assertSee('Siti Pintar')
            ->assertSee('siti@example.test');
    }

    public function test_non_admin_cannot_access_user_progress_pages(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $targetUser = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get('/admin/users')->assertForbidden();
        $this->actingAs($student)->get('/admin/users/'.$targetUser->id)->assertForbidden();
        $this->actingAs($student)->get('/admin/users/export/csv')->assertForbidden();
    }

    public function test_admin_can_view_user_detail_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['name' => 'Siti Pintar', 'email' => 'siti@example.test']);

        $g1 = $this->createGesture('kata-halo', 'kata');
        $g2 = $this->createGesture('kata-terima-kasih', 'kata');

        GestureProgress::create([
            'user_id' => $student->id,
            'gesture_id' => $g1->id,
            'learned_at' => now(),
        ]);

        PracticeSession::create([
            'user_id' => $student->id,
            'level' => 'kata',
            'total' => 5,
            'correct' => 5,
            'score' => 100,
            'duration_seconds' => 30,
        ]);

        $response = $this->actingAs($admin)->get('/admin/users/'.$student->id);
        $response->assertOk()
            ->assertSee('Siti Pintar')
            ->assertSee('kata-halo')
            ->assertSee('100%')
            ->assertSee('50%'); // 1 of 2 = 50%
    }

    public function test_admin_can_export_users_progress_to_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['name' => 'Ahmad Learner', 'email' => 'ahmad@example.test']);

        $response = $this->actingAs($admin)->get('/admin/users/export/csv');
        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type') ?? '', 'text/csv'));
    }

    public function test_admin_dashboard_shows_summary_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(3)->create();
        $this->createGesture('huruf-c', 'abjad');

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee('Total Pengguna')
            ->assertSee('Rata-rata Progres');
    }
}
