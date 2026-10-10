@extends('admin.layout')

@section('title', 'Analisis Latihan Gesture')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">STATISTIK PEMBELAJARAN</p>
            <h1>Analisis &amp; Peringkat Gesture</h1>
            <p class="page-lede">{{ $totalAttempts }} total percobaan latihan terekam dari seluruh pengguna.</p>
        </div>
        <div class="toolbar-group">
            <a class="button button-quiet" href="{{ route('admin.dashboard') }}">Ke Dashboard</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:24px">
        {{-- Gesture Tersulit (Tingkat Akurasi Terendah) --}}
        <div>
            <div class="section-heading">
                <div>
                    <p class="eyebrow">PERLU PERHATIAN</p>
                    <h2>Gesture Tersulit</h2>
                </div>
                <p>Gesture dengan tingkat akurasi jawaban benar paling rendah.</p>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Gesture</th>
                            <th>Jenjang</th>
                            <th>Percobaan</th>
                            <th>Tingkat Akurasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($hardest as $h)
                            <tr>
                                <td>
                                    <strong>{{ $h->gesture?->word ?? 'Gesture #' . $h->gesture_id }}</strong>
                                    <small class="secondary">{{ $h->gesture?->slug }}</small>
                                </td>
                                <td>{{ \App\Models\Gesture::LEVELS[$h->gesture?->level] ?? $h->gesture?->level ?? '—' }}</td>
                                <td>{{ $h->total_attempts }} kali</td>
                                <td>
                                    <strong style="color:var(--text-berry);font-size:15px">{{ $h->accuracy }}%</strong>
                                    <small class="secondary">{{ $h->correct_attempts }} benar, {{ $h->wrong_attempts }} salah</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-state">Belum ada data latihan yang cukup (minimal 2 percobaan) untuk peringkat kesulitan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Gesture Terpopuler (Paling Banyak Dilatih) --}}
        <div>
            <div class="section-heading">
                <div>
                    <p class="eyebrow">AKTIVITAS TERTINGGI</p>
                    <h2>Gesture Paling Banyak Dilatih</h2>
                </div>
                <p>Kosakata yang paling sering dicoba dalam sesi latihan.</p>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Gesture</th>
                            <th>Jenjang</th>
                            <th>Total Percobaan</th>
                            <th>Akurasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($popular as $p)
                            <tr>
                                <td>
                                    <strong>{{ $p->gesture?->word ?? 'Gesture #' . $p->gesture_id }}</strong>
                                    <small class="secondary">{{ $p->gesture?->slug }}</small>
                                </td>
                                <td>{{ \App\Models\Gesture::LEVELS[$p->gesture?->level] ?? $p->gesture?->level ?? '—' }}</td>
                                <td><strong style="color:var(--text-forest)">{{ $p->total_attempts }} kali</strong></td>
                                <td>
                                    <strong>{{ $p->accuracy }}%</strong>
                                    <small class="secondary">{{ $p->correct_attempts }} benar</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-state">Belum ada sesi latihan yang terekam.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
