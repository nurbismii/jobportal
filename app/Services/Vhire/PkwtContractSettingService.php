<?php

namespace App\Services\Vhire;

use App\Models\VhireContractSetting;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class PkwtContractSettingService
{
    public const PKWT_1_CODE = 'pkwt_1';

    public function pkwt1(): VhireContractSetting
    {
        return VhireContractSetting::firstOrCreate(
            ['code' => self::PKWT_1_CODE],
            [
                'duration_value' => 3,
                'duration_unit' => 'month',
                'default_signing_method' => 'electronic',
            ]
        );
    }

    public function updatePkwt1(array $data, ?int $userId = null): VhireContractSetting
    {
        $setting = $this->pkwt1();
        $old = $setting->only(['duration_value', 'duration_unit', 'default_signing_method']);

        $setting->fill([
            'duration_value' => (int) $data['duration_value'],
            'duration_unit' => $data['duration_unit'],
            'default_signing_method' => $data['default_signing_method'] ?? $setting->default_signing_method,
            'updated_by' => $userId,
        ])->save();

        app(VhireAuditLogger::class)->log(
            'pkwt_duration_setting_updated',
            $setting,
            $old,
            $setting->only(['duration_value', 'duration_unit', 'default_signing_method']),
            [],
            'admin'
        );

        return $setting;
    }

    public function calculateEndDate($startDate, ?int $durationValue = null, ?string $durationUnit = null): ?CarbonInterface
    {
        if (blank($startDate)) {
            return null;
        }

        $setting = $this->pkwt1();
        $value = $durationValue ?: (int) $setting->duration_value;
        $unit = $durationUnit ?: (string) $setting->duration_unit;
        $date = Carbon::parse($startDate)->startOfDay();

        switch ($unit) {
            case 'day':
                return $date->copy()->addDays($value)->subDay();
            case 'week':
                return $date->copy()->addWeeks($value)->subDay();
            case 'year':
                return $date->copy()->addYearsNoOverflow($value)->subDay();
            case 'month':
            default:
                return $date->copy()->addMonthsNoOverflow($value)->subDay();
        }
    }

    public function durationLabel(?int $durationValue = null, ?string $durationUnit = null): string
    {
        $setting = $this->pkwt1();
        $value = $durationValue ?: (int) $setting->duration_value;
        $unit = $durationUnit ?: (string) $setting->duration_unit;

        $labels = [
            'day' => 'hari',
            'week' => 'minggu',
            'month' => 'bulan',
            'year' => 'tahun',
        ];

        return $value . ' ' . ($labels[$unit] ?? $unit);
    }
}
