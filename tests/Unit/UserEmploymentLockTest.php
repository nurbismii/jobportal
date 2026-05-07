<?php

namespace Tests\Unit;

use App\Models\Hris\Employee;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserEmploymentLockTest extends TestCase
{
    public function test_active_employee_locks_only_for_vdni_or_vdnip_area()
    {
        $vdniEmployee = new Employee([
            'status_resign' => 'Aktif',
            'area_kerja' => 'VDNI',
            'entry_date' => '2026-01-10',
        ]);

        $vdnipEmployee = new Employee([
            'status_resign' => 'Aktif',
            'area_kerja' => 'VDNIP',
            'entry_date' => '2026-01-10',
        ]);

        $otherAreaEmployee = new Employee([
            'status_resign' => 'Aktif',
            'area_kerja' => 'Site Lain',
            'entry_date' => '2026-01-10',
        ]);

        $this->assertTrue(User::employmentAttributesFromHrisEmployee($vdniEmployee)['employment_lock_active']);
        $this->assertTrue(User::employmentAttributesFromHrisEmployee($vdnipEmployee)['employment_lock_active']);
        $this->assertFalse(User::employmentAttributesFromHrisEmployee($otherAreaEmployee)['employment_lock_active']);
        $this->assertSame('Aktif', User::employmentAttributesFromHrisEmployee($otherAreaEmployee)['status_pelamar']);
    }

    public function test_existing_lock_is_effective_only_for_vdni_or_vdnip_area()
    {
        $lockableUser = new User([
            'employment_lock_active' => true,
            'area_kerja' => ' vdnip ',
        ]);

        $nonLockableUser = new User([
            'employment_lock_active' => true,
            'area_kerja' => 'Site Lain',
        ]);

        $legacyLockableUser = new User([
            'employment_lock_active' => false,
            'area_kerja' => 'VDNI',
            'ket_resign' => User::activeEmploymentKetResign('2026-01-10'),
        ]);

        $legacyNonLockableUser = new User([
            'employment_lock_active' => false,
            'area_kerja' => 'Site Lain',
            'ket_resign' => User::activeEmploymentKetResign('2026-01-10'),
        ]);

        $this->assertTrue($lockableUser->hasActiveEmploymentStatusLock());
        $this->assertFalse($nonLockableUser->hasActiveEmploymentStatusLock());
        $this->assertTrue($legacyLockableUser->hasActiveEmploymentStatusLock());
        $this->assertFalse($legacyNonLockableUser->hasActiveEmploymentStatusLock());
    }
}
