<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PkwtContractSettingRequest;
use App\Services\Vhire\PkwtContractSettingService;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class PkwtContractSettingController extends Controller
{
    public function edit(PkwtContractSettingService $settings)
    {
        $setting = $settings->pkwt1();

        return view('admin.pkwt-contract-settings.edit', compact('setting'));
    }

    public function update(PkwtContractSettingRequest $request, PkwtContractSettingService $settings)
    {
        $settings->updatePkwt1($request->validated(), optional(Auth::user())->id);

        Alert::success('Berhasil', 'Pengaturan durasi PKWT 1 diperbarui.');

        return redirect()->route('pkwt-contract-settings.edit');
    }
}
