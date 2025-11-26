@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">

<style>
    .file-upload-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #f4f4f4;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 14px;
    }

    .file-icon {
        color: #009de0;
        margin-right: 10px;
        font-size: 1.5rem;
    }

    .upload-label {
        display: flex;
        align-items: center;
        color: #adb5bd;
    }

    .btn-upload {
        background: linear-gradient(90deg, #bcbcbc, #a0a0a0);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 6px 16px;
        font-weight: 600;
    }

    input[type="file"] {
        display: none;
    }

    .file-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background-color: #f4f4f4;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 14px;
    }

    .file-info {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #333;
    }

    .file-icon {
        font-size: 1.5rem;
        color: #009de0;
    }

    .file-meta {
        display: flex;
        flex-direction: column;
        color: #6c757d;
    }

    .file-meta .filename {
        color: #495057;
        font-weight: 500;
    }

    .btn-view {
        background: linear-gradient(90deg, #0072b5, #5fc1e8);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 6px 16px;
        font-weight: 600;
    }

    .btn-delete {
        background: linear-gradient(90deg, #83332d, #f2a293);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 6px 16px;
        font-weight: 600;
    }

    .btn-group-custom {
        display: flex;
        gap: 10px;
    }
</style>

@endpush
<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
    <h6 class="m-0 font-weight-bold text-primary">Edit Data Pengguna</h6>
    <a href="{{ route('pengguna.index') }}" class="btn btn-sm btn-primary">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Data Pengguna</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('pengguna.update', $pengguna->id) }}" method="POST">
            @csrf
            {{ method_field('patch') }}
            <div class="row">
                <div class="col-md-8">
                    <h6 class="text-primary">Biodata</h6>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama" class="form-control" value="{{ Auth::user()->name }}" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>No KTP
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="no_ktp" class="form-control" value="{{ Auth::user()->no_ktp }}" readonly>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>No Telp
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="no_telp" class="form-control" value="{{ $biodata->no_telp ?? '' }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>No Kartu Keluarga
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="no_kk" class="form-control" value="{{ $biodata->no_kk ?? '' }}" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="jenis_kelamin">Jenis Kelamin
                                <span class="text-danger">*</span>
                            </label>
                            <select name="jenis_kelamin" id="jenis_kelamin" class="form-control" required>
                                @php
                                $jenisKelaminList = [
                                'M 男' => 'Laki-laki 男',
                                'F 女' => 'Perempuan 女',
                                ];
                                $selected = $biodata->jenis_kelamin ?? '';
                                @endphp
                                @if($selected)
                                <option value="{{ $selected }}">{{ $jenisKelaminList[$selected] }}</option>
                                @else
                                <option value="">Pilih jenis kelamin</option>
                                @endif
                                @foreach($jenisKelaminList as $key => $label)
                                @if($key != $selected)
                                <option value="{{ $key }}">{{ $label }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="agama">Agama
                                <span class="text-danger">*</span>
                            </label>
                            <select name="agama" id="agama" class="form-control" required>
                                @php
                                $agamaOptions = [
                                'SD 小学' => 'SD 小学',
                                'SMP 初中' => 'SMP 初中',
                                'SMA 高中' => 'SMA 高中',
                                'SMK 高中' => 'SMK 高中',
                                'D3 大专三年' => 'D3 大专三年',
                                'D4 大专三年' => 'D4 大专三年',
                                'S1 本科' => 'S1 本科',
                                'S2 研究生' => 'S2 研究生',
                                ];
                                $selectedAgama = $biodata->agama ?? '';
                                @endphp
                                <option value="">Pilih agama</option>
                                @foreach($agamaOptions as $agama)
                                <option value="{{ $agama }}" {{ $selectedAgama === $agama ? 'selected' : '' }}>{{ $agama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h6 class="text-primary">Alamat KTP</h6>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Tempat Lahir
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="tempat_lahir" class="form-control" value="{{ $biodata->tempat_lahir ?? '' }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Tanggal Lahir
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tanggal_lahir" class="form-control" value="{{ $biodata->tanggal_lahir ?? '' }}" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Provinsi
                                <span class="text-danger">*</span>
                            </label>
                            <select name="provinsi" id="provinsi_id" class="form-control">
                                @if($biodata)
                                <option value="{{ $biodata->provinsi }}">{{ $biodata->getProvinsi->provinsi }}</option>
                                @else
                                <option value="">Pilih provinsi</option>
                                @endif
                                @foreach ($provinsis as $item)
                                <option value="{{ $item->id }}">{{ $item->provinsi }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Kabupaten
                                <span class="text-danger">*</span>
                            </label>
                            <select name="kabupaten" id="kabupaten_id" class="form-control">
                                @if($biodata)
                                <option value="{{ $biodata->kabupaten }}">{{ $biodata->getKabupaten->kabupaten }}</option>
                                @else
                                <option value="">Pilih kabupaten</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Kecamatan
                                <span class="text-danger">*</span>
                            </label>
                            <select name="kecamatan" id="kecamatan_id" class="form-control">
                                @if($biodata)
                                <option value="{{ $biodata->kecamatan }}">{{ $biodata->getKecamatan->kecamatan }}</option>
                                @else
                                <option value="">Pilih kecamatan</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Kelurahan/Desa
                                <span class="text-danger">*</span>
                            </label>
                            <select name="kelurahan" id="kelurahan_id" class="form-control">
                                @if($biodata)
                                <option value="{{ $biodata->kelurahan }}">{{ $biodata->getKelurahan->kelurahan }}</option>
                                @else
                                <option value="">Pilih kelurahan</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="alamat">Alamat Lengkap
                                <span class="text-danger">*</span>
                            </label>
                            <textarea name="alamat" class="form-control" id="alamat">{{ $biodata->alamat ?? '' }}</textarea>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>Kode Pos
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="kode_pos" class="form-control" value="{{ $biodata->kode_pos ?? '' }}" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>RT
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="rt" class="form-control" value="{{ $biodata->rt ?? '' }}" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>RW
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="rw" class="form-control" value="{{ $biodata->rw ?? '' }}" required>
                        </div>
                    </div>

                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sesuaiAlamatKtp" checked>
                            <label for="sesuaiAlamatKtp" class="form-check-label">Alamat Domisili sesuai dengan Alamat KTP</label>
                        </div>
                    </div>
                    <h6 class="text-primary">Lain-lain</h6>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Hobi
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="hobi" class="form-control" value="{{ $biodata->hobi ?? '' }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Golongan Darah
                                <span class="text-danger">*</span>
                            </label>
                            <select name="golongan_darah" id="" class="form-control" required>
                                @php
                                $golonganDarahList = ['A 型', 'B 型', 'O 型', 'AB 型'];
                                $selected = $biodata->golongan_darah ?? '';
                                @endphp
                                @if($selected)
                                <option value="{{ $selected }}">{{ $selected }}</option>
                                @else
                                <option value="">Pilih golongan darah</option>
                                @endif
                                @foreach($golonganDarahList as $gol)
                                @if($gol != $selected)
                                <option value="{{ $gol }}">{{ $gol }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Tinggi badan<sup>(cm)</sup>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="tinggi_badan" value="{{ $biodata->tinggi_badan ?? '' }}" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Berat badan<sup>(kg)</sup>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="berat_badan" value="{{ $biodata->berat_badan ?? '' }}" class="form-control">
                        </div>
                    </div>

                    <h6 class="text-primary">Pendidikan</h6>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="pendidikan_terakhir" class="form-label">Pendidikan Terakhir
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-control" name="pendidikan_terakhir" required>
                                @php
                                $pendidikanList = [
                                'SD 小学' => 'SD 小学',
                                'SMP 初中' => 'SMP 初中',
                                'SMA 高中' => 'SMA 高中',
                                'SMK 高中' => 'SMK 高中',
                                'D3 大专三年' => 'D3 大专三年',
                                'D4 大专三年' => 'D4 大专三年',
                                'S1 本科' => 'S1 本科',
                                'S2 研究生' => 'S2 研究生',
                                ];
                                $selected = $biodata->pendidikan_terakhir ?? '';
                                @endphp
                                @if($selected)
                                <option value="{{ $selected }}">{{ $selected }}</option>
                                @else
                                <option value="">Pilih pendidikan terakhir</option>
                                @endif
                                @foreach($pendidikanList as $pendidikan)
                                @if($pendidikan != $selected)
                                <option value="{{ $pendidikan }}">{{ $pendidikan }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="nama_instansi" class="form-label">Nama Sekolah/Kampus
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="nama_instansi" value="{{ $biodata->nama_instansi ?? '' }}" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="jurusan" class="form-label">Jurusan
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="jurusan" value="{{ $biodata->jurusan ?? '' }}" required>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="nilai_ipk" class="form-label">IPK/Nilai Ijazah
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="nilai_ipk" value="{{ $biodata->nilai_ipk ?? '' }}" required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Tahun masuk
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tahun_masuk" class="form-control" value="{{ $biodata->tahun_masuk ?? '' }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Tahun lulus
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tahun_lulus" class="form-control" value="{{ $biodata->tahun_lulus ?? '' }}">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12 mb-3">
                            <label>Prestasi</label>
                            <textarea type="text" name="prestasi" rows="5" class="form-control">{{ $biodata->prestasi ?? '' }}</textarea>
                        </div>
                    </div>

                    <h6 class="text-primary">Nama orang tua</h6>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama ibu
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_ibu" class="form-control" value="{{ $biodata->nama_ibu ?? '' }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Nama ayah
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_ayah" class="form-control" value="{{ $biodata->nama_ayah ?? '' }}" required>
                        </div>
                    </div>
                    <h6 class="text-primary">Status pernikahan</h6>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="status_pernikahan">Status pernikahan
                                <span class="text-danger">*</span>
                            </label>
                            @php
                            $statusOptions = [
                            'Belum Kawin' => 'Belum Kawin',
                            'Kawin' => 'Kawin',
                            'Cerai Hidup' => 'Cerai Hidup',
                            'Cerai Mati' => 'Cerai Mati'
                            ];
                            $selectedStatus = $biodata->status_pernikahan ?? '';
                            @endphp
                            <select name="status_pernikahan" class="form-control" id="status_pernikahan" required>
                                <option value="">Pilih status pernikahan</option>
                                @if($selectedStatus && !array_key_exists($selectedStatus, $statusOptions))
                                <option value="{{ $selectedStatus }}" selected>{{ $selectedStatus }}</option>
                                @endif
                                @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ $selectedStatus === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tanggal_nikah">Tanggal pernikahan</label>
                            <input type="date" name="tanggal_nikah" id="tanggal_nikah" value="{{ $biodata->tanggal_nikah ?? '' }}" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama Suami/Istri</label>
                            <input type="text" name="nama_pasangan" class="form-control" value="{{ $biodata->nama_pasangan ?? '' }}">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Jumlah anak</label>
                            <input type="number" name="jumlah_anak" class="form-control" value="{{ $biodata->jumlah_anak ?? '' }}">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama anak ke-1</label>
                            <input type="text" name="nama_anak_1" class="form-control" id="" value="{{ $biodata->nama_anak_1 ?? '' }}">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama anak ke-2</label>
                            <input type="text" name="nama_anak_2" class="form-control" id="" value="{{ $biodata->nama_anak_2 ?? '' }}">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama anak ke-3</label>
                            <input type="text" name="nama_anak_3" class="form-control" id="" value="{{ $biodata->nama_anak_3 ?? '' }}">
                        </div>
                    </div>

                    <h6 class="text-primary">Status pernikahan</h6>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Nama kontak darurat
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_kontak_darurat" class="form-control" value="{{ $biodata->nama_kontak_darurat ?? '' }}" required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>No telepon
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="no_telp_darurat" class="form-control" value="{{ $biodata->no_telepon_darurat ?? '' }}" required>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label>Status hubungan
                                <span class="text-danger">*</span>
                            </label>
                            <select name="status_hubungan" id="status_hubungan" class="form-control" required>
                                @php
                                $statusList = ['Orang Tua', 'Saudara', 'Sepupu', 'Teman Dekat'];
                                $selected = $biodata->status_hubungan ?? '';
                                @endphp
                                @if($selected)
                                <option value="{{ $selected }}">{{ $selected }}</option>
                                @else
                                <option value="">Pilih status hubungan</option>
                                @endif
                                @foreach($statusList as $status)
                                @if($status != $selected)
                                <option value="{{ $status }}">{{ $status }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="status_akun">Status Akun
                                <span class="text-danger">*</span>
                            </label>
                            <select name="status_akun" id="status_akun" class="form-control" required>
                                @php
                                $statusOptions = [
                                0 => 'Tidak aktif',
                                1 => 'Aktif'
                                ];
                                $currentStatus = $pengguna->status_akun;
                                @endphp
                                <option value="{{ $currentStatus }}">{{ $statusOptions[$currentStatus] }}</option>
                                @foreach($statusOptions as $value => $label)
                                @if($value != $currentStatus)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>


                <div class="col-md-4">
                    <h6 class="text-primary">Dokumen</h6>
                    <div class="row g-3">
                        <div class="col-md-12 mb-2">
                            <label class="form-label">CV</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="fas fa-file p-2"></i>
                                    @if($biodata && $biodata->cv)
                                    <span id="file-name">{{ $biodata->cv }}</span>
                                    @else
                                    <span id="file-name">Dokumen belum diunggah</span>
                                    @endif
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset($pengguna->no_ktp . '/dokumen/' . $biodata->cv) }}" target="_blank" class="btn btn-view">Lihat</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label">Pas Foto 3x4</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="fas fa-file p-2"></i>
                                    @if($biodata && $biodata->pas_foto)
                                    <span id="file-name">{{ $biodata->pas_foto }}</span>
                                    @else
                                    <span id="file-name">Dokumen belum diunggah</span>
                                    @endif
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset($pengguna->no_ktp . '/dokumen/' . $biodata->pas_foto) }}" target="_blank" class="btn btn-view">Lihat</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label">Surat Lamaran Kerja</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="fas fa-file p-2"></i>
                                    @if($biodata && $biodata->surat_lamaran)
                                    <span id="file-name">{{ $biodata->surat_lamaran }}</span>
                                    @else
                                    <span id="file-name">Dokumen belum diunggah</span>
                                    @endif
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset($pengguna->no_ktp . '/dokumen/' . $biodata->surat_lamaran) }}" target="_blank" class="btn btn-view">Lihat</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label">Ijazah dan Transkrip nilai</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="fas fa-file p-2"></i>
                                    @if($biodata && $biodata->ijazah)
                                    <span id="file-name">{{ $biodata->ijazah }}</span>
                                    @else
                                    <span id="file-name">Dokumen belum diunggah</span>
                                    @endif
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset($pengguna->no_ktp . '/dokumen/' . $biodata->ijazah) }}" target="_blank" class="btn btn-view">Lihat</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label">Kartu Tanda Penduduk (KTP)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="fas fa-file p-2"></i>
                                    @if($biodata && $biodata->ktp)
                                    <span id="file-name">{{ $biodata->ktp }}</span>
                                    @else
                                    <span id="file-name">Dokumen belum diunggah</span>
                                    @endif
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset($pengguna->no_ktp . '/dokumen/' . $biodata->ktp) }}" target="_blank" class="btn btn-view">Lihat</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label">SIM B II Umum/SIO <sup>Opsional</sup></label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="fas fa-file p-2"></i>
                                    @if($biodata && $biodata->sim_b_2)
                                    <span id="file-name">{{ $biodata->sim_b_2 }}</span>
                                    @else
                                    <span id="file-name">Dokumen belum diunggah</span>
                                    @endif
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset($pengguna->no_ktp . '/dokumen/' . $biodata->sim_b_2) }}" target="_blank" class="btn btn-view">Lihat</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label">SKCK</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="fas fa-file p-2"></i>
                                    @if($biodata && $biodata->skck)
                                    <span id="file-name">{{ $biodata->skck }}</span>
                                    @else
                                    <span id="file-name">Dokumen belum diunggah</span>
                                    @endif
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset($pengguna->no_ktp . '/dokumen/' . $biodata->skck) }}" target="_blank" class="btn btn-view">Lihat</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label">Sertifikat Vaksin</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="fas fa-file p-2"></i>
                                    @if($biodata && $biodata->sertifikat_vaksin)
                                    <span id="file-name">{{ $biodata->sertifikat_vaksin }}</span>
                                    @else
                                    <span id="file-name">Dokumen belum diunggah</span>
                                    @endif
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset($pengguna->no_ktp . '/dokumen/' . $biodata->sertifikat_vaksin) }}" target="_blank" class="btn btn-view">Lihat</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label">Kartu Keluarga</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="fas fa-file p-2"></i>
                                    @if($biodata && $biodata->kk)
                                    <span id="file-name">{{ $biodata->kk }}</span>
                                    @else
                                    <span id="file-name">Dokumen belum diunggah</span>
                                    @endif
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset($pengguna->no_ktp . '/dokumen/' . $biodata->kk) }}" target="_blank" class="btn btn-view">Lihat</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label">NPWP</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="fas fa-file p-2"></i>
                                    @if($biodata && $biodata->npwp)
                                    <span id="file-name">{{ $biodata->npwp }}</span>
                                    @else
                                    <span id="file-name">Dokumen belum diunggah</span>
                                    @endif
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset($pengguna->no_ktp . '/dokumen/' . $biodata->npwp) }}" target="_blank" class="btn btn-view">Lihat</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label">Kartu Pencari Kejra (AK1)</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="fas fa-file p-2"></i>
                                    @if($biodata && $biodata->ak1)
                                    <span id="file-name">{{ $biodata->ak1 }}</span>
                                    @else
                                    <span id="file-name">Dokumen belum diunggah</span>
                                    @endif
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset($pengguna->no_ktp . '/dokumen/' . $biodata->ak1) }}" target="_blank" class="btn btn-view">Lihat</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mb-2">
                            <label class="form-label">Sertifikat Pendukung</label>
                            <div class="file-upload-box">
                                <div class="upload-label">
                                    <i class="fas fa-file p-2"></i>
                                    @if($biodata && $biodata->sertifikat_pendukung)
                                    <span id="file-name">{{ $biodata->sertifikat_pendukung }}</span>
                                    @else
                                    <span id="file-name">Dokumen belum diunggah</span>
                                    @endif
                                </div>
                                <div class="btn-group-custom">
                                    <a href="{{ asset($pengguna->no_ktp . '/dokumen/' . $biodata->sertifikat_pendukung) }}" target="_blank" class="btn btn-view">Lihat</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary float-right">Perbarui</button>
        </form>
    </div>
</div>

@endsection