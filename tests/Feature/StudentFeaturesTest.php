<?php

namespace Tests\Feature;

use App\Models\Gesture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_register_as_student_and_role_cannot_be_forced(): void
    {
        $this->post('/daftar', [
            'name' => 'Nahda',
            'email' => 'siswa@example.test',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
            'role' => 'admin',
        ])->assertRedirectToRoute('profile');

        $this->assertDatabaseHas('users', ['email' => 'siswa@example.test', 'role' => 'student']);
        $this->assertAuthenticated();
    }

    public function test_student_is_blocked_from_admin_but_staff_is_not(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->get('/admin/gestures')->assertForbidden();

        $editor = User::factory()->create(['role' => 'editor']);
        $this->actingAs($editor)->get('/admin/gestures')->assertOk();
    }

    public function test_student_login_goes_to_profile_and_logout_goes_home(): void
    {
        User::factory()->create([
            'email' => 'siswa@example.test',
            'password' => 'password-123',
            'role' => 'student',
        ]);

        $this->post('/login', ['email' => 'siswa@example.test', 'password' => 'password-123'])
            ->assertRedirectToRoute('profile');

        $this->post('/logout')->assertRedirectToRoute('home');
        $this->assertGuest();
    }

    public function test_profile_requires_login_and_can_be_updated(): void
    {
        $this->get('/profil')->assertRedirectToRoute('login');

        $user = User::factory()->create(['role' => 'student', 'password' => 'password-123']);
        $this->actingAs($user)->get('/profil')->assertOk()->assertSee($user->name);

        $this->actingAs($user)->patch('/profil', [
            'name' => 'Nama Baru',
            'email' => $user->email,
        ])->assertRedirectToRoute('profile');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Baru']);

        // ganti sandi wajib sandi lama yang benar
        $this->actingAs($user)->patch('/profil', [
            'name' => 'Nama Baru',
            'email' => $user->email,
            'current_password' => 'salah-banget',
            'password' => 'password-baru-1',
            'password_confirmation' => 'password-baru-1',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_learned_progress_is_saved_per_user(): void
    {
        $gesture = Gesture::create($this->gesture('hello'));
        $a = User::factory()->create(['role' => 'student']);
        $b = User::factory()->create(['role' => 'student']);

        $this->putJson('/api/progress/hello/learned')->assertUnauthorized();

        $this->actingAs($a)->putJson('/api/progress/hello/learned')->assertOk();
        $this->actingAs($a)->getJson('/api/progress')->assertJson(['learned' => ['hello']]);
        $this->actingAs($b)->getJson('/api/progress')->assertJson(['learned' => []]);

        $this->actingAs($a)->deleteJson('/api/progress/hello/learned')->assertOk();
        $this->actingAs($a)->getJson('/api/progress')->assertJson(['learned' => []]);

        $this->assertSame(1, $gesture->progress()->count());
    }

    public function test_profile_journal_shows_learned_gestures_as_stickers(): void
    {
        Gesture::create($this->gesture('halo', ['word' => 'HALO-UNIK']));
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)->get('/profil')->assertOk()->assertDontSee('HALO-UNIK');

        $this->actingAs($user)->putJson('/api/progress/halo/learned')->assertOk();
        $this->actingAs($user)->get('/profil')->assertOk()->assertSee('HALO-UNIK')->assertSee('Jurnal belajarku');
    }

    public function test_practice_session_score_is_computed_by_server_and_unverified(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)->postJson('/api/practice-sessions', [
            'total' => 10,
            'correct' => 8,
            'duration_seconds' => 120,
            'score' => 100,
            'is_verified' => true,
        ])->assertCreated()->assertJson(['score' => 80]);

        $this->assertDatabaseHas('practice_sessions', [
            'user_id' => $user->id,
            'score' => 80,
            'is_verified' => false,
        ]);

        $this->actingAs($user)->postJson('/api/practice-sessions', ['total' => 5, 'correct' => 9])
            ->assertUnprocessable();
    }

    public function test_admin_can_upload_replace_and_delete_gesture_media(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/gestures', $this->gesture('a', [
            'level' => 'abjad',
            'image' => UploadedFile::fake()->create('a.png', 10, 'image/png'),
            'video' => UploadedFile::fake()->create('a.mp4', 200, 'video/mp4'),
        ]))->assertRedirectToRoute('admin.gestures.index');

        $gesture = Gesture::where('slug', 'a')->firstOrFail();
        $this->assertSame('abjad', $gesture->level);
        Storage::disk('public')->assertExists($gesture->image_path);
        Storage::disk('public')->assertExists($gesture->video_path);

        $oldImage = $gesture->image_path;
        $this->actingAs($admin)->put('/admin/gestures/'.$gesture->id, $this->gesture('a', [
            'level' => 'abjad',
            'image' => UploadedFile::fake()->create('b.jpg', 10, 'image/jpeg'),
        ]))->assertRedirectToRoute('admin.gestures.index');
        Storage::disk('public')->assertMissing($oldImage);

        $gesture->refresh();
        $this->actingAs($admin)->delete('/admin/gestures/'.$gesture->id);
        $this->assertSoftDeleted('gestures', ['id' => $gesture->id]);

        $this->actingAs($admin)->delete('/admin/gestures/'.$gesture->id.'/force-delete');
        Storage::disk('public')->assertMissing($gesture->image_path);
        Storage::disk('public')->assertMissing($gesture->video_path);
        $this->assertDatabaseMissing('gestures', ['id' => $gesture->id]);
    }

    public function test_upload_rejects_svg_and_wrong_video_type(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/gestures', $this->gesture('x', [
            'image' => UploadedFile::fake()->create('x.svg', 5, 'image/svg+xml'),
            'video' => UploadedFile::fake()->create('x.exe', 5, 'application/octet-stream'),
        ]))->assertSessionHasErrors(['image', 'video']);

        $this->assertDatabaseCount('gestures', 0);
    }

    public function test_api_exposes_level_and_can_filter_by_it(): void
    {
        Gesture::create($this->gesture('a', ['level' => 'abjad']));
        Gesture::create($this->gesture('halo', ['level' => 'kata']));

        $this->getJson('/api/gestures?level=abjad')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', 'a')
            ->assertJsonPath('0.level', 'abjad');
    }

    /**
     * @return array<string, mixed>
     */
    private function gesture(string $slug, array $overrides = []): array
    {
        $level = $overrides['level'] ?? 'kata';
        $defaultCategory = match ($level) {
            'abjad' => 'alpha',
            'angka' => 'num',
            default => 'greet',
        };

        return array_merge([
            'slug' => $slug,
            'word' => strtoupper($slug),
            'category' => $defaultCategory,
            'level' => $level,
            'difficulty' => 'Easy',
            'description' => 'Deskripsi '.$slug,
        ], $overrides);
    }
}
