@extends('layouts.app-pic')

@section('content-admin')

<div class="container-fluid">

    <h3 class="h3 mb-3 text-gray-800">Peralihan Pelamar
        <a href="{{ route('peralihan.index') }}" class="btn btn-primary btn-sm btn-icon-split float-right">
            <span class="icon text-white-50"><i class="fas fa-arrow-left"></i></span>
            <span class="text">Kembali</span>
        </a>
    </h3>

    <div class="row mb-3">
        <div class="col-12">

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Form Peralihan Pelamar</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('peralihan.update', $biodata->getLatestRiwayatLamaran->id) }}" method="POST">
                        @csrf
                        {{ method_field('patch') }}
                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <label for="nama-pelamar">Nama Pelamar
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nama-pelamar" value="{{ $biodata->user->name ?? '-' }}" required readonly>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="no-ktp">No KTP
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="no-ktp" value="{{ $biodata->no_ktp ?? '-' }}" required readonly>
                            </div>
                        </div>


                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="email">Email
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="email" id="email" class="form-control" value="{{ $biodata->user->email ?? '-' }}" required readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lowongan-dilamar">Lowongan yang Dilamar
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="jumlah-permintaan" value="{{ $biodata->getLatestRiwayatLamaran->lowongan->nama_lowongan }}" required readonly>
                                <input type="hidden" name="loker_id_lama" class="form-control" value="{{ $biodata->getLatestRiwayatLamaran->lowongan->id }}" required readonly>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12 mb-3">
                                <label for="alihkan">Alihkan Ke ?
                                    <span class="text-danger">*</span>
                                </label>
                                <select name="loker_id" class="form-control" id="tanggal-pengajuan">
                                    <option value="" disabled selected>-- Pilih Lowongan --</option>
                                    @foreach($lowongans as $lowongan)
                                    <option value="{{ $lowongan->id }}">{{ $lowongan->nama_lowongan }}</option> 
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary float-right">Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection