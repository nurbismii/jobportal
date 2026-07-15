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
    }
}
