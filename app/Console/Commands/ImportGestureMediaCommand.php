<?php

namespace App\Console\Commands;

use App\Models\Gesture;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportGestureMediaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gesture:import
                            {path=video_hasil : Path ke folder sumber media gesture}
                            {--dry-run : Hanya menampilkan tabel rencana tanpa menyalin file atau mengubah database}
                            {--force : Menimpa media pada kartu yang sudah memiliki video_path atau image_path}
                            {--connection= : Nama koneksi database yang digunakan (misal sqlite)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Impor file video (.mp4) dan poster (.webp/.jpg) dari folder media ke storage disk public dan database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourcePath = $this->argument('path');
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');
        $connection = $this->option('connection');

        if ($connection === 'sqlite' && ! file_exists((string) config('database.connections.sqlite.database'))) {
            config(['database.connections.sqlite.database' => database_path('database.sqlite')]);
        }

        // Resolve absolute or relative path
        $fullPath = Str::startsWith($sourcePath, ['/', '\\']) || preg_match('/^[A-Za-z]:/', $sourcePath)
            ? $sourcePath
            : base_path($sourcePath);

        if (! is_dir($fullPath)) {
            $this->error("Folder sumber tidak ditemukan: {$fullPath}");

            return self::FAILURE;
        }

        $this->info('Memindai folder: '.$fullPath);
        if ($isDryRun) {
            $this->warn('MODE: --dry-run aktif (tidak ada file yang disalin atau database yang diubah).');
        }

        $pairs = $this->scanMediaPairs($fullPath);
        if (empty($pairs)) {
            $this->warn("Tidak ditemukan pasangan file media di folder: {$fullPath}");

            return self::SUCCESS;
        }

        $mapping = (array) config('gesture_media_map.map', []);
        $tableRows = [];

        $countMatched = 0;
        $countSkipped = 0;
        $countUnmatched = 0;
        $countImported = 0;

        $disk = Storage::disk('public');

        foreach ($pairs as $index => $pair) {
            $folder = $pair['folder'];
            $baseName = $pair['base'];
            $videoFile = $pair['video'];
            $imageFile = $pair['image'];

            $gesture = $this->resolveGesture($folder, $baseName, $mapping, $connection);

            $gestureLabel = $gesture
                ? "{$gesture->word} ({$gesture->slug})"
                : '-';

            if (! $gesture) {
                $status = '<fg=red>Tidak cocok di DB</>';
                $countUnmatched++;
            } elseif (! $isForce && ($gesture->video_path || $gesture->image_path)) {
                $status = '<fg=yellow>Dilewati (sudah ada media)</>';
                $countSkipped++;
            } else {
                $countMatched++;
                if ($isDryRun) {
                    $status = $isForce && ($gesture->video_path || $gesture->image_path)
                        ? '<fg=cyan>Siap ditimpa (--force)</>'
                        : '<fg=green>Siap diimpor</>';
                } else {
                    // Eksekusi nyata: salin file ke disk public
                    $slug = $gesture->slug;

                    if ($videoFile && file_exists($videoFile)) {
                        $extVid = strtolower(pathinfo($videoFile, PATHINFO_EXTENSION));
                        $destVid = "gestures/videos/{$slug}.{$extVid}";
                        $disk->put($destVid, file_get_contents($videoFile));
                        $gesture->video_path = $destVid;
                    }

                    if ($imageFile && file_exists($imageFile)) {
                        $extImg = strtolower(pathinfo($imageFile, PATHINFO_EXTENSION));
                        $destImg = "gestures/images/{$slug}.{$extImg}";
                        $disk->put($destImg, file_get_contents($imageFile));
                        $gesture->image_path = $destImg;
                    }

                    $gesture->save();
                    $countImported++;
                    $status = $isForce
                        ? '<fg=green>Berhasil ditimpa (--force)</>'
                        : '<fg=green>Berhasil diimpor</>';
                }
            }

            $tableRows[] = [
                $index + 1,
                $folder,
                $videoFile ? basename($videoFile) : '-',
                $imageFile ? basename($imageFile) : '-',
                $gestureLabel,
                $status,
            ];
        }

        $this->table(
            ['No', 'Folder', 'File Video', 'File Poster', 'Target Gesture', 'Status Rencana'],
            $tableRows
        );

        $this->newLine();
        $this->info('=== RINGKASAN IMPOR ===');
        $this->line('Total media ditemukan   : '.count($pairs));
        $this->line('Cocok & diproses        : '.($isDryRun ? $countMatched : $countImported));
        $this->line('Dilewati (ada media)    : '.$countSkipped.($countSkipped > 0 && ! $isForce ? ' (gunakan --force untuk menimpa)' : ''));
        $this->line('Tidak cocok di database : '.$countUnmatched);

        if ($countUnmatched > 0) {
            $this->warn("Terdapat {$countUnmatched} media yang belum memiliki kartu cocok di database. Periksa file mapping di config/gesture_media_map.php.");
        }

        if (! $isDryRun && $countImported > 0) {
            $this->info("✓ Berhasil mengimpor media untuk {$countImported} kartu gesture ke storage public.");
        }

        return self::SUCCESS;
    }

    /**
     * Pindai folder sumber dan kelompokkan file video & poster berdasarkan folder/nama_dasar.
     *
     * @return array<int, array{folder: string, base: string, video: ?string, image: ?string}>
     */
    private function scanMediaPairs(string $sourcePath): array
    {
        $pairs = [];
        $subDirs = glob(rtrim($sourcePath, '/\\').'/*', GLOB_ONLYDIR);

        // Jika tidak ada subdirektori, scan langsung file di folder root
        if (empty($subDirs)) {
            $subDirs = [$sourcePath];
        }

        foreach ($subDirs as $dir) {
            $folderName = basename($dir);
            $files = glob(rtrim($dir, '/\\').'/*.*');

            if (! is_array($files)) {
                continue;
            }

            $grouped = [];
            foreach ($files as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $base = pathinfo($file, PATHINFO_FILENAME);

                if (in_array($ext, ['mp4', 'webm', 'mov', 'm4v'], true)) {
                    $grouped[$base]['video'] = $file;
                } elseif (in_array($ext, ['webp', 'jpg', 'jpeg', 'png'], true)) {
                    $grouped[$base]['image'] = $file;
                }
            }

            // Urutkan alfabetis berdasarkan base name
            ksort($grouped);

            foreach ($grouped as $base => $media) {
                $pairs[] = [
                    'folder' => $folderName,
                    'base' => $base,
                    'video' => $media['video'] ?? null,
                    'image' => $media['image'] ?? null,
                ];
            }
        }

        return $pairs;
    }

    /**
     * Cari gesture yang cocok berdasarkan mapping eksplisit atau nama file.
     *
     * @param  array<string, string>  $mapping
     */
    private function resolveGesture(string $folder, string $baseName, array $mapping, ?string $connection = null): ?Gesture
    {
        // 1. Cek mapping di config/gesture_media_map.php
        $mapKeyWithFolder = "{$folder}/{$baseName}";
        $target = null;

        foreach ($mapping as $key => $val) {
            if (strcasecmp($key, $mapKeyWithFolder) === 0 || strcasecmp($key, $baseName) === 0) {
                $target = $val;
                break;
            }
        }

        $newQuery = fn () => $connection ? Gesture::on($connection) : Gesture::query();

        if ($target) {
            $found = $newQuery()
                ->where('slug', $target)
                ->orWhereRaw('LOWER(word) = ?', [strtolower($target)])
                ->orWhereRaw('LOWER(indonesian) = ?', [strtolower($target)])
                ->first();

            if ($found) {
                return $found;
            }
        }

        // 2. Cek kecocokan otomatis (slug, word, indonesian)
        $cleanBase = trim($baseName);
        $slugBase = Str::slug($cleanBase);
        $wordsBase = strtolower(str_replace(['-', '_'], ' ', $cleanBase));

        return $newQuery()
            ->where('slug', $slugBase)
            ->orWhereRaw('LOWER(word) = ?', [$wordsBase])
            ->orWhereRaw('LOWER(indonesian) = ?', [$wordsBase])
            ->first();
    }
}
