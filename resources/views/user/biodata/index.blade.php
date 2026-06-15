@extends('layouts.app')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ versioned_asset('user/css/vhire-custom.css') }}">
@include('partials.syarat-ketentuan-styles')

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .biodata-wizard .sticky-tabs {
        z-index: 10;
    }
</style>
@endpush

@php
    $accountDataLocked = auth()->user()->hasActiveEmploymentStatusLock();
    $documentDeleteLocked = $accountDataLocked;
@endphp

<div class="container-fluid service py-2">
    <div class="container py-4 py-lg-5 biodata-wizard">
        <div class="mx-auto mb-2 wow fadeInUp" data-wow-delay="0.2s">
            <div class="wizard-hero" id="start">
                <div class="wizard-hero__identity">
                    <span class="wizard-hero__eyebrow">
                        <i class="fa fa-user"></i>
                        <span>Profil Pelamar</span>
                    </span>
                </div>

                <div class="wizard-hero__progress">
                    <div class="wizard-hero__progress-top">
                        <span id="wizardCurrentStep">Langkah 1</span>
                        <span id="wizardProgressText">1 dari 6 langkah</span>
                    </div>
                    <span class="wizard-hero__progress-label" id="wizardStepLabel">Data Pribadi</span>
                    <div class="wizard-progress" aria-hidden="true">
                        <span class="wizard-progress__bar" id="wizardProgressBar"></span>
                    </div>
                </div>
            </div>
        </div>
        <form id="formWizard" method="POST" action="{{ route('biodata.store') }}" enctype="multipart/form-data">
            @csrf

            @if($accountDataLocked)
            <div class="alert alert-warning border-0 rounded-4 mb-4">
                Akun ini tercatat aktif bekerja, sehingga biodata dan dokumen dikunci untuk mencegah penggunaan ulang akun oleh orang lain.
            </div>
            @endif

            <div class="wizard-shell">
                <!-- Step Indicators -->
                <div class="sticky-tabs">
                    <ul class="nav nav-tabs form-steps" id="formTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#step1" type="button">
                                <span class="wizard-step__number">01</span>
                                <span class="wizard-step__content">
                                    <span class="wizard-step__title">Data Pribadi</span>
                                    <span class="wizard-step__caption">Identitas dan alamat utama</span>
                                </span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step2" type="button">
                                <span class="wizard-step__number">02</span>
                                <span class="wizard-step__content">
                                    <span class="wizard-step__title">Pendidikan</span>
                                    <span class="wizard-step__caption">Riwayat pendidikan terakhir</span>
                                </span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step3" type="button">
                                <span class="wizard-step__number">03</span>
                                <span class="wizard-step__content">
                                    <span class="wizard-step__title">Data Keluarga</span>
                                    <span class="wizard-step__caption">Informasi keluarga inti</span>
                                </span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step4" type="button">
                                <span class="wizard-step__number">04</span>
                                <span class="wizard-step__content">
                                    <span class="wizard-step__title">Kontak Darurat</span>
                                    <span class="wizard-step__caption">Kontak yang bisa dihubungi</span>
                                </span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step5" type="button">
                                <span class="wizard-step__number">05</span>
                                <span class="wizard-step__content">
                                    <span class="wizard-step__title">Dokumen Pribadi</span>
                                    <span class="wizard-step__caption">Unggah berkas wajib</span>
                                </span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#step6" type="button">
                                <span class="wizard-step__number">06</span>
                                <span class="wizard-step__content">
                                    <span class="wizard-step__title">Syarat dan Ketentuan</span>
                                    <span class="wizard-step__caption">Review akhir dan ajukan</span>
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="wizard-panel">
                    <div class="wizard-panel__body">
                        <!-- Tab Content -->
                        <div class="tab-content">
                <!-- Step Biodata -->
                <div class="tab-pane fade show active" id="step1">
                    <h6 class="text-primary">Biodata</h6>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" value="{{ Auth::user()->name }}" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>No KTP <span class="text-danger">*</span></label>
                            <input type="text" id="no_ktp" name="no_ktp" class="form-control" value="{{ Auth::user()->no_ktp }}" readonly @if($accountDataLocked) disabled @endif>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>No Telp <span class="text-danger">*</span></label>
                            <input type="tel" name="no_telp"
                                class="form-control"
                                value="{{ old('no_telp', $biodata->no_telp ?? '') }}"
                                pattern="[0-9]{11,13}"
                                inputmode="numeric"
                                required
                                oninvalid="this.setCustomValidity('Nomor HP harus 11 sampai 13 digit angka')"
                                oninput="this.setCustomValidity(''); this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>No Kartu Keluarga <span class="text-danger">*</span></label>
                            <input type="text" id="no_kk" name="no_kk"
                                class="form-control"
                                maxlength="16"
                                inputmode="numeric"
                                value="{{ old('no_kk', $biodata->no_kk ?? '') }}"
                                required>
                        </div>
                    </div>

                    @php
                    $selectedGender = old('jenis_kelamin', $biodata->jenis_kelamin ?? '');
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="">Pilih jenis kelamin</option>
                                <option value="M 男" {{ $selectedGender == 'M 男' ? 'selected' : '' }}>Laki-laki 男</option>
                                <option value="F 女" {{ $selectedGender == 'F 女' ? 'selected' : '' }}>Perempuan 女</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>NPWP <span class="text-danger">*</span></label>
                            <input type="text" id="npwp" name="no_npwp"
                                class="form-control"
                                maxlength="20"
                                inputmode="numeric"
                                value="{{ old('no_npwp', $biodata->no_npwp ?? '') }}"
                                required>
                        </div>
                    </div>

                    @php
                    $selectedAgama = old('agama', $biodata->agama ?? '');
                    $selectedVaksin = old('vaksin', $biodata->vaksin ?? '');
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Agama <span class="text-danger">*</span></label>
                            <select name="agama" class="form-select" required>
                                <option value="">Pilih agama</option>
                                @foreach(['ISLAM 伊斯兰教','KRISTEN PROTESTAN 基督教新教','KRISTEN KATHOLIK 天主教徒','BUDHA 佛教','HINDU 印度教','KHONGHUCU 儒教'] as $agama)
                                <option value="{{ $agama }}" {{ $selectedAgama == $agama ? 'selected' : '' }}>
                                    {{ $agama }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Vaksin <span class="text-danger">*</span></label>
                            <select name="vaksin" class="form-select" required>
                                <option value="">Pilih vaksin</option>
                                @foreach(['VAKSIN 1','VAKSIN 2','VAKSIN 3'] as $vaksin)
                                <option value="{{ $vaksin }}" {{ $selectedVaksin == $vaksin ? 'selected' : '' }}>
                                    {{ $vaksin }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h6 class="text-primary">Alamat KTP</h6>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Tempat Lahir <span class="text-danger">*</span></label>
                            <input type="text" name="tempat_lahir"
                                class="form-control"
                                value="{{ old('tempat_lahir', $biodata->tempat_lahir ?? '') }}"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Tanggal Lahir <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_lahir"
                                class="form-control"
                                value="{{ old('tanggal_lahir', $biodata->tanggal_lahir ?? '') }}"
                                required>
                        </div>
                    </div>

                    <input type="hidden" id="is_edit" value="{{ $biodata ? 1 : 0 }}">

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Provinsi
                                <span class="text-danger">*</span>
                            </label>
                            <select name="provinsi" id="provinsi_id" class="form-select" required>
                                @if($biodata && $biodata->kelurahan)
                                <option value="{{ $biodata->provinsi }}">{{ $biodata->getProvinsi->provinsi }}</option>
                                @else
                                <option value="" disabled selected>Pilih provinsi</option>
                                @endif
                                @foreach ($provinsis as $item)
                                <option value="{{ $item->id }}">{{ $item->provinsi }}</option>
                                @endforeach
                            </select>
                            @error('provinsi')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Kabupaten
                                <span class="text-danger">*</span>
                            </label>
                            <select name="kabupaten" id="kabupaten_id" class="form-select" required>
                                @if($biodata && $biodata->kabupaten)
                                <option value="{{ $biodata->kabupaten }}">{{ $biodata->getKabupaten->kabupaten }}</option>
                                @else
                                <option value="" disabled selected>Pilih kabupaten</option>
                                @endif
                            </select>
                            @error('kabupaten')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Kecamatan
                                <span class="text-danger">*</span>
                            </label>
                            <select name="kecamatan" id="kecamatan_id" class="form-select" required>
                                @if($biodata && $biodata->kecamatan)
                                <option value="{{ $biodata->kecamatan }}">{{ $biodata->getKecamatan->kecamatan }}</option>
                                @else
                                <option value="" disabled selected>Pilih kecamatan</option>
                                @endif
                            </select>
                            @error('kecamatan')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Kelurahan/Desa
                                <span class="text-danger">*</span>
                            </label>
                            <select name="kelurahan" id="kelurahan_id" class="form-select" required>
                                @if($biodata && $biodata->kelurahan)
                                <option value="{{ $biodata->kelurahan }}">{{ $biodata->getKelurahan->kelurahan }}</option>
                                @else
                                <option value="" disabled selected>Pilih kelurahan</option>
                                @endif
                            </select>
                            @error('kelurahan')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Alamat Lengkap <span class="text-danger">*</span></label>
                            <textarea name="alamat" class="form-control" required>{{ old('alamat', $biodata->alamat ?? '') }}</textarea>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Kode Pos <span class="text-danger">*</span></label>
                            <input type="text" name="kode_pos" maxlength="5"
                                class="form-control"
                                value="{{ old('kode_pos', $biodata->kode_pos ?? '') }}" required>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>RT <span class="text-danger">*</span></label>
                            <input type="text" name="rt" maxlength="3"
                                class="form-control"
                                value="{{ old('rt', $biodata->rt ?? '') }}" required>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>RW <span class="text-danger">*</span></label>
                            <input type="text" name="rw" maxlength="3"
                                class="form-control"
                                value="{{ old('rw', $biodata->rw ?? '') }}" required>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="sesuaiAlamatKtp">
                        <label class="form-check-label">Alamat KTP sesuai dengan domisili</label>
                    </div>

                    <div id="alamatDomisiliField" style="display:none">
                        <label>Alamat Domisili</label>
                        <textarea name="alamat_domisili" class="form-control">
                        {{ old('alamat_domisili', $biodata->alamat_domisili ?? '') }}
                        </textarea>
                    </div>

                    <h6 class="text-primary mt-3">Lain-lain</h6>

                    @php
                    $selectedGolongan = old('golongan_darah', $biodata->golongan_darah ?? '');
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Hobi <span class="text-danger">*</span> </label>
                            <input type="text" name="hobi"
                                class="form-control"
                                value="{{ old('hobi', $biodata->hobi ?? '') }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Golongan Darah <span class="text-danger">*</span> </label>
                            <select name="golongan_darah" class="form-select" required>
                                <option value="">Pilih</option>
                                @foreach(['A 型','B 型','O 型','AB 型'] as $gol)
                                <option value="{{ $gol }}" {{ $selectedGolongan == $gol ? 'selected' : '' }}>
                                    {{ $gol }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Tinggi Badan (cm) <span class="text-danger">*</span> </label>
                            <input type="number" name="tinggi_badan" maxlength="3"
                                class="form-control"
                                value="{{ old('tinggi_badan', $biodata->tinggi_badan ?? '') }}"
                                min="0" max="999" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Berat Badan (kg) <span class="text-danger">*</span></label>
                            <input type="number" name="berat_badan" maxlength="3"
                                class="form-control"
                                value="{{ old('berat_badan', $biodata->berat_badan ?? '') }}"
                                min="0" max="999" required>
                        </div>
                    </div>
                </div>

                <!-- Step Pendidikan -->
                <div class="tab-pane fade" id="step2">
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Pendidikan Terakhir <span class="text-danger">*</span>
                            </label>

                            @php
                            $pendidikanOptions = [
                            'SD 小学' => 'SD 小学',
                            'SMP 初中' => 'SMP 初中',
                            'SMA 高中' => 'SMA 高中',
                            'SMK 高中' => 'SMK 高中',
                            'D3 大专三年' => 'D3 大专三年',
                            'D4 大专三年' => 'D4 大专三年',
                            'S1 本科' => 'S1 本科',
                            'S2 研究生' => 'S2 研究生',
                            ];

                            $selectedPendidikan = old(
                            'pendidikan_terakhir',
                            $biodata->pendidikan_terakhir ?? ''
                            );
                            @endphp

                            <select class="form-select" name="pendidikan_terakhir" required>
                                <option value="">Pilih pendidikan terakhir</option>
                                @foreach($pendidikanOptions as $value => $label)
                                <option value="{{ $value }}"
                                    {{ $selectedPendidikan === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Nama Sekolah / Kampus <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control"
                                name="nama_instansi"
                                value="{{ old('nama_instansi', $biodata->nama_instansi ?? '') }}"
                                required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Jurusan <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control"
                                name="jurusan"
                                value="{{ old('jurusan', $biodata->jurusan ?? '') }}"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Nilai Akhir / IPK <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control"
                                name="nilai_ipk"
                                id="nilai_ipk"
                                value="{{ old('nilai_ipk', $biodata->nilai_ipk ?? '') }}"
                                required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>
                                Tahun Lulus <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                name="tahun_lulus"
                                class="form-control"
                                value="{{ old('tahun_lulus', $biodata->tahun_lulus ?? '') }}"
                                required>
                        </div>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label>Prestasi</label>
                        <textarea
                            name="prestasi"
                            rows="5"
                            class="form-control">{{ old('prestasi', $biodata->prestasi ?? '') }}</textarea>
                    </div>
                </div>

                <!-- Step Keluarga -->
                <div class="tab-pane fade" id="step3">
                    <h6 class="text-primary">Nama Orang Tua</h6>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama Ibu <span class="text-danger">*</span></label>
                            <input type="text"
                                name="nama_ibu"
                                class="form-control"
                                value="{{ old('nama_ibu', $biodata->nama_ibu ?? '') }}"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nama Ayah <span class="text-danger">*</span></label>
                            <input type="text"
                                name="nama_ayah"
                                class="form-control"
                                value="{{ old('nama_ayah', $biodata->nama_ayah ?? '') }}"
                                required>
                        </div>
                    </div>

                    <h6 class="text-primary">Status Pernikahan</h6>

                    @php
                    $statusOptions = [
                    'Belum Kawin' => 'Belum Kawin',
                    'Kawin' => 'Kawin',
                    'Cerai Hidup' => 'Cerai Hidup',
                    'Cerai Mati' => 'Cerai Mati'
                    ];

                    $selectedStatus = old(
                    'status_pernikahan',
                    $biodata->status_pernikahan ?? ''
                    );
                    @endphp

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Status Pernikahan <span class="text-danger">*</span></label>
                            <select name="status_pernikahan"
                                id="status_pernikahan"
                                class="form-select"
                                required>

                                <option value="">Pilih status pernikahan</option>

                                @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}"
                                    {{ $selectedStatus === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="keluarga_section">

                        <div class="col-md-6 mb-3">
                            <label>Tanggal Pernikahan</label>
                            <input type="date"
                                name="tanggal_nikah"
                                id="tanggal_nikah"
                                class="form-control"
                                value="{{ old('tanggal_nikah', $biodata->tanggal_nikah ?? '') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nama Suami / Istri</label>
                            <input type="text"
                                name="nama_pasangan"
                                id="nama_pasangan"
                                class="form-control"
                                value="{{ old('nama_pasangan', $biodata->nama_pasangan ?? '') }}">
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label>Jumlah Anak</label>
                                <input type="number"
                                    name="jumlah_anak"
                                    class="form-control"
                                    max="3"
                                    value="{{ old('jumlah_anak', $biodata->jumlah_anak ?? '') }}">
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nama Anak ke-1</label>
                            <input type="text"
                                name="nama_anak_1"
                                class="form-control"
                                value="{{ old('nama_anak_1', $biodata->nama_anak_1 ?? '') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nama Anak ke-2</label>
                            <input type="text"
                                name="nama_anak_2"
                                class="form-control"
                                value="{{ old('nama_anak_2', $biodata->nama_anak_2 ?? '') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Nama Anak ke-3</label>
                            <input type="text"
                                name="nama_anak_3"
                                class="form-control"
                                value="{{ old('nama_anak_3', $biodata->nama_anak_3 ?? '') }}">
                        </div>

                    </div>
                </div>

                <!-- Step Kontak Darurat -->
                <div class="tab-pane fade" id="step4">

                    <div class="col-md-6 mb-3">
                        <label>Nama kontak darurat <span class="text-danger">*</span></label>
                        <input type="text"
                            name="nama_kontak_darurat"
                            class="form-control"
                            value="{{ old('nama_kontak_darurat', $biodata->nama_kontak_darurat ?? '') }}"
                            required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>No telepon <span class="text-danger">*</span></label>

                        <input type="tel" name="no_telp_darurat"
                            class="form-control"
                            value="{{ old('no_telp_darurat', $biodata->no_telepon_darurat ?? '') }}"
                            pattern="[0-9]{11,13}"
                            inputmode="numeric"
                            required
                            oninvalid="this.setCustomValidity('Nomor HP harus 11 sampai 13 digit angka')"
                            oninput="this.setCustomValidity(''); this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Status hubungan <span class="text-danger">*</span></label>
                        <select name="status_hubungan" class="form-select" required>
                            <option value="">Pilih status hubungan</option>

                            @php
                            $statusHubungan = old('status_hubungan', $biodata->status_hubungan ?? '');
                            @endphp

                            <option value="Orang Tua" {{ $statusHubungan == 'Orang Tua' ? 'selected' : '' }}>Orang Tua</option>
                            <option value="Pasangan" {{ $statusHubungan == 'Pasangan' ? 'selected' : '' }}>Pasangan</option>
                            <option value="Saudara" {{ $statusHubungan == 'Saudara' ? 'selected' : '' }}>Saudara</option>
                            <option value="Sepupu" {{ $statusHubungan == 'Sepupu' ? 'selected' : '' }}>Sepupu</option>
                            <option value="Teman Dekat" {{ $statusHubungan == 'Teman Dekat' ? 'selected' : '' }}>Teman</option>
                        </select>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="tab-pane fade" id="step5">

                    <div class="accordion" id="alertAccordion">
                        <div class="accordion-item rounded-3 shadow-sm mb-3">
                            <h2 class="accordion-header">
                                <button class="accordion-button bg-opacity-25 text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#alertBody" aria-expanded="true" aria-controls="alertBody">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    Petunjuk Pengunggahan Dokumen
                                </button>
                            </h2>
                            <div id="alertBody" class="accordion-collapse collapse show" data-bs-parent="#alertAccordion">
                                <div class="row g-5 align-items-center">
                                    <div class="col-xl-6 wow fadeInLeft" data-wow-delay="0.2s" style="visibility: visible; animation-delay: 0.2s; animation-name: fadeInLeft;">
                                        <div class="accordion-body">
                                            <p class="mb-3 fw-bold">
                                                Agar dokumen lamaran kamu bisa diproses dengan lancar, pastikan:
                                            </p>
                                            <ul class="mb-0 ps-3">
                                                <li class="mb-1">Foto dokumen diambil dalam posisi tegak (tidak miring atau terbalik)</li>
                                                <li class="mb-1">Teks pada dokumen terlihat jelas dan mudah dibaca</li>
                                                <li class="mb-1">Gunakan pencahayaan yang cukup, jangan terlalu gelap atau silau</li>
                                                <li class="mb-1">Pastikan tidak ada bagian penting yang tertutup, misalnya oleh tangan, stiker, atau pantulan cahaya</li>
                                                <li class="mb-1">Jangan menambahkan tulisan, coretan, atau gambar lain di luar isi dokumen</li>
                                            </ul>
                                            <span>
                                                <small class="text-danger mt-2 d-block">
                                                    Jika dokumen tidak sesuai, proses pengajuanmu bisa terhambat atau tidak diterima. Harap pastikan semua sudah benar sebelum mengunggah!
                                                </small>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-xl-3 wow fadeInRight" data-wow-delay="0.4s" style="visibility: visible; animation-delay: 0.4s; animation-name: fadeInRight;">
                                        <div class="rounded p-3 d-inline-block text-center">
                                            <p class="mb-0 fw-bold">
                                                Contoh letak foto KTP yang baik
                                            </p>
                                            <img src="{{ asset('img/example-ktp.jpg') }}" alt="foto contoh KTP" class="img-fluid w-100" style="height: 160px;">
                                        </div>
                                    </div>
                                    <div class="col-xl-3 wow fadeInRight" data-wow-delay="0.4s" style="visibility: visible; animation-delay: 0.4s; animation-name: fadeInRight;">
                                        <div class="rounded p-3 d-inline-block text-center">
                                            <p class="mb-2 fw-bold">
                                                Contoh letak foto SIM yang baik
                                            </p>
                                            <img src="{{ asset('img/example-sim_b2.png') }}" alt="foto contoh KTP" class="img-fluid w-100" style="height: 160px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 wow fadeInLeft" data-wow-delay="0.2s" style="visibility: visible; animation-delay: 0.4s; animation-name: fadeInLeft;">

                        @php
                        $dokumenFields = [
                        'cv' => ['label' => 'CV (pdf)', 'accept' => '.pdf', 'max_kb' => 2048],
                        'pas_foto' => ['label' => 'Pas Foto 3x4 (jpeg, jpg, png)', 'accept' => '.png,.jpg,.jpeg', 'max_kb' => 2048],
                        'surat_lamaran' => ['label' => 'Surat Lamaran Kerja (pdf)', 'accept' => '.pdf', 'max_kb' => 2048],
                        'ijazah' => ['label' => 'Ijazah dan Transkrip nilai (pdf)', 'accept' => '.pdf', 'max_kb' => 2048],
                        'ktp' => ['label' => 'Kartu Tanda Penduduk (KTP) (jpg, jpeg, png)', 'accept' => '.jpg,.jpeg,.png', 'max_kb' => 2048, 'onchange' => 'handleKtpOCR(this)'],
                        'sim_b_2' => ['label' => 'SIM B II Umum (jpg, jpeg, png) <sup>wajib bagi pelamar DT/OPR</sup>', 'accept' => '.jpg,.jpeg,.png', 'max_kb' => 2048, 'onchange' => 'handleSimB2OCR(this)'],
                        'skck' => ['label' => 'SKCK (pdf)', 'accept' => '.pdf', 'max_kb' => 2048],
                        'sio' => ['label' => 'SIO (jpeg, jpg, png) <sup>wajib bagi pelamar DT/OPR</sup>', 'accept' => '.png,.jpg,.jpeg', 'max_kb' => 2048],
                        'sertifikat_vaksin' => ['label' => 'Sertifikat Vaksin (pdf)', 'accept' => '.pdf', 'max_kb' => 2048],
                        'kartu_keluarga' => ['label' => 'Kartu Keluarga (pdf)', 'accept' => '.pdf', 'max_kb' => 2048],
                        'npwp' => ['label' => 'NPWP (pdf)', 'accept' => '.pdf', 'max_kb' => 2048],
                        'ak1' => ['label' => 'Kartu Pencari Kejra (AK1) (pdf)', 'accept' => '.pdf', 'max_kb' => 2048],
                        'sertifikat_pendukung' => ['label' => 'Sertifikat Pendukung (pdf)', 'accept' => '.pdf', 'max_kb' => 51200],
                        ];
                        @endphp

                        @if($documentDeleteLocked)
                        <div class="col-12">
                            <div class="alert alert-warning border-0 rounded-4 mb-3">
                                Dokumen yang sudah diunggah tidak dapat dihapus karena status akun Anda tercatat aktif bekerja.
                            </div>
                        </div>
                        @endif

                        <div class="row g-3 wow fadeInLeft" data-wow-delay="0.2s" style="visibility: visible; animation-delay: 0.4s; animation-name: fadeInLeft;">
                            @foreach($dokumenFields as $field => $meta)
                            @php
                            $filename = $biodata->$field ?? null;
                            $label = $meta['label'];
                            $accept = $meta['accept'];
                            $maxKb = $meta['max_kb'] ?? 2048;
                            $maxSizeLabel = $maxKb >= 1024 ? rtrim(rtrim(number_format($maxKb / 1024, 2, '.', ''), '0'), '.') . ' MB' : $maxKb . ' KB';
                            $inputId = $field . '-upload';
                            $spanId = 'file-name-' . str_replace('_', '-', $field);
                            $ocrResultId = 'ocr-result-' . str_replace('_', '-', $field); // Tambahan
                            @endphp

                            <div class="col-md-6 mb-2 document-upload-item"
                                data-field="{{ $field }}"
                                data-accept="{{ $accept }}"
                                data-max-kb="{{ $maxKb }}"
                                data-input-id="{{ $inputId }}"
                                data-span-id="{{ $spanId }}"
                                data-ocr-result-id="{{ $ocrResultId }}">
                                <label class="form-label">{!! strip_tags($label, '<sup>') !!}</label>

                                <div class="document-upload-content">
                                    @if(!$biodata || !$filename)
                                    <div class="file-upload-box">
                                        <div class="upload-label">
                                            <i class="bi bi-file-earmark-text file-icon"></i>
                                            <span id="{{ $spanId }}">{{ $filename ? $filename : 'Dokumen belum diunggah' }}</span>
                                        </div>
                                        <label for="{{ $inputId }}" class="btn btn-upload">Unggah</label>
                                        <input type="file" name="{{ $field }}" id="{{ $inputId }}" accept="{{ $accept }}" data-accept="{{ $accept }}"
                                            onchange="uploadDocumentAjax(this)">
                                    </div>
                                    @else
                                    <div class="file-box">
                                        <div class="file-info">
                                            <i class="bi bi-file-earmark-text file-icon"></i>
                                            <div class="file-meta">
                                                <span class="file-name-{{ str_replace('_', '-', $field) }}">{{ $filename }}</span>
                                                <input type="hidden" name="{{ $field }}" value="{{ $filename }}">
                                            </div>
                                        </div>
                                        <div class="btn-group-custom">
                                            <a href="{{ asset(Auth::user()->no_ktp . '/dokumen/' . $filename) }}" target="_blank" class="btn btn-view">Lihat</a>
                                            <button type="button"
                                                class="btn btn-delete btn-confirm-delete"
                                                data-url="{{ route('biodata.deleteFile', ['field' => $field]) }}"
                                                data-field="{{ $field }}"
                                                @if($documentDeleteLocked) disabled title="Dokumen tidak dapat dihapus karena akun Anda tercatat aktif bekerja." @endif>
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <span class="small text-muted d-block mt-1">
                                    Maksimal ukuran file: {{ $maxSizeLabel }}
                                </span>

                                @if($field === 'sertifikat_pendukung')
                                <span class="small text-muted fw-bold d-block mt-1">
                                    <i class="bi bi-exclamation-circle me-1"></i>
                                    Sertifikat lebih dari 1? Gabungkan semua dalam satu file PDF sebelum diunggah!
                                </span>
                                @endif
                                {{-- Tempat hasil OCR ditampilkan hanya untuk KTP dan SIM B2 --}}
                                @if(in_array($field, ['ktp', 'sim_b_2']))
                                <div id="{{ $ocrResultId }}" class="mt-2 small text-muted">Hasil baca dokumen akan muncul di sini.</div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div id="ocr-compare-result" class="mt-3"></div>
                </div>

                <!-- Step 6 -->
                <div class="tab-pane fade" id="step6">
                    <div class="row g-3">
                        <div class="col-12">
                            <div id="termsBox" class="terms-document-frame">
                                @if($syaratKetentuan && filled($syaratKetentuan->syarat_ketentuan))
                                    <article class="terms-document">
                                        {!! $syaratKetentuan->syarat_ketentuan !!}
                                    </article>
                                @else
                                    <div class="alert alert-warning mb-0">
                                        Syarat dan ketentuan rekrutmen belum tersedia. Silakan hubungi admin sebelum mengajukan biodata.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check terms-approval-check">
                                <input class="form-check-input @error('menyetujui_syarat') is-invalid @enderror" type="checkbox" name="menyetujui_syarat" value="1" id="checkBox1"
                                    {{ old('menyetujui_syarat') || ($biodata && $biodata->status_pernyataan) ? 'checked' : 'disabled' }}
                                    @if(! $syaratKetentuan || blank($syaratKetentuan->syarat_ketentuan) || $accountDataLocked) disabled @endif>
                                <label class="form-check-label" for="checkBox1">
                                    Saya telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan rekrutmen PT VDNI yang ditampilkan di atas.
                                </label>
                                @error('menyetujui_syarat')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="wizard-actions">
                    <button type="button" class="btn btn-dark" id="prevBtn" disabled>Sebelumnya</button>

                    <div class="wizard-actions__next">
                        <button type="submit" class="btn btn-success d-none" id="submitBtn" @if($accountDataLocked) disabled @endif>Ajukan</button>
                        <button type="button" class="btn btn-primary" id="nextBtn">Selanjutnya</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    window.accountDataLocked = @json($accountDataLocked);
    window.documentDeleteLocked = @json($documentDeleteLocked);
</script>

<script>

    function renderUploadDocumentState(container, field, accept) {
        const inputId = container.dataset.inputId || `${field}-upload`;
        const spanId = container.dataset.spanId || `file-name-${field.replaceAll('_', '-')}`;
        const content = container.querySelector('.document-upload-content');

        if (!content) {
            return;
        }

        content.innerHTML = `
            <div class="file-upload-box">
                <div class="upload-label">
                    <i class="bi bi-file-earmark-text file-icon"></i>
                    <span id="${spanId}">Dokumen belum diunggah</span>
                </div>
                <label for="${inputId}" class="btn btn-upload">Unggah</label>
                <input type="file" name="${field}" id="${inputId}" accept="${accept}" data-accept="${accept}" onchange="uploadDocumentAjax(this)">
            </div>
        `;
    }

    function renderUploadedDocumentState(container, field, fileName, filePath) {
        const content = container.querySelector('.document-upload-content');

        if (!content) {
            return;
        }

        content.innerHTML = `
            <div class="file-box">
                <div class="file-info">
                    <i class="bi bi-file-earmark-text file-icon"></i>
                    <div class="file-meta">
                        <span class="file-name-${field.replaceAll('_', '-')}">${fileName}</span>
                        <input type="hidden" name="${field}" value="${fileName}">
                    </div>
                </div>
                <div class="btn-group-custom">
                    <a href="/${filePath}" target="_blank" class="btn btn-view">Lihat</a>
                    <button type="button"
                        class="btn btn-delete btn-confirm-delete"
                        data-url="{{ url('biodata/delete-file') }}/${field}"
                        data-field="${field}"
                        ${window.documentDeleteLocked ? 'disabled title="Dokumen tidak dapat dihapus karena akun Anda tercatat aktif bekerja."' : ''}>
                        Hapus
                    </button>
                </div>
            </div>
        `;
    }

    async function uploadDocumentAjax(input) {
        const file = input.files[0];
        if (!file) return;
        const field = input.name;
        const container = input.closest('.document-upload-item, .col-md-6');
        const maxKb = Number(container?.dataset.maxKb || 2048);
        const maxBytes = maxKb * 1024;
        const uploadBox = container?.querySelector('.file-upload-box') || { innerHTML: '' };
        const fileNameSpan = container?.querySelector(`#file-name-${field.replaceAll('_', '-')}`) || { innerHTML: '' };

        function formatFileSize(bytes) {
            if (bytes >= 1024 * 1024) {
                return `${(bytes / (1024 * 1024)).toFixed(2).replace(/\.00$/, '')} MB`;
            }

            return `${Math.ceil(bytes / 1024)} KB`;
        }

        clearFieldError(input);

        // ==== DETEKSI GOOGLE DRIVE ====
        if (file.type === '' && file.size > 0) {
            setFieldError(input, 'File dari Google Drive tidak didukung. Silakan download file ke perangkat terlebih dahulu lalu upload ulang.');
            Swal.fire({
                icon: 'warning',
                text: 'File dari Google Drive tidak didukung. Silakan download file ke HP terlebih dahulu lalu upload ulang.'
            });
            input.value = '';
            return;
        }

        if (file.size > maxBytes) {
            setFieldError(input, `Ukuran file melebihi batas ${formatFileSize(maxBytes)}. Silakan kompres file lalu upload ulang.`);
            Swal.fire({
                icon: 'warning',
                text: `Ukuran file melebihi batas ${formatFileSize(maxBytes)}. Silakan kompres file lalu upload ulang.`
            });
            input.value = '';
            if (fileNameSpan) {
                fileNameSpan.innerHTML = 'Dokumen belum diunggah';
            }
            return;
        }


        fileNameSpan.innerHTML = '⏳ Mengunggah...';

        fileNameSpan.innerHTML = 'Mengunggah...';
        const formData = new FormData();
        formData.append(field, file);

        try {
            const response = await fetch("{{ route('biodata.upload.document') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            // ===== FETCH GAGAL TOTAL (FAILED TO FETCH) =====
            if (!response) {
                throw 'Upload gagal. Pastikan file berasal dari penyimpanan HP, bukan Google Drive.';
            }

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json')
                ? await response.json()
                : null;

            // ===== VALIDATION ERROR =====
            if (!response.ok) {
                if (response.status === 413) {
                    throw data?.message || 'Ukuran file melebihi batas upload. Maksimal 2 MB per dokumen, kecuali Sertifikat Pendukung maksimal 50 MB.';
                }

                if (data?.errors) {
                    throw Object.values(data.errors)[0][0];
                }
                throw data?.message || 'Upload gagal';
            }

            // ===== SUCCESS =====
            clearFieldError(input);
            uploadBox.innerHTML = `
        <div class="file-box">
            <div class="file-info">
                <i class="bi bi-file-earmark-text file-icon"></i>
                <div class="file-meta">
                    <span class="file-name-${field.replaceAll('_', '-')}">${data.file}</span>
                    <input type="hidden" name="${field}" value="${data.file}">
                </div>
            </div>
            <div class="btn-group-custom">
                <a href="/${data.path}" target="_blank" class="btn btn-view">Lihat</a>
                <button type="button"
                    class="btn btn-delete btn-confirm-delete"
                    data-url="{{ url('biodata/delete-file') }}/${field}"
                    data-field="${field}"
                    ${window.documentDeleteLocked ? 'disabled title="Dokumen tidak dapat dihapus karena akun Anda tercatat aktif bekerja."' : ''}>
                    Hapus
                </button>
            </div>
        </div>`;

            if (field === 'ktp') handleKtpOcr(input);
            if (field === 'sim_b_2') handleSimB2OCR(input);

        } catch (err) {
            let msg = err;

            if (err instanceof TypeError || err instanceof SyntaxError || (typeof err === 'string' && err.toLowerCase().includes('fetch'))) {
                msg = 'Upload gagal. Pastikan file berasal dari penyimpanan HP, bukan Google Drive.';
            }

            Swal.fire({
                icon: 'warning',
                text: msg
            });

            setFieldError(input, msg);

            if (fileNameSpan) {
                fileNameSpan.innerHTML = 'Dokumen belum diunggah';
            }
            input.value = '';
        }
    }


    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-confirm-delete');
        if (!btn) return;

        e.preventDefault();

        const container = btn.closest('.document-upload-item');
        const url = btn.dataset.url;
        const field = btn.dataset.field;
        const accept = container?.dataset.accept || '';

        if (!container) return;

        if (!confirm('Yakin ingin menghapus dokumen ini?')) return;

        fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(res => {
                if (!res.success) {
                    throw res.message || 'Gagal menghapus dokumen';
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Dokumen berhasil dihapus',
                    timer: 1500,
                    showConfirmButton: false
                });

                renderUploadDocumentState(container, field, accept);

                const ocrResultId = container.dataset.ocrResultId;
                const ocrResultElement = ocrResultId ? document.getElementById(ocrResultId) : null;
                if (ocrResultElement) {
                    ocrResultElement.innerHTML = 'Hasil baca dokumen akan muncul di sini.';
                }

                if (field === 'ktp') {
                    window.ktpOcrData = null;
                }

                if (field === 'sim_b_2') {
                    window.simOcrData = null;
                }
            });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const isEdit = document.getElementById('is_edit')?.value == 1;
        if (!isEdit) return;

        const provinsi = document.getElementById('provinsi_id');
        const kabupaten = document.getElementById('kabupaten_id');
        const kecamatan = document.getElementById('kecamatan_id');
        const kelurahan = document.getElementById('kelurahan_id');

        const kabKosong = !kabupaten.value;
        const kecKosong = !kecamatan.value;
        const kelKosong = !kelurahan.value;

        // Jika salah satu kosong → kosongkan provinsi saja
        if (kabKosong || kecKosong || kelKosong) {

            // Kosongkan provinsi
            provinsi.selectedIndex = 0;

            // Reset dropdown bawah ke placeholder (opsional tapi disarankan)
            kabupaten.innerHTML = '<option value="" selected disabled>Pilih kabupaten</option>';
            kecamatan.innerHTML = '<option value="" selected disabled>Pilih kecamatan</option>';
            kelurahan.innerHTML = '<option value="" selected disabled>Pilih kelurahan</option>';

            // Trigger ulang alur dropdown
            provinsi.dispatchEvent(new Event('change'));

            provinsi.focus();
        }
    });
</script>

<script>
    function handleSimB2OCR(input) {
        const file = input.files[0];
        const resultElement = document.getElementById("ocr-result-sim-b-2");

        if (!resultElement) {
            console.error("Elemen hasil OCR tidak ditemukan");
            return;
        }

        // tampilkan spinner/loading
        resultElement.innerHTML = "⏳ Memproses baca sim...";

        const formData = new FormData();
        formData.append("sim_b_2", file);

        fetch("{{ route('biodata.ocr.sim_b2') }}", {
                method: "POST",
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(result => {
                const resultElement = document.getElementById('ocr-result-sim-b-2');
                if (!resultElement) return; // Jangan lanjut jika elemen tidak ada

                if (result.success) {
                    const data = result.data;

                    window.simOcrData = data;
                    if (window.ktpOcrData) {
                        compareSimKtpOcr(window.simOcrData, window.ktpOcrData);
                    }

                    window.simOcrData = data; // Simpan data SIM secara global
                    if (window.ktpOcrData) compareSimKtpOcr(window.simOcrData, window.ktpOcrData);

                    resultElement.innerHTML = `
                        <div class="card mt-1">
                            <div class="card-header fw-bold">Hasil Baca SIM :</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Nama Lengkap:</strong> ${data.nama}</p>
                                        <p><strong>Tempat Lahir:</strong>${data.tempat_lahir}</p>
                                        <p><strong>Tanggal Lahir:</strong> ${formatTanggal(data.tanggal_lahir)}</p>
                                        <p><strong>Jenis Kelamin:</strong> ${formatJenisKelamin(data.jenis_kelamin)}</p>
                                        <p><strong>Alamat:</strong> ${data.alamat}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Pekerjaan:</strong> ${data.pekerjaan}</p>
                                        <p><strong>Wilayah Penerbit:</strong> ${data.wilayah}</p>
                                        <p><strong>Berlaku Sampai:</strong> ${formatTanggal(data.berlaku_sampai)}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    resultElement.innerHTML = `<span class="text-danger">${result.message || 'Gagal membaca data OCR.'}</span>`;
                }
            })
            .catch(error => {
                resultElement.innerHTML = `<span class="text-danger">Gagal OCR</span>`;
                console.error("OCR error:", error);
            });
    }

    function handleKtpOcr(input) {
        const file = input.files[0];
        const resultElement = document.getElementById("ocr-result-ktp");

        if (!resultElement) {
            console.error("Elemen hasil OCR tidak ditemukan");
            return;
        }

        // tampilkan spinner/loading
        resultElement.innerHTML = "⏳ Memproses baca ktp...";

        const formData = new FormData();
        formData.append("ktp", file);

        fetch("{{ route('biodata.ocr.ktp') }}", {
                method: "POST",
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(result => {
                const resultElement = document.getElementById('ocr-result-ktp');
                if (!resultElement) return; // Jangan lanjut jika elemen tidak ada

                if (result.success) {
                    const data = result.data;

                    window.ktpOcrData = data;
                    if (window.simOcrData) {
                        compareSimKtpOcr(window.simOcrData, window.ktpOcrData);
                    }

                    resultElement.innerHTML = `
                    <div class="card mt-1">
                        <div class="card-header fw-bold">Hasil baca KTP :</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>NIK:</strong> ${data.result.nik?.value || '-'}</p>
                                    <p><strong>Nama Lengkap:</strong> ${data.result.nama?.value || '-'}</p>
                                    <p><strong>Tempat Lahir:</strong> ${data.result.tempatLahir?.value || '-'}</p>
                                    <p><strong>Tanggal Lahir:</strong> ${formatTanggal(data.result.tanggalLahir?.value || '-')}</p>
                                    <p><strong>Jenis Kelamin:</strong> ${data.result.jenisKelamin?.value || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Golongan Darah:</strong> ${data.result.golonganDarah?.value || '-'}</p>
                                    <p><strong>Status Perkawinan:</strong> ${data.result.statusPerkawinan?.value || '-'}</p>
                                    <p><strong>Agama:</strong> ${data.result.agama?.value || '-'}</p>
                                    <p><strong>Pekerjaan:</strong> ${data.result.pekerjaan?.value || '-'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    `;
                } else {
                    resultElement.innerHTML = `<span class="text-danger">${result.message || 'Gagal membaca data KTP.'}</span>`;

                    if (result.clear_file) {
                        const container = document.querySelector('.document-upload-item[data-field="ktp"]');
                        if (container) {
                            renderUploadDocumentState(container, 'ktp', container.dataset.accept || '.jpg,.jpeg,.png');
                        }
                        window.ktpOcrData = null;
                    }
                }
            })
            .catch(error => {
                resultElement.innerHTML = `<span class="text-danger">Gagal membaca data OCR</span>`;
            });
    }

    function compareSimKtpOcr(simData, ktpData) {
        const resultElement = document.getElementById("ocr-compare-result");
        if (!resultElement) return;

        const errors = [];

        /* =====================
           VALIDASI NAMA (OCR FRIENDLY)
        ===================== */
        const normalizeName = (name) =>
            name
            .toLowerCase()
            .replace(/[^a-z\s]/g, '') // hapus titik & simbol
            .trim()
            .split(/\s+/);

        const simParts = normalizeName(simData.nama || '');
        const ktpParts = normalizeName(ktpData.result.nama?.value || '');

        let namaSesuai = true;

        // Nama depan wajib sama
        if (!simParts[0] || !ktpParts[0] || simParts[0] !== ktpParts[0]) {
            namaSesuai = false;
        } else {
            // Setiap kata SIM harus cocok (full / inisial) dengan KTP
            for (let i = 0; i < simParts.length; i++) {
                const simWord = simParts[i];
                const ktpWord = ktpParts[i];

                if (!ktpWord || !ktpWord.startsWith(simWord)) {
                    namaSesuai = false;
                    break;
                }
            }
        }

        if (!namaSesuai) {
            errors.push('❌ Nama pada SIM tidak sesuai dengan nama pada KTP.');
        }

        /* =====================
           VALIDASI TANGGAL LAHIR
        ===================== */
        const simTanggal = formatTanggalIso(simData.tanggal_lahir);
        const ktpTanggal = formatTanggalIso(ktpData.result.tanggalLahir?.value);

        if (simTanggal !== ktpTanggal) {
            errors.push('❌ Tanggal lahir pada SIM tidak sesuai dengan KTP.');
        }

        /* =====================
           VALIDASI MASA BERLAKU SIM
        ===================== */
        const simBerlakuSampai = new Date(simData.berlaku_sampai);
        const today = new Date();

        if (simBerlakuSampai < today) {
            errors.push('❌ Tanggal berlaku SIM sudah kadaluarsa.');
        }

        /* =====================
           TAMPILKAN HASIL
        ===================== */
        if (errors.length > 0) {
            resultElement.innerHTML = `
            <div class="alert alert-warning" role="alert">
                <h5 class="mb-2">Hasil Validasi Data SIM dan KTP:</h5>
                <ul class="mb-0">
                    ${errors.map(e => `<li>${e}</li>`).join('')}
                </ul>
            </div>
        `;
        } else {
            resultElement.innerHTML = `
            <div class="alert alert-success" role="alert">
                ✅ Data SIM dan KTP sesuai.
            </div>
        `;
        }
    }


    // Fungsi bantu format tanggal jadi yyyy-mm-dd
    function formatTanggalIso(tanggal) {
        if (!tanggal) return '';
        const d = new Date(tanggal);
        if (isNaN(d.getTime())) return ''; // Cek invalid date
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatTanggal(tanggal) {
        const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        const [dd, mm, yyyy] = tanggal.split('-');
        return `${parseInt(dd)} ${bulan[parseInt(mm) - 1]} ${yyyy}`;
    }

    function formatJenisKelamin(jk) {
        if (!jk) return '';
        jk = jk.toUpperCase();
        return jk === 'PRIA' ? 'Pria' : jk === 'WANITA' ? 'Wanita' : jk;
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkbox = document.getElementById('sesuaiAlamatKtp');
        const alamatDomisili = document.getElementById('alamatDomisiliField');

        function toggleAlamatDomisili() {
            alamatDomisili.style.display = checkbox.checked ? 'none' : 'block';
        }

        // Initial toggle
        toggleAlamatDomisili();

        // Event listener on checkbox
        checkbox.addEventListener('change', toggleAlamatDomisili);
    });

    document.addEventListener('DOMContentLoaded', function() {
        const nilaiInput = document.getElementById('nilai_ipk');

        nilaiInput.addEventListener('blur', function() {
            let rawVal = nilaiInput.value.replace(',', '').replace('.', ''); // Hilangkan koma/titik

            // Hanya ambil digit angka
            let numericOnly = rawVal.replace(/\D/g, '');

            if (numericOnly.length >= 2) {
                // Sisipkan koma setelah digit pertama, contoh: 396 -> 3,96
                let result = numericOnly.slice(0, 1) + ',' + numericOnly.slice(1, 3);
                nilaiInput.value = result;
            } else if (numericOnly.length === 1) {
                // Contoh: 3 -> 3,00
                nilaiInput.value = numericOnly + ',00';
            } else {
                nilaiInput.value = '';
            }
        });
    });

    function toggleAlertContent(button) {
        const content = document.getElementById('alertContent');
        const icon = button.querySelector('i');

        if (content.style.display === 'none') {
            content.style.display = 'flex';
            icon.classList.remove('fa-plus');
            icon.classList.add('fa-minus');
        } else {
            content.style.display = 'none';
            icon.classList.remove('fa-minus');
            icon.classList.add('fa-plus');
        }
    }
</script>

<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#provinsi_id').on('change', function() {
            var provinsiID = $(this).val();
            if (provinsiID) {
                $.ajax({
                    url: 'api/kabupaten/' + provinsiID,
                    type: "GET",
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data) {
                            $('#kabupaten_id').empty();
                            $('#kabupaten_id').append('<option value="" disabled selected>Pilih kabupaten</option>');
                            $.each(data, function(id, kabupaten) {
                                $('select[name="kabupaten"]').append('<option value="' + kabupaten.id + '">' + kabupaten.kabupaten + '</option>');
                            });
                        } else {
                            $('#kabupaten_id').empty().append('<option value="" disabled selected>Pilih kabupaten</option>');
                        }
                    }
                });
            } else {
                $('#kabupaten_id').empty();
            }
        });

        $('#kabupaten_id').on('change', function() {
            var kabupatenID = $(this).val();
            if (kabupatenID) {
                $.ajax({
                    url: 'api/kecamatan/' + kabupatenID,
                    type: "GET",
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data) {
                            $('#kecamatan_id').empty();
                            $('#kecamatan_id').append('<option value="" disabled selected>Pilih kecamatan</option>');
                            $.each(data, function(id, kecamatan) {
                                $('select[name="kecamatan"]').append('<option value="' + kecamatan.id + '">' + kecamatan.kecamatan + '</option>');
                            })
                        } else {
                            $('#kecamatan_id').empty().append('<option value="" disabled selected>Pilih kecamatan</option>');
                        }
                    }
                });
            }
        });

        $('#kecamatan_id').on('change', function() {
            var kecamatanID = $(this).val();
            if (kecamatanID) {
                $.ajax({
                    url: 'api/kelurahan/' + kecamatanID,
                    type: "GET",
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    dataType: "json",
                    success: function(data) {
                        $('#kelurahan_id').empty();

                        // default kosong agar required bekerja
                        $('#kelurahan_id').append('<option value="" disabled selected>Pilih kelurahan/desa</option>');

                        if (data) {
                            $.each(data, function(id, kelurahan) {
                                $('#kelurahan_id')
                                    .append('<option value="' + kelurahan.id + '">' + kelurahan.kelurahan + '</option>');
                            });
                        }
                    }
                });
            } else {
                $('#kelurahan_id').empty().append('<option value="" disabled selected>Pilih kelurahan/desa</option>');
            }
        });
    });
</script>

<script>
    const tabs = Array.from(document.querySelectorAll('#formTabs .nav-link'));
    const stepPanes = Array.from(document.querySelectorAll('#formWizard .tab-pane'));

    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('formWizard');
    const wizardCurrentStep = document.getElementById('wizardCurrentStep');
    const wizardProgressText = document.getElementById('wizardProgressText');
    const wizardStepLabel = document.getElementById('wizardStepLabel');
    const wizardProgressBar = document.getElementById('wizardProgressBar');
    let formSubmitting = false;
    let currentStep = 0;

    function escapeAttributeValue(value) {
        return (value || '').replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }

    function getFieldByName(name) {
        if (!form || !name) {
            return null;
        }

        return form.querySelector(`[name="${escapeAttributeValue(name)}"]`);
    }

    function getFieldWrapper(field) {
        return field?.closest('.document-upload-item, .form-check, .mb-3, .col-md-6, .col-md-12, .col-12') || field?.parentElement || null;
    }

    function getFieldInsertTarget(field, wrapper) {
        if (field?.type === 'file') {
            return wrapper?.querySelector('.document-upload-content') || field;
        }

        if (field?.type === 'checkbox') {
            return wrapper?.querySelector('.form-check-label') || field;
        }

        return field;
    }

    function getOrCreateFieldErrorElement(field) {
        const wrapper = getFieldWrapper(field);
        if (!wrapper) {
            return null;
        }

        const existingFeedback = wrapper.querySelector(`.invalid-feedback[data-field-error-for="${field.name}"]`);
        if (existingFeedback) {
            return existingFeedback;
        }

        const siblingFeedback = field.nextElementSibling;
        if (siblingFeedback?.classList.contains('invalid-feedback')) {
            siblingFeedback.dataset.fieldErrorFor = field.name;
            return siblingFeedback;
        }

        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.dataset.fieldErrorFor = field.name;

        const insertTarget = getFieldInsertTarget(field, wrapper);
        insertTarget?.insertAdjacentElement('afterend', feedback);

        return feedback;
    }

    function getFieldLabel(field) {
        if (!field) {
            return 'Field ini';
        }

        if (field.id) {
            const explicitLabel = document.querySelector(`label[for="${field.id}"]`);
            if (explicitLabel?.textContent) {
                return explicitLabel.textContent.replace('*', '').trim();
            }
        }

        const wrapper = getFieldWrapper(field);
        const nearestLabel = wrapper?.querySelector('label');

        return nearestLabel?.textContent?.replace('*', '').trim() || field.name || 'Field ini';
    }

    function getFieldValidationMessage(field) {
        const label = getFieldLabel(field);
        const validity = field.validity;

        if (validity.customError && field.validationMessage) {
            return field.validationMessage;
        }

        if (validity.valueMissing) {
            return `${label} wajib diisi.`;
        }

        if (validity.typeMismatch || validity.patternMismatch || validity.tooShort || validity.tooLong ||
            validity.rangeUnderflow || validity.rangeOverflow || validity.stepMismatch || validity.badInput) {
            return field.validationMessage || `${label} tidak valid.`;
        }

        return field.validationMessage || `${label} tidak valid.`;
    }

    function clearFieldError(field) {
        if (!field) {
            return;
        }

        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');

        const wrapper = getFieldWrapper(field);
        const feedback = wrapper?.querySelector(`.invalid-feedback[data-field-error-for="${field.name}"]`);

        if (feedback) {
            feedback.textContent = '';
            feedback.style.display = 'none';
        }
    }

    function setFieldError(field, message) {
        if (!field) {
            return;
        }

        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');

        const feedback = getOrCreateFieldErrorElement(field);
        if (feedback) {
            feedback.textContent = message;
            feedback.style.display = 'block';
        }
    }

    function isFieldVisible(field) {
        if (!field || field.disabled || field.type === 'hidden') {
            return false;
        }

        const hiddenParent = field.closest('#keluarga_section, #alamatDomisiliField');
        if (hiddenParent && window.getComputedStyle(hiddenParent).display === 'none') {
            return false;
        }

        return true;
    }

    function validateField(field) {
        if (!isFieldVisible(field)) {
            clearFieldError(field);
            return true;
        }

        if (field.checkValidity()) {
            clearFieldError(field);
            return true;
        }

        setFieldError(field, getFieldValidationMessage(field));
        return false;
    }

    function focusField(field) {
        if (!field) {
            return;
        }

        try {
            field.focus({
                preventScroll: true
            });
        } catch (error) {
            field.focus();
        }

        field.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    function applyServerValidationErrors(errors) {
        if (!errors || typeof errors !== 'object') {
            return;
        }

        form.querySelectorAll('input, select, textarea').forEach(clearFieldError);

        let firstInvalidField = null;

        Object.entries(errors).forEach(([name, messages]) => {
            const field = getFieldByName(name);
            if (!field) {
                return;
            }

            setFieldError(field, Array.isArray(messages) ? messages[0] : messages);

            if (!firstInvalidField) {
                firstInvalidField = field;
            }
        });

        if (!firstInvalidField) {
            return;
        }

        const targetStepIndex = stepPanes.findIndex((pane) => pane.contains(firstInvalidField));
        if (targetStepIndex >= 0 && targetStepIndex !== currentStep) {
            setActiveStep(targetStepIndex);
        }

        setTimeout(() => focusField(firstInvalidField), 200);
    }

    function bindInlineValidation(field) {
        if (!field || !field.name) {
            return;
        }

        const validateOnInteraction = () => {
            if (!isFieldVisible(field)) {
                clearFieldError(field);
                return;
            }

            if (!field.value && !field.required && !field.validity.customError) {
                clearFieldError(field);
                return;
            }

            validateField(field);
        };

        field.addEventListener('input', validateOnInteraction);
        field.addEventListener('change', validateOnInteraction);
        field.addEventListener('blur', validateOnInteraction);
    }

    form.addEventListener('invalid', function(event) {
        const field = event.target;

        if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
            return;
        }

        event.preventDefault();
        setFieldError(field, getFieldValidationMessage(field));
    }, true);

    form.querySelectorAll('input, select, textarea').forEach(bindInlineValidation);

    const existingValidationErrors = @json($errors->toArray());
    if (Object.keys(existingValidationErrors).length > 0) {
        applyServerValidationErrors(existingValidationErrors);
    }

    // Tampilkan loading spinner saat form disubmit, dan cegah double-submit
    form.addEventListener('submit', function(e) {
        if (formSubmitting) {
            e.preventDefault();
            return;
        }

        formSubmitting = true;

        // Ganti teks tombol dengan spinner Bootstrap kecil
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Mengirim...';
        submitBtn.disabled = true;

        // Non-aktifkan navigasi agar user tidak berpindah langkah saat mengirim
        if (typeof nextBtn !== 'undefined' && nextBtn) nextBtn.disabled = true;
        if (typeof prevBtn !== 'undefined' && prevBtn) prevBtn.disabled = true;
    });

    document.addEventListener('DOMContentLoaded', () => {
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();

                if (index === currentStep) {
                    return;
                }

                if (index > currentStep) {
                    goToNextStep(Math.min(index, currentStep + 1));
                    return;
                }

                setActiveStep(index);
            });
        });

        const hash = window.location.hash;
        const initialIndex = tabs.findIndex(tab => tab.getAttribute('data-bs-target') === hash);
        setActiveStep(initialIndex >= 0 ? initialIndex : 0, false);
    });

    function setActiveStep(index, shouldScroll = true) {
        if (!tabs.length || !stepPanes.length) {
            return;
        }

        const boundedIndex = Math.max(0, Math.min(index, tabs.length - 1));
        currentStep = boundedIndex;

        tabs.forEach((tab, tabIndex) => {
            const isActive = tabIndex === boundedIndex;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        stepPanes.forEach((pane, paneIndex) => {
            const isActive = paneIndex === boundedIndex;
            pane.classList.toggle('show', isActive);
            pane.classList.toggle('active', isActive);
        });

        const targetTab = tabs[boundedIndex];
        const targetHash = targetTab?.getAttribute('data-bs-target');
        if (targetHash) {
            history.replaceState(null, '', targetHash);
        }

        updateNavButtons();

        if (!shouldScroll) {
            return;
        }

        setTimeout(() => {
            targetTab?.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'center'
            });

            const startElement = document.getElementById('start');
            if (startElement) {
                startElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }, 200);
    }

    function validateStep(index) {
        const stepPane = stepPanes[index];
        if (!stepPane) {
            return true;
        }

        if (index === 0 && typeof window.validateNoKk === 'function' && !window.validateNoKk(true)) {
            return false;
        }

        const inputs = stepPane.querySelectorAll('input, select, textarea');
        for (const input of inputs) {
            if (!isFieldVisible(input)) {
                continue;
            }

            if (!validateField(input)) {
                focusField(input);
                return false;
            }
        }
        return true;
    }

    function goToNextStep(targetIndex = currentStep + 1) {
        if (!validateStep(currentStep)) return;

        if (currentStep === 3) {
            saveStep1to4Ajax(targetIndex);
            return;
        }

        setActiveStep(targetIndex);
    }

    nextBtn.addEventListener('click', () => {
        goToNextStep(currentStep + 1);
    });

    prevBtn.addEventListener('click', () => {
        if (currentStep === 0) {
            return;
        }

        setActiveStep(currentStep - 1);
    });

    function updateNavButtons() {
        prevBtn.disabled = currentStep === 0;
        nextBtn.classList.toggle('d-none', currentStep === tabs.length - 1);
        submitBtn.classList.toggle('d-none', currentStep !== tabs.length - 1);
        updateWizardProgress();
    }

    function updateWizardProgress() {
        const totalSteps = tabs.length;
        const activeTab = tabs[currentStep];
        const activeTitle = activeTab?.querySelector('.wizard-step__title')?.textContent?.trim() || `Langkah ${currentStep + 1}`;
        const progressWidth = totalSteps > 0 ? ((currentStep + 1) / totalSteps) * 100 : 0;

        if (wizardCurrentStep) {
            wizardCurrentStep.textContent = `Langkah ${currentStep + 1}`;
        }

        if (wizardProgressText) {
            wizardProgressText.textContent = `${currentStep + 1} dari ${totalSteps} langkah`;
        }

        if (wizardStepLabel) {
            wizardStepLabel.textContent = activeTitle;
        }

        if (wizardProgressBar) {
            wizardProgressBar.style.width = `${progressWidth}%`;
        }
    }

    const scrollBox = document.getElementById('termsBox');
    const checkBox = document.getElementById('checkBox1');
    const termsContentAvailable = @json($syaratKetentuan && filled($syaratKetentuan->syarat_ketentuan));

    function enableTermsCheckboxWhenRead() {
        if (!scrollBox || !checkBox || !termsContentAvailable || window.accountDataLocked || checkBox.checked) {
            return;
        }

        const {
            scrollTop,
            scrollHeight,
            clientHeight
        } = scrollBox;
        const isBottom = scrollTop + clientHeight >= scrollHeight - 5;

        if (isBottom) {
            checkBox.disabled = false;
        }
    }

    if (scrollBox && checkBox) {
        scrollBox.addEventListener('scroll', enableTermsCheckboxWhenRead);
        enableTermsCheckboxWhenRead();
    }

    function checkCheckboxes() {
        const checkBox1 = document.getElementById('checkBox1');
        const submitBtn = document.getElementById('submitBtn');

        if (!checkBox1 || !submitBtn) {
            return;
        }

        submitBtn.disabled = !checkBox1.checked || window.accountDataLocked;
    }

    const masterCheckbox = document.getElementById('checkBox1');
    const childCheckboxes = document.querySelectorAll('.check-pasif');

    if (masterCheckbox) {
        masterCheckbox.addEventListener('change', function() {
            childCheckboxes.forEach(cb => cb.checked = this.checked);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Cek awal
        checkCheckboxes();

        // Cek ulang setiap kali checkbox berubah
        const checkBox1 = document.getElementById('checkBox1');
        if (checkBox1) {
            checkBox1.addEventListener('change', checkCheckboxes);
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const dokumenFields = @json(array_keys($dokumenFields));

        dokumenFields.forEach(field => {
            const inputId = `${field}-upload`;
            const spanId = `file-name-${field.replace(/_/g, '-')}`;

            const input = document.getElementById(inputId);
            const span = document.getElementById(spanId);

            if (input && span) {
                input.addEventListener('change', function() {
                    const fileName = input.files[0]?.name || 'Dokumen belum diunggah';
                    span.textContent = fileName;
                });
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const ktpInput = document.getElementById('no_ktp');
        const kkInput = document.getElementById('no_kk');
        const npwpInput = document.getElementById('npwp');

        if (!ktpInput || !kkInput || !npwpInput) {
            return;
        }

        const digitsOnly = (value) => (value || '').replace(/\D/g, '');

        function formatNpwpExisting(digits) {
            let formatted = '';

            if (digits.length > 0) formatted += digits.substring(0, 2);
            if (digits.length >= 3) formatted += '.' + digits.substring(2, 5);
            if (digits.length >= 6) formatted += '.' + digits.substring(5, 8);
            if (digits.length >= 9) formatted += '.' + digits.substring(8, 9);
            if (digits.length >= 10) formatted += '-' + digits.substring(9, 12);
            if (digits.length >= 13) formatted += '.' + digits.substring(12, 15);

            return formatted;
        }

        function formatByFourDigits(digits) {
            return (digits.match(/.{1,4}/g) || []).join(' ');
        }

        function showWarning(message, onClose = null) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian',
                    text: message,
                    confirmButtonText: 'OK'
                }).then(function() {
                    if (typeof onClose === 'function') {
                        onClose();
                    }
                });
                return;
            }

            alert(message);

            if (typeof onClose === 'function') {
                onClose();
            }
        }

        function getKtpDigits() {
            return digitsOnly(ktpInput.value).slice(0, 16);
        }

        function validateNoKk(showAlert = false) {
            const ktpDigits = getKtpDigits();
            const kkDigits = digitsOnly(kkInput.value).slice(0, 16);

            kkInput.value = kkDigits;
            kkInput.setCustomValidity('');

            if (!kkDigits || !ktpDigits || kkDigits !== ktpDigits) {
                return true;
            }

            kkInput.setCustomValidity('No KK tidak boleh sama dengan No KTP.');

            if (showAlert) {
                kkInput.value = '';
                kkInput.setCustomValidity('');
                showWarning('No KK dan No KTP tidak boleh sama.', function() {
                    kkInput.focus();
                });
            }

            return false;
        }

        function formatNpwpInput() {
            const rawDigits = digitsOnly(npwpInput.value);
            const ktpDigits = getKtpDigits();
            const useKtpFormat = ktpDigits.length === 16 &&
                rawDigits.length <= ktpDigits.length &&
                ktpDigits.startsWith(rawDigits);

            if (useKtpFormat) {
                npwpInput.value = formatByFourDigits(rawDigits.slice(0, 16));
                return;
            }

            npwpInput.value = formatNpwpExisting(rawDigits.slice(0, 15));
        }

        kkInput.addEventListener('input', function() {
            validateNoKk(false);
        });

        kkInput.addEventListener('blur', function() {
            validateNoKk(true);
        });

        kkInput.addEventListener('change', function() {
            validateNoKk(true);
        });

        npwpInput.addEventListener('input', function() {
            formatNpwpInput();
        });

        window.validateNoKk = validateNoKk;
        formatNpwpInput();
    });

    function saveStep1to4Ajax(targetStep = currentStep + 1) {

        nextBtn.disabled = true;
        nextBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

        let formData = new FormData(document.getElementById('formWizard'));

        fetch("{{ route('biodata.storeStep1to4') }}", {
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(async (res) => {
                const contentType = res.headers.get('content-type') || '';
                const data = contentType.includes('application/json')
                    ? await res.json()
                    : null;

                if (!res.ok) {
                    if (data?.errors) {
                        const validationError = new Error(Object.values(data.errors)[0][0]);
                        validationError.validationErrors = data.errors;
                        throw validationError;
                    }

                    throw new Error(data?.message || 'Gagal menyimpan data');
                }

                return data;
            })
            .then(res => {
                if (res.status) {
                    setActiveStep(targetStep);
                } else {
                    throw new Error(res.message || 'Gagal menyimpan data');
                }
            })
            .catch((error) => {
                if (error.validationErrors) {
                    applyServerValidationErrors(error.validationErrors);
                    return;
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        text: error.message || 'Terjadi kesalahan server'
                    });
                    return;
                }

                alert(error.message || 'Terjadi kesalahan server');
            })
            .finally(() => {
                nextBtn.disabled = false;
                nextBtn.innerHTML = 'Selanjutnya';
            });
    }
</script>

<script>
    function toggleKeluarga() {

        let status = document.getElementById('status_pernikahan').value;
        let section = document.getElementById('keluarga_section');
        let tanggalNikah = document.getElementById('tanggal_nikah');
        let namaPasangan = document.getElementById('nama_pasangan');
        let isKawin = status === 'Kawin';

        if (status === 'Belum Kawin' || status === '') {
            section.style.display = 'none';
        } else {
            section.style.display = 'block';
        }

        if (tanggalNikah) {
            tanggalNikah.required = isKawin;
        }

        if (namaPasangan) {
            namaPasangan.required = isKawin;
        }

        if (!isKawin && typeof clearFieldError === 'function') {
            clearFieldError(tanggalNikah);
            clearFieldError(namaPasangan);
        }

    }

    document.addEventListener('DOMContentLoaded', function() {

        toggleKeluarga();

        document.getElementById('status_pernikahan')
            .addEventListener('change', toggleKeluarga);

    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (!window.accountDataLocked) {
            return;
        }

        const biodataForm = document.getElementById('formWizard');
        if (!biodataForm) {
            return;
        }

        function applyAccountDataLock(root = biodataForm) {
            root.querySelectorAll('.tab-pane input:not([type="hidden"]), .tab-pane select, .tab-pane textarea').forEach(function(element) {
                element.disabled = true;
                element.readOnly = true;
            });

            root.querySelectorAll('.btn-confirm-delete').forEach(function(button) {
                button.disabled = true;
                button.setAttribute('title', 'Dokumen tidak dapat dihapus karena akun Anda tercatat aktif bekerja.');
            });

            root.querySelectorAll('.btn-upload').forEach(function(button) {
                button.classList.add('disabled');
                button.setAttribute('aria-disabled', 'true');
                button.style.pointerEvents = 'none';
                button.style.opacity = '0.65';
            });

            root.querySelectorAll('.select2-container').forEach(function(container) {
                container.style.pointerEvents = 'none';
                container.style.opacity = '0.65';
            });

            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            const checkBox1 = document.getElementById('checkBox1');
            if (checkBox1) {
                checkBox1.checked = false;
                checkBox1.disabled = true;
            }
        }

        applyAccountDataLock();

        const observer = new MutationObserver(function() {
            applyAccountDataLock();
        });

        observer.observe(biodataForm, {
            childList: true,
            subtree: true,
        });
    });
</script>

@endpush

@endsection

