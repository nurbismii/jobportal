<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InternalAccountController extends Controller
{
    public function landing()
    {
        return view('admin.internal-accounts.landing');
    }

    public function index()
    {
        $this->authorizeAdministrator();
        $accounts = User::whereIn('role', ['manager', 'supervisor'])->orderBy('name')->paginate(20);

        return view('admin.internal-accounts.index', compact('accounts'));
    }

    public function create()
    {
        $this->authorizeAdministrator();

        return view('admin.internal-accounts.form', ['account' => new User(['status_akun' => 1])]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdministrator();
        $data = $this->validatedAccount($request);
        $account = new User();
        $account->forceFill($data + ['email_verified_at' => now()])->save();

        return redirect()->route('internal-accounts.index')->with('success', 'Akun internal berhasil ditambahkan.');
    }

    public function edit(User $akun_internal)
    {
        $this->authorizeAdministrator();
        abort_unless(in_array($akun_internal->role, ['manager', 'supervisor'], true), 404);

        return view('admin.internal-accounts.form', ['account' => $akun_internal]);
    }

    public function update(Request $request, User $akun_internal)
    {
        $this->authorizeAdministrator();
        abort_unless(in_array($akun_internal->role, ['manager', 'supervisor'], true), 404);
        $data = $this->validatedAccount($request, $akun_internal);
        // Revoke remember-me access when credentials change or the account is disabled.
        if (isset($data['password']) || $data['email'] !== $akun_internal->email || ! $data['status_akun']) {
            $data['remember_token'] = null;
        }
        $akun_internal->forceFill($data)->save();

        return redirect()->route('internal-accounts.index')->with('success', 'Akun dan izin modul berhasil diperbarui.');
    }

    private function authorizeAdministrator(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    private function validatedAccount(Request $request, ?User $account = null): array
    {
        $modules = config('admin_access.modules');
        $rules = [
            'no_ktp' => ['required', 'digits:16', Rule::unique('users', 'no_ktp')->ignore($account?->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($account?->id)],
            'role' => ['required', Rule::in(['manager', 'supervisor'])],
            'status_akun' => ['required', 'boolean'],
            'password' => [$account ? 'nullable' : 'required', 'string', 'min:8', 'max:255', 'confirmed'],
            'permissions' => ['nullable', 'array:' . implode(',', array_keys($modules))],
        ];
        foreach ($modules as $module => $definition) {
            $rules["permissions.$module"] = ['sometimes', 'array'];
            $rules["permissions.$module.*"] = ['string', 'distinct', Rule::in($definition['actions'])];
        }
        $data = $request->validate($rules);
        $permissions = $data['permissions'] ?? [];
        foreach ($permissions as $module => $actions) {
            if ($actions && ! in_array('view', $actions, true)) {
                throw ValidationException::withMessages([
                    "permissions.$module" => 'Aktifkan izin Lihat sebelum memberikan izin tindakan lainnya.',
                ]);
            }
        }
        unset($data['permissions']);
        $data['module_permissions'] = $permissions;
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }
}
