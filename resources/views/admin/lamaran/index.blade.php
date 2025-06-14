@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">
@endpush

<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
    <h2 class="m-0 font-weight-bold text-primary">{{ $lowongan->nama_lowongan }}</h2>
    <a href="{{ route('lowongan.index') }}" class="btn btn-danger btn-sm btn-icon-split">
        <span class="icon text-white-50">
            <i class="fas fa-arrow-left"></i>
        </span>
        <span class="text">Kembali</span>
    </a>
</div>

<!-- Filter data pelamar -->
<div class="card shadow mb-3">
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Filter Status --</option>
                    <option value="Tidak dapat lanjut ke tahap selanjutya" {{ request('status') == 'Tidak dapat lanjut ke tahap selanjutya' ? 'selected' : '' }}>Tidak dapat lanjut ke tahap selanjutya</option>
                    <option value="Verifikasi Online" {{ request('status') == 'Verifikasi Online' ? 'selected' : '' }}>Verifikasi Online</option>
                    <option value="Verifikasi Berkas" {{ request('status') == 'Verifikasi Berkas' ? 'selected' : '' }}>Verifikasi Berkas</option>
                    <option value="Tes Kesehatan" {{ request('status') == 'Tes Kesehatan' ? 'selected' : '' }}>Tes Kesehatan</option>
                    <option value="Tes Lapangan" {{ request('status') == 'Tes Lapangan' ? 'selected' : '' }}>Tes Lapangan</option>
                    <option value="Medical Check-Up" {{ request('status') == 'Medical Check-Up' ? 'selected' : '' }}>Medical Check-Up</option>
                    <option value="Induksi Safety" {{ request('status') == 'Induksi Safety' ? 'selected' : '' }}>Induksi Safety</option>
                    <option value="Tanda Tangan Kontrak" {{ request('status') == 'Tanda Tangan Kontrak' ? 'selected' : '' }}>Tanda Tangan Kontrak</option>
                </select>
            </div>

            <div class="col-md-3">
                <input type="number" name="umur_min" class="form-control" placeholder="Umur Minimal" value="{{ request('umur_min') }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
                <input type="number" name="umur_max" class="form-control" placeholder="Umur Maksimal" value="{{ request('umur_max') }}" onchange="this.form.submit()">
            </div>

            <div class="col-md-3">
                <select name="pendidikan" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Filter Pendidikan --</option>
                    <option value="SMA" {{ request('pendidikan') == 'SMA' ? 'selected' : '' }}>SMA</option>
                    <option value="D3" {{ request('pendidikan') == 'D3' ? 'selected' : '' }}>D3</option>
                    <option value="S1" {{ request('pendidikan') == 'S1' ? 'selected' : '' }}>S1</option>
                </select>
            </div>
        </form>
    </div>
</div>

