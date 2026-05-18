@php
$showBackHomeButton = $showBackHomeButton ?? false;
@endphp

<div class="row g-4 justify-content-center">
    @forelse($lowongans as $lowongan)
    @php
    $detailUrl = route('lowongan-kerja.show', $lowongan->id);
    $isActive = strtolower($lowongan->status_lowongan) === 'aktif';
    $rawKualifikasi = (string) $lowongan->kualifikasi;
    $kualifikasiItems = [];

    // Ambil isi dari tag <li> jika kualifikasi berasal dari editor HTML
    if (preg_match_all('/<li\b[^>]*>(.*?)<\/li>/is', $rawKualifikasi, $matches)) {
        foreach ($matches[1] as $item) {
            $text = trim(html_entity_decode(strip_tags($item)));

            if ($text !== '') {
                $kualifikasiItems[] = $text;
            }
        }
    } else {
        // Fallback jika isi bukan <li>, misalnya text biasa / paragraf
        $cleanText = str_replace(
            ['</p>', '<br>', '<br/>', '<br />'],
            "\n",
            $rawKualifikasi
        );

        $plainText = trim(html_entity_decode(strip_tags($cleanText)));

        $kualifikasiItems = preg_split('/\r\n|\r|\n/', $plainText);

        $kualifikasiItems = array_filter(array_map(function ($item) {
            return trim(preg_replace('/^\d+[\.\)]\s*/', '', $item));
        }, $kualifikasiItems));
    }

    // Batasi jumlah list yang tampil
    $kualifikasiItems = array_slice($kualifikasiItems, 0, 4);
    @endphp

        <div class="col-md-6 col-lg-4 d-flex">
            <article class="job-card h-100 w-100">
                <div class="job-card__body d-flex flex-column h-100">
                    <div class="job-card__header">
                        <div class="job-card__header-main">
                            <div class="job-card__icon">
                                <i class="fa fa-briefcase"></i>
                            </div>
                            <div>
                                <a href="{{ $detailUrl }}" class="job-card__title">{{ $lowongan->nama_lowongan }}</a>
                            </div>
                        </div>

                        <span class="job-card__badge {{ $isActive ? 'job-card__badge--active' : 'job-card__badge--inactive' }}">
                            {{ $lowongan->status_lowongan }}
                        </span>
                    </div>

                    <div class="job-card__panel job-card__panel--description">
                        <span class="job-card__section-label">Kualifikasi Singkat</span>
                        @if(count($kualifikasiItems))
                        <ol class="job-card__qualification-list">
                            @foreach($kualifikasiItems as $item)
                            <li>{{ \Illuminate\Support\Str::limit($item, 70) }}</li>
                            @endforeach
                        </ol>
                        @else
                        <div class="job-card__description text-muted">
                            Belum ada kualifikasi.
                        </div>
                        @endif
                    </div>

                    <ul class="job-card__meta mb-3">
                        <li class="job-card__meta-item">
                            <span class="job-card__meta-icon">
                                <i class="fa fa-calendar-alt"></i>
                            </span>
                            <div>
                                <span class="job-card__meta-label mb-">Periode Lowongan</span>
                                <span class="job-card__meta-value">
                                    {{ tanggalIndo($lowongan->tanggal_mulai) }} &ndash; {{ tanggalIndo($lowongan->tanggal_berakhir) }}
                                </span>
                            </div>
                        </li>
                    </ul>

                    <div class="job-card__actions mt-auto">
                        <a class="btn btn-primary btn-sm" href="{{ $detailUrl }}">
                            <i class="fa fa-eye me-2"></i>Lihat Detail
                        </a>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyToClipboard('{{ $detailUrl }}')">
                            <i class="fa fa-share-alt me-2"></i>Bagikan
                        </button>
                    </div>
                </div>
            </article>
        </div>
        @empty
        <div class="col-12">
            <div class="job-empty-state text-center p-5 my-2 shadow-sm">
                <i class="fa fa-briefcase fa-3x text-primary mb-3"></i>
                <h4 class="fw-bold mb-2">Belum ada lowongan tersedia</h4>
                <p class="text-muted mb-3">Silakan cek kembali di lain waktu. Kami terus memperbarui informasi lowongan secara berkala.</p>

                @if($showBackHomeButton)
                <a href="{{ url('/') }}" class="btn btn-primary rounded-pill px-4">Kembali ke Beranda</a>
                @endif
            </div>
        </div>
        @endforelse
</div>