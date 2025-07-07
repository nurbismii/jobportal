<?php

namespace App\Http\Controllers;

use App\Models\Hris\Divisi;
use App\Models\Hris\Kabupaten;
use App\Models\Hris\Kecamatan;
use App\Models\Hris\Kelurahan;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function fetchKabupaten($id)
    {
        $kabupaten = Kabupaten::where('id_provinsi', $id)->get();
        return response()->json($kabupaten);
    }

    public function fetchKecamatan($id)
    {
        $kecamatan = Kecamatan::where('id_kabupaten', $id)->get();
        return response()->json($kecamatan);
    }

    public function fetchKelurahan($id)
    {
        $kecamatan = Kelurahan::where('id_kecamatan', $id)->get();
        return response()->json($kecamatan);
    }

    public function getByDepartemen($departemen_id)
    {
        $divisi = Divisi::where('departemen_id', $departemen_id)->orderBy('nama_divisi', 'asc')->get();
        return response()->json($divisi);
    }
}