<form action="{{ route('lamaran.updateStatusMassal') }}" method="POST">
    <div class="card shadow mb-3">
        <div class="card-body">
            @csrf
            <div class="mb-3">
                <select name="status_proses" class="form-control" required>
                    <option value="">-- Pilih Status Baru --</option>
                    <option value="Tidak dapat lanjut ke tahap selanjutya">Tidak dapat lanjut ke tahap selanjutya</option>
                    <option value="Verifikasi Online">Verifikasi Online</option>
                    <option value="Verifikasi Berkas">Verifikasi Berkas</option>
                    <option value="Tes Kesehatan">Tes Kesehatan</option>
                    <option value="Tes Lapangan">Tes Lapangan</option>
                    <option value="Medical Check-Up">Medical Check-Up (MCU)</option>
                    <option value="Induksi Safety">Induksi Safety</option>
                    <option value="Tanda Tangan Kontrak">Tanda Tangan Kontrak</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary mb-3">Perbarui proses lamaran</button>
        </div>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Data Pelamar</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover nowrap" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th><input type="checkbox" id="checkAll"></th>
                            <th>Status</th>
                            <th>Email</th>
                            <th>No KTP</th>
                            <th>No KK</th>
                            <th>Nama</th>
                            <th class="bg-warning">Status Pelamar</th>
                            <th class="bg-warning">Ex Area</th>
                            <th>Jenis Kelamin</th>
                            <th>Tempat Lahir</th>
                            <th>Tanggal Lahir</th>
                            <th>Umur</th>
                            <th>Status Pernikahan</th>
                            <th>Jumlah Anak</th>
                            <th>RT/RW</th>
                            <th>Kode Pos</th>
                            <th>Provinsi</th>
                            <th>kabupaten</th>
                            <th>kecamatan</th>
                            <th>Kelurahan</th>
                            <th>Alamat</th>
                            <th>No HP</th>
                            <th>NPWP</th>
                            <th>Gol. Darah</th>
                            <th>Nama Instansi</th>
                            <th>Pend. Terakhir</th>
                            <th>Jurusan</th>
                            <th>Tanggal Kelulusan</th>
                            <th>Agama</th>
                            <th>Nama Ibu</th>
                            <th>Nama Ayah</th>
                            <th>Suami/Istri</th>
                            <th>Tanggal Menikah</th>
                            <th>Anak ke-1</th>
                            <th>Anak ke-2</th>
                            <th>Anak ke-3</th>
                            <th>Vaksin</th>
                            <th>Hobi</th>
                            <th>Nomor HP darurat</th>
                            <th>Pemilik kontak darurat</th>
                            <th>Hubungan kontak darurat</th>
                            <th>Tinggi Badan</th>
                            <th>Berat Badan</th>
                            <th>Surat Lamaran</th>
                            <th>CV</th>
                            <th>KTP</th>
                            <th>Status KTP</th>
                            <th>SIM B2</th>
                            <th>Status SIM</th>
                            <th>KK</th>
                            <th>Ijazah</th>
                            <th>SKCK</th>
                            <th>AK1</th>
                            <th>Vaksin</th>
                            <th>NPWP</th>
                            <th>Pas Foto</th>
                            <th>Pendukung</th>
                            <th>Rekomendasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lamarans as $data)
                        <tr>
                            <td>{{ ++$no }}</td>
                            <td><input type="checkbox" name="selected_ids[]" value="{{ $data->id }}"></td>
                            <td>{{ $data->status_proses }}</td>
                            <td>{{ $data->biodata->user->email }}</td>
                            <td>{{ $data->biodata->no_ktp }}</td>
                            <td>{{ $data->biodata->no_kk }}</td>
                            <td>{{ $data->biodata->user->name }}</td>
                            @if($data->biodata->user->status_pelamar)
                            <td class="bg-warning">{{ strtoupper($data->biodata->user->status_pelamar) }}</td>
                            @else
                            <td>{{ strtoupper($data->biodata->user->status_pelamar) }}</td>
                            @endif
                            @if($data->biodata->user->area_kerja)
                            <td class="bg-warning">{{ $data->biodata->user->area_kerja }}</td>
                            @else
                            <td>{{ $data->biodata->user->area_kerja }}</td>
                            @endif
                            <td>{{ $data->biodata->jenis_kelamin == 'L' ? 'Laki-Laki' : 'Perempuan' }}</td>
                            <td>{{ $data->biodata->tempat_lahir }}</td>
                            <td>{{ $data->biodata->tanggal_lahir }}</td>
                            <td>{{ hitungUmur($data->biodata->tanggal_lahir) }}</td>
                            <td>{{ $data->biodata->status_pernikahan }}</td>
                            <td>
                                {{ is_null($data->biodata->jumlah_anak) || $data->biodata->jumlah_anak == 0 
                                    ? 'TK' 
                                    : 'TK' . $data->biodata->jumlah_anak }}
                            </td>
                            <td>{{ $data->biodata->rt }}/{{ $data->biodata->rt }}</td>
                            <td>{{ $data->biodata->kode_pos }}</td>
                            <td>{{ $data->biodata->getProvinsi->provinsi }}</td>
                            <td>{{ $data->biodata->getKabupaten->kabupaten }}</td>
                            <td>{{ $data->biodata->getKecamatan->kecamatan }}</td>
                            <td>{{ $data->biodata->getKelurahan->kelurahan }}</td>
                            <td>{{ $data->biodata->alamat }}</td>
                            <td>{{ $data->biodata->no_telp }}</td>
                            <td>{{ $data->biodata->no_npwp }}</td>
                            <td>{{ $data->biodata->golongan_darah }}</td>
                            <td>{{ $data->biodata->nama_instansi }}</td>
                            <td>{{ $data->biodata->pendidikan_terakhir }}</td>
                            <td>{{ $data->biodata->jurusan }}</td>
                            <td>{{ $data->biodata->tahun_lulus }}</td>
                            <td>{{ $data->biodata->agama }}</td>
                            <td>{{ $data->biodata->nama_ibu }}</td>
                            <td>{{ $data->biodata->nama_ayah }}</td>
                            <td>{{ $data->biodata->nama_pasangan }}</td>
                            <td>{{ $data->biodata->tanggal_nikah }}</td>
                            <td>{{ $data->biodata->nama_anak_1 }}</td>
                            <td>{{ $data->biodata->nama_anak_2 }}</td>
                            <td>{{ $data->biodata->nama_anak_3 }}</td>
                            <td>{{ $data->biodata->vaksinasi_covid }}</td>
                            <td>{{ $data->biodata->hobi }}</td>
                            <td>{{ $data->biodata->no_telepon_darurat }}</td>
                            <td>{{ $data->biodata->nama_kontak_darurat }}</td>
                            <td>{{ $data->biodata->hubungan }}</td>
                            <td>{{ $data->biodata->tinggi_badan  }}</td>
                            <td>{{ $data->biodata->berat_badan  }}</td>

                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->surat_lamaran) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->surat_lamaran) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->cv) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->cv) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->ktp) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->ktp) }}
                                </a>
                            </td>
                            <td>{{ $data->biodata->status_ktp }}</td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sim_b_2) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sim_b_2) }}
                                </a>
                            </td>
                            <td>{{ $data->biodata->status_sim_b2 }}</td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->kartu_keluarga) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->kartu_keluarga) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->ijazah) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->ijazah) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->skck) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->skck) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->ak1) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->ak1) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sertifikat_vaksin) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sertifikat_vaksin) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->npwp) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->npwp) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->pas_foto) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->pas_foto) }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sertifikat_pendukung) }}" target="_blank">
                                    {{ asset($data->biodata->no_ktp . '/dokumen/' . $data->biodata->sertifikat_pendukung) }}
                                </a>
                            </td>
                            <td>
                                @if($data->rekomendasi != null)
                                {{ $data->rekomendasi }}
                                @else
                                <a class="btn btn-primary btn-sm btn-icon-split" data-toggle="modal" data-target="#rekomendasi{{$data->id}}">
                                    <span class="icon text-white-50">
                                        <i class="fas fa-plus"></i>
                                    </span>
                                    <span class="text">Rekomendasi</span>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>

