<?php

namespace Tests\Unit;

use App\Services\Ocr\KtpIdentityValidator;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class KtpIdentityValidatorTest extends TestCase
{
    public function test_nik_must_have_plausible_indonesian_structure()
    {
        $validator = new KtpIdentityValidator();

        $this->assertTrue($validator->isPlausibleNik('6401010101900001'));
        $this->assertFalse($validator->isPlausibleNik('1111111111111111'));
        $this->assertFalse($validator->isPlausibleNik('6401010001900001'));
        $this->assertFalse($validator->isPlausibleNik('6401010199900001'));
    }

    public function test_birth_date_must_match_nik_date_segment()
    {
        $validator = new KtpIdentityValidator();

        $this->assertTrue($validator->birthDateMatchesNik('6401010101900001', '01-01-1990'));
        $this->assertTrue($validator->birthDateMatchesNik('6401014101900001', '01-01-1990'));
        $this->assertFalse($validator->birthDateMatchesNik('6401010101900001', '02-01-1990'));
    }

    public function test_name_matching_allows_common_ocr_formatting_noise()
    {
        $validator = new KtpIdentityValidator();

        $this->assertTrue($validator->namesMatch('MOH. RIZKY PRATAMA', 'MOH RIZKY PRATAMA'));
        $this->assertFalse($validator->namesMatch('MOH', 'MOH RIZKY PRATAMA'));
        $this->assertFalse($validator->namesMatch('BUDI SANTOSO', 'MOH RIZKY PRATAMA'));
    }

    public function test_parsed_ktp_nik_must_match_account_nik()
    {
        $validator = new KtpIdentityValidator();
        $user = new User([
            'no_ktp' => '6401010101900001',
            'name' => 'MOH RIZKY PRATAMA',
        ]);

        $result = $validator->validateParsedResult([
            'result' => [
                'nik' => ['value' => '6401010201900002'],
                'nama' => ['value' => 'MOH RIZKY PRATAMA'],
                'tanggalLahir' => ['value' => '02-01-1990'],
            ],
        ], $user);

        $this->assertFalse($result['valid']);
        $this->assertSame('NIK pada foto KTP tidak sesuai dengan nomor KTP akun.', $result['message']);
    }
}
