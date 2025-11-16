@extends('layouts.app-pic')

@section('content-admin')

@push('styles')
<link href="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endpush

<div class="container-fluid">

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Data Pengumuman</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover nowrap" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Email</th>
                                    <th>Status Proses</th>
                                    <th>Status Kirim</th>
                                    <th>Dikirim</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($datas as $log)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $log->user->email ?? 'N/A' }}</td>
                                    <td>{{ $log->status_proses }}</td>
                                    <td>
                                        @php $s = strtolower($log->status_kirim ?? ''); @endphp
                                        @if(in_array($s, ['success','berhasil','terkirim']))
                                            <span class="badge badge-success">{{ strtoupper($log->status_kirim) }}</span>
                                        @elseif(in_array($s, ['failed','gagal','error']))
                                            <span class="badge badge-danger">{{ strtoupper($log->status_kirim) }}</span>
                                        @elseif(in_array($s, ['pending','queued','menunggu']))
                                            <span class="badge badge-warning">{{ strtoupper($log->status_kirim) }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ strtoupper($log->status_kirim) ?? 'Unknown' }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                @endforeach.
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- Page level plugins -->
<script src="{{ asset('admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Page level custom scripts -->
<script src="{{ asset('admin/js/demo/datatables-demo.js') }}"></script>
@endpush

@endsection