@foreach($lamarans as $data)
<div class="modal fade" id="rekomendasi{{$data->id}}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Rekomendasi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('lamarans.update', $data->id) }}" method="post">
                @csrf
                {{ method_field('PUT') }}
                <div class="modal-body">
                    <div class="col-md-12 mb-3">
                        <label for="perekomendasi">Perekomendasi</label>
                        <input type="input" name="rekomendasi" id="perekomendasi" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<!-- Page level plugins -->
<script src="{{ asset('admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Buttons + Export + ColVis -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>
<script>
    document.getElementById('checkAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    $(document).ready(function() {
        $('#dataTable').DataTable({
            scrollX: true,
            responsive: false,
            autoWidth: false,
            fixedHeader: true,
            dom: 'Bfrtip',
            buttons: [{
                    extend: 'colvis',
                    text: 'Visibility',
                    className: 'btn btn-primary btn-sm'
                },
                {
                    extend: 'excelHtml5',
                    text: 'Export Excel',
                    className: 'btn btn-success btn-sm',
                    title: 'Data Pelamar',
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                let html = $('<div>').html(data);
                                let link = html.find('a').attr('href');
                                return link ? link : html.text(); // ambil text biasa kalau bukan link
                            }
                        }
                    },
                    customize: function(xlsx) {
                        let sheet = xlsx.xl.worksheets['sheet1.xml'];
                        const columnsToLink = ['P', 'Q', 'R', 'T', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC'];

                        $(columnsToLink).each(function(index, colRef) {
                            $('c[r^="' + colRef + '"]', sheet).each(function() {
                                let cell = $(this);
                                let value = cell.text();

                                if (value.startsWith('http')) {
                                    let cellRef = cell.attr('r'); // Contoh: P5
                                    cell.attr('t', 'str');
                                    cell.empty().append(
                                        `<f>HYPERLINK("${value}", "${value}")</f>`
                                    );
                                }
                            });
                        });
                    }
                }
            ],
            fixedColumns: {
                leftColumns: 4 // Freeze sampai kolom No KTP (kolom ke-4)
            }
        });
    });
</script>
@endpush

@endsection