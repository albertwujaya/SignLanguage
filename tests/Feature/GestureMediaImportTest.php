<?php

namespace Tests\Feature;

use App\Models\Gesture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GestureMediaImportTest extends TestCase
{
    use RefreshDatabase;

    private string $tempMediaDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempMediaDir = storage_path('framework/testing/test_video_hasil');
        if (! File::exists($this->tempMediaDir)) {
            File::makeDirectory($this->tempMediaDir, 0755, true);
        }
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tempMediaDir)) {
            File::deleteDirectory($this->tempMediaDir);
        }
        parent::tearDown();
    }

    public function test_import_command_fails_if_folder_does_not_exist(): void
    {
        $this->artisan('gesture:import', ['path' => 'folder_tidak_ada_12345'])
            ->expectsOutputToContain('Folder sumber tidak ditemukan')
            ->assertExitCode(1);
    }

    public function test_import_dry_run_does_not_copy_files_or_update_database(): void
    {
        // Setup folder struktur: Alphabet/A.mp4, Alphabet/A.webp
        $alphaDir = $this->tempMediaDir.'/Alphabet';
        File::makeDirectory($alphaDir, 0755, true);
        File::put($alphaDir.'/A.mp4', 'dummy-video-content');
        File::put($alphaDir.'/A.webp', 'dummy-image-content');

        $gesture = Gesture::create([
            'slug' => 'letter-a',
            'word' => 'A',
            'indonesian' => 'HURUF A',
            'category' => 'alpha',
            'level' => 'abjad',
            'description' => 'Huruf A',
            'status' => 'published',
        ]);

        $this->artisan('gesture:import', [
            'path' => $this->tempMediaDir,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('MODE: --dry-run aktif')
            ->expectsOutputToContain('Siap diimpor')
            ->assertExitCode(0);

        $gesture->refresh();
        $this->assertNull($gesture->video_path);
        $this->assertNull($gesture->image_path);
        Storage::disk('public')->assertMissing('gestures/videos/letter-a.mp4');
        Storage::disk('public')->assertMissing('gestures/images/letter-a.webp');
    }

    public function test_import_copies_files_and_updates_database_idempotently(): void
    {
        $dailyDir = $this->tempMediaDir.'/Daily';
        File::makeDirectory($dailyDir, 0755, true);
        File::put($dailyDir.'/Good.mp4', 'dummy-good-video');
        File::put($dailyDir.'/Good.webp', 'dummy-good-image');

        $gesture = Gesture::create([
            'slug' => 'good',
            'word' => 'GOOD',
            'indonesian' => 'BAGUS / BAIK',
            'category' => 'conv',
            'level' => 'kata',
            'description' => 'Bagus',
            'status' => 'published',
        ]);

        // 1. Eksekusi pertama: impor berhasil
        $this->artisan('gesture:import', ['path' => $this->tempMediaDir])
            ->expectsOutputToContain('Berhasil diimpor')
            ->assertExitCode(0);

        $gesture->refresh();
        $this->assertEquals('gestures/videos/good.mp4', $gesture->video_path);
        $this->assertEquals('gestures/images/good.webp', $gesture->image_path);

        Storage::disk('public')->assertExists('gestures/videos/good.mp4');
        Storage::disk('public')->assertExists('gestures/images/good.webp');
        $this->assertEquals('dummy-good-video', Storage::disk('public')->get('gestures/videos/good.mp4'));

        // 2. Eksekusi kedua (idempoten): dilewati karena sudah ada media
        $this->artisan('gesture:import', ['path' => $this->tempMediaDir])
            ->expectsOutputToContain('Dilewati (sudah ada media)')
            ->assertExitCode(0);

        // 3. Eksekusi ketiga dengan --force: berhasil ditimpa
        File::put($dailyDir.'/Good.mp4', 'updated-video-content');
        $this->artisan('gesture:import', [
            'path' => $this->tempMediaDir,
            '--force' => true,
        ])
            ->expectsOutputToContain('Berhasil ditimpa (--force)')
            ->assertExitCode(0);

        $this->assertEquals('updated-video-content', Storage::disk('public')->get('gestures/videos/good.mp4'));
    }

    public function test_import_reports_unmatched_files(): void
    {
        $weirdDir = $this->tempMediaDir.'/UnknownCategory';
        File::makeDirectory($weirdDir, 0755, true);
        File::put($weirdDir.'/NonExistentWord.mp4', 'dummy-video');
        File::put($weirdDir.'/NonExistentWord.webp', 'dummy-image');

        $this->artisan('gesture:import', ['path' => $this->tempMediaDir])
            ->expectsOutputToContain('Tidak cocok di database : 1')
            ->assertExitCode(0);
    }
}
