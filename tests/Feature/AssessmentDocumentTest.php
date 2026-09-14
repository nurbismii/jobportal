<?php

namespace Tests\Feature;

use App\Models\AssessmentDocument;
use App\Models\AssessmentLink;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssessmentDocumentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'database.default' => 'documents_testing',
            'database.connections.documents_testing' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('documents_testing');
        DB::setDefaultConnection('documents_testing');
        require_once database_path('migrations/2026_07_13_000000_create_assessment_link_tables.php');
        (new \CreateAssessmentLinkTables())->up();
        (require database_path('migrations/2026_09_14_000000_create_assessment_documents_tables.php'))->up();
        (require database_path('migrations/2026_09_14_010000_add_deletion_tracking_to_assessment_documents.php'))->up();
        Storage::fake('assessment_private');
    }

    private function link(): AssessmentLink
    {
        return AssessmentLink::create([
            'assessment_type' => 'kesehatan', 'public_token' => bin2hex(random_bytes(32)),
            'pin_hash' => 'unused', 'form_schema' => [], 'created_by' => 1,
            'expires_at' => now()->addDay(), 'is_active' => true,
        ]);
    }

    private function pdf(string $name = 'hasil.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    private function excel(): UploadedFile
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Nama');
        ob_start();
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        return UploadedFile::fake()->createWithContent('rekap.xlsx', ob_get_clean());
    }

    public function test_private_folder_multiple_pdf_upload_and_scoped_download(): void
    {
        $link = $this->link();
        $link->update(['assessment_type' => AssessmentLink::TYPE_MCU]);
        $this->withSession(['assessment_link_access.'.$link->id => true]);
        $this->post(route('assessment-documents.folders.store', $link->public_token), ['name' => 'Batch September'])->assertSessionHasNoErrors();
        $folder = DB::table('assessment_document_folders')->first();
        $this->post(route('assessment-documents.store', $link->public_token), [
            'category' => 'attendance', 'folder_id' => $folder->id, 'files' => [$this->excel()],
        ])->assertSessionHasNoErrors();
        $this->post(route('assessment-documents.store', $link->public_token), [
            'category' => 'mcu_detail', 'folder_id' => $folder->id, 'files' => [$this->pdf(), $this->pdf('kedua.pdf')],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(3, AssessmentDocument::count());
        $document = AssessmentDocument::where('category', 'mcu_detail')->first();
        Storage::disk('assessment_private')->assertExists($document->path);
        $this->get(route('assessment-documents.index', $link->public_token))->assertOk()->assertSee('Batch September')->assertSee('hasil.pdf')
            ->assertSeeInOrder(['Buat folder / batch MCU', 'Daftar Hadir', 'Hasil MCU - Detail', 'Hasil MCU - Rekap']);
        $this->get(route('assessment-documents.download', [$link->public_token, $document->id]))->assertDownload('hasil.pdf');
        $admin = new \App\Models\User();
        $admin->forceFill(['id' => 1, 'role' => 'admin']);
        $this->actingAs($admin)->get(route('assessment-documents.admin.index', $link))->assertOk()->assertSee('hasil.pdf');
        $this->get(route('assessment-documents.admin.download', [$link, $document->id]))->assertDownload('hasil.pdf');

        $other = $this->link();
        $this->withSession(['assessment_link_access.'.$other->id => true]);
        $this->get(route('assessment-documents.download', [$other->public_token, $document->id]))->assertNotFound();
        $this->post(route('assessment-documents.store', $other->public_token), [
            'category' => 'mcu_detail', 'folder_id' => $folder->id, 'files' => [$this->pdf()],
        ])->assertSessionHasErrors('folder_id');
        $this->assertSame(3, AssessmentDocument::count());
    }

    public function test_pin_expiry_and_deactivation_are_enforced(): void
    {
        $link = $this->link();
        $url = route('assessment-documents.store', $link->public_token);
        $this->post($url)->assertForbidden();
        $this->withSession(['assessment_link_access.'.$link->id => true]);
        $link->update(['expires_at' => now()->subMinute()]);
        $this->post($url)->assertNotFound();
        $link->update(['expires_at' => now()->addDay(), 'is_active' => false]);
        $this->get(route('assessment-documents.index', $link->public_token))->assertNotFound();
    }

    public function test_upload_stages_are_enforced_on_server_and_isolated_per_batch(): void
    {
        $link = $this->link();
        $link->update(['assessment_type' => AssessmentLink::TYPE_MCU]);
        $this->withSession(['assessment_link_access.'.$link->id => true]);
        $first = \App\Models\AssessmentDocumentFolder::create(['assessment_link_id' => $link->id, 'name' => 'Batch 1']);
        $second = \App\Models\AssessmentDocumentFolder::create(['assessment_link_id' => $link->id, 'name' => 'Batch 2']);
        $url = route('assessment-documents.store', $link->public_token);
        $this->post($url, ['category' => 'mcu_detail', 'folder_id' => $first->id, 'files' => [$this->pdf()]])->assertSessionHasErrors('category');
        $this->assertSame(0, AssessmentDocument::count());
        $this->assertSame([], Storage::disk('assessment_private')->allFiles());
        session()->forget('errors');
        $this->post($url, ['category' => 'attendance', 'folder_id' => $first->id, 'files' => [$this->excel()]])->assertSessionHasNoErrors();
        $this->post($url, ['category' => 'mcu_recap', 'folder_id' => $first->id, 'files' => [$this->excel()]])->assertSessionHasErrors('category');
        session()->forget('errors');
        $this->post($url, ['category' => 'mcu_detail', 'folder_id' => $first->id, 'files' => [$this->pdf()]])->assertSessionHasNoErrors();
        $this->post($url, ['category' => 'mcu_recap', 'folder_id' => $first->id, 'files' => [$this->excel()]])->assertSessionHasNoErrors();
        $this->post($url, ['category' => 'mcu_detail', 'folder_id' => $second->id, 'files' => [$this->pdf()]])->assertSessionHasErrors('category');
        $this->assertSame(3, AssessmentDocument::count());
        $this->get(route('assessment-documents.index', ['token' => $link->public_token, 'folder_id' => $second->id]))
            ->assertOk()->assertSee('Terkunci')->assertDontSee('hasil.pdf');
    }

    public function test_excel_upload_and_invalid_file_rejection(): void
    {
        $link = $this->link();
        $this->withSession(['assessment_link_access.'.$link->id => true]);
        $this->post(route('assessment-documents.store', $link->public_token), [
            'category' => 'attendance', 'files' => [$this->excel()],
        ])->assertSessionHasNoErrors();
        $folder = \App\Models\AssessmentDocumentFolder::create(['assessment_link_id' => $link->id, 'name' => 'Kesehatan']);
        $this->post(route('assessment-documents.store', $link->public_token), [
            'category' => 'mcu_detail', 'folder_id' => $folder->id, 'files' => [$this->pdf()],
        ])->assertSessionHasNoErrors();
        $this->post(route('assessment-documents.store', $link->public_token), [
            'category' => 'mcu_recap', 'files' => [$this->excel()],
        ])->assertSessionHasNoErrors();
        $this->assertSame(3, AssessmentDocument::count());
        $this->post(route('assessment-documents.store', $link->public_token), [
            'category' => 'attendance', 'files' => [$this->pdf()],
        ])->assertSessionHasErrors('files.0');
        $link->update(['assessment_type' => 'lapangan']);
        $this->post(route('assessment-documents.store', $link->public_token), [
            'category' => 'mcu_recap', 'files' => [$this->pdf()],
        ])->assertSessionHasErrors('category');
        $this->get(route('assessment-documents.admin.index', $link))->assertRedirect('/login');
    }

    public function test_queue_accepts_more_than_ten_files_in_separate_requests_and_deduplicates_retry(): void
    {
        $link = $this->link();
        $link->update(['assessment_type' => AssessmentLink::TYPE_MCU]);
        $folder = \App\Models\AssessmentDocumentFolder::create(['assessment_link_id' => $link->id, 'name' => 'Queue']);
        $this->withSession(['assessment_link_access.'.$link->id => true]);
        $this->post(route('assessment-documents.store', $link->public_token), [
            'category' => 'attendance', 'folder_id' => $folder->id, 'files' => [$this->excel()],
        ])->assertSessionHasNoErrors();
        $this->withHeaders(['Accept' => 'application/json']);
        $url = route('assessment-documents.queue', $link->public_token);
        $firstId = (string) \Illuminate\Support\Str::uuid();
        for ($i = 0; $i < 12; $i++) {
            $this->post($url, [
                'category' => 'mcu_detail', 'folder_id' => $folder->id,
                'upload_id' => $i === 0 ? $firstId : (string) \Illuminate\Support\Str::uuid(),
                'files' => [$this->pdf('hasil-'.$i.'.pdf')],
            ])->assertOk()->assertJson(['saved' => true]);
        }
        $this->post($url, [
            'category' => 'mcu_detail', 'folder_id' => $folder->id, 'upload_id' => $firstId,
            'files' => [$this->pdf('hasil-0.pdf')],
        ])->assertOk()->assertJson(['saved' => true]);
        $this->assertSame(12, AssessmentDocument::where('category', 'mcu_detail')->count());
        $this->assertCount(13, Storage::disk('assessment_private')->allFiles());
        $this->post($url, [
            'category' => 'mcu_detail', 'folder_id' => $folder->id, 'upload_id' => (string) \Illuminate\Support\Str::uuid(),
            'files' => [$this->pdf(), $this->pdf('extra.pdf')],
        ])->assertUnprocessable()->assertJsonValidationErrors('files');
    }

    public function test_queue_preserves_stage_validation_and_pin_access(): void
    {
        $link = $this->link();
        $folder = \App\Models\AssessmentDocumentFolder::create(['assessment_link_id' => $link->id, 'name' => 'Queue']);
        $this->withHeaders(['Accept' => 'application/json']);
        $url = route('assessment-documents.queue', $link->public_token);
        $this->post($url)->assertForbidden();
        $this->withSession(['assessment_link_access.'.$link->id => true]);
        $this->post($url, [
            'category' => 'mcu_detail', 'folder_id' => $folder->id, 'upload_id' => (string) \Illuminate\Support\Str::uuid(),
            'files' => [$this->pdf()],
        ])->assertUnprocessable()->assertJsonValidationErrors('category');
        $this->assertSame(0, AssessmentDocument::count());
    }

    public function test_history_deletion_is_scoped_and_removes_download_access_but_retains_private_archive(): void
    {
        $link = $this->link();
        $this->withSession(['assessment_link_access.'.$link->id => true]);
        $this->post(route('assessment-documents.store', $link->public_token), ['category' => 'attendance', 'files' => [$this->excel()]])->assertSessionHasNoErrors();
        $document = AssessmentDocument::firstOrFail();
        $other = $this->link();
        $this->withSession(['assessment_link_access.'.$other->id => true]);
        $this->delete(route('assessment-documents.destroy', [$other->public_token, $document->id]))->assertNotFound();
        $this->delete(route('assessment-documents.destroy', [$link->public_token, $document->id]))->assertSessionHasNoErrors();
        $this->assertSame(0, AssessmentDocument::count());
        $archived = AssessmentDocument::withTrashed()->findOrFail($document->id);
        $this->assertTrue($archived->trashed());
        $this->assertSame('clinic', $archived->deleted_via);
        Storage::disk('assessment_private')->assertExists($document->path);
        $this->get(route('assessment-documents.download', [$link->public_token, $document->id]))->assertNotFound();
        $this->assertFalse(AssessmentDocument::stageStatus($link)['attendance']);
    }

    public function test_last_prerequisite_cannot_be_deleted_until_downstream_documents_are_removed(): void
    {
        $link = $this->link();
        $this->withSession(['assessment_link_access.'.$link->id => true]);
        $this->post(route('assessment-documents.store', $link->public_token), ['category' => 'attendance', 'files' => [$this->excel()]]);
        $folder = \App\Models\AssessmentDocumentFolder::create(['assessment_link_id' => $link->id, 'name' => 'Delete']);
        $this->post(route('assessment-documents.store', $link->public_token), ['category' => 'mcu_detail', 'folder_id' => $folder->id, 'files' => [$this->pdf()]]);
        $this->post(route('assessment-documents.store', $link->public_token), ['category' => 'mcu_recap', 'files' => [$this->excel()]]);
        $documents = AssessmentDocument::get()->keyBy('category');
        foreach (['attendance', 'mcu_detail'] as $category) {
            $this->delete(route('assessment-documents.destroy', [$link->public_token, $documents[$category]->id]))->assertSessionHasErrors('history');
        }
        $this->assertSame(3, AssessmentDocument::count());
        session()->forget('errors');
        foreach (['mcu_recap', 'mcu_detail', 'attendance'] as $category) {
            $this->delete(route('assessment-documents.destroy', [$link->public_token, $documents[$category]->id]))->assertSessionHasNoErrors();
        }
        $this->assertSame(0, AssessmentDocument::count());
    }

    public function test_history_delete_requires_access_and_admin_can_delete_expired_link_documents(): void
    {
        $link = $this->link();
        $this->delete(route('assessment-documents.destroy', [$link->public_token, 1]))->assertForbidden();
        $this->withSession(['assessment_link_access.'.$link->id => true]);
        $this->post(route('assessment-documents.store', $link->public_token), ['category' => 'attendance', 'files' => [$this->excel()]]);
        $document = AssessmentDocument::firstOrFail();
        $link->update(['expires_at' => now()->subDay()]);
        $this->delete(route('assessment-documents.destroy', [$link->public_token, $document->id]))->assertNotFound();
        $this->delete(route('assessment-documents.admin.destroy', [$link, $document->id]))->assertRedirect('/login');
        $admin = new \App\Models\User();
        $admin->forceFill(['id' => 7, 'role' => 'admin']);
        $this->actingAs($admin)->delete(route('assessment-documents.admin.destroy', [$link, $document->id]))->assertSessionHasNoErrors();
        $this->assertSame(7, AssessmentDocument::withTrashed()->findOrFail($document->id)->deleted_by);
    }

    public function test_histories_have_independent_search_pagination_and_admin_filters(): void
    {
        $link = $this->link();
        $other = $this->link();
        $folder = \App\Models\AssessmentDocumentFolder::create(['assessment_link_id' => $link->id, 'name' => 'Filter batch']);
        $timestamp = \Illuminate\Support\Carbon::parse('2026-09-14 12:00:00', 'Asia/Makassar')->setTimezone(config('app.timezone'));
        foreach (range(1, 17) as $number) {
            AssessmentDocument::create([
                'assessment_link_id' => $link->id, 'assessment_document_folder_id' => $folder->id,
                'category' => 'mcu_detail', 'original_name' => 'detail-'.$number.'.pdf',
                'path' => 'test-'.$number, 'size' => 50, 'created_at' => $timestamp,
            ]);
        }
        foreach (['attendance', 'mcu_recap'] as $category) {
            AssessmentDocument::create([
                'assessment_link_id' => $link->id, 'category' => $category,
                'original_name' => $category.'.xlsx', 'path' => $category, 'size' => 50, 'created_at' => $timestamp,
            ]);
        }
        AssessmentDocument::create(['assessment_link_id' => $other->id, 'category' => 'mcu_detail', 'original_name' => 'private.pdf', 'path' => 'private', 'size' => 50]);
        $admin = new \App\Models\User();
        $admin->forceFill(['id' => 1, 'role' => 'admin']);
        $url = route('assessment-documents.admin.index', $link);
        $response = $this->actingAs($admin)->get($url.'?mcu_detail_page=2');
        $response->assertOk()->assertDontSee('private.pdf');
        $histories = $response->viewData('histories');
        $this->assertSame(17, $histories['mcu_detail']->total());
        $this->assertSame(2, $histories['mcu_detail']->count());
        $this->assertSame(1, $histories['attendance']->currentPage());
        $response = $this->get($url.'?mcu_detail_search=detail-17&date_to=2026-09-14');
        $response->assertOk();
        $this->assertSame(1, $response->viewData('histories')['mcu_detail']->total());
        $this->assertSame(1, $response->viewData('histories')['attendance']->total());
        $response = $this->get($url.'?folder_id='.$folder->id.'&date_from=2026-09-14&date_to=2026-09-14');
        $response->assertOk();
        $this->assertSame(17, $response->viewData('histories')['mcu_detail']->total());
        $this->assertSame(0, $response->viewData('histories')['attendance']->total());
        $response = $this->get($url.'?date_from=2026-09-15');
        $response->assertOk();
        $this->assertSame(0, $response->viewData('histories')['mcu_detail']->total());
        $this->getJson($url.'?date_from=2026-09-15&date_to=2026-09-14')->assertUnprocessable();
    }
}
