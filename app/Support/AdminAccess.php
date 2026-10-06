<?php

namespace App\Support;

use Illuminate\Routing\Route;

class AdminAccess
{
    public static function requirement(Route $route): ?array
    {
        if (! in_array('redirect.role', $route->middleware(), true)) {
            return null;
        }

        [$controller, $method] = array_pad(explode('@', $route->getActionName()), 2, '');
        $module = config('admin_access.controllers.' . class_basename($controller));
        $action = config('admin_access.methods.' . $method);

        if ($method === 'directToLamaran' || $method === 'refreshDataPelamar') {
            $module = 'lamaran';
        }
        if ($method === 'autoUpdate' && request('model') === 'user') {
            $module = 'pengguna';
        }
        if (class_basename($controller) === 'KandidatPotensialController' && $method === 'store') {
            $action = 'update'; // This import updates existing candidates.
        }
        if (class_basename($controller) === 'PkwtContractSettingController' && $method === 'edit') {
            $action = 'view'; // Settings are displayed and edited on the same page.
        }

        return $module && $action ? [$module, $action] : null;
    }
}
