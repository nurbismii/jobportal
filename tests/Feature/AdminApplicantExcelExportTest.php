<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminApplicantExcelExportTest extends TestCase
{
    public function test_applicant_excel_export_serializes_identity_and_phone_cells_as_inline_strings(): void
    {
        $view = file_get_contents(resource_path('views/admin/lamaran/index.blade.php'));

        $this->assertStringContainsString("const identifierHeaders = ['No KTP', 'No KK', 'No HP', 'NPWP', 'Nomor HP darurat'];", $view);
        $this->assertStringContainsString("cell.attr('t', 'inlineStr');", $view);
        $this->assertStringContainsString('cell.empty().append(`<is><t>${escapeXml(value)}</t></is>`);', $view);
        $this->assertStringContainsString("if (\$node.is('[data-export-value]'))", $view);
    }

    public function test_applicant_table_displays_structured_profile_columns(): void
    {
        $view = file_get_contents(resource_path('views/admin/lamaran/index.blade.php'));
        $controller = file_get_contents(app_path('Http/Controllers/Admin/LowonganController.php'));

        foreach (['Pengalaman Kerja', 'Nama Perusahaan', 'Masa Kerja', 'Posisi Pengalaman', 'Minat', 'Bakat', 'Prestasi'] as $heading) {
            $this->assertStringContainsString("<th>{$heading}</th>", $view);
        }

        $this->assertStringContainsString('$data->biodata->pengalamanKerja', $view);
        $this->assertStringContainsString('$data->biodata->minatBakat', $view);
        $this->assertStringContainsString('$data->biodata->daftarPrestasi', $view);
        $this->assertStringContainsString("'biodata.pengalamanKerja'", $controller);
        $this->assertStringContainsString("'biodata.minatBakat'", $controller);
        $this->assertStringContainsString("'biodata.daftarPrestasi'", $controller);
    }
}
