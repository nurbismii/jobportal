<?php

namespace App\Services\Ocr;

use App\Models\Biodata;
use App\Models\User;

class KtpIdentityValidator
{
    public function validateParsedResult(array $parsedResult, User $user, ?Biodata $biodata = null): array
    {
        $nik = $this->onlyDigits(data_get($parsedResult, 'result.nik.value', ''));
        $accountNik = $this->onlyDigits($user->no_ktp);
        $biodataNik = $biodata ? $this->onlyDigits($biodata->no_ktp) : $accountNik;
        $ocrName = (string) data_get($parsedResult, 'result.nama.value', '');
        $birthDate = (string) data_get($parsedResult, 'result.tanggalLahir.value', '');

        if (! $this->isPlausibleNik($nik)) {
            return $this->invalid('NIK pada KTP tidak terbaca atau formatnya tidak valid.', [
                'ocr_nik' => $nik,
            ]);
        }

        if ($accountNik === '' || $nik !== $accountNik) {
            return $this->invalid('NIK pada foto KTP tidak sesuai dengan nomor KTP akun.', [
                'ocr_nik' => $nik,
                'account_nik' => $accountNik,
            ]);
        }

        if ($biodataNik === '' || $nik !== $biodataNik) {
            return $this->invalid('Nomor KTP akun dan biodata tidak sinkron. Silakan hubungi HR/admin.', [
                'ocr_nik' => $nik,
                'biodata_nik' => $biodataNik,
            ]);
        }

        if (trim($ocrName) === '') {
            return $this->invalid('Nama pada KTP tidak terbaca. Silakan unggah ulang foto KTP yang lebih jelas.', [
                'ocr_nik' => $nik,
            ]);
        }

        if (! $this->namesMatch($ocrName, (string) $user->name, 82)) {
            return $this->invalid('Nama pada KTP tidak sesuai dengan nama akun.', [
                'ocr_nik' => $nik,
            ]);
        }

        if (trim($birthDate) === '' || ! $this->birthDateMatchesNik($nik, $birthDate)) {
            return $this->invalid('Tanggal lahir pada KTP tidak sesuai dengan struktur NIK.', [
                'ocr_nik' => $nik,
            ]);
        }

        $employee = User::latestHrisEmployeeByNoKtp($nik);

        if ($employee && ! $this->namesMatch($ocrName, (string) $employee->nama_karyawan, 78)) {
            return $this->invalid('Nama pada KTP tidak sesuai dengan riwayat karyawan di HRIS.', [
                'ocr_nik' => $nik,
                'has_hris_history' => true,
            ]);
        }

        return [
            'valid' => true,
            'message' => 'KTP valid.',
            'ocr_nik' => $nik,
            'has_hris_history' => (bool) $employee,
        ];
    }

    public function isPlausibleNik(?string $nik): bool
    {
        $nik = $this->onlyDigits($nik);

        if (! preg_match('/^\d{16}$/', $nik)) {
            return false;
        }

        if (preg_match('/^(\d)\1{15}$/', $nik)) {
            return false;
        }

        $provinceCode = (int) substr($nik, 0, 2);
        $cityCode = (int) substr($nik, 2, 2);
        $districtCode = (int) substr($nik, 4, 2);
        $dayCode = (int) substr($nik, 6, 2);
        $monthCode = (int) substr($nik, 8, 2);
        $sequence = (int) substr($nik, 12, 4);

        if ($provinceCode < 11 || $provinceCode > 99) {
            return false;
        }

        if ($cityCode === 0 || $districtCode === 0 || $sequence === 0) {
            return false;
        }

        if ($dayCode > 40) {
            $dayCode -= 40;
        }

        return $dayCode >= 1
            && $dayCode <= 31
            && $monthCode >= 1
            && $monthCode <= 12;
    }

    public function birthDateMatchesNik(string $nik, string $birthDate): bool
    {
        $nik = $this->onlyDigits($nik);
        $date = $this->extractDateParts($birthDate);

        if (! $this->isPlausibleNik($nik) || ! $date) {
            return false;
        }

        $nikDay = (int) substr($nik, 6, 2);
        $nikMonth = (int) substr($nik, 8, 2);
        $nikYear = (int) substr($nik, 10, 2);

        if ($nikDay > 40) {
            $nikDay -= 40;
        }

        return $nikDay === $date['day']
            && $nikMonth === $date['month']
            && $nikYear === ($date['year'] % 100);
    }

    public function namesMatch(string $left, string $right, int $threshold = 85): bool
    {
        $left = $this->normalizeName($left);
        $right = $this->normalizeName($right);

        if ($left === '' || $right === '') {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        similar_text($left, $right, $percent);

        if ($percent >= $threshold) {
            return true;
        }

        $leftTokens = explode(' ', $left);
        $rightTokens = explode(' ', $right);
        $shorter = count($leftTokens) <= count($rightTokens) ? $leftTokens : $rightTokens;
        $longer = count($leftTokens) > count($rightTokens) ? $leftTokens : $rightTokens;

        if (count($shorter) === 1 && count($longer) > 1) {
            return false;
        }

        $matches = 0;

        foreach ($shorter as $shortToken) {
            foreach ($longer as $longToken) {
                similar_text($shortToken, $longToken, $tokenPercent);

                if ($tokenPercent >= 88 || strpos($longToken, $shortToken) === 0) {
                    $matches++;
                    break;
                }
            }
        }

        return $matches >= max(1, (int) ceil(count($shorter) * 0.8));
    }

    public function normalizeName(string $name): string
    {
        $name = strtoupper(trim($name));
        $name = preg_replace('/[^A-Z0-9\s]/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim((string) $name);
    }

    public function onlyDigits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?: '';
    }

    public function maskNik(?string $nik): string
    {
        $nik = $this->onlyDigits($nik);

        if (strlen($nik) !== 16) {
            return $nik === '' ? '-' : 'invalid';
        }

        return substr($nik, 0, 4) . '********' . substr($nik, -4);
    }

    private function extractDateParts(string $value): ?array
    {
        if (! preg_match('/(\d{1,2})\D+(\d{1,2})\D+(\d{2,4})/', $value, $matches)) {
            return null;
        }

        $day = (int) $matches[1];
        $month = (int) $matches[2];
        $year = (int) $matches[3];

        if ($year < 100) {
            $year += $year > (int) now()->format('y') ? 1900 : 2000;
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return [
            'day' => $day,
            'month' => $month,
            'year' => $year,
        ];
    }

    private function invalid(string $message, array $context = []): array
    {
        return array_merge([
            'valid' => false,
            'message' => $message,
        ], $context);
    }
}
